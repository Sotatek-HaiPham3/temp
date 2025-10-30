import BaseRequest from '../lib/BaseRequest'

export default class MarketingMailRequest extends BaseRequest {
  getTemplateMails(params) {
    let url = '/admin/api/template-mail';
    return this.get(url, params);
  }

  editTemplateMail(id) {
    let url = `/admin/api/template-mail/edit/${id}`;
    return this.get(url);
  }

  createTemplateMail(templateMail) {
    let url = '/admin/api/template-mail/create';
    return this.post(url, templateMail);
  }

  updateTemplateMail(templateMail) {
    let url = '/admin/api/template-mail/update';
    return this.post(url, templateMail);
  }

  deleteTemplateMail(templateMailId) {
    let url = `/admin/api/template-mail/delete/${templateMailId}`;
    return this.post(url);
  }

  sendMails(id, mail) {
    let url = `/admin/api/template-mail/${id}/send`;
    return this.post(url, mail);
  }

  getMarketingMailsHistory(params, id) {
    let url = `/admin/api/template-mail/${id}/history`;
    return this.post(url, params);
  }

  resendMail(params) {
    let url = '/admin/api/template-mail/resend';
    return this.get(url, params);
  }
}
