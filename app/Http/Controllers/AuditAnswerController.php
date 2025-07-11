<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditAnswer;
use App\Models\DetailAuditAnswer;
use App\Models\PicArea;
use App\Models\VariabelForm;
use Illuminate\Http\Request;

class AuditAnswerController extends Controller
{
    public function showFormAudit()
    {
        $area = Area::with('lantai:id,lantai')->get();
        $picArea = PicArea::with('karyawan:emp_id,emp_name,dept')->get();
        $auditAnswer = AuditAnswer::with('area:id,area', 'auditor:id,name')->get();
        return view('auditor.form-audit.index', compact('auditAnswer', 'area', 'picArea'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required',
            'auditor_id' => 'required',
            'area' => 'required',
            'pic_area' => 'required',
        ]);

        $auditAnswer = AuditAnswer::create([
            'tanggal' => $request->tanggal,
            'auditor_id' => $request->auditor_id,
            'area_id' => $request->area,
            'pic_area' => $request->pic_area,
            'total_score' => 0,
        ]);

        $auditAnswerId = $auditAnswer->id;

        $variabelForms = VariabelForm::all();

        foreach ($variabelForms as $variabel) {
            DetailAuditAnswer::create([
                'audit_answer_id' => $auditAnswerId,
                'variabel_form_id' => $variabel->id,
                'score' => 0,
            ]);
        }

        return redirect()->route('detail-audit-answer', $auditAnswerId)->with('form_audit_success', 'Form audit berhasil dibuat');
    }

    public function approve($id)
    {
        $auditAnswer = AuditAnswer::find($id);
        if (!$auditAnswer) {
            abort(404, 'Audit answer not found');
        }
        $auditAnswer->status = 'approved';
        $auditAnswer->save();

        return view('approve.index', compact('auditAnswer'));
    }

    public function deleteAuditForm($id)
    {
        $auditAnswer = AuditAnswer::find($id);
        $auditAnswer->delete();
        return redirect()->route('form-audit')->with('form_audit_success', 'Form audit berhasil dihapus');
    }
}
