<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Shift Kasir - {{ $shift->shift_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0 0 10px 0;
            color: #2563eb;
        }
        .header h2 {
            font-size: 16px;
            margin: 0;
            color: #666;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 25%;
            padding: 5px 10px 5px 0;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-label {
            display: table-cell;
            font-weight: bold;
            width: 60%;
            padding: 8px 10px 8px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-value {
            display: table-cell;
            text-align: right;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f8fafc;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .currency {
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 10px;
            border-left: 4px solid #f59e0b;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN SHIFT KASIR</h1>
        <h2>{{ $shift->store->name ?? 'Toko' }}</h2>
        <p>{{ $shift->shift_number }}</p>
    </div>

    <div class="section">
        <div class="section-title">INFORMASI SHIFT</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Kasir:</div>
                <div class="info-value">{{ $shift->user->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Mulai Shift:</div>
                <div class="info-value">{{ $shift->opened_at->format('d/m/Y H:i:s') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Selesai Shift:</div>
                <div class="info-value">{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i:s') : 'Belum ditutup' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($shift->status) }}</div>
            </div>
            @if($shift->location)
            <div class="info-row">
                <div class="info-label">Lokasi:</div>
                <div class="info-value">{{ $shift->location }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">RINGKASN PENJUALAN</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-label">Total Transaksi:</div>
                <div class="summary-value">{{ $sales_summary['total_transactions'] ?? 0 }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Penjualan Kotor:</div>
                <div class="summary-value currency">Rp {{ number_format($sales_summary['gross_sales'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Diskon:</div>
                <div class="summary-value currency">Rp {{ number_format($sales_summary['total_discounts'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Penjualan Bersih:</div>
                <div class="summary-value currency"><strong>Rp {{ number_format($sales_summary['net_sales'] ?? 0, 0, ',', '.') }}</strong></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Rata-rata per Transaksi:</div>
                <div class="summary-value currency">Rp {{ number_format($sales_summary['average_transaction'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Transaksi Terbesar:</div>
                <div class="summary-value currency">Rp {{ number_format($sales_summary['largest_transaction'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Transaksi Terkecil:</div>
                <div class="summary-value currency">Rp {{ number_format($sales_summary['smallest_transaction'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @if(isset($payment_breakdown) && count($payment_breakdown) > 0)
    <div class="section">
        <div class="section-title">METODE PEMBAYARAN</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Metode Pembayaran</th>
                    <th>Jenis</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment_breakdown as $payment)
                <tr>
                    <td>{{ $payment['name'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ ucfirst($payment['type'] ?? 'other') }}</td>
                    <td class="text-center">{{ $payment['count'] ?? 0 }}</td>
                    <td class="text-right currency">{{ $payment['formatted_amount'] ?? 'Rp 0' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($product_sales) && count($product_sales) > 0)
    <div class="section">
        <div class="section-title">PRODUK TERLARIS (Top 20)</div>
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Produk</th>
                    <th>Harga Satuan</th>
                    <th>Qty Terjual</th>
                    <th>Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($product_sales as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product['name'] ?? 'N/A' }}</td>
                    <td class="text-right currency">Rp {{ number_format($product['price'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $product['quantity'] ?? 0 }}</td>
                    <td class="text-right currency">{{ $product['formatted_sales'] ?? 'Rp 0' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">MANAJEMEN KAS</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-label">Kas Awal:</div>
                <div class="summary-value currency">Rp {{ number_format($cash_management['opening_cash'] ?? 0, 0, ',', '.') }}</div>
            </div>
            @if($shift->isClosed())
            <div class="summary-row">
                <div class="summary-label">Kas Akhir (Aktual):</div>
                <div class="summary-value currency">Rp {{ number_format($cash_management['closing_cash'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Kas Akhir (Diharapkan):</div>
                <div class="summary-value currency">Rp {{ number_format($cash_management['expected_cash'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Selisih Kas:</div>
                <div class="summary-value currency">
                    @php
                        $cashDiff = $cash_management['cash_difference'] ?? 0;
                        $diffColor = $cashDiff >= 0 ? 'green' : 'red';
                    @endphp
                    <strong style="color: {{ $diffColor }}">
                        Rp {{ number_format($cashDiff, 0, ',', '.') }}
                    </strong>
                </div>
            </div>
            @endif
            <div class="summary-row">
                <div class="summary-label">Total Tarik Tunai:</div>
                <div class="summary-value currency">Rp {{ number_format($cash_management['cash_outs'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @if(isset($hourly_sales) && count($hourly_sales) > 0)
    <div class="section">
        <div class="section-title">PENJUALAN PER JAM</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Jam</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hourly_sales as $hour)
                <tr>
                    <td class="text-center">{{ $hour['hour'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $hour['transactions'] ?? 0 }}</td>
                    <td class="text-right currency">Rp {{ number_format($hour['sales'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($shift->notes || $shift->closing_notes)
    <div class="section">
        <div class="section-title">CATATAN</div>
        @if($shift->notes)
        <div class="highlight">
            <strong>Catatan Buka Shift:</strong><br>
            {{ $shift->notes }}
        </div>
        @endif
        @if($shift->closing_notes)
        <div class="highlight">
            <strong>Catatan Tutup Shift:</strong><br>
            {{ $shift->closing_notes }}
        </div>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis pada {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>{{ config('app.name', 'POS System') }} - Sistem Manajemen Kasir</p>
    </div>
</body>
</html>
