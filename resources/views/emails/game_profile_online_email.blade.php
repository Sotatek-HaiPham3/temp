@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt; color: #000000">
      @lang('emails.game_profile_online_email.game_profile_online', ['gameTitle' => $gameTitle], $userLocale)
    </p>
@endsection
