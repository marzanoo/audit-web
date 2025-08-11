<?php

namespace App\Models;

use App\Mail\ApprovalMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuditAnswer extends Model
{
    use HasFactory;
    protected $fillable = [
        'auditor_id',
        'tanggal',
        'area_id',
        'total_score',
        'pic_area',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function pic_area()
    {
        return $this->belongsTo(PicArea::class, 'pic_area', 'id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id', 'id');
    }

    public function detail_audit_answers()
    {
        return $this->hasMany(DetailAuditAnswer::class, 'audit_answer_id', 'id');
    }

    public function detail_signature_audit_answers()
    {
        return $this->hasMany(DetailSignatureAuditAnswer::class, 'audit_answer_id', 'id');
    }

    public function sendEmailApproval()
    {
        $pic = $this->pic_area;
        $empId = PicArea::where('id', $pic)->first()->pic_id;

        $karyawan = Karyawan::where('emp_id', $empId)->first();
        if (!$karyawan) {
            Log::error('Karyawan dengan id ' . $empId . ' tidak ditemukan');
            return;
        }

        $dept = $karyawan->dept;
        if ($dept == 'MKT') {
            // Get the AUDITEE number (1-5) from remarks
            preg_match('/AUDITEE MKT (\d)/', $karyawan->remarks, $matches);
            if (!empty($matches)) {
                $auditeeNumber = $matches[1];

                // Find corresponding manager with same number
                $manager = Karyawan::where('dept', 'MKT')
                    ->where('emp_name', '!=', 'VACANT')
                    ->where('remarks', 'LIKE', "%MGR MKT {$auditeeNumber}%")
                    ->first();

                if ($manager) {
                    Log::info('Selected MKT manager based on AUDITEE number', [
                        'auditee_remarks' => $karyawan->remarks,
                        'manager_name' => $manager->emp_name,
                        'manager_position' => $manager->remarks
                    ]);
                } else {
                    Log::error('No matching MKT manager found for AUDITEE', [
                        'auditee_remarks' => $karyawan->remarks
                    ]);
                    return;
                }
            } else {
                Log::error('Invalid AUDITEE MKT remarks format', [
                    'remarks' => $karyawan->remarks
                ]);
                return;
            }
        } else {
            // Standard handling for other departments
            $manager = Karyawan::where('dept', $dept)
                ->where('remarks', 'LIKE', '%MGR ' . $dept . '%')
                ->where('emp_name', '!=', 'VACANT')
                ->first();
        }

        if ($manager && $manager->email) {
            Mail::to($manager->email)->send(new ApprovalMail($this));
        } else {
            Log::error('Manager email not found', [
                'department' => $dept,
                'manager' => $manager ? $manager->emp_name : 'Not found'
            ]);
        }
    }
}
