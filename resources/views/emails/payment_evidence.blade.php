<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran Denda</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f6f9fc; margin: 0; padding: 20px;">

    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        
        <!-- Header -->
        <tr>
            <td style="padding: 20px; text-align: center; border-bottom: 3px solid #e11d48;">
                <h1 style="margin: 0; font-size: 20px; color: #e11d48;">Bukti Pembayaran Denda</h1>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 20px; color: #333;">
                <p>Dear <strong>{{ $karyawanName }}</strong>,</p>
                <p>Berikut adalah bukti pembayaran denda Anda:</p>

                <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin: 15px 0; font-size: 14px;">
                    <tr style="background: #f9fafb;">
                        <td width="40%"><strong>Emp ID</strong></td>
                        <td>{{ $payment->emp_id }}</td>
                    </tr>
                    <tr>
                        <td><strong>Name</strong></td>
                        <td>{{ $payment->karyawan->emp_name }}</td>
                    </tr>
                    <tr style="background: #f9fafb;">
                        <td><strong>Amount</strong></td>
                        <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Sisa Denda</strong></td>
                        <td>Rp {{ number_format($totalDue, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="background: #f9fafb;">
                        <td><strong>Payment Date</strong></td>
                        <td>{{ $payment->paid_at->format('d-m-Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td style="color: {{ $payment->status == 'paid' ? 'green' : 'red' }}; font-weight: bold;">
                            {{ ucfirst($payment->status) }}
                        </td>
                    </tr>
                </table>

                <p>Terima kasih telah melakukan pembayaran.</p>
                <p>Jika ada pertanyaan, silakan hubungi kami.</p>
                <p>Salam hangat,</p>
                <p><strong>Tim Audit App</strong></p>

                <!-- Logo di bawah (Best Regards) -->
                <div style="margin-top: 20px; text-align: left;">
                    <img src="{{ $message->embed(public_path('logo/salam_image.png')) }}" 
                         alt="Logo Honda" 
                         style="max-height: 70px;">
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 15px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee;">
                © {{ date('Y') }} Audit App. All rights reserved.
            </td>
        </tr>
    </table>

</body>
</html>
