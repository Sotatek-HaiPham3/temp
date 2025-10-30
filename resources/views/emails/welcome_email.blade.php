@extends('emails.template')
@section('content')
<style type="text/css">
    #outlook a {
        padding: 0;
    }

    body {
        margin: 0;
        padding: 0;
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
    }

    table,
    td {
        border-collapse: collapse;
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
    }

    img {
        border: 0;
        height: auto;
        line-height: 100%;
        outline: none;
        text-decoration: none;
        -ms-interpolation-mode: bicubic;
    }

    p {
        display: block;
        margin: 13px 0;
    }
</style>
  <!--[if mso]>
        <xml>
        <o:OfficeDocumentSettings>
          <o:AllowPNG/>
          <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
        </xml>
    <![endif]-->
  <!--[if lte mso 11]>
        <style type="text/css">
          .mj-outlook-group-fix { width:100% !important; }
        </style>
    <![endif]-->
    <!--[if !mso]><!-->

    <style type="text/css">
        @media only screen and (min-width:546px) {
          .mj-column-per-66-66666666666666 {
            width: 66.66666666666666% !important;
            max-width: 66.66666666666666%;
        }
        .mj-column-per-33-33333333333333 {
            width: 33.33333333333333% !important;
            max-width: 33.33333333333333%;
        }
        .mj-column-per-100 {
            width: 100% !important;
            max-width: 100%;
        }
        .mj-column-per-50 {
            width: 50% !important;
            max-width: 50%;
        }
    }
</style>
<style type="text/css">
    @media only screen and (max-width:546px) {
      table.mj-full-width-mobile {
        width: 100% !important;
    }
    td.mj-full-width-mobile {
        width: auto !important;
    }
}
</style>
<div style="padding:0 45px 60px">
    <div style="
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
        color: #000000">
        <img src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/choose.png' }}" alt="" height="100px" width="100px">
        <div style="margin-top: 20px;margin-bottom: 0;">
            @lang('emails.welcome_email.pre_header', [], $userLocale)
        </div>
    </div>
    <div style="
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
        color: #000000">
        <div style="margin-top: 30px;">
            @lang('emails.welcome_email.headline', [], $userLocale)
        </div>
    </div>
    <div style="font-size: 15px;
        line-height: 20px;
        color: #000000;
        margin-top: 25px;
        font-weight: 500;">
        @lang('emails.welcome_email.subhead_1', [], $userLocale)
    </div>
    <div style="font-size: 15px;
        line-height: 20px;
        color: #000000;
        margin-top: 15px;
        font-weight: 500;">
        @lang('emails.welcome_email.subhead_2', [], $userLocale)
    </div>
<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
    <tbody>
        <tr>
            <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td
                       class="" style="vertical-align:top;width:199.99999999999997px;">
                <![endif]-->
                <div class="mj-column-per-33-33333333333333 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                        <tbody>
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                        <tbody>
                                            <tr>
                                                <td style="width:50px;"> <img alt="" height="auto" src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/landing/browse.svg' }}" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="50"> </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px;padding-top:3px;word-break:break-word;">
                                    <div style="font-size:14px;line-height:1;text-align:center;color:#9da3a3;margin-bottom: 20px;">
                                        <h3 style="color: #000000; font-weight: 700"> @lang('emails.welcome_email.module_1_title', [], $userLocale) </h3>
                                        <br>
                                        @lang('emails.welcome_email.module_1_subtitle', [], $userLocale)
                                    </div>
                                    <a href="{{ url(config('app.web_url')) . '/home' }}"
                                        style="
                                            background-color: rgb(2, 210, 131);
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
                                            font-weight: 500;
                                            -webkit-user-select: none;
                                            padding: 10px 0;
                                            font-size: 14px;
                                            border-radius: 6px;">
                                        @lang('emails.welcome_email.module_1_action', [], $userLocale)
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!--[if mso | IE]>
                    </td>
                  
                    <td
                       class="" style="vertical-align:top;width:199.99999999999997px;"
                    >
                <![endif]-->
                <div class="mj-column-per-33-33333333333333 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                        <tbody>
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                        <tbody>
                                            <tr>
                                                <td style="width:50px;"> <img alt="" height="auto" src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/landing/share.svg' }}" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="50"> </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px;padding-top:3px;word-break:break-word;">
                                    <div style="font-size:14px;line-height:1;text-align:center;color:#9da3a3;margin-bottom: 20px;">
                                        <h3 style="color: #000000; font-weight: 700"> @lang('emails.welcome_email.module_2_title', [], $userLocale) </h3>
                                        <br>
                                        @lang('emails.welcome_email.module_2_subtitle', [], $userLocale)
                                    </div>
                                    <a href="{{ url(config('app.web_url')) }}"
                                        style="
                                            background-color: rgb(2, 210, 131);
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
                                            font-weight: 500;
                                            -webkit-user-select: none;
                                            padding: 10px 0;
                                            font-size: 14px;
                                            border-radius: 6px;">
                                        @lang('emails.welcome_email.module_2_action', [], $userLocale)
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!--[if mso | IE]>
                    </td>
                  
                    <td
                       class="" style="vertical-align:top;width:199.99999999999997px;"
                    >
                <![endif]-->
                <div class="mj-column-per-33-33333333333333 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                        <tbody>
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                        <tbody>
                                            <tr>
                                                <td style="width:50px;"> <img alt="" height="auto" src="{{ env('ASSETS_URL', 'http://localhost:8000').'/images/landing/improve.svg' }}" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="50"> </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px;padding-top:3px;word-break:break-word;">
                                    <div style="font-size:14px;line-height:1;text-align:center;color:#9da3a3;margin-bottom: 20px;">
                                        <h3 style="color: #000000; font-weight: 700"> @lang('emails.welcome_email.module_3_title', [], $userLocale) </h3>
                                        <br>
                                        @lang('emails.welcome_email.module_3_subtitle', [], $userLocale)
                                    </div>
                                    <a href="{{ url(config('app.web_url')) . '/become-gamelancer' }}"
                                        style="
                                            background-color: rgb(2, 210, 131);
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
                                            font-weight: 500;
                                            -webkit-user-select: none;
                                            padding: 10px 0;
                                            font-size: 14px;
                                            border-radius: 6px;">
                                        @lang('emails.welcome_email.module_3_action', [], $userLocale)
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!--[if mso | IE]>
                            </td>
                  
                        </tr>
              
                    </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table>
@endsection
