@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">  Dear {{$userName}}, </p>
    <p style="margin-bottom:25px; font-size:11pt;">@lang('emails.your_code', [], $userLocale)</p>
      <p style="margin-bottom:25px; color:blue;font-size: 30px;font-weight: 600;">
          <span style="color:#0064aa">{{ $code }}</span>
      </p>
  </div>
@endsection
