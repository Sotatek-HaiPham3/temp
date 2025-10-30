@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">  Dear {{$userName}}, </p>
    <p style="margin-bottom:25px; font-size:11pt;">@lang('emails.change_user_status_email.notification_user_status', [], $userLocale)
      @if($status == "active")
        <span style="color: blue; font-weight: bold;">{{ $status }}</span>
      @else
        <span style="color: red; font-weight: bold;">{{ $status }}</span>
      @endif
    </p>
    <p style="margin-bottom:25px; font-size:11pt;">@lang('emails.change_user_status_email.offer_feedback', [], $userLocale)</p>
    <a style="color: blue;" href="#" >admin@gamelancer.com</a>
  </div>
@endsection
