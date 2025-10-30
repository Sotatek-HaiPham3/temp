@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.booking_session_gamelancer_offline_email.content', ['username' => $username], $userLocale)
    </p>
  </div>
@endsection
