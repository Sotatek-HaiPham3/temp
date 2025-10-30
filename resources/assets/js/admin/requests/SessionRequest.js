import BaseRequest from '../lib/BaseRequest'

export default class SessionRequest extends BaseRequest {
  getSession(params) {
    let url = '/admin/api/session';
    return this.get(url, params);
  }

  getSessionDetail(params) {
    const url = '/admin/api/session/detail'
    return this.get(url, params)
  }
}
