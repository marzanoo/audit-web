<?php

namespace App\Http\Controllers;

use App\Models\EmployeeFine;
use App\Models\Karyawan;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FinePaymentController extends Controller
{
    public function showFines($empId)
    {
        $karyawan = Karyawan::where('emp_id', $empId)->firstOrFail();
        $fines = EmployeeFine::where('emp_id', $empId)
            ->with(['auditAnswer', 'detailAuditAnswer'])
            ->get();
        $totalFines = EmployeeFine::getTotalFines($empId);
        $totalPayments = EmployeeFine::getTotalPayments($empId);
        $totalDue = EmployeeFine::getTotalDue($empId);

        return view('auditor.fines.index', compact('karyawan', 'fines', 'totalFines', 'totalPayments', 'totalDue'));
    }

    public function submitPayment(Request $request, $empId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'evidence' => 'required|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $totalDue = EmployeeFine::getTotalDue($empId);
        if ($request->amount > $totalDue) {
            return redirect()->back()->with(['payment_error' => 'Jumlah pembayaran tidak boleh melebihi total denda']);
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $fileName = time() . '_evidence.' . $request->file('evidence')->extension();
            $evidencePath = $request->file('evidence')->storeAs('evidences', $fileName, 'public');
        }

        try {
            // Panggil API OCR.Space dengan Guzzle
            $client = new Client();
            $response = $client->post('https://api.ocr.space/parse/image', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen(storage_path('app/public/' . $evidencePath), 'r'),
                        'filename' => $fileName,
                    ],
                    [
                        'name' => 'apikey',
                        'contents' => env('OCR_SPACE_API_KEY'),
                    ],
                    [
                        'name' => 'language',
                        'contents' => 'eng',
                    ],
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            $extractedText = $result['ParsedResults'][0]['ParsedText'] ?? '';

            // Log untuk debugging
            Log::info('OCR Extracted Text: ' . $extractedText);

            // Ekstrak amount
            $extractedAmounts = [];
            preg_match_all('/(?:Rp\s*)?(\d{1,3}(?:\.\d{3})*|\d+)/', $extractedText, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $cleanAmount = (float) str_replace('.', '', $match);
                    $extractedAmounts[] = $cleanAmount;
                }
            }
            $extractedAmount = !empty($extractedAmounts) ? max($extractedAmounts) : 0;
            $inputAmount = (float) $request->amount;

            if (abs($extractedAmount - $inputAmount) > 0.1) {
                Storage::delete('public/' . $evidencePath);
                return redirect()->back()->with('payment_error', 'Amount di evidence (' . $extractedAmount . ') tidak sesuai dengan input (' . $inputAmount . '). Silakan upload ulang.');
            }

            $payment = EmployeeFine::create([
                'emp_id' => $empId,
                'type' => 'payment',
                'amount' => $inputAmount,
                'description' => "Pembayaran cicil denda via cash",
                'evidence_path' => $evidencePath,
                'payment_method' => 'cash',
                'paid_at' => now(),
            ]);

            return redirect()->back()->with('payment_success', 'Pembayaran berhasil.');
        } catch (\Exception $e) {
            Log::error('OCR Error: ' . $e->getMessage());
            Storage::delete('public/' . $evidencePath);
            return redirect()->back()->with('payment_error', 'Terjadi kesalahan saat memproses bukti pembayaran: ' . $e->getMessage());
        }
    }
}
