<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate Request Without Course Completion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .info-row {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }
        .label {
            font-weight: bold;
            color: #495057;
        }
        .value {
            color: #6c757d;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🔔 Certificate Request Without Course Completion</h2>
        <p>A user has requested a certificate without completing the course requirements.</p>
    </div>

    <div class="content">
        <h3>Request Details:</h3>

        <div class="info-row">
            <span class="label">User Name:</span>
            <span class="value">{{ $user_name }}</span>
        </div>

        <div class="info-row">
            <span class="label">User Email:</span>
            <span class="value">{{ $user_email }}</span>
        </div>

        <div class="info-row">
            <span class="label">Course Name:</span>
            <span class="value">{{ $course_name }}</span>
        </div>

        <div class="info-row">
            <span class="label">Course ID:</span>
            <span class="value">{{ $course_id }}</span>
        </div>

        <div class="info-row">
            <span class="label">Request Date:</span>
            <span class="value">{{ $request_date }}</span>
        </div>

        <p><strong>Action Required:</strong> Please review this request and decide whether to approve or deny the certificate issuance without course completion.</p>
    </div>

    <div class="footer">
        <p>This is an automated notification from your LMS system.</p>
        <p>Generated on: {{ now()->format('M d, Y H:i:s') }}</p>
    </div>
</body>
</html>
