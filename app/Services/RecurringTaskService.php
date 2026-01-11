<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecurringTaskService
{
    public function generateRecurringTasks(): int
    {
        $recurringTasks = Task::whereNotNull('recurrence_rule')
            ->whereNull('parent_task_id')
            ->where('status', '!=', TaskStatus::Done)
            ->get();

        $created = 0;

        foreach ($recurringTasks as $task) {
            if ($this->shouldGenerateNextInstance($task)) {
                $this->createNextInstance($task);
                $created++;
            }
        }

        return $created;
    }

    public function shouldGenerateNextInstance(Task $task): bool
    {
        $lastInstance = Task::where('parent_task_id', $task->id)
            ->latest('created_at')
            ->first();

        if (!$lastInstance) {
            return true;
        }

        $nextDue = $this->calculateNextDueDate($task->recurrence_rule, $lastInstance->due_date ?? $lastInstance->created_at);

        return $nextDue && $nextDue->lte(now()->addDays(7));
    }

    public function createNextInstance(Task $task): Task
    {
        $lastInstance = Task::where('parent_task_id', $task->id)
            ->latest('created_at')
            ->first();

        $baseDueDate = $lastInstance?->due_date ?? $task->due_date ?? now();
        $nextDueDate = $this->calculateNextDueDate($task->recurrence_rule, $baseDueDate);

        return Task::create([
            'team_id' => $task->team_id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'description' => $task->description,
            'assigned_to' => $task->assigned_to,
            'created_by' => $task->created_by,
            'status' => TaskStatus::Todo,
            'priority' => $task->priority,
            'progress' => 0,
            'due_date' => $nextDueDate,
            'parent_task_id' => $task->id,
            'position' => 0,
        ]);
    }

    public function calculateNextDueDate(string $rrule, Carbon|string $fromDate): ?Carbon
    {
        $from = $fromDate instanceof Carbon ? $fromDate : Carbon::parse($fromDate);
        $parts = $this->parseRRule($rrule);

        if (!isset($parts['FREQ'])) {
            return null;
        }

        $interval = (int) ($parts['INTERVAL'] ?? 1);

        return match ($parts['FREQ']) {
            'DAILY' => $from->copy()->addDays($interval),
            'WEEKLY' => $this->calculateWeeklyNext($from, $parts, $interval),
            'MONTHLY' => $this->calculateMonthlyNext($from, $parts, $interval),
            'YEARLY' => $from->copy()->addYears($interval),
            default => null,
        };
    }

    protected function parseRRule(string $rrule): array
    {
        $parts = [];
        foreach (explode(';', $rrule) as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $parts[$key] = $value;
            }
        }
        return $parts;
    }

    protected function calculateWeeklyNext(Carbon $from, array $parts, int $interval): Carbon
    {
        if (!isset($parts['BYDAY'])) {
            return $from->copy()->addWeeks($interval);
        }

        $days = explode(',', $parts['BYDAY']);
        $dayMap = ['MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 0];

        $next = $from->copy()->addDay();
        $weekStart = $from->copy()->startOfWeek();

        for ($i = 0; $i < 14; $i++) {
            $dayAbbr = array_search($next->dayOfWeek, $dayMap);
            if ($dayAbbr && in_array($dayAbbr, $days)) {
                return $next;
            }
            $next->addDay();
        }

        return $from->copy()->addWeeks($interval);
    }

    protected function calculateMonthlyNext(Carbon $from, array $parts, int $interval): Carbon
    {
        if (isset($parts['BYMONTHDAY'])) {
            $day = (int) $parts['BYMONTHDAY'];
            $next = $from->copy()->addMonths($interval);
            $next->day = min($day, $next->daysInMonth);
            return $next;
        }

        if (isset($parts['BYDAY'])) {
            preg_match('/^(\d+)?([A-Z]{2})$/', $parts['BYDAY'], $matches);
            if ($matches) {
                $nth = (int) ($matches[1] ?? 1);
                $dayAbbr = $matches[2];
                $dayMap = ['MO' => Carbon::MONDAY, 'TU' => Carbon::TUESDAY, 'WE' => Carbon::WEDNESDAY, 'TH' => Carbon::THURSDAY, 'FR' => Carbon::FRIDAY, 'SA' => Carbon::SATURDAY, 'SU' => Carbon::SUNDAY];

                if (isset($dayMap[$dayAbbr])) {
                    $next = $from->copy()->addMonths($interval)->startOfMonth();
                    $next->nthOfMonth($nth, $dayMap[$dayAbbr]);
                    return $next;
                }
            }
        }

        return $from->copy()->addMonths($interval);
    }
}
