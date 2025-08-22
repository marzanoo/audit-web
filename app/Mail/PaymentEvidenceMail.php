<?php

namespace App\Mail;

use App\Models\EmployeeFine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentEvidenceMail extends Mailable
{
    use Queueable, SerializesModels;
    public $payment;
    public $karyawanName;
    public $totalDue;
    /**
     * Create a new message instance.
     */
    public function __construct(EmployeeFine $payment, $karyawanName, $totalDue)
    {
        $this->payment = $payment;
        $this->karyawanName = $karyawanName;
        $this->totalDue = $totalDue;
    }

    public function build()
    {
        return $this->subject('Bukti Pembayaran Denda')
            ->view('emails.payment_evidence');
    }
}
