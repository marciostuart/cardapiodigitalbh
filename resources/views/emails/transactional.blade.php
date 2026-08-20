<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $heading }}</title></head>
<body style="margin:0;background:#f1f5f9;color:#0f172a;font-family:Arial,Helvetica,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 12px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;overflow:hidden;border-radius:24px;background:#ffffff;box-shadow:0 12px 30px rgba(15,23,42,.08)">
            <tr><td style="background:#0f172a;padding:24px 32px;color:#ffffff"><div style="font-size:12px;font-weight:700;letter-spacing:1.5px;color:#6ee7b7">CARDÁPIO DIGITAL BH</div><div style="margin-top:6px;font-size:20px;font-weight:700">Seu pedido em um clique</div></td></tr>
            <tr><td style="padding:36px 32px">
                <h1 style="margin:0 0 20px;font-size:28px;line-height:1.25">{{ $heading }}</h1>
                <div style="font-size:16px;line-height:1.7;color:#475569">{!! nl2br(e($body)) !!}</div>
                @if($actionText && $actionUrl)
                    <div style="margin:30px 0"><a href="{{ $actionUrl }}" style="display:inline-block;border-radius:12px;background:#059669;padding:14px 22px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none">{{ $actionText }}</a></div>
                    <p style="margin:20px 0 0;font-size:12px;line-height:1.5;color:#94a3b8">Se o botão não funcionar, copie e cole este endereço no navegador:<br><a href="{{ $actionUrl }}" style="color:#047857;word-break:break-all">{{ $actionUrl }}</a></p>
                @endif
            </td></tr>
            @if($footer)<tr><td style="border-top:1px solid #e2e8f0;padding:22px 32px;font-size:13px;line-height:1.6;color:#64748b">{{ $footer }}</td></tr>@endif
        </table>
        <p style="margin:18px 0 0;font-size:12px;color:#94a3b8">© {{ date('Y') }} Cardápio Digital BH · Belo Horizonte, MG</p>
    </td></tr>
</table>
</body>
</html>
