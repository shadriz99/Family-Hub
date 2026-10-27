<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'parent_id' => null,
            'name' => 'Kategori '.Str::random(5),
            'type' => CategoryType::Expense,
            'icon' => 'tag',
            'color' => '#10b981',
            'system_key' => null,
            'default_allocation' => AllocationCategory::Living,
            'sort_order' => 0,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'name' => 'Pemasukan '.Str::random(4),
            'type' => CategoryType::Income,
            'default_allocation' => null,
            'color' => '#10b981',
        ]);
    }
}
