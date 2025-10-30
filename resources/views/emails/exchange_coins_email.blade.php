@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt; color: #000000">
      @lang('emails.exchange_coins_email.exchange_coins', ['coins' => number_format($coins), 'rewards' => number_format($rewards)], $userLocale)
    </p>
@endsection
