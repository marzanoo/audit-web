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
            // Get all non-vacant managers for MKT (excluding MGR MKT 4)
            $mktManagers = Karyawan::where('dept', 'MKT')
                ->where('emp_name', '!=', 'VACANT')
                ->where(function ($query) {
                    $query->where('remarks', 'LIKE', '%MGR MKT 1%')
                        ->orWhere('remarks', 'LIKE', '%MGR MKT 2%')
                        ->orWhere('remarks', 'LIKE', '%MGR MKT 3%')
                        ->orWhere('remarks', 'LIKE', '%MGR MKT 5%');
                })
                ->get();

            if ($mktManagers->count() > 0) {
                // Randomly select one manager from the collection
                $manager = $mktManagers->random();

                Log::info('Randomly selected MKT manager', [
                    'manager_name' => $manager->emp_name,
                    'manager_position' => $manager->remarks
                ]);
            }
        }
        // Standard handling for other departments
        else {
            $manager = Karyawan::where('dept', $dept)
                ->where('remarks', 'LIKE', '%MGR ' . $dept . '%')
                ->where('emp_name', '!=', 'VACANT')
                ->first();
        }

        Mail::to($manager->email)->send(new ApprovalMail($this));
    }
}
