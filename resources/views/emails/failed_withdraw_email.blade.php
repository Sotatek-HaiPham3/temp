@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:15px; font-size:11pt;">
      @lang('emails.failed_withdraw_email.withdraw_failed', ['email' => $email, 'amount' => $amount], $userLocale)
    </p>
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.failed_withdraw_email.contact_admin', [], $userLocale)
    </p>
  </div>
@endsection
