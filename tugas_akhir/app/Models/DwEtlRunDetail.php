<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DwEtlRunDetail extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'dw_etl_run_id',
        'step_key',
        'step_name',
        'step_type',
        'step_order',
        'status',
        'source_rows',
        'target_rows',
        'started_at',
        'finished_at',
        'duration_ms',
        'error_message',
        'rolled_back',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'source_rows' => 'integer',
        'target_rows' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'rolled_back' => 'boolean',
    ];

    public function etlRun(): BelongsTo
    {
        return $this->belongsTo(DwEtlRun::class, 'dw_etl_run_id');
    }
}