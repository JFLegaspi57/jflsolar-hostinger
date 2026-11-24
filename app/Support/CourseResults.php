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

    /** Answer text once saved by the "mark passed" staff tool (now the model answer is saved instead). */
    private const STAFF_MARK = '(marked passed by staff)';

    private const TABLE_KEY = 'course-quiz-results-v3';

    /** The last lesson each member opened (one row per member). */
    private const SEEN = 'course_last_seen';

    /** Every lesson each member has opened: first and last time (the course record keeps these). */
    private const VIEWS = 'course_lesson_views';

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
        if (! Schema::hasTable(self::SEEN)) {
            Schema::create(self::SEEN, function (Blueprint $t) {
                $t->unsignedBigInteger('member_id')->primary();
                $t->unsignedBigInteger('lesson_id');
                $t->timestamp('seen_at')->nullable();
            });
        }
        if (! Schema::hasTable(self::VIEWS)) {
            Schema::create(self::VIEWS, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id');
                $t->unsignedBigInteger('lesson_id');
                $t->unsignedInteger('times')->default(1);
                $t->timestamp('first_at')->nullable();
                $t->timestamp('last_at')->nullable();
                $t->unique(['member_id', 'lesson_id']);
            });
            // Start the record with what is already known: the last lesson each member opened, and lessons with checked answers.
            foreach (DB::table(self::SEEN)->get() as $r) {
                DB::table(self::VIEWS)->insertOrIgnore(['member_id' => $r->member_id, 'lesson_id' => $r->lesson_id, 'times' => 1, 'first_at' => $r->seen_at, 'last_at' => $r->seen_at]);
            }
            foreach (DB::table(self::TABLE)->groupBy('member_id', 'lesson_id')->selectRaw('member_id, lesson_id, MIN(created_at) as f, MAX(updated_at) as l')->get() as $r) {
                DB::table(self::VIEWS)->insertOrIgnore(['member_id' => $r->member_id, 'lesson_id' => $r->lesson_id, 'times' => 1, 'first_at' => $r->f, 'last_at' => $r->l]);
            }
        }
        Cache::forever(self::TABLE_KEY, true);
    }

    /**
     * Staff tool: marks every question of every published lesson "correct" for a member (and the lessons opened),
     * e.g. for a test account, recording the lesson's model answer as their answer. Questions the member already
     * answered correctly in their own words keep their answer. Returns lessons done.
     */
    public static function markAllPassed(int $memberId, ?int $onlyLessonId = null): int
    {
        self::ensureTable();
        $lessons = Course::lessons()->when($onlyLessonId, fn ($c) => $c->where('id', $onlyLessonId));
        $bodies = DB::table('course_lessons')->whereIn('id', $lessons->pluck('id'))->pluck('body', 'id');
        $now = now();
        $n = 0;
        foreach ($lessons as $l) {
            $questions = CourseQuiz::questions((string) ($bodies[$l->id] ?? ''));
            foreach ($questions as $q => $question) {
                $have = DB::table(self::TABLE)->where(['member_id' => $memberId, 'lesson_id' => $l->id, 'q' => $q])->first(['verdict', 'answer']);
                if (! $have || $have->verdict !== 'correct' || $have->answer === self::STAFF_MARK) {
                    $answer = mb_substr($question['a'], 0, 1500) ?: self::STAFF_MARK;
                    DB::table(self::TABLE)->upsert(
                        [['member_id' => $memberId, 'lesson_id' => $l->id, 'q' => $q, 'verdict' => 'correct', 'answer' => $answer, 'created_at' => $now, 'updated_at' => $now]],
                        ['member_id', 'lesson_id', 'q'], ['verdict', 'answer', 'updated_at']);
                }
            }
            DB::table(self::VIEWS)->insertOrIgnore(['member_id' => $memberId, 'lesson_id' => $l->id, 'times' => 1, 'first_at' => $now, 'last_at' => $now]);
            $n++;
        }

        return $n;
    }

    /** Staff tool: the member starts the course over (all answers, opened lessons and the last lesson seen are removed). */
    public static function resetMember(int $memberId): void
    {
        self::ensureTable();
        DB::table(self::TABLE)->where('member_id', $memberId)->delete();
        DB::table(self::VIEWS)->where('member_id', $memberId)->delete();
        DB::table(self::SEEN)->where('member_id', $memberId)->delete();
    }

    /** Saves the latest checked answer to one question (a new check replaces the old one). */
    public static function save(int $memberId, int $lessonId, int $q, string $verdict, string $answer): void
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

    /**
     * The member's course record: every lesson with at least one checked answer, in course order,
     * with its result and when it was last checked. With $answers, each checked question and answer too (for staff).
     *
     * @return list<array{lesson: object, result: array, last: ?\Illuminate\Support\Carbon, answers: list<array{n: int, question: string, verdict: string, answer: string}>}>
     */
    public static function record(int $memberId, bool $answers = false): array
    {
        self::ensureTable();
        $lessons = Course::lessons();
        $results = self::forLessons($memberId, $lessons);
        $rows = DB::table(self::TABLE)->where('member_id', $memberId)->orderBy('q')->get()->groupBy('lesson_id');
        $bodies = $answers ? DB::table('course_lessons')->pluck('body', 'id') : collect();
        $views = DB::table(self::VIEWS)->where('member_id', $memberId)->get()->keyBy('lesson_id');
        $out = [];
        foreach ($lessons as $l) {
            $r = $results[$l->id] ?? null;
            $view = $views[$l->id] ?? null;
            if (! $r || ($r['answered'] === 0 && ! $view)) {
                continue;
            }
            if ($r['answered'] === 0) {
                $r['status'] = 'opened';
            }
            $mine = collect($rows[$l->id] ?? []);
            $list = [];
            if ($answers) {
                $qs = CourseQuiz::questions((string) ($bodies[$l->id] ?? ''));
                foreach ($mine as $row) {
                    if (isset($qs[$row->q])) {
                        $list[] = ['n' => (int) $row->q + 1, 'question' => trim(html_entity_decode(strip_tags(preg_replace('/^\s*\d+\.\s*/', '', $qs[$row->q]['q'])), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 'verdict' => $row->verdict, 'answer' => $row->answer];
                    }
                }
            }
            $last = collect([$mine->max('updated_at'), $view?->last_at])->filter()->max();
            $out[] = ['lesson' => $l, 'result' => $r, 'last' => $last ? \Illuminate\Support\Carbon::parse($last) : null, 'answers' => $list,
                'first' => $view?->first_at ? \Illuminate\Support\Carbon::parse($view->first_at) : null, 'times' => (int) ($view->times ?? 0)];
        }

        return $out;
    }

    /**
     * Course progress for many members at once (for the admin members list), keyed by member id.
     *
     * @param  list<int>  $memberIds
     * @return array<int, array{passed: int, failed: int, started: int, total: int, seen: ?object, seenNumber: ?int}>
     */
    public static function progressFor(array $memberIds): array
    {
        if (! $memberIds) {
            return [];
        }
        self::ensureTable();
        $lessons = Course::lessons()->values();
        $pos = $lessons->mapWithKeys(fn ($l, $i) => [$l->id => $i + 1]);
        $totals = DB::table('course_lessons')->whereIn('id', $lessons->pluck('id'))->pluck('body', 'id')->map(fn ($b) => CourseQuiz::count((string) $b));
        $rows = DB::table(self::TABLE)->whereIn('member_id', $memberIds)->whereIn('lesson_id', $lessons->pluck('id'))->get(['member_id', 'lesson_id', 'q', 'verdict'])->groupBy('member_id');
        $seen = DB::table(self::SEEN)->whereIn('member_id', $memberIds)->pluck('lesson_id', 'member_id');
        $opened = DB::table(self::VIEWS)->whereIn('member_id', $memberIds)->whereIn('lesson_id', $lessons->pluck('id'))->groupBy('member_id')->selectRaw('member_id, COUNT(*) as n')->pluck('n', 'member_id');
        $out = [];
        foreach ($memberIds as $id) {
            $passed = $failed = $started = 0;
            foreach (collect($rows[$id] ?? [])->groupBy('lesson_id') as $lessonId => $answers) {
                $total = (int) ($totals[$lessonId] ?? 0);
                $r = self::summary($answers->filter(fn ($a) => $a->q < $total)->pluck('verdict', 'q')->all(), $total);
                $started += $r['answered'] > 0 ? 1 : 0;
                $passed += $r['status'] === 'passed' ? 1 : 0;
                $failed += $r['status'] === 'failed' ? 1 : 0;
            }
            $seenLesson = isset($seen[$id]) ? $lessons->firstWhere('id', (int) $seen[$id]) : null;
            $out[$id] = ['passed' => $passed, 'failed' => $failed, 'started' => $started, 'opened' => (int) ($opened[$id] ?? 0), 'total' => $lessons->count(), 'seen' => $seenLesson, 'seenNumber' => $seenLesson ? $pos[$seenLesson->id] : null];
        }

        return $out;
    }

    /**
     * Course-wide numbers for the admin: how many members have started, how far they got, and each lesson's reach.
     *
     * @return array{members: int, started: int, notStarted: int, startedWeek: int, passedAny: int, passedAll: int, maxPassed: int,
     *     bands: array<string, int>, lessons: list<array{number: int, title: string, opened: int, passed: int}>}
     */
    public static function overview(): array
    {
        self::ensureTable();
        $lessons = Course::lessons()->values();
        $ids = $lessons->pluck('id')->all();
        $members = DB::table('members')->whereNotIn('id', self::hiddenIds() ?: [0])->pluck('id')->flip();

        $views = DB::table(self::VIEWS)->whereIn('lesson_id', $ids)->get(['member_id', 'lesson_id', 'first_at'])
            ->filter(fn ($r) => isset($members[$r->member_id]));
        $quiz = DB::table(self::TABLE)->whereIn('lesson_id', $ids)->get(['member_id', 'lesson_id', 'q', 'verdict', 'created_at', 'updated_at'])
            ->filter(fn ($r) => isset($members[$r->member_id]));
        $seen = DB::table(self::SEEN)->whereIn('lesson_id', $ids)->pluck('member_id')->filter(fn ($id) => isset($members[$id]));

        $started = $views->pluck('member_id')->merge($quiz->pluck('member_id'))->merge($seen)->unique();

        // Lessons opened per member (a checked answer counts as opening the lesson).
        $openedBy = [];
        foreach ($views as $r) {
            $openedBy[$r->member_id][$r->lesson_id] = true;
        }
        foreach ($quiz as $r) {
            $openedBy[$r->member_id][$r->lesson_id] = true;
        }
        $n = count($ids);
        $bands = ['Lesson 1 only' => 0, '2 to 5 lessons' => 0, '6 to 12 lessons' => 0, '13 to '.max($n - 1, 13).' lessons' => 0, 'All '.$n.' lessons' => 0];
        $keys = array_keys($bands);
        foreach ($started as $m) {
            $c = count($openedBy[$m] ?? []);
            $band = match (true) {
                $c >= $n && $n > 0 => $keys[4],
                $c >= 13 => $keys[3],
                $c >= 6 => $keys[2],
                $c >= 2 => $keys[1],
                default => $keys[0],
            };
            $bands[$band]++;
        }

        // Passed lessons, per member and per lesson.
        $totals = DB::table('course_lessons')->whereIn('id', $ids)->pluck('body', 'id')->map(fn ($b) => CourseQuiz::count((string) $b));
        $passedBy = [];
        $passedLesson = [];
        // How long it took: from first opening the lesson to the last answer check of a passed lesson.
        $openedAt = [];
        foreach ($views as $r) {
            if ($r->first_at) {
                $openedAt[$r->member_id.'-'.$r->lesson_id] = (string) $r->first_at;
            }
        }
        $lessonSecs = [];       // every passed lesson, all members
        $lessonSecsBy = [];     // per lesson id
        $memberSpan = [];       // member id => [start, end] over their passed lessons
        foreach ($quiz->groupBy(fn ($r) => $r->member_id.'-'.$r->lesson_id) as $key => $rows) {
            $first = $rows->first();
            $total = (int) ($totals[$first->lesson_id] ?? 0);
            $r = self::summary($rows->filter(fn ($a) => $a->q < $total)->pluck('verdict', 'q')->all(), $total);
            if ($r['status'] === 'passed') {
                $passedBy[$first->member_id] = ($passedBy[$first->member_id] ?? 0) + 1;
                $passedLesson[$first->lesson_id] = ($passedLesson[$first->lesson_id] ?? 0) + 1;
                $start = (string) ($openedAt[$key] ?? $rows->min('created_at') ?? '');
                $end = (string) ($rows->max('updated_at') ?? '');
                if ($start !== '' && $end !== '') {
                    $secs = max(0, strtotime($end) - strtotime($start));
                    $lessonSecs[] = $secs;
                    $lessonSecsBy[$first->lesson_id][] = $secs;
                    $span = $memberSpan[$first->member_id] ?? [$start, $end];
                    $memberSpan[$first->member_id] = [min($span[0], $start), max($span[1], $end)];
                }
            }
        }
        // Whole course: from the first lesson opened to the last lesson passed, for members who passed every lesson.
        $courseSecs = [];
        $finishedIn = [];
        foreach ($passedBy as $m => $c) {
            if ($n > 0 && $c >= $n && isset($memberSpan[$m])) {
                $finishedIn[$m] = $courseSecs[] = max(0, strtotime($memberSpan[$m][1]) - strtotime($memberSpan[$m][0]));
            }
        }

        $firstAt = [];
        foreach ($views as $r) {
            if ($r->first_at && (! isset($firstAt[$r->member_id]) || $r->first_at < $firstAt[$r->member_id])) {
                $firstAt[$r->member_id] = $r->first_at;
            }
        }
        $weekAgo = now()->subDays(7)->toDateTimeString();

        $openedLesson = [];
        foreach ($openedBy as $m => $ls) {
            foreach (array_keys($ls) as $l) {
                $openedLesson[$l] = ($openedLesson[$l] ?? 0) + 1;
            }
        }

        // Everyone who started, leading first: most lessons passed, then most lessons opened, then most recently active.
        $lastAt = [];
        foreach ($views as $r) {
            $lastAt[$r->member_id] = max((string) ($lastAt[$r->member_id] ?? ''), (string) $r->first_at);
        }
        foreach (DB::table(self::VIEWS)->whereIn('lesson_id', $ids)->groupBy('member_id')->selectRaw('member_id, MAX(last_at) as l')->pluck('l', 'member_id') as $m => $l) {
            $lastAt[$m] = max((string) ($lastAt[$m] ?? ''), (string) $l);
        }
        foreach ($quiz->groupBy('member_id') as $m => $rows) {
            $lastAt[$m] = $lastAt[$m] ?? '';
        }
        // When each member started the course: the first lesson opened (or the first answer checked, for old records).
        $startedAt = $firstAt;
        foreach ($quiz as $r) {
            if ($r->created_at && (! isset($startedAt[$r->member_id]) || (string) $r->created_at < (string) $startedAt[$r->member_id])) {
                $startedAt[$r->member_id] = $r->created_at;
            }
        }
        $seenLesson = DB::table(self::SEEN)->pluck('lesson_id', 'member_id');
        $pos = $lessons->mapWithKeys(fn ($l, $i) => [$l->id => $i + 1]);
        $people = DB::table('members')->whereIn('id', $started->all())->get(['id', 'name', 'email'])->keyBy('id');
        $ranking = $started->map(fn ($m) => [
            'id' => (int) $m,
            'name' => (string) ($people[$m]->name ?? ''),
            'email' => (string) ($people[$m]->email ?? ''),
            'passed' => (int) ($passedBy[$m] ?? 0),
            'opened' => count($openedBy[$m] ?? []),
            'last' => isset($seenLesson[$m]) ? ($pos[(int) $seenLesson[$m]] ?? null) : null,
            'at' => ($lastAt[$m] ?? '') ?: null,
            'started' => isset($startedAt[$m]) ? (string) $startedAt[$m] : null,
            'took' => $finishedIn[$m] ?? null,
        ])->sort(fn ($a, $b) => [$b['passed'], $b['opened'], (string) $b['at']] <=> [$a['passed'], $a['opened'], (string) $a['at']])->values()->all();

        return [
            'hidden' => DB::table('members')->whereIn('id', self::hiddenIds() ?: [0])->get(['id', 'name', 'email'])->map(fn ($m) => ['id' => (int) $m->id, 'name' => (string) $m->name, 'email' => (string) $m->email])->all(),
            'ranking' => $ranking,
            'members' => $members->count(),
            'started' => $started->count(),
            'notStarted' => max(0, $members->count() - $started->count()),
            'startedWeek' => count(array_filter($firstAt, fn ($t) => (string) $t >= $weekAgo)),
            'passedAny' => count($passedBy),
            'passedAll' => $n > 0 ? count(array_filter($passedBy, fn ($c) => $c >= $n)) : 0,
            'maxPassed' => $passedBy ? max($passedBy) : 0,
            'bands' => $bands,
            'lessons' => $lessons->map(fn ($l, $i) => ['number' => $i + 1, 'title' => (string) $l->title, 'opened' => (int) ($openedLesson[$l->id] ?? 0), 'passed' => (int) ($passedLesson[$l->id] ?? 0),
                'avg' => self::average($lessonSecsBy[$l->id] ?? [])])->all(),
            'timing' => [
                'lessonAvg' => self::average($lessonSecs), 'lessonMedian' => self::median($lessonSecs), 'lessonCount' => count($lessonSecs),
                'courseAvg' => self::average($courseSecs), 'courseMedian' => self::median($courseSecs), 'courseCount' => count($courseSecs),
            ],
        ];
    }

    /** @param list<int> $secs */
    private static function average(array $secs): ?int
    {
        return $secs ? (int) round(array_sum($secs) / count($secs)) : null;
    }

    /** @param list<int> $secs */
    private static function median(array $secs): ?int
    {
        if (! $secs) {
            return null;
        }
        sort($secs);
        $c = count($secs);

        return (int) ($c % 2 ? $secs[intdiv($c, 2)] : round(($secs[$c / 2 - 1] + $secs[$c / 2]) / 2));
    }

    /** "45 min", "2 hours 10 min", "3 days 4 hours", "2 weeks 1 day", "1 month 2 weeks" (a month = 30 days): the two largest units that aren't zero. */
    public static function duration(?int $secs): string
    {
        if ($secs === null) {
            return '—';
        }
        if ($secs < 60) {
            return 'under 1 min';
        }
        $units = [['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['min', 60]];
        $parts = [];
        $left = $secs;
        foreach ($units as [$name, $size]) {
            $v = intdiv($left, $size);
            if ($v > 0) {
                $parts[] = $v.' '.$name.($v > 1 && $name !== 'min' ? 's' : '');
                $left -= $v * $size;
                if (count($parts) === 2) {
                    break;
                }
            }
        }

        return implode(' ', $parts);
    }

    /** Members on a course page right now (opened one within the last few minutes), hidden members left out. */
    public static function onlineCount(): int
    {
        return (int) DB::table('member_activity')->where('created_at', '>=', now()->subMinutes(Members::ONLINE_MINUTES))
            ->where('path', 'like', Course::PATH.'%')->whereNotIn('member_id', self::hiddenIds() ?: [0])->distinct()->count('member_id');
    }

    /** How many members have started the course (opened a lesson or checked an answer). */
    /**
     * Members left out of the course numbers and ranking (e.g. the owner's own test account), set in Admin → Online course.
     *
     * @return list<int>
     */
    public static function hiddenIds(): array
    {
        $f = storage_path('app/course-hidden-members.json');
        $ids = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];

        return is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
    }

    public static function setHidden(int $memberId, bool $hidden): void
    {
        $ids = array_values(array_diff(self::hiddenIds(), [$memberId]));
        if ($hidden) {
            $ids[] = $memberId;
        }
        @mkdir(storage_path('app'), 0755, true);
        file_put_contents(storage_path('app/course-hidden-members.json'), json_encode($ids), LOCK_EX);
        \Illuminate\Support\Facades\Cache::forget('public-visitor-stats-v3');
    }

    public static function studentCount(): int
    {
        self::ensureTable();
        $ids = DB::table(self::VIEWS)->select('member_id')->union(DB::table(self::TABLE)->select('member_id'))->pluck('member_id')->unique()->all();

        return $ids ? DB::table('members')->whereIn('id', $ids)->whereNotIn('id', self::hiddenIds() ?: [0])->where('status', 'active')->count() : 0;
    }

    /** The member has opened at least one lesson or checked an answer. */
    public static function started(int $memberId): bool
    {
        self::ensureTable();

        return DB::table(self::VIEWS)->where('member_id', $memberId)->exists() || DB::table(self::TABLE)->where('member_id', $memberId)->exists();
    }

    /** Remembers the lesson a member has just opened. */
    public static function seen(int $memberId, int $lessonId): void
    {
        self::ensureTable();
        DB::table(self::SEEN)->upsert([['member_id' => $memberId, 'lesson_id' => $lessonId, 'seen_at' => now()]], ['member_id'], ['lesson_id', 'seen_at']);
        $now = now();
        $done = DB::table(self::VIEWS)->where('member_id', $memberId)->where('lesson_id', $lessonId)->update(['times' => DB::raw('times + 1'), 'last_at' => $now]);
        if (! $done) {
            DB::table(self::VIEWS)->insertOrIgnore(['member_id' => $memberId, 'lesson_id' => $lessonId, 'times' => 1, 'first_at' => $now, 'last_at' => $now]);
        }
    }

    /**
     * Where the member should go next in the course.
     * kind: start (nothing done yet) | resume (an opened lesson not passed yet) | next (after the last passed lesson) | complete (all passed).
     *
     * @return array{kind: string, lesson: ?object, number: ?int, done: ?object, doneNumber: ?int}
     */
    public static function nextStep(int $memberId): array
    {
        self::ensureTable();
        $lessons = Course::lessons()->values();
        $none = ['kind' => 'start', 'lesson' => $lessons->first(), 'number' => $lessons->isEmpty() ? null : 1, 'done' => null, 'doneNumber' => null];
        if ($lessons->isEmpty()) {
            return $none;
        }
        $results = self::forLessons($memberId, $lessons);
        $passed = fn ($l) => ($results[$l->id]['status'] ?? '') === 'passed';
        $pos = $lessons->mapWithKeys(fn ($l, $i) => [$l->id => $i]);

        // The last finished lesson: the passed lesson checked most recently.
        $lastChecked = DB::table(self::TABLE)->where('member_id', $memberId)->groupBy('lesson_id')->selectRaw('lesson_id, MAX(updated_at) as at')->pluck('at', 'lesson_id');
        $done = $lessons->filter($passed)->sortByDesc(fn ($l) => (string) ($lastChecked[$l->id] ?? ''))->first();
        $seen = DB::table(self::SEEN)->where('member_id', $memberId)->first();
        $seenLesson = $seen ? $lessons->firstWhere('id', (int) $seen->lesson_id) : null;

        // Opened a lesson after finishing the last one and not passed it yet: continue there.
        if ($seenLesson && ! $passed($seenLesson) && (! $done || (string) $seen->seen_at >= (string) ($lastChecked[$done->id] ?? ''))) {
            return ['kind' => 'resume', 'lesson' => $seenLesson, 'number' => $pos[$seenLesson->id] + 1,
                'done' => $done, 'doneNumber' => $done ? $pos[$done->id] + 1 : null];
        }
        if (! $done) {
            return $seenLesson ? ['kind' => 'resume', 'lesson' => $seenLesson, 'number' => $pos[$seenLesson->id] + 1, 'done' => null, 'doneNumber' => null] : $none;
        }
        // The next lesson after the last finished one that isn't passed yet (or any earlier one still open).
        $after = $lessons->slice($pos[$done->id] + 1)->first(fn ($l) => ! $passed($l)) ?? $lessons->first(fn ($l) => ! $passed($l));
        if (! $after) {
            return ['kind' => 'complete', 'lesson' => null, 'number' => null, 'done' => $done, 'doneNumber' => $pos[$done->id] + 1];
        }

        return ['kind' => 'next', 'lesson' => $after, 'number' => $pos[$after->id] + 1, 'done' => $done, 'doneNumber' => $pos[$done->id] + 1];
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
