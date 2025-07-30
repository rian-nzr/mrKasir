<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .receipt {
            auto;
            background: #fff;
        }

        .receipt .logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .receipt .logo img {
            width: 150px;
            height: 150px;
        }

        .receipt .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .receipt .header h1 {
            margin: 0;
            font-size: 30px;
            font-weight: 300;
        }

        .receipt .header p {
            margin: 0;
            font-size: 20px;
            font-weight: 100;
        }

        .receipt .content {
            font-size: 20px;
        }

        .receipt .content p {
            margin: 4px;
            letter-spacing: 0.5px;
            /* Atur jarak antar huruf */
            font-weight: 100;
            /* Huruf lebih tipis */
        }

        .receipt .content table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt .content table th,
        .receipt .content table td {
            text-align: left;
            padding: 5px 0;
        }

        .receipt .content table td {
            font-weight: 100;
            /* Huruf lebih tipis */
        }


        .receipt .content table th {
            border-bottom: 1px dashed #ccc;
            border-top: 1px dashed #ccc;
            font-weight: 300;
        }

        .receipt .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 20px;
            margin-bottom: 10px;
            height: 50px;
        }

        .receipt .footer p {
            margin: 0;
            height: 500px;
            line-height: 100px;
        }
    </style>
</head>

<body>
    <div class="receipt">
        <div class="logo">
            <img src="{{ asset('storage/' . $setting->image) }}" alt="Logo">
        </div>
        <div class="header">
            <h1>{{$setting->shop}}</h1>
            <p>{{$setting->address}}</p>
            <p>{{$setting->phone}}</p>
        </div>
        <div class="content">
            <p>=============================</p>
            <p>Tanggal :{{ date('d-m-Y, H:i') }}</p>
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: center;">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order_items as $item)
                    @php $product = \App\Models\Product::find($item->product_id); @endphp
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: center;">{{ number_format($product->price, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
                @endforeach
                <tfoot>
                    <tr>
                        @php
                        $total = 0;
                        foreach ($order_items as $item) {
                        $total += $item->quantity * $item->unit_price;
                        }
                        @endphp
                        <th>Total</th>
                        <th></th>
                        <th colspan="2" style="text-align: center;">{{ number_format($total, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
            <div class="footer">
                <p style="font-weight: bold;">Terima Kasih!

            </div>

        </div>
    </div>

    <script>
        window.print();
    </script>
</body>

</html>