@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.claimed_bounty_email.bounty_claimed', ['title' => $bountyTitle, 'gamename' => $gamelancerName], $userLocale)
    </p>
  </div>
@endsection
