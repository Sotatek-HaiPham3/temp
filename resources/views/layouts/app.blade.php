<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000227">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (Auth::check())
        <meta name="authenticated" content="1">
    @endif

    @include('layouts.meta')

    <title>{{ config('app.name', 'Laravel') }}</title>

</head>
<body id="body-landing">
    <div id="app">
    </div>

    <script>
        var APP_NAME = "{{ env('APP_NAME') }}";
        // var APP_SHORT_NAME = "{{ env('APP_SHORT_NAME') }}";
        var SOCKET_URL = "{{ env('SOCKET_URL', 'http://' . Request::getHost() . '6001') }}";
        {{-- var API_URL = "{{ env('API_URL', 'http://' . Request::getHost()) }}"; --}}
    </script>

    {{-- Stripe payment --}}
    <script src="https://js.stripe.com/v3/" async></script>

    <!-- Start of HubSpot Embed Code -->
    <script type="text/javascript" id="hs-script-loader" async defer src="//js.hs-scripts.com/7107877.js"></script>
    <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2.js"></script>
    <!-- End of HubSpot Embed Code -->

</body>
</html>
