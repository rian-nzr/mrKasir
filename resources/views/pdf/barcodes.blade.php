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
            width: 50%; /* 2 kolom, berarti 100% / 2 = 50% */
            text-align: center;
            vertical-align: top;
        }
        img {
            width: 150px;
            height: 35px;
            margin: 5px 0;
        }
        p {
            font-size: 10px;
            margin: 5px 0;
            word-wrap: break-word;
        }
        .barcode-number {
            font-size: 9px;
            margin-top: 5px;
            font-family: monospace;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            @foreach ($barcodes as $key => $barcode)
                <td>
                    <p>{{ $barcode['name'] }} ~ Rp. {{ number_format($barcode['price'], 0, ',', '.') }}</p>
                    <img src="{{ $barcode['barcode'] }}" alt="Barcode {{ $barcode['number'] }}">
                    <br>
                    <span class="barcode-number">{{ $barcode['number'] }}</span>
                </td>

                @if (($key + 1) % 2 == 0) 
                    </tr><tr> <!-- Ganti baris setiap 2 produk -->
                @endif
            @endforeach
            
            @if (count($barcodes) % 2 != 0)
                <td></td> <!-- Tambah cell kosong jika jumlah produk ganjil -->
            @endif
        </tr>
    </table>

</body>
</html>
