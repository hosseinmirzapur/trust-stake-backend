<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
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
            text-align: center;
            border-radius: 5px 5px 0 0;
        }

        .content {
            background-color: #ffffff;
            padding: 30px;
            border: 1px solid #e9ecef;
            border-radius: 0 0 5px 5px;
        }

        .otp-code {
            background-color: #007bff;
            color: white;
            font-size: 32px;
            font-weight: bold;
            padding: 20px;
            text-align: center;
            letter-spacing: 5px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #6c757d;
        }

        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>TrustStake</h1>
        <p>Your One-Time Password (OTP)</p>
    </div>

    <div class="content">
        <p>Hello,</p>

        <p>You have requested to log in to your TrustStake account using email verification.</p>

        <div class="otp-code">
            {{ $otpCode }}
        </div>

        <p>Please enter this code in the application to complete your login process.</p>

        <div class="warning">
            <strong>Security Notice:</strong> This OTP code will expire in 5 minutes for your security.
            If you didn't request this code, please ignore this email.
        </div>

        <p>If you're having trouble, please contact our support team.</p>

        <p>Best regards,<br>The TrustStake Team</p>
    </div>

    <div class="footer">
        <p>This is an automated message, please do not reply to this email.</p>
        <p>TrustStake - Secure Staking Platform</p>
    </div>
</body>

</html>