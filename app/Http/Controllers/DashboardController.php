<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Expense;
use App\Models\InsuranceClaim;
use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'cashier') {
            return redirect()->route('pharmacy.cashier');
        }

        $today = today();
        $startOfWeek = $today->copy()->subDays(6)->startOfDay();
        $sales = $this->scopeBranch(Sale::query(), $user);
        $purchases = $this->scopeBranch(Purchase::query(), $user);
        $expenses = $this->scopeBranch(Expense::query(), $user);
        $batches = $this->scopeBranch(Batch::query(), $user);

        $weeklySales = $this->scopeBranch(Sale::query(), $user)
            ->whereBetween('created_at', [$startOfWeek, $today->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as sales_day, SUM(grand_total) as sales_total')
            ->groupBy('sales_day')
            ->pluck('sales_total', 'sales_day');

        $chart = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $weeklySales): array {
            $date = $today->copy()->subDays($daysAgo);

            return [
                'label' => $date->locale(app()->getLocale())->translatedFormat('D'),
                'date' => $date->toDateString(),
                'total' => (float) ($weeklySales[$date->toDateString()] ?? 0),
            ];
        });

        $maxChartValue = max(1, (float) $chart->max('total'));

        $lowStock = $user->branch_id === null && $user->role !== 'admin'
            ? collect()
            : Medicine::query()
                ->leftJoin('batches', function (JoinClause $join) use ($user, $today): void {
                    $join->on('batches.medicine_id', '=', 'medicines.id')
                        ->where('batches.is_active', true)
                        ->where('batches.remaining_quantity_base', '>', 0)
                        ->whereDate('batches.expiry_date', '>=', $today);

                    if ($user->branch_id !== null) {
                        $join->where('batches.branch_id', $user->branch_id);
                    }
                })
                ->where('medicines.is_active', true)
                ->select([
                    'medicines.id',
                    'medicines.trade_name',
                    'medicines.min_stock_alert',
                ])
                ->selectRaw('COALESCE(SUM(batches.remaining_quantity_base), 0) as available_stock')
                ->groupBy('medicines.id', 'medicines.trade_name', 'medicines.min_stock_alert')
                ->havingRaw('COALESCE(SUM(batches.remaining_quantity_base), 0) <= medicines.min_stock_alert')
                ->orderBy('available_stock')
                ->limit(5)
                ->get();

        $recentSales = $this->scopeBranch(Sale::query(), $user)
            ->with(['customer:id,name', 'user:id,name'])
            ->latest()
            ->limit(6)
            ->get();

        $pendingClaims = $user->hasAnyRole(['admin', 'accountant'])
            ? InsuranceClaim::query()
                ->whereHas('sale', fn (Builder $query) => $this->applyBranchScope($query, $user))
                ->where('status', 'pending')
                ->count()
            : null;

        $todaySalesQuery = clone $sales;
        $todayPurchasesQuery = clone $purchases;
        $todayExpensesQuery = clone $expenses;
        $todaySalesCountQuery = clone $sales;

        return view('pharmacy.dashboard', [
            'todaySales' => (float) $todaySalesQuery->whereDate('created_at', $today)->sum('grand_total'),
            'todaySalesCount' => (int) $todaySalesCountQuery->whereDate('created_at', $today)->count(),
            'todayPurchases' => (float) $todayPurchasesQuery->whereDate('created_at', $today)->sum('grand_total'),
            'todayExpenses' => (float) $todayExpensesQuery->whereDate('created_at', $today)->sum('amount'),
            'medicineCount' => Medicine::query()
                ->when($user->branch_id === null && $user->role !== 'admin', fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->count(),
            'availableStock' => (int) $batches
                ->where('is_active', true)
                ->whereDate('expiry_date', '>=', $today)
                ->sum('remaining_quantity_base'),
            'lowStock' => $lowStock,
            'recentSales' => $recentSales,
            'chart' => $chart,
            'chartMax' => $maxChartValue,
            'pendingClaims' => $pendingClaims,
            'currency' => Setting::query()->value('currency_code') ?: 'SDG',
            'todayLabel' => Carbon::now()->locale(app()->getLocale())->translatedFormat(
                app()->getLocale() === 'ar' ? 'l، j F Y' : 'l, F j, Y',
            ),
        ]);
    }

    private function scopeBranch(Builder $query, User $user): Builder
    {
        return $this->applyBranchScope($query, $user);
    }

    private function applyBranchScope(Builder $query, User $user): Builder
    {
        if ($user->branch_id !== null) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($user->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
