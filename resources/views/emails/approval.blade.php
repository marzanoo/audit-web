<!DOCTYPE html>
<html>
<head>
    <title>Audit Approval Request</title>
</head>
<body>
    <h1>Audit Approval Request</h1>
    <p>Dear {{ $managerName }},</p>
    <p>Mohon untuk melakukan approval audit berikut:</p>
    <p><strong>ID:</strong> {{ $auditAnswer->id }}</p>
    <p><strong>Status:</strong> {{ $auditAnswer->status }}</p>
    <a href="{{ $approveUrl }}" 
       style="background-color: #4CAF50; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; border-radius: 5px;">
       Approve Audit
    </a>
    <p>Terima kasih!</p>
</body>
</html>
