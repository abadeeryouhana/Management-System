<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $managerRole = Role::create(['name' => 'manager']);
        $userRole = Role::create(['name' => 'user']);

        // Create users
        $this->manager = User::factory()->create([
            'role_id' => $managerRole->id
        ]);

        $this->user = User::factory()->create([
            'role_id' => $userRole->id
        ]);

        // Create tasks
        $this->task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id
        ]);
    }

    public function test_manager_can_create_task()
    {
        $response = $this->actingAs($this->manager, 'api')
            ->json('POST', '/api/tasks', [
                'title' => 'Test Task',
                'description' => 'Test Description',
                'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'assigned_to' => $this->user->id
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'task' => [
                    'id', 'title', 'description', 'status', 'due_date',
                    'assigned_to', 'created_by', 'created_at', 'updated_at'
                ],
                'message'
            ]);
    }

    public function test_user_cannot_create_task()
    {
        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/tasks', [
                'title' => 'Test Task',
                'description' => 'Test Description',
                'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'assigned_to' => $this->user->id
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_view_their_tasks()
    {
        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tasks' => [
                    'current_page',
                    'data' => [
                        ['id', 'title', 'description', 'status', 'due_date']
                    ]
                ],
                'message'
            ]);
    }

    public function test_user_cannot_view_other_users_tasks()
    {
        $otherUser = User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id]);
        $otherTask = Task::factory()->create(['assigned_to' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/tasks/' . $otherTask->id);

        $response->assertStatus(403);
    }
}
