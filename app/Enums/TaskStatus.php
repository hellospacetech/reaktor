<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;
use BenSampo\Enum\Contracts\LocalizedEnum;

/**
 * @method static static Active()
 * @method static static InternalTest()
 * @method static static Done()
 */
final class TaskStatus extends Enum implements LocalizedEnum
{
    /**
     * Task is in active state
     */
    public const Active = 'active';
    
    /**
     * Task is in internal test state
     */
    public const InternalTest = 'internal_test';
    
    /**
     * Task is completed/done
     */
    public const Done = 'done';
} 