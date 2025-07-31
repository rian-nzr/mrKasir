<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function processTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Get current store
            $user = Auth::user();
            $currentStoreId = $user->isSuperAdmin() ? Session::get('selected_store_id') : $user->store_id;
            
            if (!$currentStoreId) {
                throw ValidationException::withMessages(['store' => 'Store tidak ditemukan']);
            }

            // Prepare transaction data
            $transactionData = array_merge($data, [
                'store_id' => $currentStoreId,
                'user_id' => $user->id,
                'status' => 'completed'
            ]);

            // Normalize data based on transaction type
            $transactionData = $this->normalizeTransactionData($transactionData);

            // Process based on transaction type
            $financialImpact = $this->processFinancialImpact($data, $currentStoreId);
            $transactionData['financial_impact'] = $financialImpact;

            // Create transaction record
            $transaction = Transaction::create($transactionData);

            return $transaction;
        });
    }

    private function processFinancialImpact(array $data, int $storeId): array
    {
        $impact = [];
        $type = $data['type'];

        switch ($type) {
            case 'transfer':
                $impact = $this->processTransferImpact($data, $storeId);
                break;
            
            case 'tarik_tunai':
                $impact = $this->processTarikTunaiImpact($data, $storeId);
                break;
            
            case 'jasa_transfer':
                $impact = $this->processJasaTransferImpact($data, $storeId);
                break;
            
            case 'mode_pulsa':
                $impact = $this->processModePulsaImpact($data, $storeId);
                break;
        }

        return $impact;
    }

    private function processTransferImpact(array $data, int $storeId): array
    {
        $impact = [];
        $sumberDanaId = $data['sumber_dana_id'];
        $amount = $data['amount'];
        $adminLuar = $data['admin_luar'] ?? 0;
        $adminDalam = $data['admin_dalam'] ?? 0;

        // Kurangi saldo sumber dana sebesar amount
        if ($sumberDanaId) {
            $sumberDana = PaymentMethod::where('store_id', $storeId)->findOrFail($sumberDanaId);
            $oldBalance = $sumberDana->balance;
            $sumberDana->decrement('balance', $amount);
            
            $impact['sumber_dana'] = [
                'payment_method_id' => $sumberDanaId,
                'old_balance' => $oldBalance,
                'new_balance' => $sumberDana->fresh()->balance,
                'change' => -$amount
            ];
        }

        // Tambahkan admin dalam ke sumber dana
        if ($adminDalam > 0 && $sumberDanaId) {
            $sumberDana = PaymentMethod::where('store_id', $storeId)->find($sumberDanaId);
            if ($sumberDana) {
                $sumberDana->increment('balance', $adminDalam);
                $impact['admin_dalam'] = [
                    'payment_method_id' => $sumberDanaId,
                    'amount' => $adminDalam
                ];
            }
        }

        // Tambahkan admin luar ke cash
        if ($adminLuar > 0) {
            $cash = PaymentMethod::where('store_id', $storeId)->where('is_cash', true)->first();
            if ($cash) {
                $cash->increment('balance', $adminLuar);
                $impact['admin_luar'] = [
                    'payment_method_id' => $cash->id,
                    'amount' => $adminLuar
                ];
            }
        }

        // Record profit
        $totalProfit = $adminLuar + $adminDalam;
        $impact['profit'] = $totalProfit;

        return $impact;
    }

    private function processTarikTunaiImpact(array $data, int $storeId): array
    {
        $impact = [];
        $sumberDanaId = $data['sumber_dana_id'];
        $tujuanDanaId = $data['tujuan_dana_id'] ?? null;
        $amount = $data['amount'];
        $adminLuar = $data['admin_luar'] ?? 0;
        $adminDalam = $data['admin_dalam'] ?? 0;

        // Reduce balance from source (sumber dana)
        if ($sumberDanaId) {
            $sumberDana = PaymentMethod::where('store_id', $storeId)->findOrFail($sumberDanaId);
            $oldBalance = $sumberDana->balance;
            $sumberDana->decrement('balance', $amount);
            
            $impact['sumber_dana'] = [
                'payment_method_id' => $sumberDanaId,
                'old_balance' => $oldBalance,
                'new_balance' => $sumberDana->fresh()->balance,
                'change' => -$amount
            ];
        }

        // Add balance to destination (tujuan penarikan)
        if ($tujuanDanaId) {
            $tujuanDana = PaymentMethod::where('store_id', $storeId)->findOrFail($tujuanDanaId);
            $oldBalanceTujuan = $tujuanDana->balance;
            $tujuanDana->increment('balance', $amount);
            
            $impact['tujuan_dana'] = [
                'payment_method_id' => $tujuanDanaId,
                'old_balance' => $oldBalanceTujuan,
                'new_balance' => $tujuanDana->fresh()->balance,
                'change' => $amount
            ];
        }

        // Admin dalam (profit ke sumber dana)
        if ($adminDalam > 0 && $sumberDanaId) {
            $sumberDana = PaymentMethod::where('store_id', $storeId)->find($sumberDanaId);
            if ($sumberDana) {
                $sumberDana->increment('balance', $adminDalam);
                $impact['admin_dalam'] = [
                    'payment_method_id' => $sumberDanaId,
                    'amount' => $adminDalam
                ];
            }
        }

        // Admin luar (profit ke cash)
        if ($adminLuar > 0) {
            $cash = PaymentMethod::where('store_id', $storeId)->where('is_cash', true)->first();
            if ($cash) {
                $cash->increment('balance', $adminLuar);
                $impact['admin_luar'] = [
                    'payment_method_id' => $cash->id,
                    'amount' => $adminLuar
                ];
            }
        }

        $totalProfit = $adminLuar + $adminDalam;
        $impact['profit'] = $totalProfit;

        return $impact;
    }

    private function processJasaTransferImpact(array $data, int $storeId): array
    {
        $impact = [];
        $jumlah = $data['terima_dana'] ?? 0; // Menggunakan field yang sama untuk backward compatibility
        $admin = $data['admin'] ?? 0;
        $totalKurang = $jumlah + $admin; // Total yang dikurangi dari cash

        // Kurangi total (jumlah + admin) dari cash
        $cash = PaymentMethod::where('store_id', $storeId)->where('is_cash', true)->first();
        if ($cash && $totalKurang > 0) {
            $oldBalance = $cash->balance;
            $cash->decrement('balance', $totalKurang);
            
            $impact['sumber_dana'] = [
                'payment_method_id' => $cash->id,
                'old_balance' => $oldBalance,
                'new_balance' => $cash->fresh()->balance,
                'change' => -$totalKurang
            ];
        }

        // Record profit from admin
        $impact['profit'] = $admin;
        $impact['jumlah_transfer'] = $jumlah;
        $impact['admin_fee'] = $admin;
        $impact['total_dikurangi'] = $totalKurang;

        return $impact;
    }

    private function processModePulsaImpact(array $data, int $storeId): array
    {
        $impact = [];
        $modal = $data['modal'] ?? 0;
        $hargaJual = $data['harga_jual'] ?? 0;
        $admin = $data['admin'] ?? 0;

        // Kurangi modal dari cash atau sumber dana yang dipilih
        if ($modal > 0) {
            // Prioritas: cari payment method yang dipilih, jika tidak ada gunakan cash
            $paymentMethod = null;
            
            if (!empty($data['sumber_dana_id'])) {
                $paymentMethod = PaymentMethod::where('store_id', $storeId)->find($data['sumber_dana_id']);
            }
            
            if (!$paymentMethod) {
                $paymentMethod = PaymentMethod::where('store_id', $storeId)->where('is_cash', true)->first();
            }

            if ($paymentMethod) {
                $oldBalance = $paymentMethod->balance;
                $paymentMethod->decrement('balance', $modal);
                
                $impact['modal'] = [
                    'payment_method_id' => $paymentMethod->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $paymentMethod->fresh()->balance,
                    'change' => -$modal
                ];
            }
        }

        // Tambahkan hasil penjualan ke cash
        if ($hargaJual > 0) {
            $cash = PaymentMethod::where('store_id', $storeId)->where('is_cash', true)->first();
            if ($cash) {
                $cash->increment('balance', $hargaJual);
                $impact['harga_jual'] = [
                    'payment_method_id' => $cash->id,
                    'amount' => $hargaJual
                ];
            }
        }

        // Tambahkan admin ke cash (jika ada)
        if ($admin > 0) {
            $cash = PaymentMethod::where('store_id', $storeId)->where('is_cash', true)->first();
            if ($cash) {
                $cash->increment('balance', $admin);
                $impact['admin'] = [
                    'payment_method_id' => $cash->id,
                    'amount' => $admin
                ];
            }
        }

        // Calculate profit (termasuk admin)
        $profit = $hargaJual - $modal + $admin;
        $impact['profit'] = $profit;

        return $impact;
    }

    public function validateTransactionData(array $data): array
    {
        $type = $data['type'] ?? '';
        
        $rules = [
            'type' => 'required|in:transfer,tarik_tunai,jasa_transfer,mode_pulsa',
            'keterangan' => 'nullable|string|max:1000',
        ];

        switch ($type) {
            case 'transfer':
                $rules = array_merge($rules, [
                    'sumber_dana_id' => 'required|exists:payment_methods,id',
                    'amount' => 'required|numeric|min:0',
                    'admin_luar' => 'nullable|numeric|min:0',
                    'admin_dalam' => 'nullable|numeric|min:0',
                ]);
                break;

            case 'tarik_tunai':
                $rules = array_merge($rules, [
                    'sumber_dana_id' => 'required|exists:payment_methods,id',
                    'amount' => 'required|numeric|min:0',
                    'tujuan_dana_id' => 'required|exists:payment_methods,id',
                    'admin_luar' => 'nullable|numeric|min:0',
                    'admin_dalam' => 'nullable|numeric|min:0',
                ]);
                break;

            case 'jasa_transfer':
                $rules = array_merge($rules, [
                    'terima_dana' => 'required|numeric|min:0',
                    'admin' => 'nullable|numeric|min:0',
                    'jenis_transaksi' => 'nullable|string|max:255',
                ]);
                break;

            case 'mode_pulsa':
                $rules = array_merge($rules, [
                    'sumber_dana_id' => 'nullable|exists:payment_methods,id',
                    'jenis_transaksi' => 'required|string|max:255',
                    'sumber' => 'required|string|max:255',
                    'modal' => 'required|numeric|min:0',
                    'harga_jual' => 'required|numeric|min:0',
                    'admin' => 'nullable|numeric|min:0',
                ]);
                break;
        }

        return $rules;
    }

    private function normalizeTransactionData(array $data): array
    {
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'jasa_transfer':
                // For jasa_transfer, amount should be the same as terima_dana
                $data['amount'] = $data['terima_dana'] ?? 0;
                break;
                
            case 'mode_pulsa':
                // For mode_pulsa, amount should be the same as harga_jual
                $data['amount'] = $data['harga_jual'] ?? 0;
                break;
                
            // transfer and tarik_tunai already have amount field filled
        }

        return $data;
    }
}
