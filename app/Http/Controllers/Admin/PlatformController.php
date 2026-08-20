<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Tenant; use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;
class PlatformController extends Controller {
 public function index(){ return view('admin.platform',['tenants'=>Tenant::with('venues')->latest()->get()]); }
 public function impersonate(Request $request, Tenant $tenant){ $user=User::where('tenant_id',$tenant->id)->where('role','owner')->firstOrFail(); $request->session()->put('impersonator_id',Auth::id()); Auth::login($user); $request->session()->regenerate(); return redirect()->route('dashboard')->with('status','Você está visualizando a conta do cliente.'); }
 public function leave(Request $request){ $id=$request->session()->pull('impersonator_id'); abort_unless($id,403); $owner=User::whereKey($id)->where('role','platform_owner')->firstOrFail(); Auth::login($owner); $request->session()->regenerate(); return redirect()->route('admin.platform'); }
}
