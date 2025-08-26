<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Your Course</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #27ae60; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 12px 24px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; }
        .highlight { background: #e8f8f5; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Welcome to {{ $course_title }}</h1>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{ $user_name }}</strong>,</p>
            
            <p>Welcome! You have successfully enrolled in:</p>
            
            <div class="highlight">
                <h2>{{ $course_title }}</h2>
                <p><strong>Enrollment Date:</strong> {{ $enrollment_date }}</p>
            </div>
            
            <p>You're now ready to begin your learning journey. Here's what you can expect:</p>
            
            <ul>
                <li>Access to course materials and resources</li>
                <li>Interactive learning modules</li>
                <li>Progress tracking and assessments</li>
                <li>Certificate upon completion</li>
                <li>CME credits (if applicable)</li>
            </ul>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $dashboard_url }}" class="btn">Go to Dashboard</a>
            </p>
            
            <p><strong>Getting Started:</strong></p>
            <ol>
                <li>Review the course syllabus and objectives</li>
                <li>Complete the pre-course assessment (if available)</li>
                <li>Begin with the first module</li>
                <li>Track your progress regularly</li>
            </ol>
            
            <p><strong>Need Help?</strong></p>
            <p>Our support team is here to assist you throughout your learning journey. Don't hesitate to reach out if you have any questions.</p>
        </div>
        
        <div class="footer">
            <p>Happy learning!</p>
            <p>© {{ date('Y') }} MULHIM. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

