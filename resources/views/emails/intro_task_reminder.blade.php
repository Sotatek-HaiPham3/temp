@extends('emails.template')
@section('content')
  <div style="padding:35px 42px">
    <p style="margin-bottom:25px; font-size:11pt;">
      @lang('emails.intro_task_reminder.text1', [], $userLocale)
    </p>
  </div>
@endsection
