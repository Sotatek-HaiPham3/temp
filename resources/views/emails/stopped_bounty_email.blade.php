@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.stopped_bounty_email.bounty_stopped', ['title' => $bountyTitle, 'username' => $gamelancerName], $userLocale)
    </p>
  </div>
@endsection
