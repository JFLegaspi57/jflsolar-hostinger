<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * "Continue with Google / Facebook" for member sign-up and sign-in (plain OAuth 2.0, no extra package).
 * The app keys are entered by the owner in Admin → Sign-in with Google & Facebook and kept encrypted in
 * storage (or come from GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / FACEBOOK_APP_ID / FACEBOOK_APP_SECRET).
 * A button only shows once its keys are set. Staff sign-in stays email + password only.
 */
final class SocialLogin
{
    public const PROVIDERS = ['google' => 'Google', 'facebook' => 'Facebook'];

    private const TABLE = 'member_social_accounts';

    private const GRAPH = 'v23.0';

    public static function file(): string
    {
        return storage_path('app/social-login.enc');
    }

    /** @return array{google_id?: string, google_secret?: string, facebook_id?: string, facebook_secret?: string} */
    public static function saved(): array
    {
        if (! is_file(self::file())) {
            return [];
        }
        try {
            $v = json_decode(Crypt::decryptString((string) file_get_contents(self::file())), true);

            return is_array($v) ? $v : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Saves one provider's keys; a blank secret keeps the saved one. */
    public static function save(string $provider, string $id, string $secret): void
    {
        $v = self::saved();
        $v[$provider.'_id'] = trim($id);
        if (trim($secret) !== '') {
            $v[$provider.'_secret'] = trim($secret);
        }
        if (trim($id) === '') {
            unset($v[$provider.'_id'], $v[$provider.'_secret']);
        }
        @mkdir(dirname(self::file()), 0755, true);
        file_put_contents(self::file(), Crypt::encryptString(json_encode($v)), LOCK_EX);
        @chmod(self::file(), 0600);
    }

    /** @return array{0: string, 1: string} app id and secret ('' when not set) */
    public static function keys(string $provider): array
    {
        $v = self::saved();
        $env = $provider === 'google' ? ['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET'] : ['FACEBOOK_APP_ID', 'FACEBOOK_APP_SECRET'];

        return [(string) (($v[$provider.'_id'] ?? '') ?: env($env[0], '')), (string) (($v[$provider.'_secret'] ?? '') ?: env($env[1], ''))];
    }

    public static function enabled(string $provider): bool
    {
        [$id, $secret] = self::keys($provider);

        return isset(self::PROVIDERS[$provider]) && $id !== '' && $secret !== '';
    }

    /** @return list<string> providers with keys set */
    public static function active(): array
    {
        return array_values(array_filter(array_keys(self::PROVIDERS), fn ($p) => self::enabled($p)));
    }

    /** The address Google / Facebook send the visitor back to (must be listed in their app settings). */
    public static function callback(string $provider): string
    {
        return rtrim(request()->getSchemeAndHttpHost(), '/').'/auth/'.$provider.'/callback';
    }

    public static function authUrl(string $provider, string $state): string
    {
        [$id] = self::keys($provider);
        if ($provider === 'google') {
            return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id' => $id, 'redirect_uri' => self::callback($provider), 'response_type' => 'code',
                'scope' => 'openid email profile', 'state' => $state, 'prompt' => 'select_account',
            ]);
        }

        return 'https://www.facebook.com/'.self::GRAPH.'/dialog/oauth?'.http_build_query([
            'client_id' => $id, 'redirect_uri' => self::callback($provider), 'response_type' => 'code',
            'scope' => 'email,public_profile', 'state' => $state,
        ]);
    }

    /**
     * Swaps the code from the callback for the person's account details.
     *
     * @return array{id: string, email: string, name: string}|null
     */
    public static function profile(string $provider, string $code): ?array
    {
        [$id, $secret] = self::keys($provider);
        try {
            if ($provider === 'google') {
                $tok = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
                    'code' => $code, 'client_id' => $id, 'client_secret' => $secret,
                    'redirect_uri' => self::callback($provider), 'grant_type' => 'authorization_code',
                ]);
                $access = (string) $tok->json('access_token');
                if (! $tok->successful() || $access === '') {
                    Log::warning('[social] google token failed', ['status' => $tok->status()]);

                    return null;
                }
                $u = Http::withToken($access)->timeout(20)->get('https://openidconnect.googleapis.com/v1/userinfo');
                if (! $u->successful() || ! $u->json('sub')) {
                    return null;
                }
                // Only a Google-verified email can be matched to an account.
                $email = $u->json('email_verified') ? mb_strtolower((string) $u->json('email')) : '';

                return ['id' => (string) $u->json('sub'), 'email' => $email, 'name' => trim((string) $u->json('name'))];
            }
            $tok = Http::timeout(20)->get('https://graph.facebook.com/'.self::GRAPH.'/oauth/access_token', [
                'client_id' => $id, 'client_secret' => $secret, 'redirect_uri' => self::callback($provider), 'code' => $code,
            ]);
            $access = (string) $tok->json('access_token');
            if (! $tok->successful() || $access === '') {
                Log::warning('[social] facebook token failed', ['status' => $tok->status()]);

                return null;
            }
            $u = Http::timeout(20)->get('https://graph.facebook.com/'.self::GRAPH.'/me', [
                'fields' => 'id,name,email', 'access_token' => $access,
                'appsecret_proof' => hash_hmac('sha256', $access, $secret),
            ]);
            if (! $u->successful() || ! $u->json('id')) {
                return null;
            }

            return ['id' => (string) $u->json('id'), 'email' => mb_strtolower((string) $u->json('email')), 'name' => trim((string) $u->json('name'))];
        } catch (\Throwable $e) {
            Log::warning('[social] '.$provider.' sign-in failed: '.class_basename($e));

            return null;
        }
    }

    public static function ensureTable(): void
    {
        if (Cache::get('social-accounts-table-v1') && Schema::hasTable(self::TABLE)) {
            return;
        }
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id')->index();
                $t->string('provider', 20);
                $t->string('provider_id', 191);
                $t->string('email', 254)->nullable();
                $t->timestamps();
                $t->unique(['provider', 'provider_id']);
            });
        }
        Cache::forever('social-accounts-table-v1', true);
    }

    public static function memberFor(string $provider, string $providerId): ?int
    {
        self::ensureTable();
        $id = DB::table(self::TABLE)->where('provider', $provider)->where('provider_id', $providerId)->value('member_id');

        return $id ? (int) $id : null;
    }

    public static function link(int $memberId, string $provider, string $providerId, string $email): void
    {
        self::ensureTable();
        DB::table(self::TABLE)->updateOrInsert(['provider' => $provider, 'provider_id' => $providerId], ['member_id' => $memberId, 'email' => $email ?: null, 'updated_at' => now(), 'created_at' => now()]);
    }

    /** @return list<string> providers linked to a member, e.g. ['google'] */
    public static function linked(int $memberId): array
    {
        if (! Schema::hasTable(self::TABLE)) {
            return [];
        }

        return DB::table(self::TABLE)->where('member_id', $memberId)->pluck('provider')->unique()->values()->all();
    }
}
