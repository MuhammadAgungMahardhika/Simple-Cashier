<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Transaksi - {{ $transaction->transaction_code }}</title>
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
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
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            font-size: 12px;
            background: white;
        }

        .receipt {
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }

        .salon-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .salon-info {
            font-size: 10px;
            line-height: 1.4;
        }

        .section {
            margin-bottom: 10px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }

        .label {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .divider-solid {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        .items-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .items-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 5px;
            font-size: 10px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 5px;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .item-name {
            text-align: left;
        }

        .item-qty {
            text-align: center;
        }

        .item-price {
            text-align: right;
        }

        .summary {
            margin-top: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }

        .total-row {
            font-weight: bold;
            font-size: 14px;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 2px solid #000;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 2px dashed #000;
            padding-top: 10px;
            font-size: 10px;
        }

        .thank-you {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .print-button:hover {
            background: #2563eb;
        }

        @media screen {
            body {
                background: #f3f4f6;
                padding: 20px;
            }

            .receipt {
                background: white;
                padding: 20px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Print</button>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="salon-name">SALON </div>
            <div class="salon-info">
                Jl. Contoh No. 123, Kota<br>
                Telp: 0812-3456-7890<br>
                Instagram: @salon
            </div>
        </div>

        <!-- Transaction Info -->
        <div class="section">
            <div class="row">
                <span class="label">No. Transaksi</span>
                <span>{{ $transaction->transaction_code }}</span>
            </div>
            <div class="row">
                <span class="label">Tanggal</span>
                <span>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row">
                <span class="label">Pelanggan</span>
                <span>{{ $transaction->customer->name }}</span>
            </div>
            <div class="row">
                <span class="label">Kasir</span>
                <span>{{ $transaction->created_by ?? 'Admin' }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Items -->
        <div class="section">
            <div class="items-header">
                <div class="item-name">Layanan</div>
                <div class="item-qty">Qty</div>
                <div class="item-price">Harga</div>
            </div>

            @foreach ($transaction->transactionDetails as $detail)
                <div class="item-row">
                    <div class="item-name">{{ $detail->service_name }}</div>
                    <div class="item-qty">{{ $detail->quantity }}</div>
                    <div class="item-price">{{ number_format($detail->subtotal, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>

        <div class="divider"></div>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>Rp {{ number_format($transaction->total_before_discount, 0, ',', '.') }}</span>
            </div>

            @if ($transaction->discount)
                <div class="summary-row">
                    <span>Diskon ({{ $transaction->discount->name }})</span>
                    <span>- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="summary-row total-row">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaction->total_after_discount, 0, ',', '.') }}</span>
            </div>

            <div class="divider-solid"></div>

            <div class="summary-row">
                <span>Metode Pembayaran</span>
                <span>{{ strtoupper($transaction->payment_method) }}</span>
            </div>
            <div class="summary-row">
                <span>Status</span>
                <span>{{ strtoupper($transaction->status) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">Terima Kasih Atas Kunjungan Anda!</div>
            <div>Barang yang sudah dibeli tidak dapat dikembalikan</div>
            <div style="margin-top: 10px;">{{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>
