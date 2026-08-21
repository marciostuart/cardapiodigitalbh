@php($gtmContainerId = \App\Models\PlatformSetting::value('gtm_container_id'))
@if($gtmContainerId)<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmContainerId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>@endif
