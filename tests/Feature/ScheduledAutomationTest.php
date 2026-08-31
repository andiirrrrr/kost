<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduledAutomationTest extends TestCase
{
    public function test_registers_only_phase_four_automation_at_expected_times(): void
    {
        $events = collect(app(Schedule::class)->events());

        $this->assertSame('5 0 1 * *', $this->eventFor($events, 'invoices:generate-monthly')->expression);
        $this->assertSame('10 0 * * *', $this->eventFor($events, 'invoices:update-overdue')->expression);
        $this->assertSame('0 8 * * *', $this->eventFor($events, 'whatsapp:send-reminders')->expression);
        $this->assertCount(3, $events);
    }

    /** @param Collection<int, Event> $events */
    private function eventFor(Collection $events, string $command): Event
    {
        return $events->firstOrFail(fn (Event $event): bool => Str::contains($event->command, $command));
    }
}
