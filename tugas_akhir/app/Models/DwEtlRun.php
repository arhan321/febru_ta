<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DwEtlRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const RECONCILIATION_PENDING = 'pending';

    public const RECONCILIATION_SUCCESS = 'success';

    public const RECONCILIATION_FAILED = 'failed';

   protected $fillable = [
    'run_uuid',
    'batch_code',
    'trigger_type',
    'triggered_by_user_id',
    'status',
    'dimension_rows',
    'fact_rows',
    'source_rows',
    'target_rows',
    'table_row_counts',
    'error_message',
    'started_at',
    'finished_at',
    'duration_ms',
    'reconciliation_status',
    'reconciled_at',
    'reconciliation_message',
];

    protected $casts = [
    'triggered_by_user_id' => 'integer',
    'dimension_rows' => 'integer',
    'fact_rows' => 'integer',
    'source_rows' => 'integer',
    'target_rows' => 'integer',
    'table_row_counts' => 'array',
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
    'duration_ms' => 'integer',
    'reconciled_at' => 'datetime',
];

public function triggeredByUser(): BelongsTo
{
    return $this->belongsTo(User::class, 'triggered_by_user_id');
}

    public function details(): HasMany
{
    return $this->hasMany(DwEtlRunDetail::class, 'dw_etl_run_id')
        ->orderBy('step_order');
}
}