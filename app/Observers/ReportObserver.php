<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Report;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReportObserver
{
    /**
     * Handle the Report "creating" event.
     */
    public function creating(Report $report): void
    {       
            $logo = optional(Setting::current())->image;
            $timestamp = now()->format('YmdHis');

            // Buat nama file dan path
            $fileName = 'LAPORAN' . $timestamp;
            $path = 'reports/' . $fileName;

            if ($report->report_type == 'pemasukan') {
                // Ambil data Order sesuai start_date, end_date, dan store_id
                $data = Order::query()
                    ->when($report->start_date, fn ($q) => $q->whereDate('updated_at', '>=', $report->start_date))
                    ->when($report->end_date, fn ($q) => $q->whereDate('updated_at', '<=', $report->end_date))
                    ->when($report->store_id, fn ($q) => $q->where('store_id', $report->store_id))
                    ->get();

                // Generate PDF
                $pdf = Pdf::loadView('pdf.reports.pemasukan', [
                    'report' => $report,
                    'data' => $data,
                    'logo' => $logo,
                ])->setPaper('a4', 'portrait');

            } else {
                 // Ambil data Expense sesuai start_date, end_date, dan store_id
                 $data = Expense::query()
                 ->when($report->start_date, fn ($q) => $q->whereDate('updated_at', '>=', $report->start_date))
                 ->when($report->end_date, fn ($q) => $q->whereDate('updated_at', '<=', $report->end_date))
                 ->when($report->store_id, fn ($q) => $q->where('store_id', $report->store_id))
                 ->get();

                 // Generate PDF
                $pdf = Pdf::loadView('pdf.reports.pengeluaran', [
                    'report' => $report,
                    'data' => $data,
                    'logo' => $logo,
                ])->setPaper('a4', 'portrait');
            }

            // Pastikan folder 'storage/app/public/reports' ada
            $pathDirectory = storage_path('app/public/reports');
            if (!file_exists($pathDirectory)) {
                mkdir($pathDirectory, 0755, true);
            }

            // Simpan PDF ke storage
            $fullPath = storage_path('app/public/' . $path);
            $pdf->save($fullPath);

            // Set nama dan path_file ke model
            $report->name = $fileName;
            $report->path_file = $path;
    }

    /**
     * Handle the Report "update" event.
     */
    public function updated(Report $report): void
    {
            $logo = optional(Setting::current())->image;
            // Buat nama file dan path
            $path = 'reports/' . $report->name;

            if ($report->report_type == 'pemasukan') {
                // Ambil data Order sesuai start_date, end_date, dan store_id
                $data = Order::query()
                    ->when($report->start_date, fn ($q) => $q->whereDate('updated_at', '>=', $report->start_date))
                    ->when($report->end_date, fn ($q) => $q->whereDate('updated_at', '<=', $report->end_date))
                    ->when($report->store_id, fn ($q) => $q->where('store_id', $report->store_id))
                    ->get();

                // Generate PDF
                $pdf = Pdf::loadView('pdf.reports.pemasukan', [
                    'report' => $report,
                    'data' => $data,
                    'logo' => $logo,
                ])->setPaper('a4', 'portrait');

            } else {
                 // Ambil data Expense sesuai start_date, end_date, dan store_id
                 $data = Expense::query()
                 ->when($report->start_date, fn ($q) => $q->whereDate('updated_at', '>=', $report->start_date))
                 ->when($report->end_date, fn ($q) => $q->whereDate('updated_at', '<=', $report->end_date))
                 ->when($report->store_id, fn ($q) => $q->where('store_id', $report->store_id))
                 ->get();

                 // Generate PDF
                $pdf = Pdf::loadView('pdf.reports.pengeluaran', [
                    'report' => $report,
                    'data' => $data,
                    'logo' => $logo,
                ])->setPaper('a4', 'portrait');
            }

            // Pastikan folder 'storage/app/public/reports' ada
            $pathDirectory = storage_path('app/public/reports');
            if (!file_exists($pathDirectory)) {
                mkdir($pathDirectory, 0755, true);
            }

            // Simpan PDF ke storage
            $fullPath = storage_path('app/public/' . $path);
            $pdf->save($fullPath);

    }

    /**
     * Handle the Report "deleted" event.
     */
    public function deleted(Report $report): void
    {
        // Misal file PDF disimpan di storage/app/public/orders-pdf/{order_number}.pdf
        $pdfPath = 'public/reports/' . $report->name;

        if (Storage::exists($pdfPath)) {
            Storage::delete($pdfPath);
        }
    }
   
}
