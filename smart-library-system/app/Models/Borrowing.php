<?php

namespace App\Models;

use App\States\Borrowing\BorrowedState;
use App\States\Borrowing\BorrowingState;
use App\States\Borrowing\CompletedState;
use App\States\Borrowing\FeeUnpaidState;
use App\States\Borrowing\OverdueState;
use App\States\Borrowing\PaymentPendingState;
use Carbon\CarbonImmutable;
use Database\Factories\BorrowingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int<1, max> $id
 * @property int<1, max> $user_id
 * @property int<1, max> $book_id
 * @property string $status
 * @property CarbonImmutable $borrowed_at
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable|null $returned_at
 * @property int<0, max> $overdue_fee_cents
 * @property string|null $payment_reference
 * @property string|null $payment_method
 * @property CarbonImmutable|null $payment_started_at
 * @property CarbonImmutable|null $payment_submitted_at
 * @property CarbonImmutable|null $payment_approved_at
 * @property int<1, max>|null $payment_approved_by
 * @property string|null $renewal_status
 * @property CarbonImmutable|null $renewal_requested_at
 * @property CarbonImmutable|null $renewal_reviewed_at
 * @property int<1, max>|null $renewal_reviewed_by
 * @property string|null $renewal_rejection_reason
 * @property int<0, max> $renewal_count
 */
#[Fillable([
    'user_id',
    'book_id',
    'status',
    'borrowed_at',
    'due_at',
    'payment_method',
    'renewal_status',
    'renewal_requested_at',
    'renewal_reviewed_at',
    'renewal_reviewed_by',
    'renewal_rejection_reason',
    'renewal_count',
])]
class Borrowing extends Model
{
    /** @use HasFactory<BorrowingFactory> */
    use HasFactory;

    // states
    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_FEE_UNPAID = 'fee_unpaid';

    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    public const STATUS_COMPLETED = 'completed';

    public const RENEWAL_STATUS_PENDING = 'pending';

    public const RENEWAL_STATUS_APPROVED = 'approved';

    public const RENEWAL_STATUS_REJECTED = 'rejected';

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
            'payment_started_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function paymentApprover(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'payment_approved_by'
        );
    }

    /**
     * @return HasMany<PaymentAudit, $this>
     */
    public function paymentAudits(): HasMany
    {
        return $this->hasMany(PaymentAudit::class);
    }

    /**
     * @param  Builder<Borrowing>  $query
     * @return Builder<Borrowing>
     */
    public function scopeActiveCopies(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    /**
     * @param  Builder<Borrowing>  $query
     * @return Builder<Borrowing>
     */
    public function scopeUnresolvedOverdue(Builder $query): Builder
    {
        return $query->whereIn(
            'status',
            self::UNRESOLVED_OVERDUE_STATUSES
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function renewalReviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'renewal_reviewed_by'
        );
    }

    public function state(): BorrowingState
    {
        return match ($this->status) {
            self::STATUS_BORROWED => new BorrowedState($this),

            self::STATUS_OVERDUE => new OverdueState($this),

            self::STATUS_FEE_UNPAID => new FeeUnpaidState($this),

            self::STATUS_PAYMENT_PENDING => new PaymentPendingState($this),

            self::STATUS_COMPLETED => new CompletedState($this),

            default => throw new LogicException(
                'Unknown borrowing state.'
            ),
        };
    }
}
