@php
    $cookieConsent = (string) request()->cookie('cd_tracking_consent', '');
    $hasCookieChoice = preg_match('/^a[01]m[01]$/', $cookieConsent) === 1;
@endphp
<style>
    .cd-cookie-banner{position:fixed;z-index:10000;right:16px;bottom:max(16px,env(safe-area-inset-bottom));width:min(620px,calc(100vw - 32px));padding:20px;border:1px solid #eee6de;border-radius:18px;background:#fff;color:#18202c;box-shadow:0 18px 60px #18202c2b;font:14px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}
    .cd-cookie-banner[hidden],.cd-cookie-manage[hidden],.cd-cookie-settings[hidden],.cd-cookie-save[hidden]{display:none!important}
    .cd-cookie-banner h2{margin:0 0 6px;font-size:18px;line-height:1.25;font-weight:850}
    .cd-cookie-banner p{margin:0 0 10px;color:#526071}
    .cd-cookie-banner a{color:#b82e25;font-weight:700;text-decoration:underline}
    .cd-cookie-settings{display:grid;gap:9px;margin:14px 0;padding:12px;border:1px solid #eee6de;border-radius:12px;background:#fffaf5}
    .cd-cookie-settings label{display:flex;align-items:flex-start;gap:9px;color:#18202c}
    .cd-cookie-settings input{margin-top:3px;accent-color:#ea4335}
    .cd-cookie-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
    .cd-cookie-actions button,.cd-cookie-manage{min-height:40px;padding:9px 13px;border:1px solid #d9d1ca;border-radius:10px;background:#fff;color:#18202c;font:inherit;font-weight:750;cursor:pointer}
    .cd-cookie-actions [data-cookie-accept]{border-color:#ea4335;background:#ea4335;color:#fff}
    .cd-cookie-actions button:focus-visible,.cd-cookie-manage:focus-visible{outline:3px solid #fbbc05;outline-offset:2px}
    .cd-cookie-manage{position:fixed;z-index:9999;right:16px;bottom:max(16px,env(safe-area-inset-bottom));background:#fff;box-shadow:0 5px 22px #18202c24}
    @media(max-width:520px){.cd-cookie-banner{right:10px;bottom:max(10px,env(safe-area-inset-bottom));width:calc(100vw - 20px);padding:16px}.cd-cookie-actions{display:grid;grid-template-columns:1fr 1fr}.cd-cookie-actions button{width:100%}}
</style>
<div data-cookie-consent>
    <section class="cd-cookie-banner" data-cookie-banner role="region" aria-label="Preferências de cookies" @if($hasCookieChoice) hidden @endif>
        <h2>Você escolhe como usamos cookies</h2>
        <p>Cookies necessários mantêm o site funcionando. Medição de audiência e publicidade só são ativadas com sua escolha. Veja a <a href="{{ route('marketing.privacy') }}">Política de Privacidade</a>.</p>
        <div class="cd-cookie-settings" data-cookie-settings hidden>
            <label><input type="checkbox" data-cookie-analytics> Medição de audiência (Google Analytics)</label>
            <label><input type="checkbox" data-cookie-marketing> Publicidade (Meta Pixel)</label>
            <p>O Google Tag Manager pode carregar tags de ambas as categorias e só é ativado quando as duas forem autorizadas.</p>
        </div>
        <div class="cd-cookie-actions">
            <button type="button" data-cookie-accept>Aceitar todos</button>
            <button type="button" data-cookie-necessary>Apenas necessários</button>
            <button type="button" data-cookie-customize>Personalizar</button>
            <button type="button" class="cd-cookie-save" data-cookie-save hidden>Salvar preferências</button>
        </div>
    </section>
    <button type="button" class="cd-cookie-manage" data-cookie-manage @unless($hasCookieChoice) hidden @endunless>Preferências de cookies</button>
</div>
<script defer src="{{ asset('js/cookie-consent-20261121.js') }}"></script>
