<?php

declare(strict_types=1);

namespace Tests\Feature\Organizer;

use App\Core\Actions\CreateHousehold;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Organizer\Actions\CompleteTask;
use App\Modules\Organizer\Actions\CreateTask;
use App\Modules\Organizer\Actions\UndoCompleteTask;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Livewire\Tasks\Form as TaskForm;
use App\Modules\Organizer\Livewire\Tasks\Index as TasksIndex;
use App\Modules\Organizer\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class TaskLifecycleAndRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $partner;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Budi',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        $this->partner = User::create([
            'name' => 'Ani Santoso',
            'email' => 'ani@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);

        HouseholdMember::create([
            'household_id' => $this->household->id,
            'user_id' => $this->partner->id,
            'role' => 'partner',
            'color' => '#f43f5e',
            'joined_at' => now(),
            'slot' => 2,
        ]);

        app(HouseholdContext::class)->use($this->household);
    }

    public function test_user_can_create_task_with_checklist_items_and_render_index(): void
    {
        $this->actingAs($this->user);

        Livewire::test(TaskForm::class)
            ->set('title', 'Cuci Filter AC')
            ->set('description', 'Pakai semprotan tekanan sedang')
            ->set('priority', 'high')
            ->set('due_date', CarbonImmutable::now()->addDay()->toDateString())
            ->set('items', [
                ['text' => 'Lepas filter'],
                ['text' => 'Bilas dengan air mengalir'],
            ])
            ->call('save')
            ->assertRedirect('/organizer/tasks');

        $this->assertDatabaseHas('org_tasks', [
            'household_id' => $this->household->id,
            'title' => 'Cuci Filter AC',
            'priority' => 'high',
        ]);

        $task = Task::query()->where('title', 'Cuci Filter AC')->first();
        $this->assertNotNull($task);
        $this->assertCount(2, $task->items);

        Livewire::test(TasksIndex::class, ['tab' => 'upcoming'])
            ->assertSee('Cuci Filter AC')
            ->assertSee('Tinggi');
    }

    public function test_recurring_task_generates_next_instance_on_completion_with_rotation(): void
    {
        $this->actingAs($this->user);

        $task = app(HouseholdContext::class)->runInContext($this->household, function () {
            return app(CreateTask::class)->handle($this->user, [
                'title' => 'Sapu dan Pel Lantai',
                'due_date' => '2026-11-01',
                'rrule' => 'FREQ=WEEKLY',
                'rotate_assignee' => true,
                'assignee_user_id' => $this->user->id,
                'items' => ['Sapu', 'Pel'],
            ]);
        });

        $this->assertSame($this->user->id, $task->assignee_user_id);

        // Complete the task
        app(HouseholdContext::class)->runInContext($this->household, function () use ($task) {
            app(CompleteTask::class)->handle($task, $this->user);
        });

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);

        // Verify next recurring instance was generated
        $nextTask = Task::query()
            ->where('household_id', $this->household->id)
            ->where('series_id', $task->series_id)
            ->where('status', TaskStatus::Open)
            ->first();

        $this->assertNotNull($nextTask);
        $this->assertSame('2026-11-08', $nextTask->due_date->toDateString());
        // Rotated to partner
        $this->assertSame($this->partner->id, $nextTask->assignee_user_id);
        // Checklist reset
        $this->assertCount(2, $nextTask->items);
        $this->assertFalse($nextTask->items->first()->is_done);
    }

    public function test_undo_within_five_seconds_reopens_task_and_removes_untouched_next_instance(): void
    {
        $this->actingAs($this->user);

        $task = app(HouseholdContext::class)->runInContext($this->household, function () {
            return app(CreateTask::class)->handle($this->user, [
                'title' => 'Buang Sampah',
                'due_date' => '2026-11-01',
                'rrule' => 'FREQ=DAILY',
            ]);
        });

        // Complete
        app(HouseholdContext::class)->runInContext($this->household, function () use ($task) {
            app(CompleteTask::class)->handle($task, $this->user);
        });

        $this->assertSame(TaskStatus::Done, $task->fresh()->status);
        $this->assertDatabaseCount('org_tasks', 2);

        // Undo immediately
        app(HouseholdContext::class)->runInContext($this->household, function () use ($task) {
            app(UndoCompleteTask::class)->handle($task->fresh());
        });

        // Next instance was deleted and original task reopened
        $this->assertDatabaseCount('org_tasks', 1);
        $this->assertSame(TaskStatus::Open, $task->fresh()->status);
    }

    public function test_task_complete_with_expense_records_transaction_via_finance_gateway(): void
    {
        $this->actingAs($this->user);

        $account = Account::create([
            'household_id' => $this->household->id,
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opening_date' => '2026-01-01',
        ]);

        $category = Category::query()->where('household_id', $this->household->id)->first();

        $task = app(HouseholdContext::class)->runInContext($this->household, function () use ($category) {
            return app(CreateTask::class)->handle($this->user, [
                'title' => 'Beli token listrik',
                'expense_on_complete' => true,
                'expense_estimate' => 200000,
                'expense_category_id' => $category?->id,
            ]);
        });

        $expenseData = new ExpenseData(
            accountId: $account->id,
            categoryId: $category->id,
            amount: 200000,
            occurredOn: CarbonImmutable::now(),
            note: 'Token PLN',
            sourceType: 'org_task',
            sourceId: $task->id,
        );

        app(HouseholdContext::class)->runInContext($this->household, function () use ($task, $expenseData) {
            app(CompleteTask::class)->handle($task, $this->user, $expenseData);
        });

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->expense_transaction_id);

        $this->assertDatabaseHas('fin_transactions', [
            'id' => $task->expense_transaction_id,
            'amount' => 200000,
            'source_type' => 'org_task',
        ]);
    }
}
