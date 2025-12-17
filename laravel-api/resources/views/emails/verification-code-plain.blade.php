<!DOCTYPE html>
<html>

<head>
    <title>Verification Code – E-Com Array</title>
    <meta charset="UTF-8">
    <style>
        body {
            background: #f6f8fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 30px 0;
        }

        .container {
            background: #ffffff;
            max-width: 420px;
            margin: 0 auto;
            padding: 36px 30px 28px 30px;
            border-radius: 10px;
            box-shadow: 0 0 16px 1px #ececf1;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            border-radius: 5px;
            margin-bottom: 9px;
        }

        .brand-name {
            font-size: 24px;
            letter-spacing: 1px;
            color: #1a73e8;
            font-weight: bold;
        }

        .title {
            font-size: 18px;
            color: #193350;
            font-weight: 600;
            margin-bottom: 18px;
            text-align: center;
        }

        .code-box {
            background: #f2f6fb;
            color: #1a73e8;
            font-size: 26px;
            letter-spacing: 7px;
            border-radius: 8px;
            font-weight: 700;
            padding: 17px 0;
            text-align: center;
            margin: 25px 0;
            border: 1.5px dashed #1a73e8;
        }

        p {
            font-size: 16px;
            color: #44484e;
            margin: 18px 0 0 0;
            line-height: 1.6;
            text-align: center;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            color: #7d7d7d;
            font-size: 13px;
        }

        .brand-logo {
            width: 300px;
            height: 80px;
            background: #f2f6fb;

        }
    </style>
</head>

<body>
    <div class="container">
        <div class="brand-header">
            <!-- Logo stored in public/logo.png -->
            <img class="brand-logo" src="{{ $message->embed(public_path() . '/logo.png') }}" alt="E-Com Array Logo">
            <div class="brand-name">E-Com Array</div>
        </div>

        <div class="title">Your Verification Code</div>

        <p>Please use the code below to complete your verification process on <b>E-Com Array</b>.</p>

        <div class="code-box">
            {{ $code }}
        </div>

        <p>This code is valid for a short time and can only be used once.</p>

        <div class="footer">
            If you did not request this code, please ignore this message.<br>
            <b>The E-Com Array Team</b>
        </div>
    </div>
</body>

</html>
