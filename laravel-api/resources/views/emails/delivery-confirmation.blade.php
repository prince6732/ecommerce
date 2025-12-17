<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Your Delivery</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .order-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background: #059669;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Your Order Has Been Delivered!</h1>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $order->user->name }}</strong>,</p>
        
        <p>Great news! Your order has been delivered successfully. We hope you're enjoying your purchase!</p>
        
        <div class="order-info">
            <h3>Order Details:</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Total Amount:</strong> ₹{{ number_format($order->total, 2) }}</p>
            <p><strong>Items:</strong> {{ $order->orderItems->count() }} item(s)</p>
        </div>
        
        <p>To confirm that you have received your order in good condition, please click the button below:</p>
        
        <div style="text-align: center;">
            <a href="{{ $confirmationUrl }}" class="button">
                ✓ Confirm Delivery Received
            </a>
        </div>
        
        <div class="warning">
            <strong>⚠️ Important:</strong> By confirming delivery, you acknowledge that you have received all items in your order and they are in good condition. This will mark your order as completed.
        </div>
        
        <p>If you haven't received your order or there are any issues, please contact our customer support immediately instead of confirming delivery.</p>
        
        <p>Thank you for shopping with us! We appreciate your business.</p>
        
        <p>Best regards,<br>The E-Com Array Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>If you have any questions, contact us at support@ecomarray.com</p>
        <p>&copy; {{ date('Y') }} E-Com Array. All rights reserved.</p>
    </div>
</body>
</html>
