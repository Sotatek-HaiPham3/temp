@extends('emails.template')
@section('content')
<style>
    .class {
        color: red;
    }
</style>
<div style="padding:35px 45px">
    <div style="
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
        color: #000000">
        <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/shield-complete.png' }}" alt="" height="50px">
        <div style="margin-top: 30px;" class="class">
            @lang('emails.otp_email_content.title', [], $userLocale)
        </div>
        <div>{{ $userName }}</div>
    </div>
    <div style="font-size: 15px;
        line-height: 20px;
        color: #000000;
        text-align: center;
        margin-top: 45px;
        font-weight: 500;">
        @lang('emails.otp_email_content.sub_text', [], $userLocale)
    </div>
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
@endsection
