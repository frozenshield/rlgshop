<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You left items in your cart - RLG Hobby Shop</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #1e293b;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper {
      width: 100%;
      background-color: #f8fafc;
      padding: 32px 12px;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      border: 1px solid #e2e8f0;
    }
    .header {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
      padding: 36px 32px;
      text-align: center;
      color: #ffffff;
    }
    .header h1 {
      margin: 0;
      font-size: 24px;
      font-weight: 900;
      letter-spacing: -0.5px;
    }
    .header p {
      margin: 8px 0 0;
      font-size: 14px;
      color: #cbd5e1;
    }
    .content {
      padding: 32px;
    }
    .badge-offer {
      background-color: #fff1f2;
      border: 1px solid #fecdd3;
      border-radius: 12px;
      padding: 16px;
      text-align: center;
      margin-bottom: 24px;
    }
    .badge-offer .code {
      font-family: monospace;
      font-size: 20px;
      font-weight: 800;
      color: #e11d48;
      letter-spacing: 2px;
      display: inline-block;
      margin-top: 4px;
    }
    .cart-list {
      margin: 24px 0;
      border-top: 1px solid #e2e8f0;
      border-bottom: 1px solid #e2e8f0;
      padding: 16px 0;
    }
    .cart-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
    }
    .cart-item img {
      width: 64px;
      height: 64px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      margin-right: 16px;
    }
    .cart-item-info {
      flex: 1;
    }
    .cart-item-title {
      font-size: 14px;
      font-weight: 700;
      color: #0f172a;
      margin: 0 0 4px;
    }
    .cart-item-meta {
      font-size: 12px;
      color: #64748b;
      margin: 0;
    }
    .cart-item-price {
      font-size: 14px;
      font-weight: 800;
      color: #0f172a;
      text-align: right;
    }
    .summary-box {
      background-color: #f8fafc;
      border-radius: 12px;
      padding: 16px;
      margin: 20px 0;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      color: #64748b;
      margin-bottom: 6px;
    }
    .summary-total {
      display: flex;
      justify-content: space-between;
      font-size: 16px;
      font-weight: 800;
      color: #0f172a;
      padding-top: 8px;
      border-top: 1px dashed #cbd5e1;
    }
    .cta-btn {
      display: block;
      width: 100%;
      text-align: center;
      background-color: #e11d48;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 800;
      font-size: 15px;
      padding: 16px;
      border-radius: 12px;
      margin: 24px 0 16px;
      box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
    }
    .guarantee {
      text-align: center;
      font-size: 11px;
      color: #64748b;
      margin-top: 16px;
    }
    .footer {
      background-color: #f1f5f9;
      padding: 24px 32px;
      text-align: center;
      font-size: 12px;
      color: #64748b;
      border-top: 1px solid #e2e8f0;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <!-- Header -->
      <div class="header">
        <h1>RLG HOBBY SHOP</h1>
        <p>Premium TCG, Model Kits, Figures &amp; Collectibles</p>
      </div>

      <!-- Main Body -->
      <div class="content">
        <p style="font-size: 16px; font-weight: 700; margin-top: 0;">
          Hi {{ $cart->customer_name }},
        </p>
        <p style="font-size: 14px; line-height: 1.6; color: #475569;">
          You left items in your shopping cart! We saved your cart so you can pick up right where you left off. Complete your purchase now and enjoy an exclusive <strong>10% recovery discount</strong> on us.
        </p>

        <!-- Offer Voucher Box -->
        <div class="badge-offer">
          <div style="font-size: 12px; font-weight: 700; color: #9f1239; text-transform: uppercase; letter-spacing: 1px;">
            Your Limited-Time 10% Voucher
          </div>
          <div class="code">{{ $cart->discount_code ?? 'RECOVER10' }}</div>
          <div style="font-size: 11px; color: #be123c; margin-top: 4px;">
            Automatically applies at checkout &bull; Valid for 48 hours
          </div>
        </div>

        <!-- Items Table -->
        <h3 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px;">
          Items Left in Your Cart ({{ $cart->items_count }})
        </h3>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
          @if(is_array($cart->cart_items) && count($cart->cart_items) > 0)
            @foreach($cart->cart_items as $item)
              <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; width: 60px;">
                  <img 
                    src="{{ $item['image_url'] ?? 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=150' }}" 
                    alt="{{ $item['name'] ?? 'Product' }}" 
                    style="width: 54px; height: 54px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; display: block;"
                  />
                </td>
                <td style="padding: 10px 12px;">
                  <div style="font-size: 13px; font-weight: 700; color: #0f172a;">{{ $item['name'] ?? 'Product' }}</div>
                  <div style="font-size: 12px; color: #64748b;">Qty: {{ $item['quantity'] ?? 1 }}</div>
                </td>
                <td style="padding: 10px 0; text-align: right; font-size: 13px; font-weight: 700; color: #0f172a;">
                  ₱{{ number_format((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1), 2) }}
                </td>
              </tr>
            @endforeach
          @else
            <tr style="border-bottom: 1px solid #f1f5f9;">
              <td style="padding: 12px 0;">
                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">TCG Collector Boosters &amp; Box Sets</div>
                <div style="font-size: 12px; color: #64748b;">Qty: {{ $cart->items_count }}</div>
              </td>
              <td style="padding: 12px 0; text-align: right; font-size: 13px; font-weight: 700; color: #0f172a;">
                ₱{{ number_format((float) $cart->total_value, 2) }}
              </td>
            </tr>
          @endif
        </table>

        <!-- Order Totals Box -->
        <div class="summary-box">
          <div class="summary-row">
            <span>Subtotal Value:</span>
            <span>₱{{ number_format((float) $cart->total_value, 2) }}</span>
          </div>
          <div class="summary-row" style="color: #e11d48; font-weight: 600;">
            <span>10% Recovery Voucher:</span>
            <span>-₱{{ number_format(((float) $cart->total_value) * 0.10, 2) }}</span>
          </div>
          <div class="summary-total">
            <span>Your Discounted Total:</span>
            <span style="color: #e11d48;">₱{{ number_format(((float) $cart->total_value) * 0.90, 2) }}</span>
          </div>
        </div>

        <!-- CTA Button -->
        <a href="{{ $recoveryUrl }}" class="cta-btn">
          Complete Your Order &amp; Save 10% &rarr;
        </a>

        <div class="guarantee">
          🛡️ Authentic Sealed Guarantee &bull; 🚚 Secure Nationwide Shipping &bull; 💬 24/7 Collector Support
        </div>
      </div>

      <!-- Footer -->
      <div class="footer">
        <p style="margin: 0 0 6px;">
          RLG Hobby Shop &bull; Manila, Philippines &bull; <a href="mailto:support@rlghobby.com" style="color: #64748b;">support@rlghobby.com</a>
        </p>
        <p style="margin: 0; font-size: 11px; color: #94a3b8;">
          You received this email because you started an order on RLG Hobby Shop.
        </p>
      </div>
    </div>
  </div>
</body>
</html>

