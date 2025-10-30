import BaseRequest from '../lib/BaseRequest'

export default class AdminRequest extends BaseRequest {

  clearCache() {
    let url = '/admin/api/clear-cache';
    return this.post(url);
  }

  getUsers(params) {
    let url = '/admin/api/users2';
    return this.get(url, params);
  }

  getGamelancerForms(params) {
    let url = '/admin/api/gamelancer-forms';
    return this.get(url, params);
  }

  approveGamelancer(params) {
    let url = '/admin/api/gamelancer-forms/approveGamelancer';
    return this.put(url, params);
  }

  approveFreeGamelancer(params) {
    let url = '/admin/api/gamelancer-forms/approveFreeGamelancer';
    return this.put(url, params);
  }

  disapproveGamelancer(params) {
    let url = '/admin/api/gamelancer-forms/disapproveGamelancer';
    return this.put(url, params);
  }

  getInvitationCodes(params) {
    let url = '/admin/api/invitation-codes';
    return this.get(url, params);
  }

  getUserBalances(params) {
    return this.get('/admin/api/user-balances', params);
  }

  getUserTransactions(params) {
    return this.get('/admin/api/user-transactions', params);
  }

  updateUser(params) {
    let url = '/admin/api/user/update';
    return this.post(url, params);
  }

  getUserLoginHistory(params) {
    let url = '/admin/api/user-login-history/';
    return this.get(url, params);
  }

  getAdmins(params) {
    const url = '/admin/api/administrators';
    return this.get(url, params);
  }

  getAdministratorById(id) {
    const url = `/admin/api/administrators/${id}`;
    return this.get(url);
  }

  updateAdministrator(params) {
    const url = '/admin/api/administrators/update';
    return this.post(url, params);
  }

  createAdministrator(params) {
    const url = '/admin/api/administrators/create';
    return this.post(url, params);
  }

  deleteAdministrator(id) {
    const url = '/admin/api/administrators/delete';
    return this.del(url, { id: id });
  }

  updateUserBalance(params) {
    let url = '/admin/api/user-balances/update';
    return this.post(url, params);
  }

  updateExcuteTransaction(params) {
    const url = '/admin/api/user-transactions/excute-transaction';
    return this.post(url, params);
  }

  getGamelancerInfoDetail(params) {
    const url = '/admin/api/gamelancer-forms/detail';
    return this.get(url, params);
  }

  getMasterdata() {
    const url = '/admin/api/masterdata';
    return this.get(url);
  }

  getReviews(params) {
    const url = '/admin/api/reviews'
    return this.get(url, params)
  }

  deleteReivew(params) {
    let url = '/admin/api/reviews/delete'
    return this.del(url, params)
  }

  getGamesData (params) {
    let url = '/admin/api/masterdata/games'
    return this.get(url, params)
  }
}
