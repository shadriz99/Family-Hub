<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $household_id
 * @property string $user_id
 * @property string|null $telegram_chat_id
 * @property string|null $telegram_username
 * @property string|null $link_code
 * @property \Carbon\CarbonImmutable|null $link_code_expires_at
 * @property array<string, mixed>|null $pending_draft
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 */
final class TelegramLink extends Model
{
    use HasUlids;

    protected $table = 'fin_telegram_links';

    protected $fillable = [
        'household_id',
        'user_id',
        'telegram_chat_id',
        'telegram_username',
        'link_code',
        'link_code_expires_at',
        'pending_draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'link_code_expires_at' => 'immutable_datetime',
            'pending_draft' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Household, $this>
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLinked(): bool
    {
        return ! empty($this->telegram_chat_id);
    }

    /**
     * Generates a unique 6-digit linking code valid for 15 minutes.
     */
    public function generateLinkCode(): string
    {
        $code = (string) random_int(100000, 999999);
        $this->update([
            'link_code' => $code,
            'link_code_expires_at' => CarbonImmutable::now()->addMinutes(15),
        ]);

        return $code;
    }

    /**
     * @param array<string, mixed> $draft
     */
    public function setDraft(array $draft): void
    {
        $this->update([
            'pending_draft' => $draft,
        ]);
    }

    public function clearDraft(): void
    {
        $this->update([
            'pending_draft' => null,
        ]);
    }
}
