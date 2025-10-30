<!DOCTYPE html>
<html lang="en">
  <head>
  </head>
  <body>
    <div class="form-contact-us">
      <label class="title">CONTACT US</label>
      <div id="contacts-us"></div>
      <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2.js"></script>
      <script>
        var HUBSPOT_FORM_PORTAL_ID = "{{ env('HUBSPOT_FORM_PORTAL_ID') }}";
        console.log('HUBSPOT_FORM_PORTAL_ID', HUBSPOT_FORM_PORTAL_ID);
        var HUBSPOT_FORM_ID = "{{ env('HUBSPOT_FORM_ID') }}" ;
        hbspt.forms.create({
          portalId: HUBSPOT_FORM_PORTAL_ID,
          formId: HUBSPOT_FORM_ID,
          target: "#contacts-us",
          css: "",
          cssClass: "contacts-us-mobile"
      });
      </script>
    </div>
    <style lang="scss" scoped>
      body {
        height: 100%;
        margin: 0px;
        background: transparent;
      }
      .form-contact-us {
        background: #fff;
        width: 700px;
        margin: 0 auto;
        margin-top: 30px;
        box-shadow: 0 4px 8px 0 rgba(53,105,128,0.3), 0 6px 20px 0 rgba(165,200,213,0.41);
        padding: 40px;
        text-align: center;
      }
      #contacts-us {
        margin-top: 30px;
      }
      .title {
        font-size: 30px;
      }
    </style>
    <style lang="scss">
      .contacts-us-mobile label {
        font-size: 14px;
        width: 130px;
        text-align: right;
        color: #33475b;
        display: block;
        float: none;
        width: auto;
        font-weight: 500;
        text-align: left;
        line-height: 20px;
        padding-top: 0;
        margin-bottom: 4px;
      }
      .contacts-us-mobile .hs-form-field label:not(.hs-error-msg) {
        color: #33475b;
      }
      .contacts-us-mobile .hs-form-field label {
        font-family: arial, helvetica, sans-serif;
        font-size: 13px;
      }
      .contacts-us-mobile .hs-input {
        display: inline-block;
        width: 90%;
        max-width: 500px;
        height: 40px;
        padding: 9px 10px;
        font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
        font-size: 16px;
        font-weight: normal;
        line-height: 22px;
        color: #33475b;
        background-color: #f5f8fa;
        border: 1px solid #cbd6e2;
        box-sizing: border-box;
        -webkit-border-radius: 3px;
        -moz-border-radius: 3px;
        -ms-border-radius: 3px;
        border-radius: 3px;
      }
      .contacts-us-mobile .field {
        margin-bottom: 18px;
      }
      .contacts-us-mobile textarea {
        -webkit-writing-mode: horizontal-tb !important;
        text-rendering: auto;
        color: -internal-light-dark-color(black, white);
        letter-spacing: normal;
        word-spacing: normal;
        text-transform: none;
        text-indent: 0px;
        text-shadow: none;
        display: inline-block;
        text-align: start;
        -webkit-appearance: textarea;
        background-color: -internal-light-dark-color(white, black);
        -webkit-rtl-ordering: logical;
        flex-direction: column;
        resize: auto;
        cursor: text;
        white-space: pre-wrap;
        overflow-wrap: break-word;
        margin: 0em;
        font: 400 13.3333px Arial;
        border-width: 1px;
        border-style: solid;
        border-color: rgb(169, 169, 169);
        border-image: initial;
        padding: 2px;
      }
      .contacts-us-mobile .hs-form-required {
        color: red;
      }
      .contacts-us-mobile textarea.hs-input {
          min-height: 120px;
      }
      .contacts-us-mobile .hs-button {
        background: #ff7a59;
        border-color: #ff7a59;
        color: #ffffff;
        font-size: 12px;
        font-family: arial, helvetica, sans-serif;
        margin: 0;
        cursor: pointer;
        display: inline-block;
        font-weight: 700;
        line-height: 12px;
        position: relative;
        text-align: center;
        background-color: #ff7a59;
        border-color: #ff7a59;
        border-radius: 3px;
        border-style: solid;
        border-width: 1px;
        padding: 12px 24px;
      }
      .contacts-us-mobile .hs-error-msgs label {
        color: #f2545b;
      }
      .contacts-us-mobile ul.no-list {
        margin-top: 0;
        list-style: none;
        padding: 0;
      }
    </style>
  </body>
