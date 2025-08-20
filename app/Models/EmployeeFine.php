<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFine extends Model
{
    use HasFactory;

    protected $table = 'employee_fines';

    protected $fillable = [
        'emp_id',
        'audit_answer_id',
        'detai_audit_answer_id',
        'type',
        'amount',
        'description',
        'evidence_path',
        'payment_method',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'description' => 'string',
        'paid_at' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'emp_id', 'emp_id');
    }

    public function auditAnswer()
    {
        return $this->belongsTo(AuditAnswer::class, 'audit_answer_id', 'id');
    }

    public function detailAuditAnswer()
    {
        return $this->belongsTo(DetailAuditAnswer::class, 'detail_audit_answer_id', 'id');
    }

    public static function getTotalFines($empId)
    {
        return self::where('emp_id', $empId)
            ->where('type', 'fine')
            ->sum('amount');
    }

    public static function getTotalPayments($empId)
    {
        return self::where('emp_id', $empId)
            ->where('type', 'payment')
            ->where('status', 'paid')
            ->sum('amount');
    }

    public static function getTotalDue($empId)
    {
        return self::getTotalFines($empId) - self::getTotalPayments($empId);
    }

    public function validatePaymentWithEvidence($extractedAmount)
    {
        return abs($this->amount - $extractedAmount) <= 0.01; // Allow a small margin of error
    }
}
