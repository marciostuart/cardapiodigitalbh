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

        $containerId = Schema::hasTable('platform_settings') ? PlatformSetting::value('gtm_container_id') : null;
        if (! $containerId) return $response;

        $content = $response->getContent();
        if (! is_string($content) || str_contains($content, 'googletagmanager.com/gtm.js')) {
            return $response;
        }

        $id = json_encode($containerId, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG);
        $head = "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',{$id});</script>";
        $body = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.e($containerId).'" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';

        $content = preg_replace('/<\/head>/i', $head.'</head>', $content, 1) ?? $content;
        $content = preg_replace('/<body([^>]*)>/i', '<body$1>'.$body, $content, 1) ?? $content;
        $response->setContent($content);

        return $response;
    }
}
