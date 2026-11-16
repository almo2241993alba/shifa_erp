<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
                'to_date' => __('The end date must be on or after the start date.'),
            ]);
        }

        if ($fromDate->diffInDays($toDate) > 366) {
            throw ValidationException::withMessages([
                'to_date' => __('Choose a date range of no more than one year.'),
            ]);
        }

        $user = $request->user();
        $startAt = $fromDate->copy()->startOfDay();
        $endAt = $toDate->copy()->endOfDay();
        $userOnly = $cashierReport || $user->role === 'cashier';
        $sales = Sale::query()
            ->with(['customer:id,name', 'user:id,name'])
            ->whereBetween('created_at', [$startAt, $endAt]);

        if ($user->branch_id !== null) {
            $sales->where('branch_id', $user->branch_id);
        } elseif ($user->role !== 'admin') {
            $sales->whereRaw('1 = 0');
        }

        if ($userOnly) {
            $sales->where('user_id', $user->id);
        }

        $returns = SaleReturn::query()
            ->with(['sale:id,invoice_number', 'user:id,name'])
            ->whereBetween('created_at', [$startAt, $endAt])
            ->when($user->branch_id !== null, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            ->when($user->branch_id === null && $user->role !== 'admin', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($userOnly, fn (Builder $query) => $query->where('user_id', $user->id));

        $expenses = Expense::query()
            ->with(['expenseType:id,type_name', 'user:id,name', 'treasury:id,name'])
            ->whereBetween('expense_date', [$from, $to])
            ->when($user->branch_id !== null, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            ->when($user->branch_id === null && $user->role !== 'admin', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($userOnly, fn (Builder $query) => $query->where('user_id', $user->id));

        $sessions = PosSession::query()
            ->with(['user:id,name', 'treasury:id,name', 'closingTreasury:id,name'])
            ->whereBetween('opened_at', [$startAt, $endAt])
            ->when($user->branch_id !== null, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            ->when($user->branch_id === null && $user->role !== 'admin', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($userOnly, fn (Builder $query) => $query->where('user_id', $user->id));

        $payments = SalePayment::query()
            ->with('treasury:id,name')
            ->whereHas('sale', function (Builder $query) use ($startAt, $endAt, $user, $userOnly): void {
                $query->whereBetween('created_at', [$startAt, $endAt]);
                if ($user->branch_id !== null) {
                    $query->where('branch_id', $user->branch_id);
                } elseif ($user->role !== 'admin') {
                    $query->whereRaw('1 = 0');
                }
                if ($userOnly) {
                    $query->where('user_id', $user->id);
                }
            });

        $summary = (clone $sales)
            ->selectRaw('COUNT(*) as invoice_count, COALESCE(SUM(grand_total), 0) as gross_total, COALESCE(SUM(paid_amount), 0) as paid_total')
            ->first();

        $daily = (clone $sales)
            ->selectRaw('DATE(created_at) as sales_date, COUNT(*) as invoice_count, SUM(grand_total) as gross_total, SUM(paid_amount) as paid_total')
            ->groupBy('sales_date')
            ->orderBy('sales_date')
            ->get();

        $paymentBreakdown = (clone $payments)
            ->selectRaw('payment_method, treasury_id, COALESCE(SUM(amount), 0) as amount, COUNT(*) as payment_count')
            ->groupBy('payment_method', 'treasury_id')
            ->orderBy('payment_method')
            ->get();

        $sessionSummary = (clone $sessions)
            ->selectRaw('COUNT(*) as session_count, COALESCE(SUM(cash_transfer_amount), 0) as transferred_total, COALESCE(SUM(difference), 0) as variance_total')
            ->first();

        return view('pharmacy.reports', [
            'title' => $cashierReport ? __('Cashier reports') : __('Sales reports'),
            'isCashierReport' => $cashierReport,
            'fromDate' => $from,
            'toDate' => $to,
            'currency' => Setting::query()->value('currency_code') ?: 'SDG',
            'summary' => $summary,
            'daily' => $daily,
            'sales' => (clone $sales)->latest()->paginate(25)->withQueryString(),
            'returns' => (clone $returns)->latest()->limit(50)->get(),
            'expenses' => (clone $expenses)->latest('expense_date')->limit(50)->get(),
            'sessions' => (clone $sessions)->latest('opened_at')->limit(50)->get(),
            'paymentBreakdown' => $paymentBreakdown,
            'returnTotal' => (clone $returns)->sum('total_refund_amount'),
            'expenseTotal' => (clone $expenses)
                ->selectRaw('COALESCE(SUM(amount + amount_tax), 0) as total')
                ->value('total'),
            'sessionSummary' => $sessionSummary,
        ]);
    }
}
