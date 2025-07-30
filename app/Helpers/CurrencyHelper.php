<?php

if (!function_exists('formatRupiah')) {
    /**
     * Format angka menjadi format rupiah
     *
     * @param int|float $amount
     * @param bool $withSymbol
     * @return string
     */
    function formatRupiah($amount, $withSymbol = true)
    {
        $formatted = number_format($amount, 0, ',', '.');
        return $withSymbol ? 'Rp ' . $formatted : $formatted;
    }
}

if (!function_exists('parseRupiah')) {
    /**
     * Parse format rupiah menjadi angka
     *
     * @param string $rupiahString
     * @return int
     */
    function parseRupiah($rupiahString)
    {
        // Hapus semua karakter selain angka
        $number = preg_replace('/[^\d]/', '', $rupiahString);
        return (int) $number;
    }
}

if (!function_exists('calculateProfit')) {
    /**
     * Hitung laba dari harga jual dan harga beli
     *
     * @param int|float $sellingPrice
     * @param int|float $costPrice
     * @return array
     */
    function calculateProfit($sellingPrice, $costPrice)
    {
        $profit = $sellingPrice - $costPrice;
        $profitPercentage = $costPrice > 0 ? round(($profit / $costPrice) * 100, 2) : 0;

        return [
            'profit' => $profit,
            'profit_percentage' => $profitPercentage,
            'profit_formatted' => formatRupiah($profit),
        ];
    }
}
