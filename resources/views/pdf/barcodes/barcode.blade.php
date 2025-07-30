<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Produk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            text-align: center;
        }
        table {
            width: 100%;
        }
        td {
            border: 1px solid #000;
            padding: 10px;
            width: 20%; /* 5 kolom, berarti 100% / 5 = 20% */
            text-align: center;
        }
        img {
            width: 120px;
            height: 30px;
        }
        p {
            font-size: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            @foreach ($barcodes as $key => $barcode)
                <td>
                    <p>{{ $barcode['name'] }} ~ Rp. {{ number_format($barcode['price'], 0, ',', '.') }}</p>
                    <img src="{{ $barcode['barcode'] }}" alt="Barcode" alt="{{ $barcode['number'] }}">
                    <br>
                    {{ $barcode['number'] }}
                </td>

                @if (($key + 1) % 5 == 0) 
                    </tr><tr> <!-- Ganti baris setiap 5 produk -->
                @endif
            @endforeach
        </tr>
    </table>

</body>
</html>
