@extends('emails.template')
@section('content')
  <div style="padding:35px 45px">
    <div style="
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
        color: #000000">
        <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/shield-complete.png' }}" alt="" height="50px">
        <div style="margin-top: 30px;">
            @lang('emails.confirmation_change_email_content.title', [], $userLocale)
        </div>
    </div>
    <div style="font-size: 15px;
        line-height: 20px;
        color: #000000;
        text-align: center;
        margin-top: 45px;
        font-weight: 500;">
        @lang('emails.confirmation_email_content.sub_text', [], $userLocale)
    </div>
<!--     <p style="margin-bottom:25px; font-size:11pt;">  Dear {{$userName}}, </p>
    <p style="margin-bottom:25px; font-size:11pt;">{{ __('emails.welcome', [], $userLocale) }} </p>
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.confirmation_email_content.text1', [], $userLocale)
      @lang('emails.confirmation_email_content.text2', [], $userLocale)
    </p> -->
    <div style="text-align: center;
        margin-top: 30px;">
        <span style="color: #000000;
            background-color: #efefef;
            padding: 7px 18px;
            font-size: 24px;
            letter-spacing: 5px;
            font-family: 'IBM Plex Mono', monospace;
            border-radius: 3px;">{{ $code }}</span>
    </div>
    <div style="margin-top: 30px; text-align: center;">
        <a href="{{ verify_change_email_url($code) }}"
            style="
                background-color: rgb(2, 210, 131);
                width: 100%;
                max-width: 350px;
                color: #ffffff; 
                display: inline-block;
                line-height: 1;
                border: none;
                -webkit-appearance: none;
                text-decoration: none;
                text-align: center;
                outline: none;
                margin: 0;
                transition: .1s;
                font-weight: 500;
                -webkit-user-select: none;
                padding: 10px 0;
                font-size: 14px;
                border-radius: 6px;">
            @lang('emails.confirmation_change_email_content.action', [], $userLocale)
        </a>
    </div>
@endsection
