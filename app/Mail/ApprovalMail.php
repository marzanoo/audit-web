<?php

namespace App\Mail;

use App\Models\AuditAnswer;
use App\Models\Karyawan;
use App\Models\PicArea;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $auditAnswer;

    /**
     * Create a new message instance.
     */
    public function __construct(AuditAnswer $auditAnswer)
    {
        $this->auditAnswer = $auditAnswer;
    }

    /*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Build the message.
     *
     * @return $this
     */

    /*******  2efce167-0996-4c34-99d6-01d3105530ce  *******/
    public function build()
    {
        $pic = $this->auditAnswer->pic_area;
        $empId = PicArea::where('id', $pic)->first()->pic_id;
        $karyawan = Karyawan::where('emp_id', $empId)->first();
        $dept = $karyawan->dept;

        if ($dept == 'MKT') {
            // Get the AUDITEE number from remarks
            preg_match('/AUDITEE MKT (\d)/', $karyawan->remarks, $matches);
            if (!empty($matches)) {
                $auditeeNumber = $matches[1];

                // Find corresponding manager with same number
                $manager = Karyawan::where('dept', 'MKT')
                    ->where('emp_name', '!=', 'VACANT')
                    ->where('remarks', 'LIKE', "%MGR MKT {$auditeeNumber}%")
                    ->first();

                $managerName = $manager ? $manager->emp_name : 'Manager';
            } else {
                Log::error('Invalid AUDITEE MKT remarks format', [
                    'remarks' => $karyawan->remarks
                ]);
                $managerName = 'Manager';
            }
        } else {
            // Standard handling for other departments
            $manager = Karyawan::where('dept', $dept)
                ->where('remarks', 'LIKE', '%MGR ' . $dept . '%')
                ->where('emp_name', '!=', 'VACANT')
                ->first();

            $managerName = $manager ? $manager->emp_name : 'Manager';
        }

        $approveUrl = route('audit.approve', ['id' => $this->auditAnswer->id]);

        return $this->subject('Audit Approval Request')
            ->view('emails.approval')
            ->with([
                'auditAnswer' => $this->auditAnswer,
                'managerName' => $managerName,
                'approveUrl' => $approveUrl,
            ]);
    }

    /**
     * Get the message envelope.
     */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Approval Mail',
    //     );
    // }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
