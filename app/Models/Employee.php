<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';
    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'user_id',
        'department_id',
        'employee_code',
        'first_name',
        'last_name',
        'role',
        'specialization',
        'phone',
        'status',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'employee_id', 'employee_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
}
