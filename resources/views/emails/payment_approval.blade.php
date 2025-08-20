<!DOCTYPE html>
<html>
<head>
    <title>Audit Approval Payment</title>
</head>
<body>
    <h1>Audit Approval Payment</h1>
    <p>Dear {{ $bendaharaName }},</p>
    <p>Ada pembayaran pending, mohon untuk melakukan validasi pembayaran berikut:</p>
    <p><strong>Emp ID:</strong> {{ $payment->emp_id }}</p>
    <p><strong>Name:</strong> {{ $payment->karyawan->emp_name }}</p>
    <p><strong>Amount:</strong> Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
    <a href="{{ route('approve.payment', $payment->id) }}" style="background-color: green; color: white; padding: 10px;">Approve Pembayaran</a>
    <p>Terima kasih!</p>
</body>
</html>
