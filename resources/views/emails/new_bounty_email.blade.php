@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt; color: #000000">
      @lang('emails.new_bounty_email.new_bounty', ['username' => $username, 'title' => $title], $userLocale)
    </p>
@endsection
