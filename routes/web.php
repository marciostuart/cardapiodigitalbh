<?php

use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PlatformController;
use App\Http\Controllers\Admin\PlatformDashboardController;
use App\Http\Controllers\Admin\WhatsappSupportController;
use App\Http\Controllers\Admin\WhatsappTemplateController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ConsumerAccountController;
use App\Http\Controllers\EvolutionWebhookController;
use App\Http\Controllers\KitchenDisplayController;
use App\Http\Controllers\OperationsDashboardController;
use App\Http\Controllers\OwnerBannerController;
use App\Http\Controllers\OwnerCatalogController;
use App\Http\Controllers\OwnerCommerceController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\OwnerInventoryController;
use App\Http\Controllers\OwnerOrderController;
use App\Http\Controllers\OwnerReportController;
use App\Http\Controllers\OwnerScheduleController;
use App\Http\Controllers\OwnerSettingsController;
use App\Http\Controllers\OwnerTableController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\PublicAgentController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\PublicPwaController;
use App\Http\Controllers\QrMenuController;
use App\Http\Controllers\TableOrderController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WhatsappIntegrationController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.cardapiodigitalbh.com.br')->middleware('tenant')->group(function () {
    Route::get('/', [PublicMenuController::class, 'show'])->name('public.menu');
    Route::get('/llms.txt', [PublicAgentController::class, 'tenant'])->name('public.llms');
    Route::get('/menu', fn () => redirect('/'))->name('public.menu.legacy');
    Route::get('/mesa/{token}', [QrMenuController::class, 'show'])->name('public.tenant-table');
    Route::post('/mesa/{token}/pedidos', [TableOrderController::class, 'storeByToken'])->middleware('throttle:30,1')->name('public.tenant-table-order');
    Route::post('/pedidos', [PublicOrderController::class, 'store'])->middleware('throttle:30,1')->name('public.orders.store');
    Route::post('/conta/cadastro', [ConsumerAccountController::class, 'register'])->middleware('throttle:5,1')->name('public.consumer.register');
    Route::post('/conta/entrar', [ConsumerAccountController::class, 'login'])->middleware('throttle:10,1')->name('public.consumer.login');
    Route::post('/conta/sair', [ConsumerAccountController::class, 'logout'])->name('public.consumer.logout');
    Route::post('/conta/esqueci-senha', [ConsumerAccountController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('public.consumer.password.email');
    Route::get('/conta/nova-senha/{token}', [ConsumerAccountController::class, 'showResetPassword'])->middleware('guest:consumer')->name('public.consumer.password.reset');
    Route::post('/conta/nova-senha', [ConsumerAccountController::class, 'resetPassword'])->middleware('guest:consumer')->name('public.consumer.password.store');
    Route::get('/pedido/{token}', [PublicOrderController::class, 'track'])->name('public.order.track');
    Route::get('/produto/{product}', [PublicProductController::class, 'show'])->name('public.product');
    Route::get('/manifest.webmanifest', [PublicPwaController::class, 'manifest'])->name('public.manifest');
    Route::get('/sw.js', [PublicPwaController::class, 'serviceWorker'])->name('public.service-worker');
});

Route::get('/', function () {
    return view('marketing.home');
});
Route::get('/llms.txt', [PublicAgentController::class, 'platform'])->name('platform.llms');
Route::post('/webhooks/evolution', EvolutionWebhookController::class)->name('webhooks.evolution');

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
        Route::get('/owner', PlatformDashboardController::class)->name('platform.dashboard');
        Route::get('/admin', [PlatformController::class, 'index'])->name('admin.platform');
        Route::patch('/admin/mensuracao', [PlatformController::class, 'updateMeasurement'])->name('admin.measurement.update');
        Route::post('/admin/estabelecimentos/{tenant}/acessar', [PlatformController::class, 'impersonate'])->name('admin.impersonate');
        Route::get('/admin/estabelecimentos/{tenant}/acessar', [PlatformController::class, 'impersonate'])->name('admin.impersonate.get');
        Route::patch('/admin/estabelecimentos/{tenant}/plano', [PlatformController::class, 'updatePlan'])->name('admin.plan.update');
        Route::delete('/admin/estabelecimentos/{tenant}/whatsapp', [WhatsappSupportController::class, 'reset'])->name('admin.whatsapp.reset');
        Route::get('/admin/planos', [PlanController::class, 'index'])->name('admin.plans.index');
        Route::post('/admin/planos', [PlanController::class, 'store'])->name('admin.plans.store');
        Route::patch('/admin/planos/{plan}', [PlanController::class, 'update'])->name('admin.plans.update');
        Route::get('/admin/whatsapp/modelos', [WhatsappTemplateController::class, 'index'])->name('admin.whatsapp-templates.index');
        Route::patch('/admin/whatsapp/modelos/{template}', [WhatsappTemplateController::class, 'update'])->name('admin.whatsapp-templates.update');
        Route::get('/admin/emails', [EmailTemplateController::class, 'index'])->name('admin.email-templates.index');
        Route::post('/admin/emails/teste', [EmailTemplateController::class, 'sendTest'])->name('admin.email-templates.test');
        Route::patch('/admin/emails/{template}', [EmailTemplateController::class, 'update'])->name('admin.email-templates.update');
    });
    Route::post('/admin/retornar', [PlatformController::class, 'leave'])->name('admin.leave');
    Route::middleware('role:owner')->group(function () {
        Route::get('/painel/cardapio', [OwnerCatalogController::class, 'index'])->name('owner.catalog');
        Route::post('/painel/categorias', [OwnerCatalogController::class, 'storeCategory'])->name('owner.categories.store');
        Route::patch('/painel/categorias/{category}', [OwnerCatalogController::class, 'updateCategory'])->name('owner.categories.update');
        Route::delete('/painel/categorias/{category}', [OwnerCatalogController::class, 'destroyCategory'])->name('owner.categories.destroy');
        Route::post('/painel/produtos', [OwnerCatalogController::class, 'storeProduct'])->name('owner.products.store');
        Route::get('/painel/produtos/novo', [OwnerCatalogController::class, 'createProduct'])->name('owner.products.create');
        Route::get('/painel/produtos/{product}', [OwnerCatalogController::class, 'editProduct'])->name('owner.products.edit');
        Route::patch('/painel/produtos/{product}', [OwnerCatalogController::class, 'updateProduct'])->name('owner.products.update');
        Route::delete('/painel/produtos/{product}', [OwnerCatalogController::class, 'destroyProduct'])->name('owner.products.destroy');
        Route::delete('/painel/produtos/imagens/{image}', [OwnerCatalogController::class, 'destroyProductImage'])->name('owner.product-images.destroy');
        Route::post('/painel/produtos/{product}/grupos', [OwnerCatalogController::class, 'storeOptionGroup'])->name('owner.option-groups.store');
        Route::patch('/painel/grupos/{group}', [OwnerCatalogController::class, 'updateOptionGroup'])->name('owner.option-groups.update');
        Route::delete('/painel/grupos/{group}', [OwnerCatalogController::class, 'destroyOptionGroup'])->name('owner.option-groups.destroy');
        Route::post('/painel/grupos/{group}/opcoes', [OwnerCatalogController::class, 'storeOption'])->name('owner.options.store');
        Route::patch('/painel/opcoes/{option}', [OwnerCatalogController::class, 'updateOption'])->name('owner.options.update');
        Route::delete('/painel/opcoes/{option}', [OwnerCatalogController::class, 'destroyOption'])->name('owner.options.destroy');
        Route::get('/painel/mesas', [OwnerTableController::class, 'index'])->name('owner.tables');
        Route::post('/painel/mesas', [OwnerTableController::class, 'store'])->name('owner.tables.store');
        Route::get('/painel/mesas/{table}/qr.svg', [OwnerTableController::class, 'qr'])->name('owner.tables.qr');
        Route::get('/painel/equipe', [TeamController::class, 'index'])->name('owner.team');
        Route::post('/painel/equipe', [TeamController::class, 'store'])->name('owner.team.store');
        Route::patch('/painel/equipe/{member}', [TeamController::class, 'update'])->name('owner.team.update');
        Route::patch('/painel/equipe/{member}/senha', [TeamController::class, 'resetPassword'])->name('owner.team.password');
        Route::get('/painel/configuracoes', [OwnerSettingsController::class, 'edit'])->name('owner.settings');
        Route::patch('/painel/configuracoes', [OwnerSettingsController::class, 'update'])->name('owner.settings.update');
        Route::post('/painel/configuracoes/banners', [OwnerBannerController::class, 'store'])->name('owner.banners.store');
        Route::patch('/painel/configuracoes/banners/{banner}', [OwnerBannerController::class, 'update'])->name('owner.banners.update');
        Route::delete('/painel/configuracoes/banners/{banner}', [OwnerBannerController::class, 'destroy'])->name('owner.banners.destroy');
        Route::get('/painel/estoque', [OwnerInventoryController::class, 'index'])->name('owner.inventory.index');
        Route::post('/painel/estoque', [OwnerInventoryController::class, 'store'])->name('owner.inventory.store');
        Route::patch('/painel/estoque/{inventoryItem}', [OwnerInventoryController::class, 'adjust'])->name('owner.inventory.adjust');
        Route::post('/painel/produtos/{product}/ficha-tecnica', [OwnerInventoryController::class, 'recipe'])->name('owner.inventory.recipe');
        Route::get('/painel/horarios', [OwnerScheduleController::class, 'index'])->name('owner.schedules.index');
        Route::post('/painel/horarios', [OwnerScheduleController::class, 'store'])->name('owner.schedules.store');
        Route::delete('/painel/horarios/{schedule}', [OwnerScheduleController::class, 'destroy'])->name('owner.schedules.destroy');
        Route::patch('/painel/horarios/funcionamento', [OwnerScheduleController::class, 'override'])->name('owner.schedules.override');
        Route::post('/painel/horarios/excecoes', [OwnerScheduleController::class, 'storeException'])->name('owner.schedule-exceptions.store');
        Route::delete('/painel/horarios/excecoes/{exception}', [OwnerScheduleController::class, 'destroyException'])->name('owner.schedule-exceptions.destroy');
        Route::get('/painel/vendas', [OwnerCommerceController::class, 'index'])->name('owner.commerce.index');
        Route::post('/painel/cupons', [OwnerCommerceController::class, 'storeCoupon'])->name('owner.coupons.store');
        Route::patch('/painel/cupons/{coupon}', [OwnerCommerceController::class, 'updateCoupon'])->name('owner.coupons.update');
        Route::post('/painel/regioes-entrega', [OwnerCommerceController::class, 'storeZone'])->name('owner.delivery-zones.store');
        Route::patch('/painel/regioes-entrega/{zone}', [OwnerCommerceController::class, 'updateZone'])->name('owner.delivery-zones.update');
        Route::get('/painel/pedidos', [OwnerOrderController::class, 'index'])->name('owner.orders.index');
        Route::get('/painel/pedidos/{order}', [OwnerOrderController::class, 'show'])->name('owner.orders.show');
        Route::patch('/painel/pedidos/{order}', [OwnerOrderController::class, 'update'])->name('owner.orders.update');
        Route::patch('/painel/pedidos/{order}/detalhes', [OwnerOrderController::class, 'updateDetails'])->name('owner.orders.details.update');
        Route::get('/painel/relatorios', [OwnerReportController::class, 'index'])->name('owner.reports.index');
        Route::get('/painel/relatorios/pedidos.csv', [OwnerReportController::class, 'csv'])->name('owner.reports.csv');
        Route::get('/painel/integracoes/whatsapp', [WhatsappIntegrationController::class, 'index'])->name('owner.whatsapp.index');
        Route::post('/painel/integracoes/whatsapp/conectar', [WhatsappIntegrationController::class, 'connect'])->name('owner.whatsapp.connect');
        Route::post('/painel/integracoes/whatsapp/atualizar', [WhatsappIntegrationController::class, 'refresh'])->name('owner.whatsapp.refresh');
        Route::patch('/painel/integracoes/whatsapp/modelos/{event}', [WhatsappIntegrationController::class, 'updateTemplate'])->name('owner.whatsapp.templates.update');
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
