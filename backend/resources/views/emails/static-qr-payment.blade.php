<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Static QR Payment Instructions</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 30px 15px;
            line-height: 1.6;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 32px 30px 24px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background-color: #e0e7ff;
            color: #3730a3;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }
        .content {
            padding: 32px 30px;
        }
        .highlight-box {
            background-color: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            margin-bottom: 24px;
        }
        .amount-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .amount-value {
            font-size: 32px;
            font-weight: 900;
            color: #0f172a;
            font-family: Consolas, monospace;
        }
        .qr-wrapper {
            margin: 20px auto 14px;
            display: inline-block;
            background: #ffffff;
            padding: 12px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .qr-image {
            max-width: 240px;
            height: auto;
            display: block;
            border-radius: 8px;
        }
        .merchant-details {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 12px;
            margin-top: 14px;
            text-align: left;
            display: inline-block;
            min-width: 260px;
        }
        .merchant-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .merchant-row:last-child {
            margin-bottom: 0;
        }
        .merchant-label {
            color: #64748b;
            font-weight: 600;
        }
        .merchant-val {
            color: #0f172a;
            font-weight: 800;
        }
        .instruction-callout {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            border-radius: 0 12px 12px 0;
            padding: 16px 18px;
            margin-bottom: 24px;
            font-size: 13.5px;
            color: #1e3a8a;
            font-weight: 600;
            line-height: 1.5;
        }
        .steps {
            margin: 0 0 24px 0;
            padding: 0 0 0 20px;
            color: #334155;
            font-size: 13px;
        }
        .steps li {
            margin-bottom: 10px;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 30px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Zero-Fee Static QR Payment</span>
            <h1>Order #{{ $order->order_number }} Confirmed</h1>
            <p>Please complete payment to initiate item packaging and dispatch</p>
        </div>

        <div class="content">
            <!-- Exact required user instruction banner -->
            <div class="instruction-callout">
                Scan this {{ $merchant?->name ?? 'Wallet' }} QR, input the exact total of ₱{{ number_format($order->total_amount, 2) }}, and reply to this email with the payment screenshot.
            </div>

            <!-- QR Code and Account Card -->
            <div class="highlight-box">
                <div class="amount-label">Exact Payable Amount</div>
                <div class="amount-value">₱{{ number_format($order->total_amount, 2) }}</div>

                <div class="qr-wrapper">
                    <img src="{{ config('app.url') . ($merchant?->qr_image_url ?? '/images/qr/gotyme-qr.png') }}"
                         alt="{{ $merchant?->name ?? 'QR Code' }}"
                         class="qr-image">
                </div>

                <div class="merchant-details">
                    <div class="merchant-row">
                        <span class="merchant-label">Channel:</span>
                        <span class="merchant-val">{{ $merchant?->name ?? 'GoTyme / E-Wallet' }}</span>
                    </div>
                    @if($merchant?->account_name)
                    <div class="merchant-row">
                        <span class="merchant-label">Account Name:</span>
                        <span class="merchant-val">{{ $merchant->account_name }}</span>
                    </div>
                    @endif
                    @if($merchant?->account_number)
                    <div class="merchant-row">
                        <span class="merchant-label">Account / ID:</span>
                        <span class="merchant-val">{{ $merchant->account_number }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Steps for the Customer -->
            <h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 800; color: #0f172a;">How to Complete Your Order:</h4>
            <ol class="steps">
                <li>Open your <strong>{{ $merchant?->name ?? 'Banking / E-Wallet' }}</strong> app (or any InstaPay-compatible bank app).</li>
                <li>Scan the QR code displayed above or open the attached QR file on your device.</li>
                <li>Enter the exact amount: <strong>₱{{ number_format($order->total_amount, 2) }}</strong>.</li>
                <li>Save or capture a screenshot of your successful transaction confirmation / reference number.</li>
                <li><strong>Reply directly to this email</strong> with the transaction receipt attached.</li>
                <li>Our fulfillment team will verify the payment and immediately update your order to <strong>Paid</strong>!</li>
            </ol>

            <p style="font-size: 12px; color: #64748b; margin: 0;">
                <em>Note: We have also attached the static QR code image directly to this email so you can easily save it to your gallery or scan it from another screen.</em>
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0 0 6px;"><strong>RLG Toys &amp; Hobbies</strong> &bull; Premium Authentic Collector Vault</p>
            <p style="margin: 0;">Thank you for shopping with us! If you have questions, message our live support or reply to this email.</p>
        </div>
    </div>
</body>
</html>
