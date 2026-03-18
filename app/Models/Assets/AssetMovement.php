<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use App\Models\Branch;
use App\Models\Hr\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AssetMovement extends Model
{
    use LogsActivity;
    
    protected $table = 'asset_movements';

    protected $fillable = [
        'company_id','asset_id',
        'from_branch_id','from_department_id','from_user_id',
        'to_branch_id','to_department_id','to_user_id',
        'movement_voucher','reason','status',
        'initiated_at','initiated_by','reviewed_at','reviewed_by',
        'approved_at','approved_by','completed_at','completed_by',
        'gl_post','gl_posted','gl_posted_at','journal_id','notes'
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'gl_posted_at' => 'datetime',
        'gl_post' => 'boolean',
        'gl_posted' => 'boolean',
    ];

    public function asset() { return $this->belongsTo(Asset::class); }
    public function fromBranch() { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch() { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function fromDepartment() { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment() { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function fromUser() { return $this->belongsTo(User::class, 'from_user_id'); }
    public function toUser() { return $this->belongsTo(User::class, 'to_user_id'); }
    public function journal() { return $this->belongsTo(\App\Models\Journal::class); }

    public function scopeForCompany($q, $companyId) { return $q->where('company_id', $companyId); }
    public function scopeForBranch($q, $branchId) { return $branchId ? $q->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId) : $q; }
}


