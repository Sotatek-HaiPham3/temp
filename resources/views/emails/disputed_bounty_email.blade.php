@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.disputed_bounty_email.bounty_disputed', ['title' => $bountyTitle, 'username' => $userName], $userLocale)
    </p>
  </div>
@endsection
