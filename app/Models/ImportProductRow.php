<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportProductRow extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'data',
        'status',
        'error_message'
    ];
    protected $casts = [
        'data' => 'array',
    ];
    public function batch(){
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
