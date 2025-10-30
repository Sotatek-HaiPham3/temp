@extends('emails.template')
@section('content')
  <div style="padding:35px 45px">
    <div style="
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
        color: #000000">
        <div style="margin-top: 10px;">
            @lang('emails.reset_password_email.title', [], $userLocale)
        </div>
    </div>
    <div style="font-size: 15px;
        margin-top: 30px;
        line-height: 20px;
        color: #000000;
        font-weight: 500;">
        @lang('emails.reset_password_email.forget_password_1', [], $userLocale)
        <br>
        @lang('emails.reset_password_email.forget_password', [], $userLocale)
    </div>
    <div class="link" style="margin-top: 18px;text-align: center;">
        <a href="{{ reset_password_url($token, $email, $username) }}"
            style="text-decoration: none; font-size: 13px; line-height: 20px; color: #000000; opacity: 0.5;">
            {{ reset_password_url($token, $email, $username) }}
        </a>
    </div>
@endsection
