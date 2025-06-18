<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'employees';

    protected $fillable = [
        'nik',
        'full_name',
        'position'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'employee_id', 'id');
    }
}
