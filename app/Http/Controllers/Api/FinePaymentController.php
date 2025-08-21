<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PaymentApprovalMail;
use App\Models\EmployeeFine;
use App\Models\Karyawan;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        return response()->json([
            'karyawan' => $karyawan,
            'fines' => $fines,
            'total_fines' => $totalFines,
            'total_payments' => $totalPayments,
            'total_due' => $totalDue
        ]);
    }

    public function submitPayment(Request $request, $empId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'evidence' => 'required|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $totalDue = EmployeeFine::getTotalDue($empId);
        if ($request->amount > $totalDue) {
            return response()->json(['message' => 'Jumlah pembayaran tidak boleh melebihi total denda'], 422);
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $fileName = time() . '_evidence.' . $request->file('evidence')->extension();
            $evidencePath = $request->file('evidence')->storeAs('evidences', $fileName, 'public');
        }

        try {
            $client = new Client();
            $response = $client->post('https://api.ocr.space/parse/image', [
                'multipart' => [
                    ['name' => 'file', 'contents' => fopen(storage_path('app/public/' . $evidencePath), 'r'), 'filename' => $fileName],
                    ['name' => 'apikey', 'contents' => env('OCR_SPACE_API_KEY')],
                    ['name' => 'language', 'contents' => 'eng'],
                    ['name' => 'OCREngine', 'contents' => '2'],
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            $extractedText = $result['ParsedResults'][0]['ParsedText'] ?? '';
            Log::info('OCR Result: ' . $extractedText);

            preg_match('/(?:Jumlah|Total|Amount)[\s:]*Rp\.?\s*(\d{1,3}(?:\.\d{3})*)/i', $extractedText, $matches);
            $extractedAmountRaw = !empty($matches[1]) ? $matches[1] : '';
            $extractedAmount = (float) str_replace('.', '', $extractedAmountRaw);
            $inputAmount = (float) $request->amount;

            $payment = EmployeeFine::create([
                'emp_id' => $empId,
                'type' => 'payment',
                'amount' => $inputAmount,
                'description' => "Pembayaran cicil denda via cash",
                'evidence_path' => $evidencePath,
                'payment_method' => 'cash',
                'paid_at' => Carbon::now(),
                'status' => 'pending',
            ]);

            if ($extractedAmount === 0 || abs($extractedAmount - $inputAmount) > 0.1) {
                $this->sendApprovalEmail($payment);
                return response()->json([
                    'message' => 'Pembayaran menunggu verifikasi',
                    'status' => 'pending',
                ], 200);
            }

            $payment->status = 'paid';
            return response()->json([
                'message' => 'Pembayaran berhasil',
                'status' => 'paid',
                'payment' => $payment,
            ], 200);
        } catch (\Exception $e) {
            Log::error('OCR Error: ' . $e->getMessage());
            if ($evidencePath) {
                Storage::delete('public/' . $evidencePath);
            }
            return response()->json(['message' => 'Terjadi kesalahan saat memproses bukti pembayaran: ' . $e->getMessage()], 500);
        }
    }

    protected function sendApprovalEmail($payment)
    {
        $bendahara = Karyawan::where('emp_id', 2011060104)->first();
        if (!$bendahara) {
            Log::error('Bendahara not found');
            return;
        }

        $bendaharaEmail = $bendahara->email;
        $bendaharaName = $bendahara->emp_name;

        Mail::to($bendaharaEmail)->send(new PaymentApprovalMail($payment, $bendaharaName));
        Log::info('Approval email sent to ' . $bendaharaEmail);
    }

    public function approvePayment($id)
    {
        $payment = EmployeeFine::find($id);
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'paid']);
            return response()->json(['message' => 'Pembayaran berhasil disetujui', 'status' => 'paid'], 200);
        }

        return response()->json(['message' => 'Pembayaran sudah disetujui sebelumnya', 'status' => $payment->status], 400);
    }
}
