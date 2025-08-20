<?php

namespace App\Mail;

use App\Models\EmployeeFine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;
    public $bendaharaName;
    /**
     * Create a new message instance.
     */
    public function __construct(EmployeeFine $payment, $bendaharaName)
    {
        $this->payment = $payment;
        $this->bendaharaName = $bendaharaName;
    }

    /**
     * Get the message envelope.
     */

    public function build()
    {
        return $this->subject('Persetujuan Pembayaran Denda')
            ->view('emails.payment_approval')
            ->attach(storage_path('app/public/' . $this->payment->evidence_path));
    }
}
