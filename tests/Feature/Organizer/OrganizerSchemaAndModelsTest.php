<?php

declare(strict_types=1);

namespace Tests\Feature\Organizer;

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\EventKind;
use App\Modules\Organizer\Enums\NoteType;
use App\Modules\Organizer\Enums\ShoppingGroup;
use App\Modules\Organizer\Enums\ShoppingListStatus;
use App\Modules\Organizer\Enums\TaskPriority;
use App\Modules\Organizer\Enums\TaskReminderOffset;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Event;
use App\Modules\Organizer\Models\EventException;
use App\Modules\Organizer\Models\Note;
use App\Modules\Organizer\Models\ShoppingItem;
use App\Modules\Organizer\Models\ShoppingList;
use App\Modules\Organizer\Models\Task;
use App\Modules\Organizer\Models\TaskItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class OrganizerSchemaAndModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_organizer_tables_are_created_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('org_tasks'));
        $this->assertTrue(Schema::hasTable('org_task_items'));
        $this->assertTrue(Schema::hasTable('org_notes'));
        $this->assertTrue(Schema::hasTable('org_note_items'));
        $this->assertTrue(Schema::hasTable('org_shopping_lists'));
        $this->assertTrue(Schema::hasTable('org_shopping_items'));
        $this->assertTrue(Schema::hasTable('org_events'));
        $this->assertTrue(Schema::hasTable('org_event_exceptions'));

        $this->assertTrue(config('modules.organizer'));
    }

    public function test_organizer_enums_provide_correct_values_and_labels(): void
    {
        $this->assertSame('Rendah', TaskPriority::Low->label());
        $this->assertSame('Normal', TaskPriority::Normal->label());
        $this->assertSame('Tinggi', TaskPriority::High->label());

        $this->assertSame('Perlu Dilakukan', TaskStatus::Open->label());
        $this->assertSame('Selesai', TaskStatus::Done->label());
        $this->assertSame('Dibatalkan', TaskStatus::Cancelled->label());

        $this->assertSame('Saat jatuh tempo', TaskReminderOffset::AtDue->label());
        $this->assertSame('1 jam sebelum', TaskReminderOffset::OneHourBefore->label());
        $this->assertSame('1 hari sebelum (08:00)', TaskReminderOffset::OneDayBefore0800->label());

        $this->assertSame('Teks Catatan', NoteType::Text->label());
        $this->assertSame('Checklist', NoteType::Checklist->label());

        $this->assertSame('Aktif', ShoppingListStatus::Active->label());
        $this->assertSame('Sayur & Buah', ShoppingGroup::Produce->label());
        $this->assertSame('Daging & Ikan', ShoppingGroup::MeatSeafood->label());

        $this->assertSame('Acara Keluarga', EventKind::Event->label());
        $this->assertSame('Ulang Tahun', EventKind::Birthday->label());
        $this->assertSame('Anniversary', EventKind::Anniversary->label());
    }

    private function createUser(string $email = 'user@example.com'): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => bcrypt('password123'),
        ]);
    }

    private function runInContext(Household $household, callable $callback): mixed
    {
        return app(HouseholdContext::class)->runInContext($household, $callback);
    }

    public function test_task_lifecycle_and_checklist_items(): void
    {
        $household = Household::create(['name' => 'Keluarga Budi', 'slug' => 'keluarga-budi']);
        $user = $this->createUser('budi@example.com');
        $assignee = $this->createUser('ani@example.com');

        $task = $this->runInContext($household, function () use ($household, $user, $assignee): Task {
            return Task::create([
                'household_id' => $household->id,
                'created_by' => $user->id,
                'title' => 'Cuci toren air',
                'description' => 'Pakai sabun khusus',
                'assignee_user_id' => $assignee->id,
                'due_date' => CarbonImmutable::now()->addDays(2)->toDateString(),
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Open,
            ]);
        });

        $this->assertNotEmpty($task->id);
        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertSame(TaskStatus::Open, $task->status);

        $item1 = TaskItem::create([
            'task_id' => $task->id,
            'text' => 'Beli sikat toren',
            'is_done' => true,
            'sort_order' => 1,
        ]);

        $item2 = TaskItem::create([
            'task_id' => $task->id,
            'text' => 'Kuras toren sampai bersih',
            'is_done' => false,
            'sort_order' => 2,
        ]);

        $this->assertCount(2, $task->fresh()->items);
        $this->assertTrue($task->fresh()->items->first()->is_done);

        // Test cascade deletion
        $task->forceDelete();
        $this->assertDatabaseMissing('org_task_items', ['id' => $item1->id]);
        $this->assertDatabaseMissing('org_task_items', ['id' => $item2->id]);
    }

    public function test_note_privacy_global_scope(): void
    {
        $household = Household::create(['name' => 'Keluarga Budi', 'slug' => 'keluarga-budi']);
        $userA = $this->createUser('usera@example.com');
        $userB = $this->createUser('userb@example.com');

        // Run in household context
        $publicNote = $this->runInContext($household, function () use ($household, $userA): Note {
            return Note::create([
                'household_id' => $household->id,
                'created_by' => $userA->id,
                'type' => NoteType::Text,
                'title' => 'Resep Nasi Goreng',
                'body' => 'Bahan-bahan...',
                'is_private' => false,
            ]);
        });

        $privateNoteA = $this->runInContext($household, function () use ($household, $userA): Note {
            return Note::create([
                'household_id' => $household->id,
                'created_by' => $userA->id,
                'type' => NoteType::Text,
                'title' => 'Rencana Kado Ulang Tahun Pasangan',
                'body' => 'Beli tas rahasia',
                'is_private' => true,
            ]);
        });

        // When User A is authenticated, User A can see both
        $this->actingAs($userA);
        $notesForUserA = $this->runInContext($household, fn () => Note::all());
        $this->assertCount(2, $notesForUserA);
        $this->assertTrue($notesForUserA->pluck('id')->contains($publicNote->id));
        $this->assertTrue($notesForUserA->pluck('id')->contains($privateNoteA->id));

        // When User B is authenticated, User B can ONLY see the public note
        $this->actingAs($userB);
        $notesForUserB = $this->runInContext($household, fn () => Note::all());
        $this->assertCount(1, $notesForUserB);
        $this->assertSame($publicNote->id, $notesForUserB->first()->id);
        $this->assertFalse($notesForUserB->pluck('id')->contains($privateNoteA->id));
    }

    public function test_shopping_list_estimated_total_and_items(): void
    {
        $household = Household::create(['name' => 'Keluarga Budi', 'slug' => 'keluarga-budi']);
        $user = $this->createUser('shop@example.com');

        $list = $this->runInContext($household, function () use ($household, $user): ShoppingList {
            return ShoppingList::create([
                'household_id' => $household->id,
                'created_by' => $user->id,
                'name' => 'Belanja Superindo',
                'status' => ShoppingListStatus::Active,
            ]);
        });

        ShoppingItem::create([
            'shopping_list_id' => $list->id,
            'name' => 'Telur Ayam',
            'quantity' => 2.0,
            'unit' => 'kg',
            'estimated_price' => 30000,
            'group' => ShoppingGroup::Produce,
            'is_checked' => true,
        ]);

        ShoppingItem::create([
            'shopping_list_id' => $list->id,
            'name' => 'Minyak Goreng',
            'quantity' => 1.0,
            'unit' => 'pouch',
            'estimated_price' => 35000,
            'group' => ShoppingGroup::Groceries,
            'is_checked' => false,
        ]);

        // Total: (2 * 30000) + (1 * 35000) = 95000
        $this->assertSame(95000, $list->estimatedTotal());
    }

    public function test_event_and_exceptions(): void
    {
        $household = Household::create(['name' => 'Keluarga Budi', 'slug' => 'keluarga-budi']);
        $user = $this->createUser('shop@example.com');

        $event = $this->runInContext($household, function () use ($household, $user): Event {
            return Event::create([
                'household_id' => $household->id,
                'created_by' => $user->id,
                'kind' => EventKind::Birthday,
                'title' => 'Ulang Tahun Anak',
                'all_day' => true,
                'start_date' => CarbonImmutable::parse('2026-11-15')->toDateString(),
                'rrule' => 'FREQ=YEARLY',
                'birth_year' => 2020,
            ]);
        });

        $exception = EventException::create([
            'event_id' => $event->id,
            'original_date' => '2026-11-15',
            'is_cancelled' => false,
            'override' => ['title' => 'Pesta Ultah Anak di Resto'],
        ]);

        $this->assertCount(1, $event->fresh()->exceptions);
        $this->assertSame('Pesta Ultah Anak di Resto', $event->fresh()->exceptions->first()->override['title']);
    }

    public function test_tenant_isolation_for_organizer_models(): void
    {
        $household1 = Household::create(['name' => 'Keluarga 1', 'slug' => 'keluarga-1']);
        $household2 = Household::create(['name' => 'Keluarga 2', 'slug' => 'keluarga-2']);
        $user = $this->createUser('isolation@example.com');

        $this->runInContext($household1, function () use ($household1, $user): void {
            Task::create([
                'household_id' => $household1->id,
                'created_by' => $user->id,
                'title' => 'Tugas Household 1',
            ]);
            ShoppingList::create([
                'household_id' => $household1->id,
                'created_by' => $user->id,
                'name' => 'Belanja Household 1',
            ]);
        });

        $this->runInContext($household2, function () use ($household2, $user): void {
            Task::create([
                'household_id' => $household2->id,
                'created_by' => $user->id,
                'title' => 'Tugas Household 2',
            ]);
        });

        // In household 1 context, only household 1 records are visible
        $this->runInContext($household1, function (): void {
            $this->assertCount(1, Task::all());
            $this->assertSame('Tugas Household 1', Task::first()->title);
            $this->assertCount(1, ShoppingList::all());
        });

        // In household 2 context, only household 2 records are visible
        $this->runInContext($household2, function (): void {
            $this->assertCount(1, Task::all());
            $this->assertSame('Tugas Household 2', Task::first()->title);
            $this->assertCount(0, ShoppingList::all());
        });
    }
}
