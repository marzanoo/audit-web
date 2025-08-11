<?php

namespace App\Http\Controllers;

use App\Exports\AuditAnswerExport;
use App\Models\Area;
use App\Models\AuditAnswer;
use App\Models\DetailAuditAnswer;
use App\Models\DetailAuditeeAnswer;
use App\Models\DetailSignatureAuditAnswer;
use App\Models\Karyawan;
use App\Models\Lantai;
use App\Models\PicArea;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AuditOfficeAdminController extends Controller
{
    public function showLantai()
    {
        $lantai = Lantai::all();
        return view('admin.audit-office.index', compact('lantai'));
    }

    public function showAreaByLantai($id)
    {
        $lantaiId = $id;
        $area = Area::with('lantai:id,lantai')->where('lantai_id', $lantaiId)->get();
        return view('admin.audit-office.area', compact('area'));
    }

    public function showArea()
    {
        $area = Area::with('lantai:id,lantai')->where('lantai_id', 4)->get();
        return view('admin.audit-office.area', compact('area'));
    }

    public function showAuditForm($id)
    {
        $areaId = $id;
        $lantaiId = Area::find($areaId)->lantai_id;
        $audit_form = AuditAnswer::with('area:id,area,lantai_id', 'auditor:id,name')->where('area_id', $areaId)->orderBy('created_at', 'desc')->get();
        return view('admin.audit-office.audit-form', compact('audit_form', 'lantaiId'));
    }

    public function auditApprove($id)
    {
        $auditAnswerId = $id;
        $auditAnswer = AuditAnswer::find($auditAnswerId);
        if (!$auditAnswer) {
            return redirect()->back()->with('audit_office_error', 'Form audit tidak ditemukan');
        }

        if ($auditAnswer->status === 'approved') {
            return redirect()->back()->with('audit_office_error', 'Form audit sudah disetujui');
        }

        $auditAnswer->status = 'approved';
        $auditAnswer->save();
        return redirect()->back()->with('audit_office_success', 'Form audit berhasil disetujui');
    }

    public function showAuditAnswer($id)
    {
        $auditAnswerId = $id;
        $data = DetailAuditAnswer::with([
            'variabel.temaForm.form',
            'variabel.standarFotos',
            'detailAuditeeAnswer.userAuditee',
            'detailFotoAuditAnswer'
        ])->where('audit_answer_id', $auditAnswerId)->get();

        if ($data->isEmpty() || $data->contains(fn($detail) => $detail->audit_answer_id != $auditAnswerId)) {
            return redirect()->back()->with('audit_office_error', 'Data tidak ditemukan');
        }

        $formattedData = $data->map(function ($detail) {
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
                'standar_foto' => $detail->variabel->standar_foto,
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
        $signatures = DetailSignatureAuditAnswer::where('audit_answer_id', $auditAnswerId)->first();
        $auditAnswer = AuditAnswer::where('id', $auditAnswerId)->first();
        $picId = $auditAnswer->pic_area;
        $empId = PicArea::where('id', $picId)->first()->pic_id ?? null;
        $karyawan = Karyawan::where('emp_id', $empId)->first();
        $picName = $karyawan ? $karyawan->emp_name : null;
        $auditAnswer->pic_name = $picName;
        $grade = $this->getGrade($auditAnswerId);

        $chargeFees = $this->calculateAuditChargeFees($auditAnswerId);

        return view('admin.audit-office.detail.index', compact('formattedData', 'signatures', 'auditAnswer', 'grade', 'chargeFees'));
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

    public function calculateAuditChargeFees($auditAnswerId)
    {
        $detailAuditAnswer = DetailAuditAnswer::where('audit_answer_id', $auditAnswerId)->get();
        $grade = $this->getGrade($auditAnswerId);

        $chargeFeeRates = [
            'Diamond' => 0,
            'Platinum' => 2000,
            'Gold' => 4000,
            'Silver' => 10000,
            'Bronze' => 20000,
        ];

        $feeRate = $chargeFeeRates[$grade] ?? 0;
        $tertuduhFees = [];
        $tertuduhDetails = [];
        $picAreaFee = 0;
        $managerFees = [];
        $gmFees = [];

        //Track tertuduh dept dan temuan
        $deptFindings = [];
        $totalFindings = 0;

        foreach ($detailAuditAnswer as $detail) {
            $tertuduhEntries = DetailAuditeeAnswer::where('detail_audit_answer_id', $detail->id)->get();

            foreach ($tertuduhEntries as $entry) {
                $karyawan = null;
                if ($entry->auditee) {
                    $karyawan = Karyawan::find($entry->auditee);
                }

                $tertuduhName = $karyawan ? $karyawan->emp_name : $entry->auditee_name;
                $tertuduhId = $entry->auditee;

                //skip klo gaada tertuduh dan temuan
                if (empty($tertuduhName) || empty($entry->temuan)) {
                    continue;
                }

                // Parse temuan to get the actual count
                // Expected format: string may contain numbers like "2.00" or "1.00"
                $findingCount = 1; // Default value

                // Try to extract numeric value from temuan string
                if (preg_match('/(\d+(?:\.\d+)?)/', $entry->temuan, $matches)) {
                    $findingCount = (float) $matches[1];
                }

                $totalFindings += $findingCount;

                //calculate fee
                $fee = $findingCount * $feeRate;

                if (!isset($tertuduhFees[$tertuduhName])) {
                    $tertuduhFees[$tertuduhName] = 0;
                    $tertuduhDetails[$tertuduhName] = [
                        'findings' => 0,
                        'fee' => 0,
                        'dept' => null
                    ];
                }
                $tertuduhFees[$tertuduhName] += $fee;
                $tertuduhDetails[$tertuduhName]['findings'] += $findingCount;
                $tertuduhDetails[$tertuduhName]['fee'] += $fee;

                if ($tertuduhId) {
                    $employee = Karyawan::find($tertuduhId);
                    if ($employee && $employee->dept) {
                        $tertuduhDetails[$tertuduhName]['dept'] = $employee->dept;

                        if (!isset($deptFindings[$employee->dept])) {
                            $deptFindings[$employee->dept] = 0;
                        }

                        $deptFindings[$employee->dept] += $findingCount;
                    }
                }
            }
        }

        //hitung pic area fee
        $picAreaFee = $totalFindings * $feeRate * 0.5;

        $managerDetails = [];
        $gmDetails = [];

        $gmDeptMap = [
            'TSD' => 'ASD',
            'PMD' => 'ASD',
        ];

        // 1. Kumpulkan total temuan per GM Dept (ASD, MKT, dll)
        $gmFindingCount = [];

        foreach ($deptFindings as $dept => $findingCount) {
            // 1.1 Hitung untuk manager
            $manager = Karyawan::where('remarks', 'LIKE', "%MGR $dept%")->first();
            if ($manager) {
                $managerFee = $findingCount * 1000;
                $managerFees[$manager->emp_name] = $managerFee;
                $managerDetails[$manager->emp_name] = [
                    'dept' => $dept,
                    'findings' => $findingCount,
                    'fee' => $managerFee
                ];
            }

            // 1.2 Akumulasi temuan untuk GM berdasarkan mapped dept
            $targetDept = $gmDeptMap[$dept] ?? $dept;
            if (!isset($gmFindingCount[$targetDept])) {
                $gmFindingCount[$targetDept] = 0;
            }
            $gmFindingCount[$targetDept] += $findingCount;
        }

        // 2. Hitung GM Fee setelah akumulasi
        foreach ($gmFindingCount as $gmDept => $findingCount) {
            $gm = Karyawan::where('remarks', 'LIKE', "%GM $gmDept%")->first();
            if ($gm) {
                $gmFee = $findingCount * 2000;
                $gmFees[$gm->emp_name] = $gmFee;
                $gmDetails[$gm->emp_name] = [
                    'dept' => $gmDept,
                    'findings' => $findingCount,
                    'fee' => $gmFee
                ];
            }
        }

        return [
            'grade' => $grade,
            'feeRate' => $feeRate,
            'tertuduhFees' => $tertuduhFees,
            'tertuduhDetails' => $tertuduhDetails,
            'picAreaFee' => $picAreaFee,
            'managerFees' => $managerFees,
            'managerDetails' => $managerDetails,
            'gmFees' => $gmFees,
            'gmDetails' => $gmDetails,
            'totalFindings' => $totalFindings,
            'totalFee' => array_sum($tertuduhFees) + $picAreaFee + array_sum($managerFees) + array_sum($gmFees)
        ];
    }

    public function previewExcel($id)
    {
        $auditAnswerId = $id;
        $data = DetailAuditAnswer::with([
            'variabel.temaForm.form',
            'variabel.standarFotos',
            'detailAuditeeAnswer.userAuditee',
            'detailFotoAuditAnswer'
        ])->where('audit_answer_id', $auditAnswerId)->get();

        if ($data->isEmpty()) {
            return redirect()->back()->with('audit_office_error', 'Data tidak ditemukan');
        }

        $formattedData = $this->formatAuditData($data);
        $auditAnswer = AuditAnswer::where('id', $auditAnswerId)->first();

        $grade = $this->getGrade($auditAnswerId);
        $chargeFees = $this->calculateAuditChargeFees($auditAnswerId);
        return view('admin.audit-office.detail.preview-excel', compact('formattedData', 'auditAnswer', 'grade', 'id', 'chargeFees'));
    }

    public function downloadExcel($id)
    {
        $auditAnswerId = $id;
        $data = DetailAuditAnswer::with([
            'variabel.temaForm.form',
            'variabel.standarFotos',
            'detailAuditeeAnswer.userAuditee',
            'detailFotoAuditAnswer'
        ])->where('audit_answer_id', $auditAnswerId)->get();

        if ($data->isEmpty()) {
            return redirect()->back()->with('audit_office_error', 'Data tidak ditemukan');
        }

        $formattedData = $this->formatAuditData($data);
        $auditAnswer = AuditAnswer::where('id', $auditAnswerId)->first();
        $picId = $auditAnswer->pic_area;
        $empId = PicArea::where('id', $picId)->first()->pic_id;
        $karyawan = Karyawan::where('emp_id', $empId)->first();
        $picName = $karyawan ? $karyawan->emp_name : null;
        $grade = $this->getGrade($auditAnswerId);

        // Get signatures data
        $signatures = DetailSignatureAuditAnswer::where('audit_answer_id', $auditAnswerId)->first();

        $fileName = 'Audit_Report_' . $auditAnswer->area_id . '_' . date('Y-m-d') . '.xlsx';

        $chargeFees = $this->calculateAuditChargeFees($auditAnswerId);
        return Excel::download(new AuditAnswerExport($formattedData, $auditAnswer, $grade, $signatures, $chargeFees, $picName), $fileName);
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
                'standar_foto' => $detail->variabel->standar_foto,
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

    public function downloadPdf($id)
    {
        $auditAnswerId = $id;
        $data = DetailAuditAnswer::with([
            'variabel.temaForm.form',
            'detailAuditeeAnswer.userAuditee',
            'detailFotoAuditAnswer'
        ])->where('audit_answer_id', $auditAnswerId)->get();

        if ($data->isEmpty()) {
            return redirect()->back()->with('audit_office_error', 'Data tidak ditemukan');
        }

        $formattedData = $this->formatAuditData($data);
        $auditAnswer = AuditAnswer::where('id', $auditAnswerId)->first();
        $grade = $this->getGrade($auditAnswerId);

        // Install package PDF: composer require barryvdh/laravel-dompdf
        $pdf = Pdf::loadView('admin.audit-office.detail.pdf', compact('formattedData', 'auditAnswer', 'grade'));
        $fileName = 'Audit_Report_' . $auditAnswer->area_id . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }
}
