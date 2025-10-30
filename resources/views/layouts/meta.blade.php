<meta property="og:site_name" content="Gamelancer">
<meta property="og:type" content="Website">
<meta property="og:locale" content="en_US">
<meta property="og:url" itemprop="url" content="{{ request()->fullUrl() }}">
{{-- <meta property="fb:app_id" content="221422942628124">
<meta property="fb:pages" content="1473686186269201"> --}}

@if (!empty($data))

    @if ($data['screen'] === \App\Consts::GAME_BOUNTY_SCREEN && $data['detail'])
        <meta property="og:type" content="article">
        <meta property="og:title" itemprop="name" content="{{ $data['detail']->title }}">
        <meta property="og:image" itemprop="thumbnailUrl" content="{{ $data['detail']->thumbnail ?? $data['detail']->game->cover }}">
        <meta property="og:description" content="{{ $data['detail']->description }}">

    @elseif ($data['screen'] === \App\Consts::USER_SCREEN && $data['detail'])
        <meta property="og:title" itemprop="name" content="{{ $data['detail']->full_name ?? $data['detail']->username }}">
        <meta property="og:image" content="{{ $data['detail']->avatar }}">
        <meta property="og:description" content="{{ $data['detail']->description }}">

    @endif

@endif
