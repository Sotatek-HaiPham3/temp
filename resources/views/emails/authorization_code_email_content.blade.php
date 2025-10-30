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
            @lang('emails.authorization_code_email_content.title', [], $userLocale)
        </div>
    </div>
    <div style="font-size: 15px;
        line-height: 20px;
        color: #000000;
        margin-top: 45px;
        font-weight: 500;">
        @lang('emails.authorization_code_email_content.sub_text', ['code' => $code], $userLocale)
    </div>
@endsection
