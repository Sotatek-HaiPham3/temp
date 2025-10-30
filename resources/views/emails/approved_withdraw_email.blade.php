@extends('emails.template')
@section('content')
<div style="padding: 0px 45px 45px 45px;color: rgb(34, 34, 34);">
  <div style="text-align: center;">
    <h1 style="font-size: 24px;">@lang('emails.approved_withdraw_email.headline', [], $userLocale)</h1>
  </div>

  <div style="background-color: #fff;">
    <div style="text-align: center;">
      <h3>@lang('emails.approved_withdraw_email.hi', ['username' => $username], $userLocale)</h3>
        <p>@lang('emails.approved_withdraw_email.text_1', [], $userLocale)</p>
        <p style="color: #9da3a3;">
            @lang('emails.approved_withdraw_email.withdraw', ['releaseAmount' => $releaseAmount, 'receiveAmount' => $receiveAmount], $userLocale)
        </p>
      </div>
      
      <div style="padding-top: 15px;">
        <span style="text-transform: uppercase;font-size: 14px;color: #9da3a3;font-weight: bold;">
            @lang('emails.approved_withdraw_email.title_1', [], $userLocale)
          </span>
          <div style="height: 2px;background-color: #9da3a3;"></div>
          <div>
            <div style="width: 50%;padding-top: 10px;float: left;">
                <p style="font-weight: bold;margin-bottom: 4px;">@lang('emails.approved_withdraw_email.title_1_subtitle_1', [], $userLocale)</p>
                  <span>{{ $transactionId }}</span>
              </div>
              <div style="width: 50%;padding-top: 10px;float: right;">
                <p style="font-weight: bold;margin-bottom: 4px;">@lang('emails.approved_withdraw_email.title_1_subtitle_2', [], $userLocale)</p>
                  <span>{{ $paypalReceiverEmail }}</span>
              </div>
              <div style="width: 50%;padding-top: 10px;float: left;clear: both;">
                <p style="font-weight: bold;margin-bottom: 4px;">@lang('emails.approved_withdraw_email.title_1_subtitle_3', [], $userLocale)</p>
                  <span>{{ $date }}</span>
              </div>
              <div style="width: 50%;padding-top: 10px;float: right;">
                <p style="font-weight: bold;margin-bottom: 4px;">@lang('emails.approved_withdraw_email.title_1_subtitle_4', [], $userLocale)</p>
                  <span> @lang('emails.approved_withdraw_email.source', [], $userLocale)</span>
              </div>
          </div>
      </div>
    </div>
    
    <div style="clear: both;padding-top: 15px;">
      <span style="text-transform: uppercase;font-size: 14px;color: #9da3a3;font-weight: bold;">
        @lang('emails.approved_withdraw_email.title_2', [], $userLocale)
      </span>

      <div style="height: 2px;background-color: #9da3a3;"></div>

      <table style="border-collapse: collapse;border-spacing: 0;width: 100%;display: table;">
        <tr style="background-color: #f1f1f1;">
          <th style="padding: 8px 8px;display: table-cell;text-align: left;vertical-align: top;">
              @lang('emails.approved_withdraw_email.title_2_subtitle_1', [], $userLocale)
          </th>
          <th style="padding: 8px 8px;display: table-cell;text-align: center;vertical-align: top;">
              @lang('emails.approved_withdraw_email.title_2_subtitle_2', [], $userLocale)
          </th>
          <th style="padding: 8px 8px;display: table-cell;text-align: right;vertical-align: top;">
              @lang('emails.approved_withdraw_email.title_2_subtitle_3', [], $userLocale)
          </th>
        </tr>
        <tr>
          <td style="padding: 8px 8px;display: table-cell;text-align: left;vertical-align: top;">
            @lang('emails.approved_withdraw_email.memo', [], $userLocale)
          </td>
          <td style="padding: 8px 8px;display: table-cell;text-align: center;vertical-align: top;">
              @lang('emails.approved_withdraw_email.seller_name', [], $userLocale)
          </td>
          <td style="font-weight: bold;padding: 8px 8px;display: table-cell;text-align: right;vertical-align: top;">
               @lang('emails.approved_withdraw_email.amount', ['amount' => $releaseAmount], $userLocale)
          </td>
        </tr>
        <tr>
          <td style="padding: 8px 8px;display: table-cell;text-align: left;vertical-align: top;"></td>
          <td style="text-transform: uppercase;font-size: 14px;color: #9da3a3;font-weight: bold;padding: 8px 8px;display: table-cell;text-align: right;vertical-align: top;">@lang('emails.approved_withdraw_email.title_2_subtitle_4', [], $userLocale)</td>
          <td style="font-weight: bold;padding: 8px 8px;display: table-cell;text-align: right;vertical-align: top;">
              @lang('emails.approved_withdraw_email.amount', ['amount' => $releaseAmount], $userLocale)
          </td>
        </tr>
      </table>
    </div>
  
    <div style="clear: both;padding-top: 15px;">
      <span style="text-transform: uppercase;font-size: 14px;color: #9da3a3;font-weight: bold;">
        @lang('emails.approved_withdraw_email.title_3', [], $userLocale)
      </span>
      <div style="height: 2px;background-color: #9da3a3;"></div>
      <div style="padding: 10px 8px 10px 0;">
        <span style="text-transform: uppercase;font-size: 14px;color: #9da3a3;font-weight: bold;">
            @lang('emails.approved_withdraw_email.title_4', [], $userLocale)
        </span>
        <span style="font-weight: bold;">
            @lang('emails.approved_withdraw_email.payment_type', ['paymentType' => $paymentType], $userLocale)
        </span>
        <span style="float: right;font-weight: bold;">
            @lang('emails.approved_withdraw_email.amount', ['amount' => $releaseAmount], $userLocale)
        </span>
      </div>
      <div style="height: 2px;background-color: #9da3a3;"></div>
    </div>
  
    <div style="margin-top: 30px; text-align: center;">
      <a href="{{ url(config('app.web_url').'?login=true') }}"
        style="background-color: rgb(2,210,131);
          width: 100%;
          max-width: 350px;
          color: #ffffff;
          display: inline-block;
          line-height: 1;
          border: none;
          -webkit-appearance: none;
          text-decoration: none;
          text-align: center;
          outline: none;
          margin: 0;
          transition: .1s;
          font-weight: bold;
          -webkit-user-select: none;
          padding: 10px 0;
          font-size: 14px;
          border-radius: 6px;">
          @lang('emails.approved_withdraw_email.action', [], $userLocale)
        </a>
    </div>
  </div>
</div>
@endsection
