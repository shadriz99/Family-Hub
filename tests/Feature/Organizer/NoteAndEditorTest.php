<?php

declare(strict_types=1);

namespace Tests\Feature\Organizer;

use App\Core\Actions\CreateHousehold;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\NoteType;
use App\Modules\Organizer\Livewire\Notes\Editor as NoteEditor;
use App\Modules\Organizer\Livewire\Notes\Index as NotesIndex;
use App\Modules\Organizer\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class NoteAndEditorTest extends TestCase
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

    public function test_user_can_create_and_edit_note(): void
    {
        $this->actingAs($this->user);

        Livewire::test(NoteEditor::class)
            ->set('title', 'Resep Rawon Spesial')
            ->set('body', '**Bahan utama:** Daging sapi 500gr, kluwek 3 buah.')
            ->set('color', 'yellow')
            ->set('labelsInput', 'resep, masakan')
            ->call('save');

        $this->assertDatabaseHas('org_notes', [
            'household_id' => $this->household->id,
            'title' => 'Resep Rawon Spesial',
            'color' => 'yellow',
        ]);

        Livewire::test(NotesIndex::class)
            ->assertSee('Resep Rawon Spesial')
            ->assertSee('Daging sapi 500gr');
    }

    public function test_checklist_note_adds_and_toggles_items(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(NoteEditor::class)
            ->set('type', 'checklist')
            ->set('title', 'Persiapan Liburan Bali')
            ->set('newItemText', 'Tiket Pesawat')
            ->call('addChecklistItem')
            ->set('newItemText', 'Booking Hotel')
            ->call('addChecklistItem')
            ->call('save');

        $note = Note::query()->where('title', 'Persiapan Liburan Bali')->first();
        $this->assertNotNull($note);
        $this->assertSame(NoteType::Checklist, $note->type);
        $this->assertCount(2, $note->items);
    }

    public function test_note_privacy_isolation(): void
    {
        // User creates private note
        $this->actingAs($this->user);
        Livewire::test(NoteEditor::class)
            ->set('title', 'Rencana Kejutan Ultah Istri')
            ->set('body', 'Pesan kue di Harvest')
            ->set('is_private', true)
            ->call('save');

        // User sees it
        Livewire::test(NotesIndex::class)
            ->assertSee('Rencana Kejutan Ultah Istri');

        // Partner does NOT see it
        $this->actingAs($this->partner);
        Livewire::test(NotesIndex::class)
            ->assertDontSee('Rencana Kejutan Ultah Istri');
    }

    public function test_note_archive_and_soft_delete(): void
    {
        $this->actingAs($this->user);

        $note = app(HouseholdContext::class)->runInContext($this->household, function () {
            return Note::create([
                'household_id' => $this->household->id,
                'created_by' => $this->user->id,
                'title' => 'Catatan Lama',
            ]);
        });

        // Archive
        Livewire::test(NotesIndex::class)
            ->call('archive', $note->id);

        $this->assertNotNull($note->fresh()->archived_at);

        // Active list should not show archived note
        Livewire::test(NotesIndex::class, ['view' => 'active'])
            ->assertDontSee('Catatan Lama');

        // Archived list shows it
        Livewire::test(NotesIndex::class, ['view' => 'archived'])
            ->assertSee('Catatan Lama');
    }
}
