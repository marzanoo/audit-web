<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditAnswer;
use App\Models\DetailAuditAnswer;
use App\Models\Karyawan;
use App\Models\PicArea;
use App\Models\VariabelForm;
use Illuminate\Http\Request;

class AuditAnswerController extends Controller
{
    public function getTotalAuditByAuditor($id)
    {
        $total = AuditAnswer::where('auditor_id', $id)->count();
        return response()->json(['total' => $total]);
    }

    public function getTotalAudit()
    {
        $total = AuditAnswer::count();
        return response()->json([
            'message' => 'Total audit berhasil diambil',
            'total' => $total
        ]);
    }

    public function getAuditAnswerByArea($areaId)
    {
        $auditAnswers = AuditAnswer::where('area_id', $areaId)
            ->with('auditor:id,name', 'area:id,area,lantai_id')->orderBy('tanggal', 'desc') // This loads the related auditor with just id and name fields
            ->get();

        return response()->json([
            'message' => 'Audit answers berhasil diambil',
            'audit_answer' => $auditAnswers
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'tanggal' => 'required',
            'auditor_id' => 'required',
            'area_id' => 'required',
            'pic_area' => 'required',
        ]);

        // 1. Insert ke tabel audit_answers
        $auditAnswer = AuditAnswer::create([
            'tanggal' => $request->tanggal,
            'auditor_id' => $request->auditor_id,
            'area_id' => $request->area_id,
            'pic_area' => $request->pic_area,
            'total_score' => 0,
        ]);

        // 2. Ambil ID audit_answers yang baru saja dibuat
        $auditAnswerId = $auditAnswer->id;

        // 3. Ambil semua data dari tabel variabel_form
        $variabelForms = VariabelForm::all();

        // 4. Insert ke tabel detail_audit_answers
        foreach ($variabelForms as $variabel) {
            DetailAuditAnswer::create([
                'audit_answer_id' => $auditAnswerId, // Foreign key dari audit_answers
                'variabel_form_id' => $variabel->id, // Foreign key dari variabel_form
                'score' => 0, // Score diisi nanti
            ]);
        }

        return response()->json([
            'message' => 'Audit answer dan detail berhasil disimpan.',
            'audit_answer' => $auditAnswer,
            'detail_audit_answers' => DetailAuditAnswer::where('audit_answer_id', $auditAnswerId)->get(),
        ]);
    }

    public function show($id)
    {
        $auditAnswer = AuditAnswer::find($id);

        if (!$auditAnswer) {
            return response()->json([
                'message' => 'Audit answer tidak ditemukan'
            ], 404);
        }

        $auditAnswer->grade = $this->getGrade($id);

        $picId = $auditAnswer->pic_area;
        $empId = PicArea::where('id', $picId)->first()->pic_id;
        $karyawan = Karyawan::where('emp_id', $empId)->first();
        $picName = $karyawan ? $karyawan->emp_name : null;

        $auditAnswer->pic_name = $picName;

        return response()->json([
            'message' => 'Audit answer berhasil diambil',
            'audit_answer' => $auditAnswer
        ]);
    }


    private function getGrade($id)
    {
        $grade = "";
        $auditAnswer = AuditAnswer::where('id', $id)->first();
        if ($auditAnswer->total_score <= 2) {
            return $grade = "Diamond";
        } else if ($auditAnswer->total_score <= 4) {
            return $grade = "Platinum";
        } else if ($auditAnswer->total_score <= 6) {
            return $grade = "Gold";
        } else if ($auditAnswer->total_score <= 8) {
            return $grade = "Silver";
        } else if ($auditAnswer->total_score >= 9) {
            return $grade = "Bronze";
        } else {
            return $grade = "Unknown";
        }
    }
}
