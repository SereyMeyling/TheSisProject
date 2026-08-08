<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'admission_id',
        'patient_name',
        'patient_phone',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'notes',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'paid_amount'  => 'float',
        'balance'      => 'float',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Statuses that allow editing / cancellation.
     * Paid or partially-paid invoices must never be edited or cancelled here —
     * use refunds/credit notes for those in a future iteration.
     */
    public const EDITABLE_STATUSES = ['unpaid'];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * Admission this invoice belongs to (IPD only).
     * NULL for OPD invoices.
     */
    public function admission()
    {
        return $this->belongsTo(Admission::class, 'admission_id', 'admission_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * 'ipd' when tied to an admission, otherwise 'opd'.
     */
    public function getVisitTypeAttribute(): string
    {
        return $this->admission_id ? 'ipd' : 'opd';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Editable only when unpaid, nothing has been paid yet, and not cancelled.
     * Mirrors the DB-level guard applied in the controller — this is the
     * single source of truth for "can this invoice be touched" in the UI.
     */
    public function isEditable(): bool
    {
        return $this->status === 'unpaid'
            && (float) $this->paid_amount === 0.0
            && !$this->isCancelled();
    }

    /**
     * Cancellable under the same conditions as editable: nothing paid yet.
     * Once even a partial payment exists, cancellation must go through a
     * refund workflow instead (out of scope here, flagged in the writeup).
     */
    public function isCancellable(): bool
    {
        return $this->isEditable();
    }
}
