<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
