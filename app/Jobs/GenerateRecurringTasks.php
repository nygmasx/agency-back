<?php

namespace App\Jobs;

use App\Services\RecurringTaskService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateRecurringTasks implements ShouldQueue
{
    use Queueable;

    public function handle(RecurringTaskService $service): void
    {
        $created = $service->generateRecurringTasks();

        Log::info("Generated {$created} recurring task instances.");
    }
}
