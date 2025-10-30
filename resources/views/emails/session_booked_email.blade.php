@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.session_booked_email.gameprofile_booked', ['title' => $gameProfileTitle, 'type' => $sessionType, 'username' => $userFullname], $userLocale)
    </p>
  </div>
@endsection
