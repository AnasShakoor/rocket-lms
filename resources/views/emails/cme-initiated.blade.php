<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CME Initiated Successfully</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .highlight { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 CME Successfully Initiated!</h1>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{ $user_name }}</strong>,</p>
            
            <p>We are pleased to inform you that your Continuing Medical Education (CME) has been successfully initiated for the following course:</p>
            
            <div class="highlight">
                <h3>{{ $course_title }}</h3>
                <p><strong>Completion Date:</strong> {{ $completion_date }}</p>
                <p><strong>CME Hours:</strong> {{ $cme_hours }} hours</p>
            </div>
            
            <p>Your CME credits have been recorded in our system and are now available for your professional development records.</p>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>Your completion has been verified and recorded</li>
                <li>CME credits are now available in your account</li>
                <li>You can download your certificate anytime</li>
                <li>Your progress will be tracked for future reference</li>
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $certificate_url }}" class="btn">Download Certificate</a>
            </div>
            
            <p>If you have any questions about your CME credits or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Thank you for choosing our platform for your continuing education needs!</p>
            
            <p>Best regards,<br>
            <strong>The MULHIM Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This email was sent to {{ $user_email }}</p>
            <p>© {{ date('Y') }} MULHIM. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

