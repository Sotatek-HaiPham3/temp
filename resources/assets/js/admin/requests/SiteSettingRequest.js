import BaseRequest from '../lib/BaseRequest'

export default class SiteSettingRequest extends BaseRequest {

    getSocialNetworks() {
        let url = '/admin/api/socical-networks';
        return this.get(url);
    }

    addSocialNetwork(params) {
        let url = '/admin/api/socical-networks';
        return this.post(url, params);
    }

    updateSocialNetWork(params) {
        let url = '/admin/api/socical-networks/update';
        return this.post(url, params);
    }

    removeSocialNetwork(id) {
        let url = `api/socical-networks/${id}`;
        return this.del(url);
    }
}
