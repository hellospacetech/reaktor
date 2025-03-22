<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Task;

use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property Organization $organization Organization from model binding
 * @property Task|null $task Task from model binding
 */
class TaskUpdateStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:' . implode(',', TaskStatus::getValues()),
            ],
        ];
    }

    /**
     * Get the status value from the request.
     */
    public function getStatus(): string
    {
        return $this->input('status');
    }
} 