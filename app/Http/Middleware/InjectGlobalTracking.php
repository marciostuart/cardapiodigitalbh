<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class InjectGlobalTracking
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type');

        if (! str_contains($contentType, 'text/html') || $response->isRedirection()) {
            return $response;
        }

        // A global GTM container can include analytics and advertising tags, so
        // require explicit consent to both non-essential categories.
        if ($request->cookie('cd_tracking_consent') !== 'a1m1') {
            return $response;
        }

        $containerId = Schema::hasTable('platform_settings') ? PlatformSetting::value('gtm_container_id') : null;
        if (! $containerId) return $response;

        $content = $response->getContent();
        if (! is_string($content) || str_contains($content, 'googletagmanager.com/gtm.js')) {
            return $response;
        }

        $id = json_encode($containerId, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG);
        $head = "<script>(function(w,d,l,i){var done=false,load=function(){if(done)return;done=true;w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var s=d.createElement('script');s.async=true;s.src='https://www.googletagmanager.com/gtm.js?id='+i;d.head.appendChild(s);};['pointerdown','keydown','touchstart','scroll'].forEach(function(e){w.addEventListener(e,load,{once:true,passive:true});});if('requestIdleCallback'in w){w.requestIdleCallback(load,{timeout:4000});}else{w.setTimeout(load,4000);}})(window,document,'dataLayer',{$id});</script>";
        $body = '';

        $content = preg_replace('/<\/head>/i', $head.'</head>', $content, 1) ?? $content;
        $content = preg_replace('/<body([^>]*)>/i', '<body$1>'.$body, $content, 1) ?? $content;
        $response->setContent($content);

        return $response;
    }
}
