<?php

declare(strict_types=1);

namespace Tests\Feature\Organizer;

use App\Core\Actions\CreateHousehold;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\NoteType;
use App\Modules\Organizer\Enums\TaskPriority;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Integrations\OrganizerHouseholdExporter;
use App\Modules\Organizer\Models\Note;
use App\Modules\Organizer\Models\ShoppingList;
use App\Modules\Organizer\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizerHouseholdExporterTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $partner;
    private Household $household;
    private OrganizerHouseholdExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner Organizer',
            'email' => 'owner.org@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle(
            $this->owner,
            'Keluarga Organizer',
            1,
            'Asia/Jakarta'
        );
        $this->household->forceFill(['onboarded_at' => now()])->save();
        app(HouseholdContext::class)->use($this->household);

        $this->partner = User::create([
            'name' => 'Partner Organizer',
            'email' => 'partner.org@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->household->members()->create([
            'user_id' => $this->partner->id,
            'role' => 'partner',
            'slot' => 2,
            'color' => '#ec4899',
            'joined_at' => now(),
        ]);

        $this->exporter = app(OrganizerHouseholdExporter::class);
    }

    public function test_module_name_is_organizer(): void
    {
        $this->assertSame('organizer', $this->exporter->module());
    }

    public function test_private_notes_are_strictly_isolated_by_exporter_identity(): void
    {
        // 1. Public note created by Owner
        Note::withoutGlobalScope('privacy')->create([
            'household_id' => $this->household->id,
            'type' => NoteType::Text,
            'title' => 'Catatan Publik Keluarga',
            'body' => 'Bisa dibaca siapa saja',
            'is_private' => false,
            'created_by' => $this->owner->id,
        ]);

        // 2. Private note created by Owner
        Note::withoutGlobalScope('privacy')->create([
            'household_id' => $this->household->id,
            'type' => NoteType::Text,
            'title' => 'Catatan Rahasia Owner',
            'body' => 'Hanya untuk owner',
            'is_private' => true,
            'created_by' => $this->owner->id,
        ]);

        // 3. Private note created by Partner
        Note::withoutGlobalScope('privacy')->create([
            'household_id' => $this->household->id,
            'type' => NoteType::Text,
            'title' => 'Catatan Rahasia Partner',
            'body' => 'Hanya untuk partner',
            'is_private' => true,
            'created_by' => $this->partner->id,
        ]);

        // Case A: Owner exports
        $this->actingAs($this->owner);
        $ownerExport = $this->exporter->export($this->household);
        /** @var array<int, array<string, mixed>> $ownerNotes */
        $ownerNotes = $ownerExport['organizer/notes.json'];
        $ownerTitles = array_column($ownerNotes, 'title');

        $this->assertContains('Catatan Publik Keluarga', $ownerTitles);
        $this->assertContains('Catatan Rahasia Owner', $ownerTitles);
        $this->assertNotContains('Catatan Rahasia Partner', $ownerTitles, 'BR-ORG-010: Owner export must NOT include Partner private note');

        // Case B: Partner exports
        $this->actingAs($this->partner);
        $partnerExport = $this->exporter->export($this->household);
        /** @var array<int, array<string, mixed>> $partnerNotes */
        $partnerNotes = $partnerExport['organizer/notes.json'];
        $partnerTitles = array_column($partnerNotes, 'title');

        $this->assertContains('Catatan Publik Keluarga', $partnerTitles);
        $this->assertContains('Catatan Rahasia Partner', $partnerTitles);
        $this->assertNotContains('Catatan Rahasia Owner', $partnerTitles, 'BR-ORG-010: Partner export must NOT include Owner private note');

        // Case C: Unauthenticated (e.g. system automated backup)
        auth()->logout();
        $systemExport = $this->exporter->export($this->household);
        /** @var array<int, array<string, mixed>> $systemNotes */
        $systemNotes = $systemExport['organizer/notes.json'];
        $systemTitles = array_column($systemNotes, 'title');

        $this->assertContains('Catatan Publik Keluarga', $systemTitles);
        $this->assertNotContains('Catatan Rahasia Owner', $systemTitles, 'Unauthenticated export must NOT include any private notes');
        $this->assertNotContains('Catatan Rahasia Partner', $systemTitles, 'Unauthenticated export must NOT include any private notes');
    }

    public function test_exporter_includes_all_household_domains_and_isolates_other_households(): void
    {
        $this->actingAs($this->owner);

        // Populate items in this household
        Task::create([
            'household_id' => $this->household->id,
            'title' => 'Tugas Cuci Mobil',
            'priority' => TaskPriority::Normal,
            'status' => TaskStatus::Open,
            'created_by' => $this->owner->id,
        ]);

        ShoppingList::create([
            'household_id' => $this->household->id,
            'name' => 'Belanja Pasar Minggu',
            'created_by' => $this->owner->id,
        ]);

        // Populate items in other household
        $otherUser = User::create([
            'name' => 'Tetangga',
            'email' => 'tetangga@example.com',
            'password' => bcrypt('password123'),
        ]);
        $otherHousehold = app(CreateHousehold::class)->handle(
            $otherUser,
            'Keluarga Tetangga',
            1,
            'Asia/Jakarta'
        );

        $context = app(HouseholdContext::class);
        $context->runInContext($otherHousehold, function () use ($otherHousehold, $otherUser): void {
            Task::create([
                'household_id' => $otherHousehold->id,
                'title' => 'Tugas Tetangga Rahasia',
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Open,
                'created_by' => $otherUser->id,
            ]);

            ShoppingList::create([
                'household_id' => $otherHousehold->id,
                'name' => 'Belanja Tetangga Rahasia',
                'created_by' => $otherUser->id,
            ]);
        });

        $context->use($this->household);

        $exported = $this->exporter->export($this->household);

        $this->assertArrayHasKey('organizer/tasks.json', $exported);
        $this->assertArrayHasKey('organizer/notes.json', $exported);
        $this->assertArrayHasKey('organizer/shopping_lists.json', $exported);
        $this->assertArrayHasKey('organizer/events.json', $exported);

        $taskTitles = array_column($exported['organizer/tasks.json'], 'title');
        $this->assertContains('Tugas Cuci Mobil', $taskTitles);
        $this->assertNotContains('Tugas Tetangga Rahasia', $taskTitles);

        $shoppingNames = array_column($exported['organizer/shopping_lists.json'], 'name');
        $this->assertContains('Belanja Pasar Minggu', $shoppingNames);
        $this->assertNotContains('Belanja Tetangga Rahasia', $shoppingNames);
    }
}
