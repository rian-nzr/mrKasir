// Format angka ke format rupiah Indonesia
function formatRupiah(angka, prefix = 'Rp ') {
    var number_string = angka.replace(/[^,\d]/g, '').toString(),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return prefix == undefined ? rupiah : (rupiah ? prefix + rupiah : '');
}

// Format currency untuk input
function formatCurrencyInput(value) {
    if (!value) return '';
    // Hapus semua karakter selain angka
    const cleanValue = value.replace(/[^\d]/g, '');
    if (!cleanValue) return '';
    
    // Format dengan pemisah ribuan Indonesia
    return parseInt(cleanValue).toLocaleString('id-ID');
}

// Parse currency dari string ke number
function parseCurrencyInput(value) {
    if (!value) return 0;
    return parseInt(value.replace(/[^\d]/g, '')) || 0;
}

// Event listener untuk input dengan class .rupiah-input
document.addEventListener('DOMContentLoaded', function() {
    // Format input saat user mengetik
    document.querySelectorAll('.rupiah-input').forEach(function(input) {
        input.addEventListener('input', function(e) {
            this.value = formatCurrencyInput(this.value);
        });
        
        input.addEventListener('blur', function(e) {
            this.value = formatCurrencyInput(this.value);
        });
    });

    // Format input Filament dengan prefix Rp
    document.querySelectorAll('input[wire\\:model*="price"], input[wire\\:model*="cost"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            const formatted = formatCurrencyInput(this.value);
            this.value = formatted;
        });
        
        input.addEventListener('blur', function(e) {
            const formatted = formatCurrencyInput(this.value);
            this.value = formatted;
        });
    });
});

// Alpine.js directive untuk currency formatting
document.addEventListener('alpine:init', () => {
    Alpine.directive('currency', (el, { expression }, { evaluate }) => {
        const format = () => {
            el.value = formatCurrencyInput(el.value);
        };
        
        el.addEventListener('input', format);
        el.addEventListener('blur', format);
    });
});

// Untuk Filament form components
window.formatCurrency = function(value) {
    return formatCurrencyInput(value);
};

window.parseCurrency = function(value) {
    return parseCurrencyInput(value);
};

// Global currency formatter
window.currencyFormatter = {
    format: formatCurrencyInput,
    parse: parseCurrencyInput,
    formatRupiah: formatRupiah
};
