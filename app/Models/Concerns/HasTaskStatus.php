<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

/**
 * Trait for managing Task Status
 */
trait HasTaskStatus
{
    /**
     * @return Attribute<bool, never>
     */
    public function isDone(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->status && $this->status->is(TaskStatus::Done),
        );
    }

    /**
     * Check if task can be marked as internal test
     */
    public function canMarkAsInternalTest(): bool
    {
        return $this->status && ($this->status->is(TaskStatus::Active) || $this->status->is(TaskStatus::Done));
    }

    /**
     * Check if task can be marked as done
     */
    public function canMarkAsDone(): bool
    {
        return $this->status && $this->status->is(TaskStatus::InternalTest);
    }

    /**
     * Mark task as internal test
     */
    public function markAsInternalTest(): void
    {
        if ($this->canMarkAsInternalTest()) {
            $this->status = TaskStatus::InternalTest();
            $this->save();
        }
    }

    /**
     * Mark task as done
     */
    public function markAsDone(): void
    {
        if ($this->canMarkAsDone()) {
            $this->status = TaskStatus::Done();
            $this->done_at = Carbon::now();
            $this->save();
        }
    }

    /**
     * Mark task as active (active from done or internal test)
     */
    public function markAsActive(): void
    {
        $this->status = TaskStatus::Active();
        $this->done_at = null;
        $this->save();
    }
} 