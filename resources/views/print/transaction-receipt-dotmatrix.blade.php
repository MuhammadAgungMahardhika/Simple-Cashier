{{-- Format untuk Printer Dot Matrix LQ310 --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - {{ $transaction->transaction_code }}</title>
    <style>
        @media print {
            @page {
                size: 210mm 140mm;
                /* Half of continuous form */
                margin: 5mm;
            }

            body {
                margin: 0;
                padding: 0;
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
            font-family: 'Courier New', 'Courier', monospace;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            width: 210mm;
            margin: 0 auto;
            padding: 5mm;
        }

        .receipt {
            width: 100%;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 8mm;
            border-bottom: 1px solid #000;
            padding-bottom: 3mm;
        }

        .salon-name {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 2mm;
        }

        .salon-info {
            font-size: 9pt;
            line-height: 1.4;
        }

        .receipt-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin: 5mm 0;
            letter-spacing: 3px;
        }

        /* Transaction Info */
        .trans-info {
            margin-bottom: 5mm;
            font-size: 10pt;
        }

        .info-row {
            display: flex;
            margin-bottom: 1.5mm;
        }

        .info-label {
            width: 35mm;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 4mm 0;
        }

        .separator-solid {
            border-top: 1px solid #000;
            margin: 4mm 0;
        }

        .separator-double {
            border-top: 3px double #000;
            margin: 4mm 0;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 5mm;
        }

        .items-header {
            display: flex;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
            font-size: 10pt;
        }

        .col-no {
            width: 10mm;
            text-align: center;
        }

        .col-item {
            flex: 1;
            padding-left: 2mm;
        }

        .col-qty {
            width: 15mm;
            text-align: center;
        }

        .col-price {
            width: 30mm;
            text-align: right;
        }

        .col-total {
            width: 35mm;
            text-align: right;
        }

        .item-row {
            display: flex;
            margin-bottom: 2mm;
            font-size: 10pt;
        }

        /* Summary Section */
        .summary {
            margin-top: 5mm;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 11pt;
        }

        .summary-row.subtotal {
            font-size: 10pt;
        }

        .summary-row.discount {
            font-size: 10pt;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 13pt;
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            padding: 3mm 0;
            margin-top: 3mm;
        }

        .summary-label {
            font-weight: bold;
        }

        /* Payment Info */
        .payment-section {
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: 1px dashed #000;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 10pt;
        }

        .payment-label {
            font-weight: bold;
        }

        /* Footer */
        .footer {
            margin-top: 8mm;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 3mm;
            font-size: 9pt;
        }

        .thank-you {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 3mm;
        }

        .footer-note {
            margin-top: 3mm;
            font-style: italic;
        }

        .print-date {
            margin-top: 5mm;
            font-size: 8pt;
        }

        /* Print Button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            font-family: Arial, sans-serif;
        }

        .print-button:hover {
            background: #1d4ed8;
        }

        /* Screen View */
        @media screen {
            body {
                background: #e5e7eb;
                padding: 20px;
            }

            .receipt {
                background: white;
                padding: 10mm;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Print Struk</button>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="salon-name">SALON </div>
            <div class="salon-info">
                Jl. Contoh No. 123, Kota ABC<br>
                Telp: 0812-3456-7890<br>
                Instagram: @salon
            </div>
        </div>

        <div class="receipt-title">STRUK PEMBAYARAN</div>

        <!-- Transaction Info -->
        <div class="trans-info">
            <div class="info-row">
                <span class="info-label">No. Transaksi</span>
                <span class="info-value">: {{ $transaction->transaction_code }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal</span>
                <span class="info-value">:
                    {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Pelanggan</span>
                <span class="info-value">: {{ $transaction->customer->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Kasir</span>
                <span class="info-value">: {{ $transaction->created_by ?? 'Admin' }}</span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Items -->
        <div class="items-section">
            <div class="items-header">
                <div class="col-no">No</div>
                <div class="col-item">Layanan</div>
                <div class="col-qty">Qty</div>
                <div class="col-price">Harga</div>
                <div class="col-total">Subtotal</div>
            </div>

            @foreach ($transaction->transactionDetails as $index => $detail)
                <div class="item-row">
                    <div class="col-no">{{ $index + 1 }}</div>
                    <div class="col-item">{{ $detail->service_name }}</div>
                    <div class="col-qty">{{ $detail->quantity }}</div>
                    <div class="col-price">{{ number_format($detail->price, 0, ',', '.') }}</div>
                    <div class="col-total">{{ number_format($detail->subtotal, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>

        <div class="separator"></div>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-row subtotal">
                <span class="summary-label">Subtotal</span>
                <span>Rp {{ number_format($transaction->total_before_discount, 0, ',', '.') }}</span>
            </div>

            @if ($transaction->discount)
                <div class="summary-row discount">
                    <span class="summary-label">Diskon ({{ $transaction->discount->name }})</span>
                    <span>- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="summary-row total">
                <span class="summary-label">TOTAL BAYAR</span>
                <span>Rp {{ number_format($transaction->total_after_discount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-section">
            <div class="payment-row">
                <span class="payment-label">Metode Pembayaran</span>
                <span>: {{ strtoupper($transaction->payment_method) }}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Status Pembayaran</span>
                <span>: {{ strtoupper($transaction->status) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
            <div>Semoga Anda Puas dengan Layanan Kami</div>
            <div class="footer-note">
                Barang yang sudah dibeli tidak dapat dikembalikan
            </div>
            <div class="print-date">
                Dicetak: {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>

    <script>
        // Optional: Auto print saat halaman dibuka
        // window.onload = function() { 
        //     window.print(); 
        // }
    </script>
</body>

</html>
