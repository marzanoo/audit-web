<?php

namespace App\Http\Controllers\Api;

use App\Exports\AuditAnswerExport;
use App\Http\Controllers\Controller;
use App\Models\AuditAnswer;
use App\Models\DetailAuditAnswer;
use App\Models\DetailAuditeeAnswer;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AuditOfficeAdminController extends Controller
{
    public function getDetailAuditAnswerForExport($id)
    {
        $auditAnswerId = $id;
        $data = DetailAuditAnswer::with([
            'variabel.temaForm.form',
            'detailAuditeeAnswer.userAuditee',
            'detailFotoAuditAnswer'
        ])->where('audit_answer_id', $auditAnswerId)->get();

        if ($data->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $formattedData = $this->formatAuditData($data);
        $auditAnswer = AuditAnswer::with('area:id,area,lantai_id', 'auditor:id,name')
            ->where('id', $auditAnswerId)->first();
        $grade = $this->getGrade($auditAnswerId);

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'auditAnswer' => $auditAnswer,
            'grade' => $grade
        ]);
    }

    public function downloadAuditExcel($id)
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

        // Simpan file Excel ke storage

        Excel::store(new AuditAnswerExport($formattedData, $auditAnswer, $grade, $signatures, $chargeFees, $picName), $fileName, 'public');
        // Kembalikan URL untuk download
        $url = url(Storage::url($fileName));

        return response()->json([
            'success' => true,
            'message' => 'File Excel berhasil dibuat',
            'fileName' => $fileName,
            'fileUrl' => $url
        ]);
    }

    private function calculateAuditChargeFees($auditAnswerId)
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
                'tema' => $detail->variabel->temaForm->tema,
                'kategori' => $detail->variabel->temaForm->form->kategori,
                'score' => $detail->score,
                'auditees' => $detail->detailAuditeeAnswer->map(function ($auditee) {
                    return [
                        'id' => $auditee->id,
                        'name' => $auditee->userAuditee ? $auditee->userAuditee->emp_name : $auditee->auditee_name,
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
