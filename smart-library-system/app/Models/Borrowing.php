<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\States\Borrowing\BorrowedState;
use App\States\Borrowing\BorrowingState;
use App\States\Borrowing\CompletedState;
use App\States\Borrowing\FeeUnpaidState;
use App\States\Borrowing\OverdueState;
use App\States\Borrowing\PaymentPendingState;
use LogicException;

#[Fillable([
    'user_id',
    'book_id',
    'status',
    'borrowed_at',
    'due_at',
])]
class Borrowing extends Model
{
    use HasFactory;

    // states
    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_FEE_UNPAID = 'fee_unpaid';

    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    public const STATUS_COMPLETED = 'completed';

    public const UNRESOLVED_OVERDUE_STATUSES = [
        self::STATUS_OVERDUE,
        self::STATUS_FEE_UNPAID,
        self::STATUS_PAYMENT_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'immutable_datetime',
            'due_at' => 'immutable_datetime',
            'returned_at' => 'immutable_datetime',
            'overdue_fee_cents' => 'integer',
            'payment_submitted_at' => 'immutable_datetime',
            'payment_approved_at' => 'immutable_datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function paymentApprover(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'payment_approved_by'
        );
    }

    public function scopeActiveCopies(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeUnresolvedOverdue(Builder $query): Builder
    {
        return $query->whereIn(
            'status',
            self::UNRESOLVED_OVERDUE_STATUSES
        );
    }

    public function state(): BorrowingState
    {
        return match ($this->status) {
            self::STATUS_BORROWED =>
                new BorrowedState($this),

            self::STATUS_OVERDUE =>
                new OverdueState($this),

            self::STATUS_FEE_UNPAID =>
                new FeeUnpaidState($this),

            self::STATUS_PAYMENT_PENDING =>
                new PaymentPendingState($this),

            self::STATUS_COMPLETED =>
                new CompletedState($this),

            default =>
                throw new LogicException(
                    'Unknown borrowing state.'
                ),
        };
    }
}