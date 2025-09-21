<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTaskRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::user()->isManager();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
            'status' => 'nullable|in:pending,in_progress,completed',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => 'The task title is required.',
            'title.max' => 'The task title may not be greater than 255 characters.',
            'due_date.after' => 'The due date must be a date after today.',
            'status.in' => 'The status must be one of: pending, in_progress, completed.',
            'assigned_to.exists' => 'The selected user does not exist.',
        ];
    }
}
