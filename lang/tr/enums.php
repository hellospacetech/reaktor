<?php

declare(strict_types=1);

use App\Enums\TaskStatus;

return [
    TaskStatus::class => [
        TaskStatus::Active => 'Aktif',
        TaskStatus::InternalTest => 'Dahili Test',
        TaskStatus::Done => 'Tamamlandı',
    ],
]; 