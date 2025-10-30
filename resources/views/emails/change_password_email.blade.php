@extends('emails.template')
@section('content')
  <div style="padding:35px 42px; text-align: center;">
    <p style="margin-bottom:25px; font-size:11pt;color: #000000;">
      @lang('emails.change_password_email.change_password', [], $userLocale)
    </p>
    <div style="margin-top: 30px; text-align: center;">
        <a href="{{ url(config('app.web_url').'?login=true') }}"
            style="background-color: #08d283;
                width: 100%;
                max-width: 120px;
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
            @lang('emails.change_password_email.action', [], $userLocale)
        </a>
    </div>
@endsection
