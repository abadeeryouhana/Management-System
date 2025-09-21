<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskDependencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->isManager()) {
            return response()->json(['error' => 'Unauthorized. Only managers can add task dependencies.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'depends_on_task_id' => 'required|exists:tasks,id|different:task_id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check for circular dependencies
        if ($this->wouldCreateCircularDependency($request->task_id, $request->depends_on_task_id)) {
            return response()->json([
                'error' => 'Cannot create dependency. This would create a circular dependency.'
            ], 422);
        }

        $dependency = TaskDependency::create([
            'task_id' => $request->task_id,
            'depends_on_task_id' => $request->depends_on_task_id
        ]);

        return response()->json([
            'dependency' => $dependency->load(['task', 'dependsOnTask']),
            'message' => 'Dependency added successfully'
        ], 201);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user->isManager()) {
            return response()->json(['error' => 'Unauthorized. Only managers can remove task dependencies.'], 403);
        }

        $dependency = TaskDependency::find($id);

        if (!$dependency) {
            return response()->json(['error' => 'Dependency not found'], 404);
        }

        $dependency->delete();

        return response()->json(['message' => 'Dependency removed successfully']);
    }

    private function wouldCreateCircularDependency($taskId, $dependsOnTaskId)
    {
        // Check if the dependency would create a circular reference
        $currentTaskId = $dependsOnTaskId;
        $visited = [$taskId => true];

        while ($currentTaskId) {
            if (isset($visited[$currentTaskId])) {
                return true; // Circular dependency detected
            }

            $visited[$currentTaskId] = true;

            $dependencies = TaskDependency::where('task_id', $currentTaskId)->get();

            if ($dependencies->isEmpty()) {
                break;
            }

            $currentTaskId = $dependencies->first()->depends_on_task_id;
        }

        return false;
    }
}
