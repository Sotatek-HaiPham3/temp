@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.session_accepted_email.gameprofile_accepted', ['title' => $gameProfileTitle, 'gamelancername' => $gamelancerFullname], $userLocale)
    </p>
  </div>
@endsection
