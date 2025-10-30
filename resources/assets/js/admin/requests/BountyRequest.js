import BaseRequest from '../lib/BaseRequest'

export default class BountyRequest extends BaseRequest {
  getBounties(params) {
    let url = '/admin/api/bounty';
    return this.get(url, params);
  }
  getBountyClaim(params) {
    let url = '/admin/api/bounty/request';
    return this.get(url, params);
  }
}
