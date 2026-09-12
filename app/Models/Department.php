<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $primaryKey = 'department_id';
    protected $fillable = [
        'department_name',
        'description',
    ];
    public function users()
    {
        return $this->hasMany(user::class, 'department_id', 'department_id');
    }


}
