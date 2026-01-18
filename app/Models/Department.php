<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'group',
    ];

    public function monthlyDuties()
    {
        return $this->hasMany(MonthlyDuty::class);
    }
}
