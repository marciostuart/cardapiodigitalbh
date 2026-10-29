<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title>{{ $heading }}</title></head>
<body style="margin:0;padding:0;background-color:#fffaf5;color:#18202c;font-family:Arial,Helvetica,sans-serif">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">{{ $heading }} — Cardápio Digital BH</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fffaf5;padding:28px 12px">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;overflow:hidden;border:1px solid #eee6de;border-top:5px solid #ea4335;border-radius:20px;background-color:#ffffff;box-shadow:0 14px 38px rgba(83,37,26,.09)">
                <tr><td style="padding:24px 30px;background-color:#fff6ee;border-bottom:1px solid #f4e9df">
                    <div style="font-size:12px;font-weight:800;letter-spacing:1.2px;line-height:1.3"><span style="color:#ea4335">CARDÁPIO DIGITAL</span> <span style="color:#d99c00">BH</span></div>
                    <div style="margin-top:8px;color:#657184;font-size:13px;line-height:1.5">Um jeito simples de atender e vender.</div>
                </td></tr>
                <tr><td style="padding:34px 30px">
                    <h1 style="margin:0 0 18px;color:#18202c;font-size:26px;line-height:1.25;letter-spacing:-.4px">{{ $heading }}</h1>
                    <div style="color:#4d596a;font-size:16px;line-height:1.7">{!! nl2br(e($body)) !!}</div>
                    @if($actionText && $actionUrl)
                        <div style="margin:28px 0"><a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 22px;border:1px solid #ea4335;border-radius:12px;background-color:#ea4335;color:#ffffff;font-size:15px;font-weight:700;line-height:1.2;text-decoration:none">{{ $actionText }}</a></div>
                        <p style="margin:22px 0 0;color:#7a8492;font-size:12px;line-height:1.6">Se o botão não funcionar, copie e cole este endereço no navegador:<br><a href="{{ $actionUrl }}" style="color:#c72f26;word-break:break-all">{{ $actionUrl }}</a></p>
                    @endif
                </td></tr>
                @if($footer)<tr><td style="padding:20px 30px;border-top:1px solid #eee6de;color:#657184;font-size:13px;line-height:1.6">{{ $footer }}</td></tr>@endif
                <tr><td style="padding:18px 30px;background-color:#18202c;color:#ffffff;font-size:12px;line-height:1.6">Cardápio Digital BH<br><span style="color:#fbbc05">Seu negócio merece um cardápio que vende.</span></td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
