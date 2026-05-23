<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'user_id',
        'warehouse_id',
        'status',
        'total_rows',
        'master_data_resolution_result'
    ];

    protected $casts = [
        'master_data_resolution_result' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function rows()
    {
        return $this->hasMany(ImportProductRow::class, 'import_batch_id');
    }
}
