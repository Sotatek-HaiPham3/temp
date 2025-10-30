@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt; color: #000000">
      @lang('emails.session_review_email.session_review', ['reviewerName' => $reviewerName, 'gameTitle' => $gameTitle, 'rate' => $rate], $userLocale)
    </p>
@endsection
