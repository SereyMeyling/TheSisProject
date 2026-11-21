<?php

namespace App\Models\Billing;

use App\Models\Admission;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PatientCharge extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_INVOICED = 'invoiced';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_MEDICINE = 'medicine';
    public const TYPE_LAB = 'lab';
    public const TYPE_ROOM = 'room';

    protected $table = 'patient_charges';

    protected $fillable = [
        'patient_id',
        'medical_record_id',
        'admission_id',
        'source_type',
        'source_id',
        'source_key',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'charge_date',
        'status',
        'invoice_id',
        'invoiced_at',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'charge_date' => 'date',
        'invoiced_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id', 'record_id');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class, 'admission_id', 'admission_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Value for invoice_items.item_type. The enum allows:
     * service, room, medicine, lab_test, consultation, prescription, other.
     */
    public function invoiceItemType(): string
    {
        switch ($this->source_type) {
            case self::TYPE_LAB:
                return 'lab_test';
            case self::TYPE_ROOM:
                return 'room';
            default:
                return 'medicine';
        }
    }
}
