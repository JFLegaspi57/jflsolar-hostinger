<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Certificates of completion for the online course. Each one gets its own certificate number (CTN),
 * e.g. JFL-2026-0001-K7Q: year issued, a running number, and three random letters/digits.
 * Issued by staff in Admin → Members → (member); shown on the member's account page.
 */
final class CourseCertificates
{
    private const TABLE = 'course_certificates';

    private const TABLE_KEY = 'course-certificates-table-v2';

    /** Fee for the certificate download, in pesos. */
    public const FEE_PHP = 3000;

    public static function ensureTable(): void
    {
        if (Cache::get(self::TABLE_KEY)) {
            return;
        }
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id')->unique();
                $t->string('ctn', 40)->unique();
                $t->date('completed_on')->nullable();
                $t->timestamp('issued_at');
                $t->timestamps();
            });
        }
        // v2: the certificate files and the GCash payment
        $add = [
            'image' => fn (Blueprint $t) => $t->string('image', 80)->nullable(),
            'pdf' => fn (Blueprint $t) => $t->string('pdf', 80)->nullable(),
            'gcash_ref' => fn (Blueprint $t) => $t->string('gcash_ref', 60)->nullable(),
            'ref_at' => fn (Blueprint $t) => $t->timestamp('ref_at')->nullable(),
            'paid_at' => fn (Blueprint $t) => $t->timestamp('paid_at')->nullable(),
        ];
        foreach ($add as $col => $def) {
            if (! Schema::hasColumn(self::TABLE, $col)) {
                Schema::table(self::TABLE, $def);
            }
        }
        Cache::forever(self::TABLE_KEY, true);
    }

    public static function for(int $memberId): ?object
    {
        self::ensureTable();

        return DB::table(self::TABLE)->where('member_id', $memberId)->first();
    }

    /** Issues the member's certificate number (or returns the one they already have). */
    public static function issue(int $memberId, ?string $completedOn = null): object
    {
        self::ensureTable();
        if ($have = self::for($memberId)) {
            return $have;
        }

        return DB::transaction(function () use ($memberId, $completedOn) {
            $id = DB::table(self::TABLE)->insertGetId([
                'member_id' => $memberId, 'ctn' => 'pending-'.$memberId.'-'.bin2hex(random_bytes(6)),
                'completed_on' => $completedOn, 'issued_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O or 1/I, so it can't be misread
            do {
                $tail = '';
                for ($i = 0; $i < 3; $i++) {
                    $tail .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $ctn = 'JFL-'.now('Asia/Manila')->format('Y').'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT).'-'.$tail;
            } while (DB::table(self::TABLE)->where('ctn', $ctn)->exists());
            DB::table(self::TABLE)->where('id', $id)->update(['ctn' => $ctn]);

            return DB::table(self::TABLE)->where('id', $id)->first();
        });
    }

    public static function revoke(int $memberId): void
    {
        self::ensureTable();
        if ($c = self::for($memberId)) {
            foreach (['image', 'pdf'] as $f) {
                if ($c->{$f}) {
                    @unlink(self::dir().'/'.basename($c->{$f}));
                }
            }
        }
        DB::table(self::TABLE)->where('member_id', $memberId)->delete();
    }

    /** The GCash QR code for certificate payments (separate from the course donations QR). */
    public static function qrFile(): string
    {
        return self::dir().'/gcash-qr.jpg';
    }

    public static function qrUrl(): ?string
    {
        return is_file(self::qrFile()) ? '/account/certificate/gcash-qr.jpg?v='.filemtime(self::qrFile()) : null;
    }

    /** Saves the certificate-payment QR picture (at most 1200 px). Returns an error or null. */
    public static function saveQr(?\Illuminate\Http\UploadedFile $file): ?string
    {
        $img = $file && $file->isValid() && $file->getSize() <= 8 * 1024 * 1024 ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;
        if (! $img) {
            return 'Upload the QR code as a JPG, PNG or WebP picture under 8 MB.';
        }
        if (! is_dir(self::dir()) && ! @mkdir(self::dir(), 0775, true)) {
            return 'The picture could not be stored.';
        }
        [$w, $h] = [imagesx($img), imagesy($img)];
        $s = min(1, 1200 / max($w, $h));
        $out = imagecreatetruecolor((int) round($w * $s), (int) round($h * $s));
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, 0, 0, imagesx($out), imagesy($out), $w, $h);
        imagejpeg($out, self::qrFile(), 92);
        imagedestroy($img);
        imagedestroy($out);

        return null;
    }

    public static function dir(): string
    {
        return storage_path('app/course/certificates');
    }

    public static function path(object $cert, string $kind): ?string
    {
        $f = $cert->{$kind} ?? null;

        return $f && is_file(self::dir().'/'.basename($f)) ? self::dir().'/'.basename($f) : null;
    }

    /** Stores the certificate picture (PNG/JPG, required once) and the PDF (optional). Returns an error or null. */
    public static function saveFiles(int $memberId, ?\Illuminate\Http\UploadedFile $image, ?\Illuminate\Http\UploadedFile $pdf): ?string
    {
        $cert = self::for($memberId);
        if (! $cert) {
            return 'Issue the certificate number first.';
        }
        if (! $image && ! $pdf) {
            return 'Choose the certificate picture (PNG or JPG) and/or the PDF.';
        }
        if (! is_dir(self::dir()) && ! @mkdir(self::dir(), 0775, true)) {
            return 'The certificate could not be stored.';
        }
        $update = [];
        if ($image) {
            $img = $image->isValid() && $image->getSize() <= 20 * 1024 * 1024 ? @imagecreatefromstring((string) file_get_contents($image->getRealPath())) : false;
            if (! $img) {
                return 'The certificate picture must be a PNG or JPG under 20 MB.';
            }
            $name = 'cert-'.$cert->id.'-'.bin2hex(random_bytes(8)).'.png';
            imagepng($img, self::dir().'/'.$name, 6);
            imagedestroy($img);
            $update['image'] = $name;
        }
        if ($pdf) {
            $head = $pdf->isValid() && $pdf->getSize() <= 20 * 1024 * 1024 ? (string) file_get_contents($pdf->getRealPath(), false, null, 0, 5) : '';
            if ($head !== '%PDF-') {
                return 'The certificate PDF must be a PDF file under 20 MB.';
            }
            $name = 'cert-'.$cert->id.'-'.bin2hex(random_bytes(8)).'.pdf';
            $pdf->move(self::dir(), $name);
            $update['pdf'] = $name;
        }
        foreach ($update as $k => $v) {
            if ($cert->{$k}) {
                @unlink(self::dir().'/'.basename($cert->{$k}));
            }
        }
        DB::table(self::TABLE)->where('id', $cert->id)->update($update + ['updated_at' => now()]);

        return null;
    }

    /** A small JPEG preview; until it is paid, it carries a "PREVIEW" mark. Null when there is no picture. */
    public static function thumbnail(object $cert): ?string
    {
        $path = self::path($cert, 'image');
        if (! $path || ! ($src = @imagecreatefrompng($path))) {
            return null;
        }
        $w = 480;
        $h = (int) round(imagesy($src) * $w / imagesx($src));
        $out = imagecreatetruecolor($w, $h);
        imagecopyresampled($out, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        imagedestroy($src);
        if (! $cert->paid_at) {
            // "PREVIEW" in big letters across the middle: built-in font drawn small, then enlarged
            $txt = 'PREVIEW';
            $small = imagecreatetruecolor(imagefontwidth(5) * strlen($txt) + 4, imagefontheight(5) + 2);
            imagefill($small, 0, 0, imagecolorallocate($small, 255, 255, 255));
            imagestring($small, 5, 2, 1, $txt, imagecolorallocate($small, 0, 0, 0));
            $bw = (int) ($w * 0.8);
            $bh = (int) round(imagesy($small) * $bw / imagesx($small));
            $big = imagecreatetruecolor($bw, $bh);
            imagecopyresized($big, $small, 0, 0, 0, 0, $bw, $bh, imagesx($small), imagesy($small));
            $red = imagecolorallocate($out, 200, 30, 30);
            for ($y = 0; $y < $bh; $y++) {
                for ($x = 0; $x < $bw; $x++) {
                    if ((imagecolorat($big, $x, $y) & 0xFF) < 128) {
                        imagesetpixel($out, (int) (($w - $bw) / 2) + $x, (int) (($h - $bh) / 2) + $y, $red);
                    }
                }
            }
            imagedestroy($small);
            imagedestroy($big);
        }
        ob_start();
        imagejpeg($out, null, 80);
        imagedestroy($out);

        return (string) ob_get_clean();
    }

    /** The member sends the GCash reference number of their payment. Returns an error or null. */
    public static function submitReference(int $memberId, string $ref): ?string
    {
        $cert = self::for($memberId);
        $ref = preg_replace('/[^A-Za-z0-9 \-]/', '', trim($ref));
        if (! $cert) {
            return 'No certificate has been issued to you yet.';
        }
        if (strlen(preg_replace('/\D/', '', $ref)) < 6) {
            return 'Enter the GCash reference number shown on your payment receipt.';
        }
        DB::table(self::TABLE)->where('id', $cert->id)->update(['gcash_ref' => mb_substr($ref, 0, 60), 'ref_at' => now(), 'updated_at' => now()]);

        return null;
    }

    public static function setPaid(int $memberId, bool $paid): void
    {
        self::ensureTable();
        DB::table(self::TABLE)->where('member_id', $memberId)->update(['paid_at' => $paid ? now() : null, 'updated_at' => now()]);
    }

    /** Certificates whose GCash reference number was sent but not yet marked paid. */
    public static function waiting(): \Illuminate\Support\Collection
    {
        self::ensureTable();

        return DB::table(self::TABLE.' as c')->join('members as m', 'm.id', '=', 'c.member_id')
            ->whereNotNull('c.gcash_ref')->whereNull('c.paid_at')->orderBy('c.ref_at')
            ->get(['c.member_id', 'c.ctn', 'c.gcash_ref', 'c.ref_at', 'm.name']);
    }
}
