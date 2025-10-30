@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.session_starting_email.session_starting', ['gameTitle' => $gameTitle, 'username' => $username, 'minutes' => $minutes], $userLocale)
    </p>
  </div>
@endsection
