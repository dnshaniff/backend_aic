<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reimbursement extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'reimbursements';

    protected $fillable = [
        'reimbursement_number',
        'category_id',
        'title',
        'description',
        'amount',
        'file',
        'status',
        'created_by',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'approved_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by', 'id');
    }
}
