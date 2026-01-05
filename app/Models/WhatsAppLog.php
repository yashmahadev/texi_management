<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppLog extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'phone_number',
        'message_type',
        'payload',
        'sent_at',
        'status',
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
