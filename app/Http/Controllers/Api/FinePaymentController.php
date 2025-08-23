<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PaymentApprovalMail;
use App\Mail\PaymentEvidenceMail;
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
        Log::info("Fetching fines for empId: $empId");
        $karyawan = Karyawan::where('emp_id', $empId)->first();
        if (!$karyawan) {
            Log::error("Karyawan not found for empId: $empId");
            return response()->json(['message' => 'Karyawan tidak ditemukan'], 404);
        }
        $fines = EmployeeFine::where('emp_id', $empId)
            ->with(['auditAnswer', 'detailAuditAnswer'])
            ->get();
        $totalFines = EmployeeFine::getTotalFines($empId);
        $totalPayments = EmployeeFine::getTotalPayments($empId);
        $totalDue = EmployeeFine::getTotalDue($empId);

        return response()->json([
            'karyawan' => $karyawan,
            'fines' => $fines,
            'totalFines' => $totalFines,  // Ganti key ke camelCase untuk match model Android
            'totalPayments' => $totalPayments,
            'totalDue' => $totalDue
        ]);
    }

    public function paymentSubmit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'name' => 'required|string|max:255',
        ]);

        $karyawan = Karyawan::where('emp_name', 'like', "%$request->name%")->firstOrFail();
        $empId = $karyawan->emp_id;
        $totalDue = EmployeeFine::getTotalDue($empId);
        if ($request->amount > $totalDue) {
            return response()->json(['message' => 'Jumlah pembayaran tidak boleh melebihi total denda'], 422);
        }
        if ($request->amount <= 0) {
            return response()->json(['message' => 'Jumlah pembayaran harus lebih dari 0'], 422);
        }
        $payment = EmployeeFine::create([
            'emp_id' => $empId,
            'type' => 'payment',
            'amount' => $request->amount,
            'description' => "Pembayaran cicil denda via cash",
            'evidence_path' => null, // Tidak ada bukti untuk pembayaran manual
            'payment_method' => 'cash',
            'paid_at' => Carbon::now(),
            'status' => 'paid', // Status langsung paid untuk pembayaran manual
        ]);
        // Kirim email bukti pembayaran ke user
        $this->sendPaymentEmail($payment);
        return response()->json([
            'message' => 'Pembayaran berhasil.',
        ]);
    }

    protected function sendPaymentEmail($payment)
    {
        $karyawan = Karyawan::where('emp_id', $payment->emp_id)->first();
        if (!$karyawan) {
            return response()->json(['message' => 'Karyawan tidak ditemukan untuk email pembayaran'], 404);
        }

        $totalDue = EmployeeFine::getTotalDue($payment->emp_id);
        $karyawanEmail = $karyawan->email;
        $karyawanName = $karyawan->emp_name;

        Mail::to($karyawanEmail)->send(new PaymentEvidenceMail(
            $payment,
            $karyawanName,
            $totalDue
        ));
    }

    // public function submitPayment(Request $request, $empId)
    // {
    //     Log::info("Submitting payment for empId: $empId, amount: {$request->amount}");
    //     $request->validate([
    //         'amount' => 'required|numeric|min:1',
    //         'evidence' => 'required|image|mimes:jpg,png,jpeg|max:2048',
    //     ]);

    //     $totalDue = EmployeeFine::getTotalDue($empId);
    //     if ($request->amount > $totalDue) {
    //         Log::warning("Amount exceeds total due for empId: $empId");
    //         return response()->json(['message' => 'Jumlah pembayaran tidak boleh melebihi total denda'], 422);
    //     }
    //     if ($request->amount <= 0) {
    //         Log::warning("Invalid amount 0 for empId: $empId");
    //         return response()->json(['message' => 'Jumlah pembayaran harus lebih dari 0'], 422);
    //     }

    //     $evidencePath = null;
    //     if ($request->hasFile('evidence')) {
    //         $fileName = time() . '_evidence.' . $request->file('evidence')->extension();
    //         $evidencePath = $request->file('evidence')->storeAs('evidences', $fileName, 'public');
    //     }

    //     try {
    //         $client = new Client();
    //         $response = $client->post('https://api.ocr.space/parse/image', [
    //             'multipart' => [
    //                 ['name' => 'file', 'contents' => fopen(storage_path('app/public/' . $evidencePath), 'r'), 'filename' => $fileName],
    //                 ['name' => 'apikey', 'contents' => env('OCR_SPACE_API_KEY')],
    //                 ['name' => 'language', 'contents' => 'eng'],
    //                 ['name' => 'OCREngine', 'contents' => '2'],
    //             ],
    //         ]);

    //         $result = json_decode($response->getBody(), true);
    //         $extractedText = $result['ParsedResults'][0]['ParsedText'] ?? '';
    //         Log::info('OCR Result for empId $empId: ' . $extractedText);

    //         preg_match('/(?:Jumlah|Total|Amount)[\s:]*Rp\.?\s*(\d{1,3}(?:\.\d{3})*)/i', $extractedText, $matches);
    //         $extractedAmountRaw = !empty($matches[1]) ? $matches[1] : '';
    //         $extractedAmount = (float) str_replace('.', '', $extractedAmountRaw);
    //         $inputAmount = (float) $request->amount;

    //         $payment = EmployeeFine::create([
    //             'emp_id' => $empId,
    //             'type' => 'payment',
    //             'amount' => $inputAmount,
    //             'description' => "Pembayaran cicil denda via cash",
    //             'evidence_path' => $evidencePath,
    //             'payment_method' => 'cash',
    //             'paid_at' => Carbon::now(),
    //             'status' => 'pending',
    //         ]);

    //         if ($extractedAmount === 0 || abs($extractedAmount - $inputAmount) > 0.1) {
    //             $this->sendApprovalEmail($payment);
    //             return response()->json([
    //                 'message' => 'Pembayaran menunggu verifikasi',
    //                 'status' => 'pending',
    //             ], 200);
    //         }

    //         $payment->status = 'paid';
    //         $payment->save();
    //         return response()->json([
    //             'message' => 'Pembayaran berhasil',
    //             'status' => 'paid',
    //             'payment' => $payment,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('OCR Error for empId $empId: ' . $e->getMessage());
    //         if ($evidencePath) {
    //             Storage::delete('public/' . $evidencePath);
    //         }
    //         return response()->json(['message' => 'Terjadi kesalahan saat memproses bukti pembayaran: ' . $e->getMessage()], 500);
    //     }
    // }

    // protected function sendApprovalEmail($payment)
    // {
    //     $bendahara = Karyawan::where('emp_id', 2011060104)->first();
    //     if (!$bendahara) {
    //         Log::error('Bendahara not found');
    //         return;
    //     }

    //     $bendaharaEmail = $bendahara->email;
    //     $bendaharaName = $bendahara->emp_name;

    //     Mail::to($bendaharaEmail)->send(new PaymentApprovalMail($payment, $bendaharaName));
    //     Log::info('Approval email sent to ' . $bendaharaEmail);
    // }

    public function approvePayment($id)
    {
        Log::info("Approving payment id: $id");
        $payment = EmployeeFine::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment tidak ditemukan'], 404);
        }
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'paid']);
            return response()->json(['message' => 'Pembayaran berhasil disetujui', 'status' => 'paid'], 200);
        }

        return response()->json(['message' => 'Pembayaran sudah disetujui sebelumnya', 'status' => $payment->status], 400);
    }
}
