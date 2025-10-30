@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.canceled_bounty_email.bounty_canceled', ['title' => $bountyTitle, 'gamename' => $gamelancerName], $userLocale)
    </p>
  </div>
@endsection
