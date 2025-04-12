<?php

namespace App\Http\Controllers;

use App\Models\PaymentSale;
use App\Models\PaymentPurchase;
use App\Models\PaymentSaleReturns;
use App\Models\PaymentPurchaseReturns;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;

class PaymentReportController extends Controller
{
    public function getAllPayments(Request $request)
    {
        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sortField = $request->input('SortField', 'date');
        $sortType = $request->input('SortType', 'desc');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $payment_type = $request->input('payment_type');
        $search = $request->input('search');

        $offset = ($page - 1) * $limit;

        // Base query for each payment type with proper joins and calculations
        $salesQuery = PaymentSale::join('sales', 'payment_sales.sale_id', '=', 'sales.id')
            ->join('sale_details', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->where('payment_sales.deleted_at', '=', null)
            ->where('sales.deleted_at', '=', null)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('payment_sales.user_id', '=', Auth::user()->id);
                }
            })
            ->select(
                'payment_sales.date',
                'payment_sales.Ref',
                DB::raw("'sales' as payment_type"),
                'payment_sales.montant',
                'sales.GrandTotal',
                'products.name as product_name',
                DB::raw('CASE
                    WHEN payment_sales.montant >= sales.GrandTotal THEN "paid"
                    WHEN payment_sales.montant > 0 THEN "partial"
                    ELSE "unpaid"
                END as payment_status'),
                'payment_sales.id'
            )
            ->groupBy('payment_sales.id', 'payment_sales.date', 'payment_sales.Ref', 'payment_sales.montant',
                     'sales.GrandTotal', 'products.name', 'payment_sales.id');

        $purchasesQuery = PaymentPurchase::join('purchases', 'payment_purchases.purchase_id', '=', 'purchases.id')
            ->join('purchase_details', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->join('products', 'purchase_details.product_id', '=', 'products.id')
            ->where('payment_purchases.deleted_at', '=', null)
            ->where('purchases.deleted_at', '=', null)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('payment_purchases.user_id', '=', Auth::user()->id);
                }
            })
            ->select(
                'payment_purchases.date',
                'payment_purchases.Ref',
                DB::raw("'purchases' as payment_type"),
                'payment_purchases.montant',
                'purchases.GrandTotal',
                'products.name as product_name',
                DB::raw('CASE
                    WHEN payment_purchases.montant >= purchases.GrandTotal THEN "paid"
                    WHEN payment_purchases.montant > 0 THEN "partial"
                    ELSE "unpaid"
                END as payment_status'),
                'payment_purchases.id'
            )
            ->groupBy('payment_purchases.id', 'payment_purchases.date', 'payment_purchases.Ref', 'payment_purchases.montant',
                     'purchases.GrandTotal', 'products.name', 'payment_purchases.id');

        $saleReturnsQuery = PaymentSaleReturns::join('sale_returns', 'payment_sale_returns.sale_return_id', '=', 'sale_returns.id')
            ->join('sale_return_details', 'sale_returns.id', '=', 'sale_return_details.sale_return_id')
            ->join('products', 'sale_return_details.product_id', '=', 'products.id')
            ->where('payment_sale_returns.deleted_at', '=', null)
            ->where('sale_returns.deleted_at', '=', null)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('payment_sale_returns.user_id', '=', Auth::user()->id);
                }
            })
            ->select(
                'payment_sale_returns.date',
                'payment_sale_returns.Ref',
                DB::raw("'sale_returns' as payment_type"),
                'payment_sale_returns.montant',
                'sale_returns.GrandTotal',
                'products.name as product_name',
                DB::raw('CASE
                    WHEN payment_sale_returns.montant >= sale_returns.GrandTotal THEN "paid"
                    WHEN payment_sale_returns.montant > 0 THEN "partial"
                    ELSE "unpaid"
                END as payment_status'),
                'payment_sale_returns.id'
            )
            ->groupBy('payment_sale_returns.id', 'payment_sale_returns.date', 'payment_sale_returns.Ref', 'payment_sale_returns.montant',
                     'sale_returns.GrandTotal', 'products.name', 'payment_sale_returns.id');

        $purchaseReturnsQuery = PaymentPurchaseReturns::join('purchase_returns', 'payment_purchase_returns.purchase_return_id', '=', 'purchase_returns.id')
            ->join('purchase_return_details', 'purchase_returns.id', '=', 'purchase_return_details.purchase_return_id')
            ->join('products', 'purchase_return_details.product_id', '=', 'products.id')
            ->where('payment_purchase_returns.deleted_at', '=', null)
            ->where('purchase_returns.deleted_at', '=', null)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('payment_purchase_returns.user_id', '=', Auth::user()->id);
                }
            })
            ->select(
                'payment_purchase_returns.date',
                'payment_purchase_returns.Ref',
                DB::raw("'purchase_returns' as payment_type"),
                'payment_purchase_returns.montant',
                'purchase_returns.GrandTotal',
                'products.name as product_name',
                DB::raw('CASE
                    WHEN payment_purchase_returns.montant >= purchase_returns.GrandTotal THEN "paid"
                    WHEN payment_purchase_returns.montant > 0 THEN "partial"
                    ELSE "unpaid"
                END as payment_status'),
                'payment_purchase_returns.id'
            )
            ->groupBy('payment_purchase_returns.id', 'payment_purchase_returns.date', 'payment_purchase_returns.Ref', 'payment_purchase_returns.montant',
                     'purchase_returns.GrandTotal', 'products.name', 'payment_purchase_returns.id');

        // Apply date filters if provided
        if ($start_date && $end_date) {
            $start = Carbon::parse($start_date)->startOfDay();
            $end = Carbon::parse($end_date)->endOfDay();

            $salesQuery->whereBetween('payment_sales.date', [$start, $end]);
            $purchasesQuery->whereBetween('payment_purchases.date', [$start, $end]);
            $saleReturnsQuery->whereBetween('payment_sale_returns.date', [$start, $end]);
            $purchaseReturnsQuery->whereBetween('payment_purchase_returns.date', [$start, $end]);
        }

        // Apply payment type filter if provided
        $queries = [];
        if ($payment_type) {
            switch ($payment_type) {
                case 'sales':
                    $queries[] = $salesQuery;
                    break;
                case 'purchases':
                    $queries[] = $purchasesQuery;
                    break;
                case 'sale_returns':
                    $queries[] = $saleReturnsQuery;
                    break;
                case 'purchase_returns':
                    $queries[] = $purchaseReturnsQuery;
                    break;
                default:
                    $queries = [$salesQuery, $purchasesQuery, $saleReturnsQuery, $purchaseReturnsQuery];
            }
        } else {
            $queries = [$salesQuery, $purchasesQuery, $saleReturnsQuery, $purchaseReturnsQuery];
        }

        // Combine queries
        $query = $queries[0];
        for ($i = 1; $i < count($queries); $i++) {
            $query->union($queries[$i]);
        }

        // Apply search if provided
        if ($search) {
            $query = DB::query()->fromSub($query, 'payments_data')
                ->where(function ($q) use ($search) {
                    $q->where('Ref', 'like', "%{$search}%")
                      ->orWhere('montant', 'like', "%{$search}%")
                      ->orWhere('payment_type', 'like', "%{$search}%")
                      ->orWhere('payment_status', 'like', "%{$search}%");
                });
        }

        // Get total count for pagination
        $totalRows = $query->count();

        // Apply sorting and pagination
        $payments = $query->orderBy($sortField, $sortType)
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'payments' => $payments,
            'totalRows' => $totalRows
        ]);
    }
}
