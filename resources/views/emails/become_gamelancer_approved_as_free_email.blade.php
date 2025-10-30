@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <div style="
        text-align: center;
        font-weight: 600;
        color: #000000">
        <div style="margin-top: 20px;font-size: 24px;">
            @lang('emails.become_gamelancer_approved_as_free_email.title', [], $userLocale)
        </div>
    </div>
    <p style="margin-top:30px; font-size:11pt;color: #000000">
      @lang('emails.become_gamelancer_approved_as_free_email.approved_text_1', [], $userLocale)
    </p>
    <div style="margin-top: 30px; text-align: center;">
        <a href="{{ go_game_profile($username, $game) }}"
            style="background-color: rgb(2,210,131);
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
            @lang('emails.become_gamelancer_approved_as_free_email.action', [], $userLocale)
        </a>
    </div>
@endsection
