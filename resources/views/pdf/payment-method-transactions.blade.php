<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Riwayat Transaksi E-Wallet</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-gray { background-color: #e2e3e5; color: #383d41; }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .amount {
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RIWAYAT TRANSAKSI E-WALLET</h1>
        @if($store)
            <h2>{{ $store->name }}</h2>
        @endif
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Tanggal Cetak:</span>
            <span>{{ $generated_at->format('d/m/Y H:i:s') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total Transaksi:</span>
            <span>{{ number_format($transactions->count()) }} transaksi</span>
        </div>
        @if($store)
        <div class="info-row">
            <span class="info-label">Toko:</span>
            <span>{{ $store->name }}</span>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="8%">No</th>
                <th width="15%">Payment Method</th>
                <th width="12%">Tipe</th>
                <th width="12%">Jumlah</th>
                <th width="15%">No. Referensi</th>
                <th width="10%">Saldo Sebelum</th>
                <th width="10%">Saldo Sesudah</th>
                <th width="18%">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $transaction)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        @if($transaction->type === 'topup' || $transaction->type === 'transfer_in')
                            {{ $transaction->toPaymentMethod?->name ?? '-' }}
                        @else
                            {{ $transaction->fromPaymentMethod?->name ?? '-' }}
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $badgeClass = match($transaction->type) {
                                'topup' => 'badge-success',
                                'transfer_in' => 'badge-info',
                                'withdraw' => 'badge-danger',
                                'transfer_out' => 'badge-warning',
                                'adjustment' => 'badge-gray',
                                default => 'badge-gray',
                            };
                            
                            $typeLabel = match($transaction->type) {
                                'topup' => 'Top Up',
                                'withdraw' => 'Tarik Saldo',
                                'transfer_in' => 'Transfer Masuk',
                                'transfer_out' => 'Transfer Keluar',
                                'adjustment' => 'Penyesuaian',
                                default => ucfirst($transaction->type)
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td class="text-right amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                    <td>{{ $transaction->reference_number }}</td>
                    <td class="text-right">Rp {{ number_format($transaction->balance_before, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data transaksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis oleh sistem pada {{ $generated_at->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
