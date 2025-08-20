<?php

namespace App\Http\Controllers;

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
                    [
                        'name' => 'OCREngine',
                        'contents' => '2',
                    ],
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            $extractedText = $result['ParsedResults'][0]['ParsedText'] ?? '';
            Log::info('OCR Extracted Text: ' . $extractedText);

            // Cari pola seperti "Jumlah: 1.000.000", "Total: Rp 1.000.000", atau "Amount: Rp. 1000000"
            preg_match('/(?:Jumlah|Total|Amount)[\s:]*Rp\.?\s*(\d{1,3}(?:\.\d{3})*)/i', $extractedText, $matches);
            $extractedAmountRaw = !empty($matches[1]) ? $matches[1] : '';
            $extractedAmount = (float) str_replace('.', '', $extractedAmountRaw);
            $inputAmount = (float) $request->amount;

            // Buat payment terlebih dahulu (akan diupdate statusnya nanti)
            $payment = EmployeeFine::create([
                'emp_id' => $empId,
                'type' => 'payment',
                'amount' => $inputAmount,
                'description' => "Pembayaran cicil denda via cash",
                'evidence_path' => $evidencePath,
                'payment_method' => 'cash',
                'paid_at' => now(),
                'status' => 'pending', // Default ke pending
            ]);

            if ($extractedAmount === 0) {
                // OCR gagal menemukan amount, kirim email untuk pengecekan manual
                $this->sendApprovalEmail($payment);
                return redirect()->back()->with('payment_pending', 'Pembayaran menunggu verifikasi karena OCR gagal membaca amount. Email telah dikirim ke bendahara.');
            }

            if (abs($extractedAmount - $inputAmount) > 0.1) {
                // Amount tidak cocok, kirim email untuk pengecekan manual
                $this->sendApprovalEmail($payment);
                return redirect()->back()->with('payment_pending', 'Pembayaran menunggu verifikasi karena amount tidak cocok. Email telah dikirim ke bendahara.');
            }

            // Jika OCR berhasil, update status ke approved dan kurangi denda
            $payment->update(['status' => 'paid']);
            return redirect()->back()->with('payment_success', 'Pembayaran berhasil.');
        } catch (\Exception $e) {
            Log::error('OCR Error: ' . $e->getMessage());
            Storage::delete('public/' . $evidencePath);
            return redirect()->back()->with('payment_error', 'Terjadi kesalahan saat memproses bukti pembayaran: ' . $e->getMessage());
        }
    }

    protected function sendApprovalEmail($payment)
    {
        $bendahara = Karyawan::where('emp_id', 2011060104)->first(); // Ganti dengan emp_id bendahara yang sesuai
        if (!$bendahara) {
            Log::error('Bendahara not found for approval email');
            return;
        }

        $bendaharaEmail = $bendahara->email;
        $bendaharaName = $bendahara->emp_name;

        Mail::to($bendaharaEmail)->send(new PaymentApprovalMail(
            $payment,
            $bendaharaName
        ));

        Log::info('Approval email sent to ' . $bendaharaEmail);
    }

    public function approvePayment($paymentId)
    {
        $payment = EmployeeFine::findOrFail($paymentId);
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'paid']);
            return view('payments.success')->with('payment_success', 'Pembayaran berhasil disetujui.');
        }
        return redirect()->back()->with('payment_error', 'Pembayaran sudah disetujui sebelumnya.');
    }
}
