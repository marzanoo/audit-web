<?php

namespace App\Http\Controllers;

use App\Models\AuditAnswer;
use App\Models\DetailAuditAnswer;
use App\Models\DetailAuditeeAnswer;
use App\Models\DetailFotoAuditAnswer;
use App\Models\DetailFotoStandarVariabel;
use App\Models\DetailSignatureAuditAnswer;
use App\Models\EmployeeFine;
use App\Models\Karyawan;
use App\Models\PicArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetailAuditAnswerController extends Controller
{
    public function showFormAuditDetail($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('login_error', 'Silakan login terlebih dahulu');
        }

        $auditAnswerId = $id;
        $detailAuditAnswer = DetailAuditAnswer::with([
            'variabel.temaForm.form'
        ])->where('audit_answer_id', $auditAnswerId)->get()
            ->map(function ($detail) {
                // Ambil daftar foto standar variabel dari DetailFotoStandarVariabel
                $standarFotoList = DetailFotoStandarVariabel::where('variabel_form_id', $detail->variabel_form_id)
                    ->get()
                    ->map(function ($foto) {
                        return [
                            'id' => $foto->id,
                            'variable_form_id' => $foto->variabel_form_id,
                            'image_path' => $foto->image_path,
                            'photo_url' => $foto->image_path ? asset('storage/' . $foto->image_path) : null
                        ];
                    });

                return [
                    'id' => $detail->id,
                    'audit_answer_id' => $detail->audit_answer_id,
                    'variabel_form_id' => $detail->variabel_form_id,
                    'variabel' => $detail->variabel->variabel,
                    'standar_variabel' => $detail->variabel->standar_variabel,
                    'standar_foto' => $detail->variabel->standar_foto, // Biarkan untuk kompatibilitas, jika digunakan
                    'standar_foto_list' => $standarFotoList, // Tambahkan daftar foto
                    'tema' => $detail->variabel->temaForm->tema,
                    'kategori' => $detail->variabel->temaForm->form->kategori,
                    'score' => $detail->score,
                ];
            });

        return view('auditor.form-audit.detail.index', compact('detailAuditAnswer', 'auditAnswerId'), ['showBottomNav' => false]);
    }

    public function submitAnswer(Request $request)
    {
        DB::beginTransaction();
        try {
            $auditAnswerId = $request->input('audit_answer_id');

            $totalScore = 0;
            foreach ($request->input('score', []) as $detailAuditAnswerId => $score) {
                $detail = DetailAuditAnswer::findOrFail($detailAuditAnswerId);
                $detail->score = $score;
                $detail->save();

                $totalScore += $score;

                $this->processTertuduh(
                    $detailAuditAnswerId,
                    $request->input('tertuduh_' . $detailAuditAnswerId, []),
                    $request->input('temuan_' . $detailAuditAnswerId, [])
                );

                // Pass the entire request to processImageUploads
                $this->processImageUploads($detailAuditAnswerId, $request);
            }

            $auditAnswer = AuditAnswer::findOrFail($auditAnswerId);
            $auditAnswer->total_score = $totalScore;
            $auditAnswer->save();

            $this->processSignatures($auditAnswerId, $request);
            $this->saveFines($auditAnswerId); // Simpan denda setelah audit di submit

            $auditAnswer->sendEmailApproval();

            DB::commit();
            Log::info('Audit Answer Submitted Successfully', [
                'user_id' => Auth::id(),
                'audit_answer_id' => $auditAnswerId,
                'total_score' => $totalScore
            ]);

            return redirect()->route('dashboard')->with('audit_answer_success', 'Audit berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('detail-audit-answer', $auditAnswerId)->with(['audit_answer_error' => 'Gagal menyimpan audit: ' . $e->getMessage()]);
        }
    }

    private function processTertuduh($detailAuditAnswerId, $tertuduhList, $temuanList)
    {
        // Hapus data tertuduh sebelumnya
        DetailAuditeeAnswer::where('detail_audit_answer_id', $detailAuditAnswerId)->delete();

        // Proses setiap tertuduh
        foreach ($tertuduhList as $index => $tertuduh) {
            if (empty($tertuduh)) continue;

            $temuan = $temuanList[$index] ?? null;

            // Cari karyawan berdasarkan nama
            $employee = Karyawan::where('emp_name', 'like', '%' . $tertuduh . '%')->first();

            DetailAuditeeAnswer::create([
                'detail_audit_answer_id' => $detailAuditAnswerId,
                'auditee' => $employee ? $employee->emp_id : null,
                'auditee_name' => $employee ? null : $tertuduh,
                'temuan' => $temuan
            ]);
        }
    }

    private function processImageUploads($detailAuditAnswerId, Request $request)
    {
        $inputName = 'image_path_' . $detailAuditAnswerId;

        if (!$request->hasFile($inputName)) {
            return;
        }

        $imageFiles = $request->file($inputName);

        foreach ($imageFiles as $index => $imageFile) {
            if ($imageFile->isValid()) {
                // Create a truly unique filename using microtime and a random component
                $uniqueId = microtime(true) . '_' . uniqid();
                $fileName = $uniqueId . '_' . $imageFile->getClientOriginalName();
                $filePath = $imageFile->storeAs('uploads', $fileName, 'public');

                DetailFotoAuditAnswer::create([
                    'detail_audit_answer_id' => $detailAuditAnswerId,
                    'image_path' => $filePath
                ]);
            }
        }
    }

    private function processSignatures($auditAnswerId, $request)
    {
        $signatures = [
            'auditor_signature' => $request->file('auditor_signature'),
            'auditee_signature' => $request->file('auditee_signature'),
            'facilitator_signature' => $request->file('facilitator_signature')
        ];

        $signaturePaths = [];

        foreach ($signatures as $type => $signatureFile) {
            if ($signatureFile) {
                $fileName = time() . '_' . $type . '_signature.' . $signatureFile->getClientOriginalExtension();
                $filePath = $signatureFile->storeAs('signatures', $fileName, 'public');
                $signaturePaths[$type] = $filePath;
            }
        }

        // Simpan signature jika ada
        if (!empty($signaturePaths)) {
            DetailSignatureAuditAnswer::create([
                'audit_answer_id' => $auditAnswerId,
                'auditor_signature' => $signaturePaths['auditor_signature'] ?? null,
                'auditee_signature' => $signaturePaths['auditee_signature'] ?? null,
                'facilitator_signature' => $signaturePaths['facilitator_signature'] ?? null
            ]);
        }
    }

    private function getGrade($id)
    {
        $auditAnswer = AuditAnswer::where('id', $id)->first();
        if ($auditAnswer->total_score <= 2) {
            return "Diamond";
        } elseif ($auditAnswer->total_score <= 4) {
            return "Platinum";
        } elseif ($auditAnswer->total_score <= 6) {
            return "Gold";
        } elseif ($auditAnswer->total_score <= 8) {
            return "Silver";
        } elseif ($auditAnswer->total_score >= 9) {
            return "Bronze";
        }
        return "Unknown";
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
        $managerDetails = [];
        $gmFees = [];
        $gmDetails = [];

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

                if (empty($tertuduhName) || empty($entry->temuan)) {
                    continue;
                }

                $findingCount = 1;
                if (preg_match('/(\d+(?:\.\d+)?)/', $entry->temuan, $matches)) {
                    $findingCount = (float) $matches[1];
                }

                $totalFindings += $findingCount;

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

        $picAreaFee = $totalFindings * $feeRate * 0.5;

        $gmDeptMap = [
            'TSD' => 'ASD',
            'PMD' => 'ASD',
        ];

        $gmFindingCount = [];

        foreach ($deptFindings as $dept => $findingCount) {
            if ($dept === 'MKT') {
                for ($i = 1; $i <= 5; $i++) {
                    $mktAuditeeRemark = "AUDITEE MKT $i";
                    $mktManagerRemark = "MGR MKT $i";

                    $mktEmployees = Karyawan::where('remarks', 'LIKE', "%$mktAuditeeRemark%")->pluck('emp_id')->toArray();
                    $mktFindingCount = 0;

                    foreach ($detailAuditAnswer as $detail) {
                        $tertuduhEntries = DetailAuditeeAnswer::where('detail_audit_answer_id', $detail->id)
                            ->whereIn('auditee', $mktEmployees)
                            ->get();

                        foreach ($tertuduhEntries as $entry) {
                            if (empty($entry->temuan)) {
                                continue;
                            }

                            $findingCount = 1;
                            if (preg_match('/(\d+(?:\.\d+)?)/', $entry->temuan, $matches)) {
                                $findingCount = (float) $matches[1];
                            }
                            $mktFindingCount += $findingCount;
                        }
                    }

                    if ($mktFindingCount > 0) {
                        $manager = Karyawan::where('remarks', 'LIKE', "%$mktManagerRemark%")->first();
                        if ($manager) {
                            $managerFee = $mktFindingCount * 1000;
                            $managerFees[$manager->emp_name] = $managerFee;
                            $managerDetails[$manager->emp_name] = [
                                'dept' => "MKT $i",
                                'findings' => $mktFindingCount,
                                'fee' => $managerFee
                            ];
                        }
                    }
                }
            } else {
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
            }

            $targetDept = $gmDeptMap[$dept] ?? $dept;
            if (!isset($gmFindingCount[$targetDept])) {
                $gmFindingCount[$targetDept] = 0;
            }
            $gmFindingCount[$targetDept] += $findingCount;
        }

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

    private function saveFines($auditAnswerId)
    {
        $chargeFees = $this->calculateAuditChargeFees($auditAnswerId);

        $detailAuditAnswer = DetailAuditAnswer::where('audit_answer_id', $auditAnswerId)->get();
        foreach ($detailAuditAnswer as $detail) {
            $tertuduhEntries = DetailAuditeeAnswer::where('detail_audit_answer_id', $detail->id)->get();
            foreach ($tertuduhEntries as $entry) {
                if (empty($entry->temuan)) continue;

                $karyawan = $entry->auditee ? Karyawan::find($entry->auditee) : null;
                $tertuduhName = $karyawan ? $karyawan->emp_name : $entry->auditee_name;
                $empId = $entry->auditee;

                $findingCount = 1;
                if (preg_match('/(\d+(?:\.\d+)?)/', $entry->temuan, $matches)) {
                    $findingCount = (float) $matches[1];
                }

                $fee = $findingCount * $chargeFees['feeRate'];

                EmployeeFine::create([
                    'emp_id' => $empId,
                    'audit_answer_id' => $auditAnswerId,
                    'detail_audit_answer_id' => $detail->id,
                    'type' => 'fine',
                    'amount' => $fee,
                    'description' => "Denda tertuduh dari poin audit {$detail->id}, temuan {$entry->temuan}, grade {$chargeFees['grade']}"
                ]);
            }
        }

        $auditAnswer = AuditAnswer::find($auditAnswerId);
        $picId = $auditAnswer->pic_area;
        $empId = PicArea::where('id', $picId)->first()->pic_id ?? null;
        if ($empId && $chargeFees['picAreaFee'] > 0) {
            EmployeeFine::create([
                'emp_id' => $empId,
                'audit_answer_id' => $auditAnswerId,
                'type' => 'fine',
                'amount' => $chargeFees['picAreaFee'],
                'description' => "Denda PIC Area dari audit {$auditAnswerId}, total findings {$chargeFees['totalFindings']}"
            ]);
        }

        foreach ($chargeFees['managerDetails'] as $name => $details) {
            $employee = Karyawan::where('emp_name', $name)->first();
            if ($employee && $details['fee'] > 0) {
                EmployeeFine::create([
                    'emp_id' => $employee->emp_id,
                    'audit_answer_id' => $auditAnswerId,
                    'type' => 'fine',
                    'amount' => $details['fee'],
                    'description' => "Denda Manager dept {$details['dept']} dari audit {$auditAnswerId}, findings {$details['findings']}"
                ]);
            }
        }

        foreach ($chargeFees['gmDetails'] as $name => $details) {
            $employee = Karyawan::where('emp_name', $name)->first();
            if ($employee && $details['fee'] > 0) {
                EmployeeFine::create([
                    'emp_id' => $employee->emp_id,
                    'audit_answer_id' => $auditAnswerId,
                    'type' => 'fine',
                    'amount' => $details['fee'],
                    'description' => "Denda GM dept {$details['dept']} dari audit {$auditAnswerId}, findings {$details['findings']}"
                ]);
            }
        }
    }
}
