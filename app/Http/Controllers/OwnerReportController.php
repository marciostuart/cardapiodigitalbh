<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OwnerReportController extends Controller
{
    public function index(Request $request)
    {
        [$venue, $from, $to, $orders] = $this->query($request);
        $summary = (clone $orders)->selectRaw('count(*) as orders_count, coalesce(sum(total),0) as revenue, coalesce(avg(total),0) as average_ticket')->first();
        $channels = (clone $orders)->selectRaw('channel, count(*) as orders_count, sum(total) as revenue')->groupBy('channel')->orderByDesc('revenue')->get();
        $products = OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')->where('orders.venue_id', $venue->id)->whereBetween('orders.created_at', [$from, $to])->whereNotIn('orders.status', ['cancelled', 'refunded'])->selectRaw('order_items.name_snapshot, sum(order_items.quantity) as quantity, sum(order_items.total) as revenue')->groupBy('order_items.name_snapshot')->orderByDesc('quantity')->limit(20)->get();
        return view('owner.reports', compact('venue', 'from', 'to', 'summary', 'channels', 'products'));
    }

    public function csv(Request $request)
    {
        [$venue, $from, $to, $orders] = $this->query($request);
        $filename = 'pedidos-'.$venue->subdomain.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';
        return response()->streamDownload(function () use ($orders) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Pedido', 'Data', 'Canal', 'Status', 'Cliente', 'Subtotal', 'Desconto', 'Entrega', 'Total', 'Pagamento'], ';');
            $orders->orderBy('created_at')->chunkById(500, function ($rows) use ($output) {
                foreach ($rows as $order) fputcsv($output, [$order->id, $order->created_at->format('d/m/Y H:i'), $order->channel, $order->status, $order->customer_name, number_format((float) $order->subtotal, 2, ',', ''), number_format((float) $order->discount, 2, ',', ''), number_format((float) $order->delivery_fee, 2, ',', ''), number_format((float) $order->total, 2, ',', ''), $order->payment_method], ';');
            });
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query(Request $request): array
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now())->endOfDay();
        abort_if($from->diffInDays($to) > 366, 422, 'O período máximo do relatório é de 366 dias.');
        return [$venue, $from, $to, Order::where('venue_id', $venue->id)->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled', 'refunded'])];
    }
}
