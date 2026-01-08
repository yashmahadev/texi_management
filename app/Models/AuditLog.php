<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'performed_by',
        'performer_type',
        'remarks',
    ];

    public function performer()
    {
        return $this->morphTo('performer', 'performer_type', 'performed_by');
    }
}
