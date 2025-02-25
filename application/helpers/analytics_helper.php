<?php

// prevent direct access
if (!defined('BASEPATH')) exit('No direct script access allowed');

class AnalyticsHelper {
    private const GTM_PREFIX = 'GTM-';
    private const GA4_PREFIX = 'G-';

    public static function renderAnalyticsScript(?string $analyticsKey): string 
    {
        if (empty($analyticsKey)) {
            return '';
        }

        return match (true) {
            str_starts_with($analyticsKey, self::GA4_PREFIX) => self::renderGA4Script($analyticsKey),
            str_starts_with($analyticsKey, self::GTM_PREFIX) => self::renderGTMScript($analyticsKey),
            default => ''
        };
    }


    public static function renderGTMBodyTag(?string $analyticsKey): string 
    {
        if (empty($analyticsKey) || !str_starts_with($analyticsKey, self::GTM_PREFIX)) {
            return '';
        }

        return <<<HTML
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$analyticsKey}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        HTML;
    }

    private static function renderGA4Script(string $measurementId): string 
    {
        return <<<HTML
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={$measurementId}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{$measurementId}');
        </script>
        HTML;
    }

    private static function renderGTMScript(string $tagId): string 
    {
        return <<<HTML
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer', '{$tagId}');</script>
        <!-- End Google Tag Manager -->
        HTML;
    }
}


if (!function_exists('render_analytics')) {
    function render_analytics($analyticsKey) {
        return AnalyticsHelper::renderAnalyticsScript($analyticsKey);
    }
}

if (!function_exists('render_gtm_body')) {
    function render_gtm_body($analyticsKey) {
        return AnalyticsHelper::renderGTMBodyTag($analyticsKey);
    }
}
