<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Laporan Shift Kasir - {{ $shift->shift_number }}</title>
        <style>
            body {
                font-family: "DejaVu Sans", Arial, sans-serif;
                font-size: 11px;
                line-height: 1.4;
                color: #333;
                margin: 15px;
            }
            .header {
                text-align: center;
                margin-bottom: 25px;
                border-bottom: 3px solid #2563eb;
                padding-bottom: 15px;
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                padding: 20px;
                border-radius: 8px;
            }
            .header h1 {
                font-size: 22px;
                margin: 0 0 8px 0;
                color: #1e40af;
                font-weight: bold;
            }
            .header h2 {
                font-size: 16px;
                margin: 0 0 5px 0;
                color: #374151;
            }
            .header .shift-info {
                font-size: 12px;
                color: #6b7280;
                margin: 5px 0;
            }
            .info-grid {
                display: table;
                width: 100%;
                margin-bottom: 20px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
            }
            .info-row {
                display: table-row;
            }
            .info-row:nth-child(even) {
                background-color: #f9fafb;
            }
            .info-label {
                display: table-cell;
                font-weight: bold;
                width: 30%;
                padding: 8px 12px;
                color: #374151;
                border-right: 1px solid #e5e7eb;
            }
            .info-value {
                display: table-cell;
                padding: 8px 12px;
                color: #111827;
            }
            .section {
                margin-bottom: 25px;
                page-break-inside: avoid;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                color: #1f2937;
                background-color: #3b82f6;
                color: white;
                padding: 8px 12px;
                margin-bottom: 12px;
                border-radius: 4px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .summary-grid {
                display: table;
                width: 100%;
                margin-bottom: 15px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
            }
            .summary-row {
                display: table-row;
            }
            .summary-row:nth-child(even) {
                background-color: #f3f4f6;
            }
            .summary-label {
                display: table-cell;
                font-weight: 600;
                width: 60%;
                padding: 10px 12px;
                color: #374151;
                border-right: 1px solid #d1d5db;
            }
            .summary-value {
                display: table-cell;
                padding: 10px 12px;
                text-align: right;
                font-weight: bold;
                color: #059669;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
                background-color: white;
                border-radius: 6px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            .table th {
                background-color: #4f46e5;
                color: white;
                font-weight: bold;
                padding: 8px;
                text-align: left;
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .table td {
                padding: 6px 8px;
                border-bottom: 1px solid #e5e7eb;
                font-size: 10px;
            }
            .table tbody tr:nth-child(even) {
                background-color: #f8fafc;
            }
            .table tbody tr:hover {
                background-color: #e0f2fe;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .page-break {
                page-break-before: always;
            }
            .status-badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 9px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-active {
                background-color: #10b981;
                color: white;
            }
            .status-closed {
                background-color: #6b7280;
                color: white;
            }
            .currency {
                font-family: "DejaVu Sans Mono", monospace;
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
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M8 14v3"></path><path d="M12 10v7"></path><path d="M16 6v11"></path></svg>
                LAPORAN SHIFT KASIR
            </h1>
            <h2>{{ $shift->store->name ?? 'Toko' }}</h2>
            <div class="shift-info">
                <strong>Shift #{{ $shift->shift_number }}</strong> |
                <span
                    class="status-badge {{ $shift->status === 'active' ? 'status-active' : 'status-closed' }}"
                >
                    {{ strtoupper($shift->status) }}
                </span>
            </div>
        </div>
        <!-- INFORMASI SHIFT -->
        <div class="section">
            <div class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>
                INFORMASI SHIFT
            </div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> Kasir</div>
                    <div class="info-value">
                        {{ $shift->user->name ?? 'N/A' }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M3 9l9-6 9 6"></path><path d="M21 9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9"></path></svg> Toko</div>
                    <div class="info-value">
                        {{ $shift->store->name ?? 'N/A' }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M7 7h10v10H7z"></path></svg> No. Shift</div>
                    <div class="info-value">{{ $shift->shift_number }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path></svg> Tanggal</div>
                    <div class="info-value">
                        {{ $shift->created_at->format('d/m/Y') }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v6l4 2"></path></svg> Jam Buka</div>
                    <div class="info-value">
                        {{ $shift->opened_at ? $shift->opened_at->format('H:i:s') : 'Belum dibuka' }}
                    </div>
                </div>
                @if($shift->closed_at && $shift->opened_at)
                <div class="info-row">
                    <div class="info-label">🕐 Jam Tutup</div>
                    <div class="info-value">
                        {{ $shift->closed_at->format('H:i:s') }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">⏱️ Durasi Shift</div>
                    <div class="info-value">
                        {{ $shift->closed_at->diff($shift->opened_at)->format('%H jam %I menit') }}
                    </div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> Status</div>
                    <div class="info-value">
                        @if($shift->location)
                        <span
                            class="status-badge {{ $shift->status === 'active' ? 'status-active' : 'status-closed' }}"
                        >
                            {{ ucfirst($shift->status) }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @if(isset($balance_tracking))
        <!-- SALDO AWAL -->
        <div class="section">
            <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><path d="M21 8v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8"></path><rect x="2" y="3" width="20" height="5" rx="2"></rect></svg> SALDO AWAL KASIR</div>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-label">💵 Total Saldo Awal</div>
                    <div class="summary-value currency">
                        {{
                            $balance_tracking["opening_balance_formatted"] ??
                                "Rp 0"
                        }}
                    </div>
                </div>
            </div>
            @if(isset($balance_tracking['opening_balance_breakdown']) &&
            is_array($balance_tracking['opening_balance_breakdown']))
            <div class="section">
                <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><path d="M3 3v18h18"></path><path d="M7 13v6"></path><path d="M12 9v10"></path><path d="M17 5v14"></path></svg> BREAKDOWN SALDO AWAL</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"></svg> Metode Pembayaran</th>
                            <th><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"></svg> Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($balance_tracking['opening_balance_breakdown']
                        as $method)
                        <tr>
                            <td>{{ $method["name"] ?? "N/A" }}</td>
                            <td class="text-right currency">
                                {{ $method["formatted_amount"] ?? "Rp 0" }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @if($shift->status === 'closed' &&
        isset($balance_tracking['closing_balance_breakdown']))
        <!-- SALDO AKHIR -->
        <div class="section">
            <div class="section-title">🏦 SALDO AKHIR KASIR</div>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-label">💵 Total Saldo Akhir</div>
                    <div class="summary-value currency">
                        {{
                            $balance_tracking["closing_balance_formatted"] ??
                                "Rp 0"
                        }}
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">📈 Selisih Saldo</div>
                    <div
                        class="summary-value currency"
                        style="color: {{
                            ($balance_tracking['balance_difference'] ?? 0) >= 0
                                ? '#059669'
                                : '#dc2626'
                        }};"
                    >
                        {{
                            $balance_tracking["balance_difference_formatted"] ??
                                "Rp 0"
                        }}
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">🎯 Status Saldo</div>
                    <div
                        class="summary-value"
                        style="color: {{
                            ($balance_tracking['balance_difference'] ?? 0) >= 0
                                ? '#059669'
                                : '#dc2626'
                        }};"
                    >
                        {{
                            ($balance_tracking["balance_difference"] ?? 0) >= 0
                                ? "✅ Sesuai/Lebih"
                                : "⚠️ Kurang"
                        }}
                    </div>
                </div>
            </div>
            <div class="section">
                <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><path d="M3 3v18h18"></path><path d="M7 13v6"></path><path d="M12 9v10"></path><path d="M17 5v14"></path></svg> BREAKDOWN SALDO AKHIR</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>💳 Metode Pembayaran</th>
                            <th>💰 Saldo Awal</th>
                            <th>📊 Penjualan</th>
                            <th>🏦 Saldo Akhir</th>
                            <th>📈 Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($balance_tracking['closing_balance_breakdown']
                        as $method)
                        <tr>
                            <td>{{ $method["name"] ?? "N/A" }}</td>
                            <td class="text-right currency">
                                {{ $method["opening_formatted"] ?? "Rp 0" }}
                            </td>
                            <td class="text-right currency">
                                {{ $method["sales_formatted"] ?? "Rp 0" }}
                            </td>
                            <td class="text-right currency">
                                {{ $method["closing_formatted"] ?? "Rp 0" }}
                            </td>
                            <td
                                class="text-right currency"
                                style="color: {{
                                    ($method['difference'] ?? 0) >= 0
                                        ? '#059669'
                                        : '#dc2626'
                                }};"
                            >
                                {{ $method["difference_formatted"] ?? "Rp 0" }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif @endif
        <!-- RINGKASAN PENJUALAN -->
        <div class="section">
            <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><path d="M3 3v18h18"></path><path d="M7 13v6"></path><path d="M12 9v10"></path><path d="M17 5v14"></path></svg> RINGKASAN PENJUALAN</div>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"></svg> Total Transaksi</div>
                    <div class="summary-value">
                        {{
                            $sales_summary["total_transactions"] ?? 0
                        }}
                        transaksi
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-label"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"></svg> Total Item Terjual</div>
                    <div class="summary-value">
                        {{ $sales_summary["total_items"] ?? 0 }} item
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">💰 Total Penjualan</div>
                    <div class="summary-value currency">
                        {{ $sales_summary["total_sales_formatted"] ?? "Rp 0" }}
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">💸 Total Diskon</div>
                    <div class="summary-value currency" style="color: #dc2626">
                        {{
                            $sales_summary["total_discount_formatted"] ?? "Rp 0"
                        }}
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">🧾 Total Pajak</div>
                    <div class="summary-value currency">
                        {{ $sales_summary["total_tax_formatted"] ?? "Rp 0" }}
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">💯 Penjualan Bersih</div>
                    <div class="summary-value currency">
                        {{ $sales_summary["net_sales_formatted"] ?? "Rp 0" }}
                    </div>
                </div>
                @if(isset($sales_summary['profit_total']))
                <div class="summary-row">
                    <div class="summary-label">📊 Total Profit</div>
                    <div class="summary-value currency" style="color: #059669">
                        {{ $sales_summary["profit_total_formatted"] ?? "Rp 0" }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @if(isset($payment_breakdown) && count($payment_breakdown) > 0)
        <div class="section">
            <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M2 10h20"></path></svg> BREAKDOWN PEMBAYARAN</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>💳 Metode Pembayaran</th>
                        <th>🏷️ Jenis</th>
                        <th>🔢 Jumlah Transaksi</th>
                        <th>💰 Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment_breakdown as $payment)
                    <tr>
                        <td>{{ $payment["name"] ?? "N/A" }}</td>
                        <td class="text-center">
                            {{ ucfirst($payment["type"] ?? "other") }}
                        </td>
                        <td class="text-center">
                            {{ $payment["count"] ?? 0 }}
                        </td>
                        <td class="text-right currency">
                            {{ $payment["formatted_amount"] ?? "Rp 0" }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif @if(isset($product_sales) && count($product_sales) > 0)
        <div class="section page-break">
            <div class="section-title">🏆 PRODUK TERLARIS (Top 20)</div>
            <table class="table">
                <thead>
                        <tr>
                        <th>Ranking</th>
                        <th>📦 Nama Produk</th>
                        <th>💰 Harga Satuan</th>
                        <th>🔢 Qty Terjual</th>
                        <th>💸 Total Penjualan</th>
                        <th>📊 Profit</th>
                        <th>📈 % dari Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($product_sales, 0, 20) as $index =>
                    $product)
                    <tr>
                        <td class="text-center">
                            @if($index === 0) 🥇 @elseif($index === 1) 🥈
                            @elseif($index === 2) 🥉 @else
                            {{ $index + 1 }} @endif
                        </td>
                        <td>{{ $product["name"] ?? "N/A" }}</td>
                        <td class="text-right currency">
                            {{ $product["price_formatted"] ?? "Rp 0" }}
                        </td>
                        <td class="text-center">
                            {{ $product["quantity"] ?? 0 }}
                        </td>
                        <td class="text-right currency">
                            {{ $product["total_formatted"] ?? "Rp 0" }}
                        </td>
                        <td class="text-right currency">
                            {{ $product["profit_formatted"] ?? "Rp 0" }}
                        </td>
                        <td class="text-center">
                            {{ $product["percentage"] ?? "0" }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif @if(isset($cash_flow_history) && count($cash_flow_history) > 0)
        <div class="section">
            <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><path d="M3 3v18h18"></path><path d="M3 12h18"></path></svg> RIWAYAT ARUS KAS</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>🕐 Waktu</th>
                        <th>📋 Jenis Transaksi</th>
                        <th>💰 Jumlah</th>
                        <th>🏦 Saldo Setelah</th>
                        <th>📝 Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cash_flow_history as $flow)
                    <tr>
                        <td>{{ $flow["time"] ?? "N/A" }}</td>
                        <td>{{ $flow["type"] ?? "N/A" }}</td>
                        <td
                            class="text-right currency"
                            style="color: {{
                                ($flow['amount'] ?? 0) >= 0
                                    ? '#059669'
                                    : '#dc2626'
                            }};"
                        >
                            {{ $flow["formatted_amount"] ?? "Rp 0" }}
                        </td>
                        <td class="text-right currency">
                            {{ $flow["balance_after_formatted"] ?? "Rp 0" }}
                        </td>
                        <td>{{ $flow["description"] ?? "-" }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif @if(isset($recommendations) && count($recommendations) > 0)
        <div class="section">
            <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><path d="M9 18h6"></path><path d="M10 14a4 4 0 1 1 4 0"></path><path d="M12 2v4"></path></svg> REKOMENDASI & INSIGHTS</div>
            @foreach($recommendations as $rec)
            <div class="highlight">
                <strong>{{ $rec["title"] ?? "Rekomendasi" }}</strong
                ><br />
                {{ $rec["message"] ?? "N/A" }}
            </div>
            @endforeach
        </div>
        @endif
        <div class="footer">
            <p>
                <strong>Laporan ini dibuat secara otomatis pada {{ now()->format('d/m/Y H:i:s') }}</strong>
            </p>
            <p>
                {{ config("app.name", "POS System") }} - Sistem Manajemen Kasir v2.0
            </p>
            <p>
                Dokumen ini bersifat rahasia dan hanya untuk keperluan internal
            </p>
            @if($shift->status === 'active')
            <p style="color: #dc2626"><strong>PERHATIAN: Shift masih aktif - Data dapat berubah</strong></p>
            @endif
        </div>
    </body>
</html>
