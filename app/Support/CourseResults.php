<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each member's "Check your understanding" results: the latest verdict per question, and whether a lesson is passed.
 * Correct = 1 point, partly correct = ½, not quite = 0. A lesson is passed with at least PASS_PERCENT of the points.
 */
final class CourseResults
{
    public const PASS_PERCENT = 70;

    private const TABLE = 'course_quiz_results';

    private const TABLE_KEY = 'course-quiz-results-v1';

    private const POINTS = ['correct' => 1.0, 'partly' => 0.5, 'wrong' => 0.0];

    public static function ensureTable(): void
    {
        if (Cache::get(self::TABLE_KEY)) {
            return;
        }
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id');
                $t->unsignedBigInteger('lesson_id');
                $t->unsignedSmallInteger('q');
                $t->string('verdict', 8);
                $t->text('answer');
                $t->timestamps();
                $t->unique(['member_id', 'lesson_id', 'q']);
            });
        }
        Cache::forever(self::TABLE_KEY, true);
    }

    /** Saves the latest checked answer to one question (a new check replaces the old one). */
    public static function record(int $memberId, int $lessonId, int $q, string $verdict, string $answer): void
    {
        if (! isset(self::POINTS[$verdict])) {
            return;
        }
        self::ensureTable();
        $now = now();
        DB::table(self::TABLE)->upsert(
            [['member_id' => $memberId, 'lesson_id' => $lessonId, 'q' => $q, 'verdict' => $verdict, 'answer' => mb_substr($answer, 0, 1500), 'created_at' => $now, 'updated_at' => $now]],
            ['member_id', 'lesson_id', 'q'],
            ['verdict', 'answer', 'updated_at']
        );
    }

    /**
     * One lesson's result for a member.
     *
     * @return array{total: int, answered: int, score: float, percent: int, status: string, missed: list<int>}
     */
    public static function lesson(int $memberId, int $lessonId, int $total): array
    {
        self::ensureTable();
        $rows = DB::table(self::TABLE)->where('member_id', $memberId)->where('lesson_id', $lessonId)->where('q', '<', $total)->pluck('verdict', 'q')->all();

        return self::summary($rows, $total);
    }

    /**
     * Results for every lesson, keyed by lesson id.
     *
     * @return array<int, array{total: int, answered: int, score: float, percent: int, status: string, missed: list<int>}>
     */
    public static function forLessons(int $memberId, Collection $lessons): array
    {
        self::ensureTable();
        $ids = $lessons->pluck('id')->all();
        $bodies = DB::table('course_lessons')->whereIn('id', $ids)->pluck('body', 'id');
        $all = DB::table(self::TABLE)->where('member_id', $memberId)->whereIn('lesson_id', $ids)->get(['lesson_id', 'q', 'verdict'])->groupBy('lesson_id');
        $out = [];
        foreach ($ids as $id) {
            $total = CourseQuiz::count((string) ($bodies[$id] ?? ''));
            $rows = collect($all[$id] ?? [])->filter(fn ($r) => $r->q < $total)->pluck('verdict', 'q')->all();
            $out[$id] = self::summary($rows, $total);
        }

        return $out;
    }

    /** "5.5" or "6": half points only when there are any. */
    public static function points(float $score): string
    {
        return fmod($score, 1.0) === 0.0 ? (string) (int) $score : number_format($score, 1);
    }

    /** @param array<int, string> $rows question index => verdict */
    private static function summary(array $rows, int $total): array
    {
        $score = 0.0;
        $missed = [];
        foreach ($rows as $q => $verdict) {
            $score += self::POINTS[$verdict] ?? 0.0;
            if ($verdict !== 'correct') {
                $missed[] = (int) $q + 1;
            }
        }
        sort($missed);
        $answered = count($rows);
        $percent = $total > 0 ? (int) floor($score / $total * 100) : 0;
        $status = match (true) {
            $total === 0 => 'none',
            $answered === 0 => 'none',
            $answered < $total => 'progress',
            $score / $total * 100 >= self::PASS_PERCENT => 'passed',
            default => 'failed',
        };

        return ['total' => $total, 'answered' => $answered, 'score' => $score, 'percent' => $percent, 'status' => $status, 'missed' => $missed];
    }
}
