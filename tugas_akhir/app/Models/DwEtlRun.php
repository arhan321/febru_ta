<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'status',
        'dimension_rows',
        'fact_rows',
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
        'dimension_rows' => 'integer',
        'fact_rows' => 'integer',
        'table_row_counts' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'reconciled_at' => 'datetime',
    ];
}