<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EvolutionInstance;
use App\Models\Order;
use App\Models\OutboundMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformDashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->startOfDay();

        return view('admin.dashboard', [
            'metrics' => [
                'tenants' => Tenant::count(),
                'active_tenants' => Tenant::where('status', 'active')->count(),
                'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
                'new_tenants' => Tenant::where('created_at', '>=', $today)->count(),
                'orders_today' => Order::where('created_at', '>=', $today)->whereNotIn('status', ['cancelled', 'refunded'])->count(),
                'gmv_today' => Order::where('created_at', '>=', $today)->whereNotIn('status', ['cancelled', 'refunded'])->sum('total'),
                'whatsapp_connected' => EvolutionInstance::where('status', 'connected')->count(),
                'whatsapp_attention' => EvolutionInstance::whereIn('status', ['disconnected', 'degraded'])->count(),
                'message_failures' => OutboundMessage::where('status', 'failed')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
            'recentTenants' => Tenant::with('venues')->latest()->limit(8)->get(),
            'recentAudits' => AuditLog::latest()->limit(8)->get(),
            'owners' => User::where('role', 'owner')->count(),
        ]);
    }
}
