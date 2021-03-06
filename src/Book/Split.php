<?php

namespace GnuCash\Models\Book;

use GnuCash\Models\Book;

abstract class Split extends Book implements SplitInterface
{
    use SplitTrait;

    protected $table = 'splits';
    protected $primaryKey = 'guid';

    protected $fillable = [
        'reconcile_state',
        'reconcile_date',
        'account_guid',
    ];

    protected $appends = [
        'amount',
    ];

    public function account()
    {
        return $this->belongsTo($this->namespaceForBook(Account::class), 'account_guid');
    }

    public function transaction()
    {
        return $this->belongsTo($this->namespaceForBook(Transaction::class), 'tx_guid');
    }

    public function scopeForAccount($query, $accountGuid, $reconcileStates = null)
    {
        $query->where('account_guid', $accountGuid);

        if (is_array($reconcileStates)) {
            $query->whereIn('reconcile_state', $reconcileStates);
        }

        return $query;
    }

    public function scopeForTransaction($query, $txGuid)
    {
        $query->where('tx_guid', $txGuid);

        return $query;
    }

    public static function scopeOrphans($query, $relation)
    {
        $query->whereNotIn(
            $relation->splits()->getForeignKey(),
            $relation->all()->pluck($relation->getKeyName())
        );
    }

    public static function getAccountTransactions(array $accountGuids, $txGuid)
    {
        $query = static::forTransaction($txGuid);
        $query->whereIn('account_guid', $accountGuids);

        return $query->get();
    }

    public static function reconcileStateLabels($mode)
    {
        $labels = [
            static::REPLICATED     => [
                static::RECONCILE_STATE_NEW        => 'New',
                static::RECONCILE_STATE_CLEARED    => 'Agreed',
                static::RECONCILE_STATE_RECONCILED => 'Final',
            ],
            static::NOT_REPLICATED => [
                static::RECONCILE_STATE_NEW        => 'New',
                static::RECONCILE_STATE_CLEARED    => 'Cleared',
                static::RECONCILE_STATE_RECONCILED => 'Reconciled',
            ],
        ];

        return $labels[$mode];
    }

    public static function validReconcileState($state)
    {
        return in_array($state, [
            static::RECONCILE_STATE_NEW,
            static::RECONCILE_STATE_CLEARED,
            static::RECONCILE_STATE_RECONCILED,
        ]);
    }
}
