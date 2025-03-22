<?php

declare(strict_types=1);

use App\Enums\TaskStatus;

return [
    TaskStatus::class => [
        TaskStatus::Active => 'Active',
        TaskStatus::InternalTest => 'Internal Test',
        TaskStatus::Done => 'Done',
    ],
]; 