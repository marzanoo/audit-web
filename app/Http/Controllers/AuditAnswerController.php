<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditAnswer;
use App\Models\DetailAuditAnswer;
use App\Models\DetailSignatureAuditAnswer;
use App\Models\Karyawan;
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

    public function approve(Request $request, $id)
    {
        $auditAnswer = AuditAnswer::find($id);
        if (!$auditAnswer) {
            abort(404);
        }

        // Update status
        $auditAnswer->status = 'approved';
        $auditAnswer->save();

        // Get audit data
        $data = DetailAuditAnswer::with([
            'variabel.temaForm.form',
            'variabel.standarFotos',
            'detailAuditeeAnswer.userAuditee',
            'detailFotoAuditAnswer'
        ])->where('audit_answer_id', $id)->get();

        if ($data->isEmpty()) {
            abort(404);
        }

        $formattedData = $this->formatAuditData($data);
        $signatures = DetailSignatureAuditAnswer::where('audit_answer_id', $id)->first();

        // Get PIC name
        $picId = $auditAnswer->pic_area;
        $empId = PicArea::where('id', $picId)->first()->pic_id;
        $karyawan = Karyawan::where('emp_id', $empId)->first();
        $picName = $karyawan ? $karyawan->emp_name : null;
        $auditAnswer->pic_name = $picName;

        $grade = $this->getGrade($id);
        $chargeFees = app(AuditOfficeAdminController::class)->calculateAuditChargeFees($auditAnswerId = $id);

        return view('approve.index', compact(
            'formattedData',
            'auditAnswer',
            'grade',
            'id',
            'chargeFees',
            'signatures'
        ));
    }

    public function deleteAuditForm($id)
    {
        $auditAnswer = AuditAnswer::find($id);
        $auditAnswer->delete();
        return redirect()->route('form-audit')->with('form_audit_success', 'Form audit berhasil dihapus');
    }

    private function formatAuditData($data)
    {
        return $data->map(function ($detail) {
            return [
                'id' => $detail->id,
                'audit_answer_id' => $detail->audit_answer_id,
                'variabel_form_id' => $detail->variabel_form_id,
                'variabel' => $detail->variabel->variabel,
                'standar_variabel' => $detail->variabel->standar_variabel,
                'list_standar_foto' => $detail->variabel->standarFotos->map(function ($foto) {
                    return [
                        'id' => $foto->id,
                        'image_path' => $foto->image_path
                    ];
                }),
                'tema' => $detail->variabel->temaForm->tema,
                'kategori' => $detail->variabel->temaForm->form->kategori,
                'score' => $detail->score,
                'auditees' => $detail->detailAuditeeAnswer->map(function ($auditee) {
                    return [
                        'id' => $auditee->id,
                        'auditee' => $auditee->userAuditee ? $auditee->userAuditee->emp_name : $auditee->auditee_name,
                        'temuan' => $auditee->temuan
                    ];
                }),
                'images' => $detail->detailFotoAuditAnswer->map(function ($foto) {
                    return [
                        'id' => $foto->id,
                        'image_path' => $foto->image_path
                    ];
                }),
            ];
        });
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
