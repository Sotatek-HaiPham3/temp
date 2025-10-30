import BaseRequest from '../lib/BaseRequest'

export default class CommunityRequest extends BaseRequest {
  getListRequestNameChange (params) {
    let url = '/admin/api/community/get-list-request-name-change'
    return this.get(url, params)
  }

  approveRequestNameChange (params) {
    let url = '/admin/api/community/approve-request-name-change'
    return this.post(url, params)
  }

  rejectRequestNameChange (params) {
    let url = '/admin/api/community/reject-request-name-change'
    return this.post(url, params)
  }

  getGallery (params) {
    let url = '/admin/api/community/gallery'
    return this.get(url, params)
  }

  updateGallery (params) {
    let url = '/admin/api/community/gallery/update'
    return this.post(url, params)
  }

  createGallery (params) {
    let url = '/admin/api/community/gallery/create'
    return this.post(url, params)
  }

  deleteGallery (params) {
    let url = '/admin/api/community/gallery/delete'
    return this.del(url, params)
  }
}
