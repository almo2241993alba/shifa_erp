<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        return $this->report($request, false);
    }

    public function cashier(Request $request): View
    {
        return $this->report($request, true);
    }

    private function report(Request $request, bool $cashierReport): View
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $from = $validated['from_date'] ?? today()->startOfMonth()->toDateString();
        $to = $validated['to_date'] ?? today()->toDateString();
        $fromDate = Carbon::createFromFormat('!Y-m-d', $from);
        $toDate = Carbon::createFromFormat('!Y-m-d', $to);

        if ($toDate->lt($fromDate)) {
            throw ValidationException::withMessages([
                'to_date' => 'يجب أن يكون تاريخ النهاية مساوياً لتاريخ البداية أو بعده.',
            ]);
        }

        if ($fromDate->diffInDays($toDate) > 366) {
            throw ValidationException::withMessages([
                'to_date' => 'اختر فترة لا تتجاوز سنة واحدة.',
            ]);
        }

        $user = $request->user();
        $sales = Sale::query()
            ->with(['customer:id,name', 'user:id,name'])
            ->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);

        if ($user->branch_id !== null) {
            $sales->where('branch_id', $user->branch_id);
        } elseif ($user->role !== 'admin') {
            $sales->whereRaw('1 = 0');
        }

        if ($cashierReport || $user->role === 'cashier') {
            $sales->where('user_id', $user->id);
        }

        $summary = (clone $sales)
            ->selectRaw('COUNT(*) as invoice_count, COALESCE(SUM(grand_total), 0) as gross_total, COALESCE(SUM(paid_amount), 0) as paid_total')
            ->first();

        $daily = (clone $sales)
            ->selectRaw('DATE(created_at) as sales_date, COUNT(*) as invoice_count, SUM(grand_total) as gross_total, SUM(paid_amount) as paid_total')
            ->groupBy('sales_date')
            ->orderBy('sales_date')
            ->get();

        return view('pharmacy.reports', [
            'title' => $cashierReport ? 'تقارير الكاشير' : 'تقارير المبيعات',
            'isCashierReport' => $cashierReport,
            'fromDate' => $from,
            'toDate' => $to,
            'currency' => Setting::query()->value('currency_code') ?: 'SDG',
            'summary' => $summary,
            'daily' => $daily,
            'sales' => (clone $sales)->latest()->paginate(25)->withQueryString(),
        ]);
    }
}
