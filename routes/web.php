<?php

use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\PlatformController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\KitchenDisplayController;
use App\Http\Controllers\OperationsDashboardController;
use App\Http\Controllers\OwnerCatalogController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\OwnerSettingsController;
use App\Http\Controllers\OwnerTableController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\QrMenuController;
use App\Http\Controllers\TableOrderController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.cardapiodigitalbh.com.br')->middleware('tenant')->group(function () {
    Route::get('/', [PublicMenuController::class, 'show'])->name('public.menu');
    Route::get('/menu', fn () => redirect('/'))->name('public.menu.legacy');
});

Route::get('/', function () {
    return view('marketing.home');
});

Route::view('/cardapio-digital-em-belo-horizonte', 'marketing.local-seo')->name('marketing.local-seo');
Route::view('/termos-de-uso', 'marketing.terms')->name('marketing.terms');
Route::view('/politica-de-privacidade', 'marketing.privacy')->name('marketing.privacy');
Route::get('/sitemap.xml', function () {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc>https://cardapiodigitalbh.com.br/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
<url><loc>https://cardapiodigitalbh.com.br/cardapio-digital-em-belo-horizonte</loc><changefreq>monthly</changefreq><priority>0.9</priority></url>
<url><loc>https://cardapiodigitalbh.com.br/register</loc><changefreq>monthly</changefreq><priority>0.7</priority></url>
<url><loc>https://cardapiodigitalbh.com.br/login</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
<url><loc>https://cardapiodigitalbh.com.br/politica-de-privacidade</loc><changefreq>yearly</changefreq><priority>0.3</priority></url>
<url><loc>https://cardapiodigitalbh.com.br/termos-de-uso</loc><changefreq>yearly</changefreq><priority>0.3</priority></url>
</urlset>
XML;

    return response($xml)->header('Content-Type', 'application/xml');
})->name('marketing.sitemap');
Route::get('/m/{token}', [QrMenuController::class, 'show'])->name('public.qr-menu');
Route::post('/m/{token}/orders', [TableOrderController::class, 'storeByToken'])->middleware('throttle:30,1')->name('public.qr-order');

Route::get('/dashboard', OwnerDashboardController::class)
    ->middleware(['auth', 'account.active'])
    ->name('dashboard');

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::middleware('platform.owner')->group(function () {
        Route::get('/admin', [PlatformController::class, 'index'])->name('admin.platform');
        Route::post('/admin/estabelecimentos/{tenant}/acessar', [PlatformController::class, 'impersonate'])->name('admin.impersonate');
        Route::patch('/admin/estabelecimentos/{tenant}/plano', [PlatformController::class, 'updatePlan'])->name('admin.plan.update');
        Route::get('/admin/emails', [EmailTemplateController::class, 'index'])->name('admin.email-templates.index');
        Route::patch('/admin/emails/{template}', [EmailTemplateController::class, 'update'])->name('admin.email-templates.update');
    });
    Route::post('/admin/retornar', [PlatformController::class, 'leave'])->name('admin.leave');
    Route::middleware('role:owner')->group(function () {
        Route::get('/painel/cardapio', [OwnerCatalogController::class, 'index'])->name('owner.catalog');
        Route::post('/painel/categorias', [OwnerCatalogController::class, 'storeCategory'])->name('owner.categories.store');
        Route::patch('/painel/categorias/{category}', [OwnerCatalogController::class, 'updateCategory'])->name('owner.categories.update');
        Route::delete('/painel/categorias/{category}', [OwnerCatalogController::class, 'destroyCategory'])->name('owner.categories.destroy');
        Route::post('/painel/produtos', [OwnerCatalogController::class, 'storeProduct'])->name('owner.products.store');
        Route::patch('/painel/produtos/{product}', [OwnerCatalogController::class, 'updateProduct'])->name('owner.products.update');
        Route::delete('/painel/produtos/{product}', [OwnerCatalogController::class, 'destroyProduct'])->name('owner.products.destroy');
        Route::get('/painel/mesas', [OwnerTableController::class, 'index'])->name('owner.tables');
        Route::post('/painel/mesas', [OwnerTableController::class, 'store'])->name('owner.tables.store');
        Route::get('/painel/mesas/{table}/qr.svg', [OwnerTableController::class, 'qr'])->name('owner.tables.qr');
        Route::get('/painel/equipe', [TeamController::class, 'index'])->name('owner.team');
        Route::post('/painel/equipe', [TeamController::class, 'store'])->name('owner.team.store');
        Route::patch('/painel/equipe/{member}', [TeamController::class, 'update'])->name('owner.team.update');
        Route::patch('/painel/equipe/{member}/senha', [TeamController::class, 'resetPassword'])->name('owner.team.password');
        Route::get('/painel/configuracoes', [OwnerSettingsController::class, 'edit'])->name('owner.settings');
        Route::patch('/painel/configuracoes', [OwnerSettingsController::class, 'update'])->name('owner.settings.update');
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware('tenant')->post('/tables/{table}/orders', [TableOrderController::class, 'store']);
Route::middleware(['auth', 'account.active', 'tenant', 'venue.access'])->group(function () {
    Route::middleware('role:owner,cashier')->group(function () {
        Route::get('/caixa', [CashierController::class, 'index'])->name('cashier.index');
        Route::post('/tabs/{tab}/payments', [CashierController::class, 'pay'])->name('cashier.pay');
    });
    Route::middleware('role:owner,kitchen')->group(function () {
        Route::get('/cozinha', [KitchenDisplayController::class, 'index'])->name('kds.index');
        Route::patch('/cozinha/itens/{item}', [KitchenDisplayController::class, 'update'])->name('kds.items.update');
    });
    Route::get('/operacao', [OperationsDashboardController::class, 'index'])->middleware('role:owner,waiter')->name('operations.index');
});
