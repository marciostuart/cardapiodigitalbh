<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_and_marketing_scripts_wait_for_their_respective_choices(): void
    {
        PlatformSetting::putValue('gtm_container_id', 'GTM-ABCD123');
        $tenant = Tenant::create(['name' => 'Consentimento', 'slug' => 'consentimento']);
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Consentimento',
            'subdomain' => 'consentimento',
            'settings' => ['google_analytics_id' => 'G-ABC123', 'meta_pixel_id' => '123456789'],
        ]);
        $url = "http://{$venue->subdomain}.cardapiodigitalbh.com.br/";

        $this->get($url)->assertOk()
            ->assertSee('data-cookie-banner', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);

        $this->withUnencryptedCookie('cd_tracking_consent', 'a0m0')->get($url)->assertOk()
            ->assertSee('data-cookie-manage', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);

        $this->withUnencryptedCookie('cd_tracking_consent', 'a1m0')->get($url)->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);

        $this->withUnencryptedCookie('cd_tracking_consent', 'a0m1')->get($url)->assertOk()
            ->assertSee('connect.facebook.net/en_US/fbevents.js', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false);

        $this->withUnencryptedCookie('cd_tracking_consent', 'a1m1')->get($url)->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123', false)
            ->assertSee('connect.facebook.net/en_US/fbevents.js', false)
            ->assertSee('googletagmanager.com/gtm.js?id=', false);
    }

    public function test_global_tags_stay_off_panels_password_resets_and_tokenized_table_menus(): void
    {
        PlatformSetting::putValue('gtm_container_id', 'GTM-ABCD123');
        $tenant = Tenant::create(['name' => 'Protegido', 'slug' => 'protegido']);
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Protegido',
            'subdomain' => 'protegido',
            'settings' => ['google_analytics_id' => 'G-ABC123', 'meta_pixel_id' => '123456789'],
        ]);
        Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Painel Protegido',
            'subdomain' => 'localhost',
            'settings' => ['google_analytics_id' => 'G-ABC123', 'meta_pixel_id' => '123456789'],
        ]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $token = (string) Str::uuid();
        DiningTable::create(['venue_id' => $venue->id, 'code' => '01', 'qr_token' => $token]);

        $this->withUnencryptedCookie('cd_tracking_consent', 'a1m1')->get('/login')->assertOk()
            ->assertDontSee('googletagmanager.com/gtm.js', false);

        $this->actingAs($owner)->withUnencryptedCookie('cd_tracking_consent', 'a1m1')->get('/operacao')->assertOk()
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);

        $base = 'http://protegido.cardapiodigitalbh.com.br';
        $this->withUnencryptedCookie('cd_tracking_consent', 'a1m1')->get($base.'/conta/nova-senha/segredo-reset')
            ->assertOk()->assertDontSee('googletagmanager.com/gtm.js', false);

        $this->withUnencryptedCookie('cd_tracking_consent', 'a1m1')->get($base.'/mesa/'.$token)
            ->assertOk()
            ->assertSee('data-cookie-banner', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);

        $this->withUnencryptedCookie('cd_tracking_consent', 'a1m1')->get('/m/'.$token)
            ->assertOk()
            ->assertSee('data-cookie-banner', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);
    }
}
