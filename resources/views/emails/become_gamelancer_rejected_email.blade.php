@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;color: #000000">
      @lang('emails.become_gamelancer_rejected_email.become_gamelancer_rejected', ['username' => $username], $userLocale)
    </p>
@endsection
