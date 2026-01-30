<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $transaction->transaction_code }}</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 20mm;
            }
            .no-print {
                display: none;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3b82f6;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .company-details {
            font-size: 12px;
            color: #666;
            line-height: 1.8;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .detail-title {
            font-size: 12px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .detail-content {
            font-size: 14px;
        }

        .detail-row {
            margin-bottom: 5px;
        }

        .detail-label {
            font-weight: 600;
            display: inline-block;
            width: 120px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background: #3b82f6;
            color: white;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .items-table tbody tr:hover {
            background: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .summary-box {
            width: 350px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }

        .summary-row.total {
            border-top: 2px solid #3b82f6;
            border-bottom: 3px double #3b82f6;
            font-size: 18px;
            font-weight: bold;
            color: #3b82f6;
            padding: 15px 0;
        }

        .payment-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .payment-label {
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
            font-size: 12px;
            color: #666;
        }

        .footer-note {
            margin-top: 20px;
            font-style: italic;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .print-button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Print Invoice</button>

    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">SALON CANTIK</div>
                <div class="company-details">
                    Jl. Contoh No. 123, Kota ABC<br>
                    Telp: 0812-3456-7890<br>
                    Email: info@saloncantik.com<br>
                    Instagram: @saloncantik
                </div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $transaction->transaction_code }}</div>
                <div class="invoice-number">{{ $transaction->transaction_date->format('d F Y') }}</div>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <div class="detail-box">
                <div class="detail-title">Pelanggan</div>
                <div class="detail-content">
                    <div class="detail-row">
                        <strong>{{ $transaction->customer->name }}</strong>
                    </div>
                    @if($transaction->customer->phone)
                    <div class="detail-row">{{ $transaction->customer->phone }}</div>
                    @endif
                    @if($transaction->customer->email)
                    <div class="detail-row">{{ $transaction->customer->email }}</div>
                    @endif
                    @if($transaction->customer->address)
                    <div class="detail-row">{{ $transaction->customer->address }}</div>
                    @endif
                </div>
            </div>

            <div class="detail-box">
                <div class="detail-title">Detail Transaksi</div>
                <div class="detail-content">
                    <div class="detail-row">
                        <span class="detail-label">Tanggal</span>
                        <span>{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Kasir</span>
                        <span>{{ $transaction->created_by ?? 'Admin' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="status-badge status-{{ $transaction->status }}">
                            {{ strtoupper($transaction->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Layanan</th>
                    <th class="text-center" style="width: 80px;">Qty</th>
                    <th class="text-right" style="width: 150px;">Harga Satuan</th>
                    <th class="text-right" style="width: 150px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->transactionDetails as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $detail->service_name }}</td>
                    <td class="text-center">{{ $detail->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($detail->price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($transaction->total_before_discount, 0, ',', '.') }}</span>
                </div>

                @if($transaction->discount)
                <div class="summary-row">
                    <span>Diskon ({{ $transaction->discount->name }})</span>
                    <span>- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif

                <div class="summary-row total">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($transaction->total_after_discount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-row">
                <span class="payment-label">Metode Pembayaran:</span>
                <span>{{ strtoupper($transaction->payment_method) }}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Status Pembayaran:</span>
                <span class="status-badge status-{{ $transaction->status }}">
                    {{ strtoupper($transaction->status) }}
                </span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <strong>Terima kasih atas kepercayaan Anda!</strong>
            <div class="footer-note">
                Invoice ini dicetak secara otomatis dan sah tanpa tanda tangan.<br>
                Untuk pertanyaan, hubungi kami di 0812-3456-7890
            </div>
            <div style="margin-top: 20px; font-size: 11px;">
                Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>
</body>
</html>