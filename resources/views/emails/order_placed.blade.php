<!DOCTYPE html>
<html>

<head>
    <title>Order Confirmation</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2>Hello {{ $customerName }},</h2>
    <p>Thank you for your order! We are pleased to confirm that your order has been successfully placed.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr style="background-color: #f2f2f2;">
            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Order ID</th>
            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Total Amount</th>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;">#{{ $orderId }}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">${{ number_format($totalAmount, 2) }}</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Product Name</th>
                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Qty</th>
                <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orderItems as $item)
            <tr>
                <!-- যদি সরাসরি রিলেশন থাকে তবে $item->name, আর যদি order_items টেবিল হয়ে প্রোডাক্ট মডেলে যায় তবে $item->product->name -->
                <td style="padding: 10px; border: 1px solid #ddd;">
                    {{ $item->product->name ?? $item->product_name ?? 'N/A' }}
                </td>
                <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">
                    {{ $item->quantity ?? $item->qty }}
                </td>
                <td style="padding: 10px; text-align: right; border: 1px solid #ddd;">
                    ${{ number_format($item->price ?? $item->unit_price, 2) }}
                </td>
            </tr>
            @endforeach

            <!-- টোটাল প্রাইস রো -->
            <tr>
                <td colspan="2" style="padding: 10px; text-align: right; font-weight: bold; border: 1px solid #ddd;">
                    Total:</td>
                <td style="padding: 10px; text-align: right; font-weight: bold; border: 1px solid #ddd;">
                    ${{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p>We will notify you once your order is processed and shipped.</p>
    <br>
    <p>Best Regards,</p>
    <p><strong>Mini Order Team</strong></p>

</body>

</html>