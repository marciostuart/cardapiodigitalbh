@php($currentPublicUrl = 'https://'.request()->getHttpHost().request()->getPathInfo())
@php($footerTrackingUrl = 'https://360bh.com.br/?utm_source=cardapiodigitalbh-footer&utm_medium='.$currentPublicUrl)
<footer class="platform-footer"><div>© {{ now()->year }} Cardápio Digital BH. Todos os direitos reservados.</div><div>Desenvolvido por <a href="{{ $footerTrackingUrl }}" target="_blank" rel="noopener noreferrer">360BH</a></div></footer>
<style>.platform-footer{margin-top:auto;padding:28px 16px;background:var(--dark,var(--brand-dark,#102b3c));color:#fff;text-align:center;font-size:13px;line-height:1.65}.platform-footer a{color:#fff;font-weight:900;text-decoration:none}.platform-footer a:hover{text-decoration:underline}</style>
