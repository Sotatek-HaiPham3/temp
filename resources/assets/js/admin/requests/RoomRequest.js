import BaseRequest from '../lib/BaseRequest'

export default class RoomRequest extends BaseRequest {
  getRoomCategories (params) {
    let url = '/admin/api/setting/room/categories'
    return this.get(url, params)
  }

  createRoomCategory (params) {
    let url = '/admin/api/setting/room/category/create'
    return this.post(url, params)
  }

  updateRoomCategory (params) {
    let url = '/admin/api/setting/room/category/update'
    return this.post(url, params)
  }

  deleteRoomCategory (params) {
    let url = '/admin/api/setting/room/category/delete'
    return this.post(url, params)
  }
}
