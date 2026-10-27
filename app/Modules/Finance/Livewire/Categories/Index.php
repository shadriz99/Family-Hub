<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Categories;

use App\Core\Support\HouseholdContext;
use App\Modules\Finance\Actions\MergeCategories;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Index extends Component
{
    public string $activeTab = 'expense';

    // Modal states
    public bool $showFormModal = false;
    public bool $showMergeModal = false;

    // Form fields
    public ?string $editingCategoryId = null;
    public ?string $parent_id = null;
    public string $name = '';
    public string $type = 'expense';
    public ?string $icon = null;
    public ?string $color = null;
    public ?string $default_allocation = null;

    // Merge fields
    public ?string $mergeSourceId = null;
    public ?string $mergeTargetId = null;

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openCreate(?string $parentId = null): void
    {
        $this->resetValidation();
        $this->editingCategoryId = null;
        $this->parent_id = $parentId;
        $this->name = '';
        $this->type = $this->activeTab;
        $this->icon = null;
        $this->color = null;
        $this->default_allocation = $this->activeTab === 'expense' ? AllocationCategory::Living->value : null;
        $this->showFormModal = true;
    }

    public function openEdit(string $id, HouseholdContext $context): void
    {
        $household = $context->current();
        $category = Category::query()->where('household_id', $household->id)->findOrFail($id);

        $this->resetValidation();
        $this->editingCategoryId = $category->id;
        $this->parent_id = $category->parent_id;
        $this->name = $category->name;
        $this->type = $category->type->value;
        $this->icon = $category->icon;
        $this->color = $category->color;
        $this->default_allocation = $category->default_allocation?->value;
        $this->showFormModal = true;
    }

    public function saveCategory(HouseholdContext $context): void
    {
        $household = $context->current();

        $this->validate([
            'name' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'parent_id' => ['nullable', 'exists:fin_categories,id'],
            'default_allocation' => ['nullable', Rule::enum(AllocationCategory::class)],
        ]);

        if ($this->editingCategoryId === null && $this->parent_id === null && $this->type === 'expense') {
            $parentExpenseCount = Category::query()
                ->where('household_id', $household->id)
                ->whereNull('parent_id')
                ->where('type', CategoryType::Expense)
                ->whereNull('archived_at')
                ->count();

            if ($parentExpenseCount >= 5) {
                $this->addError('name', 'Maksimal 5 kategori utama pengeluaran. Anda dapat menambahkan sub-kategori di bawah kategori utama yang ada.');

                return;
            }
        }

        if ($this->editingCategoryId !== null) {
            $category = Category::query()->where('household_id', $household->id)->findOrFail($this->editingCategoryId);
            $category->name = $this->name;
            $category->icon = $this->icon;
            $category->color = $this->color;
            if (! $category->isSystem()) {
                $category->parent_id = $this->parent_id;
            }
            if ($this->type === 'expense') {
                $category->default_allocation = $this->default_allocation !== null ? AllocationCategory::from($this->default_allocation) : null;
            }
            $category->save();
            session()->flash('message', "Kategori '{$category->name}' berhasil diperbarui.");
        } else {
            $category = new Category();
            $category->household_id = $household->id;
            $category->name = $this->name;
            $category->type = CategoryType::from($this->type);
            $category->parent_id = $this->parent_id;
            $category->icon = $this->icon;
            $category->color = $this->color;
            if ($this->type === 'expense') {
                $category->default_allocation = $this->default_allocation !== null ? AllocationCategory::from($this->default_allocation) : null;
            }
            $category->created_by = auth()->id();
            $category->save();
            session()->flash('message', "Kategori '{$category->name}' berhasil dibuat.");
        }

        $this->showFormModal = false;
    }

    public function toggleArchive(string $id, HouseholdContext $context): void
    {
        $household = $context->current();
        $category = Category::query()->where('household_id', $household->id)->findOrFail($id);

        if ($category->isSystem()) {
            session()->flash('error', 'Kategori sistem tidak dapat diarsipkan.');

            return;
        }

        if ($category->isArchived()) {
            $category->unarchive();
            session()->flash('message', "Kategori '{$category->name}' diaktifkan kembali.");
        } else {
            $category->archive();
            session()->flash('message', "Kategori '{$category->name}' berhasil diarsipkan.");
        }
    }

    public function openMerge(string $sourceId, HouseholdContext $context): void
    {
        $household = $context->current();
        $source = Category::query()->where('household_id', $household->id)->findOrFail($sourceId);

        if ($source->isSystem()) {
            session()->flash('error', 'Kategori sistem tidak dapat digabungkan.');

            return;
        }

        $this->mergeSourceId = $source->id;
        $this->mergeTargetId = null;
        $this->showMergeModal = true;
    }

    public function executeMerge(MergeCategories $merger, HouseholdContext $context): void
    {
        $household = $context->current();
        $source = Category::query()->where('household_id', $household->id)->findOrFail($this->mergeSourceId);
        $target = Category::query()->where('household_id', $household->id)->findOrFail($this->mergeTargetId);

        $merger->execute($source, $target);

        $this->showMergeModal = false;
        session()->flash('message', "Kategori '{$source->name}' berhasil digabungkan ke '{$target->name}'.");
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();

        $type = CategoryType::from($this->activeTab);

        $categories = Category::query()
            ->where('household_id', $household->id)
            ->where('type', $type)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $mergeOptions = Category::query()
            ->where('household_id', $household->id)
            ->where('type', $type)
            ->whereNull('archived_at')
            ->when($this->mergeSourceId, fn ($q) => $q->where('id', '!=', $this->mergeSourceId))
            ->orderBy('name')
            ->get();

        return view('finance::categories.index', [
            'categories' => $categories,
            'mergeOptions' => $mergeOptions,
        ])->layout('layouts.app', ['title' => 'Kategori Keuangan']);
    }
}
