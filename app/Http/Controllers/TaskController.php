<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Requests\StoreTaskRequest;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Task::with(['assignee', 'creator', 'dependencies.dependsOnTask']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('due_date_from') && $request->has('due_date_to')) {
            $query->whereBetween('due_date', [
                $request->due_date_from,
                $request->due_date_to
            ]);
        }

        // Users can only see their assigned tasks
        if ($user->isUser()) {
            $query->where('assigned_to', $user->id);
        }

        $tasks = $query->paginate(10);

        return response()->json([
            'tasks' => $tasks,
            'message' => 'Tasks retrieved successfully'
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->isManager()) {
            return response()->json(['error' => 'Unauthorized. Only managers can create tasks.'], 403);
        }

        $validated = $request->validated();

        // Add the created_by field if needed
        $validated['created_by'] = auth()->id();

        $task = Task::create($validated);

        return response()->json([
            'task' => $task->load(['assignee', 'creator']),
            'message' => 'Task created successfully'
        ], 201);
    }

    public function show($id)
    {
        $user = auth()->user();
        $task = Task::with(['assignee', 'creator', 'dependencies.dependsOnTask'])->find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Users can only see their assigned tasks
        if ($user->isUser() && $task->assigned_to !== $user->id) {
            return response()->json(['error' => 'Unauthorized. You can only view tasks assigned to you.'], 403);
        }

        return response()->json([
            'task' => $task,
            'message' => 'Task retrieved successfully'
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = auth()->user();
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if ($user->isUser()) {
            if ($task->assigned_to !== $user->id) {
                return response()->json(['error' => 'Unauthorized. You can only update tasks assigned to you.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'canceled'])]
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Check if task can be completed (all dependencies completed)
            if ($request->status === 'completed' && !$task->canBeCompleted()) {
                return response()->json([
                    'error' => 'Cannot complete task. All dependencies must be completed first.'
                ], 422);
            }

            $task->update(['status' => $request->status]);

        } elseif ($user->isManager()) {
            // Managers can update all fields
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'sometimes|required|date|after:now',
                'assigned_to' => 'sometimes|required|exists:users,id',
                'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'completed', 'canceled'])]
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Check if task can be completed (all dependencies completed)
            if ($request->has('status') && $request->status === 'completed' && !$task->canBeCompleted()) {
                return response()->json([
                    'error' => 'Cannot complete task. All dependencies must be completed first.'
                ], 422);
            }

            $task->update($request->all());
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'task' => $task->load(['assignee', 'creator', 'dependencies.dependsOnTask']),
            'message' => 'Task updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user->isManager()) {
            return response()->json(['error' => 'Unauthorized. Only managers can delete tasks.'], 403);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Check if task has dependencies before deleting
        if ($task->dependentTasks()->exists()) {
            return response()->json([
                'error' => 'Cannot delete task. Other tasks depend on this task.'
            ], 422);
        }

        $task->dependencies()->delete();
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
