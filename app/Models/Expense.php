<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';
    protected $primaryKey = 'expense_id';

    protected $fillable = [
        'expense_date',
        'category',
        'description',
        'quantity',
        'amount',
        'payment_method',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'quantity' => 'integer',
        'amount' => 'float',
    ];

    public const CATEGORIES = [
        'doctor' => 'ថ្លៃពេទ្យ',
        'service' => 'សេវា (Echo / Lab)',
        'supplies' => 'សម្ភារៈ',
        'other' => 'ផ្សេងៗ',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
