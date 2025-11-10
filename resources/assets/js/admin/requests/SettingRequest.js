import BaseRequest from '../lib/BaseRequest'

export default class SettingRequest extends BaseRequest {

    getPlatforms(params) {
        let url = '/admin/api/setting/platform';
        return this.get(url, params);
    }

    createNewPlatform(params) {
        let url = '/admin/api/setting/platform/create';
        return this.post(url, params);
    }

    updatePlatform(params) {
        let url = '/admin/api/setting/platform/update';
        return this.post(url, params);
    }

    removePlatform(params) {
        let url = '/admin/api/setting/platform/remove';
        return this.post(url, params);
    }

    getOffers(params) {
        let url = '/admin/api/setting/offer';
        return this.get(url, params);
    }

    createOffer(params) {
        let url = '/admin/api/setting/offer/create';
        return this.post(url, params);
    }

    updateOffer(params) {
        let url = '/admin/api/setting/offer/update';
        return this.post(url, params);
    }

    removeOffer(params) {
        let url = '/admin/api/setting/offer/remove';
        return this.post(url, params);
    }

    getSiteSettings (params) {
        let url = '/admin/api/setting/site';
        return this.get(url, params);
    }

    updateSiteSettings (params) {
        let url = '/admin/api/setting/site/update';
        return this.put(url, params);
    }

    getBanners (params) {
        let url = '/admin/api/setting/banner'
        return this.get(url, params)
    }

    updateBanner (params) {
        let url = '/admin/api/setting/banner/update'
        return this.post(url, params)
    }

    createBanner (params) {
        let url = '/admin/api/setting/banner/create'
        return this.post(url, params)
    }

    deleteBanner (params) {
        let url = '/admin/api/setting/banner/delete'
        return this.del(url, params)
    }

    getLevelingRankings (params) {
        let url = '/admin/api/setting/ranking'
        return this.get(url, params)
    }

    createRanking (params) {
        let url = '/admin/api/setting/ranking/create'
        return this.post(url, params)
    }

    updateRanking (params) {
        let url = '/admin/api/setting/ranking/update'
        return this.post(url, params)
    }

    deleteRanking (params) {
        let url = '/admin/api/setting/ranking/delete'
        return this.del(url, params)
    }

    getLevelingRewards (params) {
        let url = '/admin/api/setting/reward'
        return this.get(url, params)
    }

    createReward (params) {
        let url = '/admin/api/setting/reward/create'
        return this.post(url, params)
    }

    updateReward (params) {
        let url = '/admin/api/setting/reward/update'
        return this.post(url, params)
    }

    deleteReward (params) {
        let url = '/admin/api/setting/reward/delete'
        return this.del(url, params)
    }

    getLevelingTaskings (params) {
        let url = '/admin/api/setting/tasking'
        return this.get(url, params)
    }

    createTasking (params) {
        let url = '/admin/api/setting/tasking/create'
        return this.post(url, params)
    }

    updateTasking (params) {
        let url = '/admin/api/setting/tasking/update'
        return this.post(url, params)
    }

    deleteTasking (params) {
        let url = '/admin/api/setting/tasking/delete'
        return this.del(url, params)
    }

    getDailyCheckinPeriod () {
        let url = '/admin/api/setting/daily-checkin/period'
        return this.get(url)
    }

    updateDailyCheckinPeriod (params) {
        let url = '/admin/api/setting/daily-checkin/period/update'
        return this.post(url, params)
    }

    getDailyCheckinPoints (params) {
        let url = '/admin/api/setting/daily-checkin'
        return this.get(url, params)
    }

    createDailyCheckinPoint (params) {
        let url = '/admin/api/setting/daily-checkin/create'
        return this.post(url, params)
    }

    updateDailyCheckinPoint (params) {
        let url = '/admin/api/setting/daily-checkin/update'
        return this.post(url, params)
    }

    updateMultipleDailyCheckinPoints (params) {
        let url = '/admin/api/setting/daily-checkin/update/multiple'
        return this.post(url, params)
    }

}
