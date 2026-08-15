<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Application audit entries for the simulated bank-payment workflow.
 *
 * This model never contains bank credentials or externally verified bank
 * transaction data. It is only an internal activity record.
 *
 * @property int<1, max> $id
 * @property int<1, max> $borrowing_id
 * @property int<1, max>|null $actor_user_id
 * @property string|null $payment_reference
 * @property string $event
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'borrowing_id',
    'actor_user_id',
    'payment_reference',
    'event',
    'metadata',
])]
class PaymentAudit extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Borrowing, $this>
     */
    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
