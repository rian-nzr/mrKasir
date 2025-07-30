<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Laporan Order</title>
    <style>
        body {
            margin: 0 auto;
            font-family: Arial, sans-serif;
            background: #FFFFFF;
            font-size: 12px;
            color: #001028;
        }

        header {
            padding: 10px 0;
            text-align: center;
            border-bottom: 1px solid #5D6975;
            margin-bottom: 20px;
        }

        #logo img {
            width: 80px;
        }

        h1 {
            font-size: 2em;
            margin: 10px 0;
        }

        span {
            font-size: 16px;
            color: #5D6975;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #C1CED9;
            padding: 8px;
            text-align: center;
        }

        table th {
            background-color: yellow;
            color: black;
        }

        .desc {
            text-align: left;
        }

        footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 30px;
            border-top: 1px solid #C1CED9;
            text-align: center;
            padding: 8px 0;
            font-size: 0.8em;
            color: #5D6975;
        }
    </style>
</head>

<body>

    <header>
        <div id="logo">
            <img src="{{ storage_path('app/public/' . $logo) }}" alt="{{ asset('storage/' . $logo) }}">
        </div>
        <h1>Laporan Pengeluaran<br><span>{{ '(' . $report->start_date . ')' . ' - ' . '(' . $report->end_date . ')' }}</span></h1>
    </header>

    <main>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Pengeluaran</th>
                    <th>Catatan</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <?php $total_expense_amount = 0 ?>
            <tbody>
                @foreach($data as $item)
                    <tr>
                        <td>{{ $item->date_expense }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->note }}</td>
                        <td>Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                    <?php $total_expense_amount += $item->amount ?>
                @endforeach
                    <tr>
                        <td colspan="3">Total Keseluruhan:</td>
                        <td>Rp {{ number_format( $total_expense_amount, 0, ',', '.') }}</td>
                    </tr>
            </tbody>
        </table>
    </main>

    <footer>
        Laporan ini dihasilkan secara otomatis tanpa tanda tangan.
    </footer>

</body>

</html>
