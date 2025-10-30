@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt; color: #000000">
      @lang('emails.bounty_review_email.bounty_review', ['reviewerName' => $reviewerName, 'bountyTitle' => $bountyTitle, 'rate' => $rate], $userLocale)
    </p>
@endsection
