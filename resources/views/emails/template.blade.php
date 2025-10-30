@if(isset($userLocale))
<html lang="{{ $userLocale }}">
@else
<html lang="{{ app()->getLocale() }}">
@endif
<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@200;300;400;500;600&display=swap" rel="stylesheet">
</head>
<body style="
    background-color: #08d283;
    margin: 0;
    padding: 10px;
    font-family: 'Montserrat', sans-serif;
    box-sizing: border-box;">
    <div>
        <div style="width:auto;
            max-width: 666px;
            background-color:#ffffff;
            margin:50px auto 0;
            border-radius:2px;">
            <section style="display: flex;
                padding: 24px;
                align-items: center;">
                <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/logo.png' }}" alt="" height="26px" width="26px">
                <span
                    style="color: #000000;
                    text-transform: uppercase;
                    font-weight: 600;">Gamelancer</span>
            </section>
            <div style="height: 1.5px;
            background: #b1b1b1;
            width: 100%;"></div>
            @yield('content')
        </div>
    </div>
    <div style="background-color: #08d283;
        padding: 5px 0 60px 0;
        margin-bottom: 66px;
        font-weight: 500;
        color: #333333;
        font-size: 10px;
        text-align: center;">
        <div class="list_link">
            <a href="https://facebook.com/Gamelancer/" target="_blank" style="margin: 0px 15px;text-decoration: none;">
                <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/fb.png' }}" alt="">
            </a>
            <a href="https://Instagram.com/Gamelancer/" target="_blank" style="margin: 0px 15px;text-decoration: none;">
                <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/Instagram.png' }}" alt="">
            </a>
            <a href="https://twitter.com/gamelancer" target="_blank" style="margin: 0px 15px;text-decoration: none;">
                <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/twitter.png' }}" alt="">
            </a>
            <a href="https://discord.gg/xPaazbz" target="_blank" style="margin: 0px 15px;text-decoration: none;">
                <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/discord.png' }}" alt="">
            </a>
            <a href="https://tiktok.com/@gamelancer" target="_blank" style="margin: 0px 15px;text-decoration: none;">
                <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/tiktok.png' }}" alt="">
            </a>
        </div>
        <p style="color: #fff;">{{ __('emails.team_inc', [], $userLocale) }}</p>
    </div>
</body>
