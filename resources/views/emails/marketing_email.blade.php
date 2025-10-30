@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:20px; font-size:11pt;">{{ __('emails.welcome', [], $userLocale) }} </p>
  </div>
  <div style="padding:25px 42px">
    <h3>{{ $title }}</h3>
    <p style="margin-bottom:15px; font-size: 13px;">{!! $content !!} </p>
  </div>
@endsection
