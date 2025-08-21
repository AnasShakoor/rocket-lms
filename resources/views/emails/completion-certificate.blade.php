<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Completion Certificate</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
        .highlight { background: #e8f4fd; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Course Completion Certificate</h1>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{ $user_name }}</strong>,</p>
            
            <p>Congratulations! You have successfully completed the course:</p>
            
            <div class="highlight">
                <h2>{{ $course_title }}</h2>
                <p><strong>Completion Date:</strong> {{ $completion_date }}</p>
                <p><strong>CME Hours Earned:</strong> {{ $cme_hours }} hours</p>
            </div>
            
            <p>Your certificate is now available for download. This certificate serves as official documentation of your course completion and can be used for:</p>
            
            <ul>
                <li>Professional development requirements</li>
                <li>Continuing medical education (CME) credits</li>
                <li>License renewal applications</li>
                <li>Professional portfolio</li>
            </ul>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $certificate_url }}" class="btn">Download Certificate</a>
            </p>
            
            <p><strong>Important Notes:</strong></p>
            <ul>
                <li>Keep this certificate in a safe place</li>
                <li>You may need to submit it to your licensing board</li>
                <li>For any questions, contact our support team</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing our platform for your professional development.</p>
            <p>© {{ date('Y') }} MULHIM. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

