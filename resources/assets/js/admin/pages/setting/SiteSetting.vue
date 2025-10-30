<template>
  <div id="site-setting">
    <div class="setting-row col-lg-6 col-xs-12">
      <div class="wrap-content">
        <h4>Socials</h4>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Monthly Views</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_monthly_views"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Total Monthly Views"
                   v-model="settings.total_monthly_views">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Followers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Total Followers"
                   v-model="settings.total_followers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Snapchat Subscribers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Snapchat Subscribers"
                   v-model="settings.snapchat_subscribers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Snapchat Egirl Subscribers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Snapchat Egirl Subscribers"
                   v-model="settings.snapchat_egirl_subscribers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Snapchat Minecraft Subscribers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Snapchat Minecraft Subscribers"
                   v-model="settings.snapchat_minecraft_subscribers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Snapchat GTA Subscribers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Snapchat GTA Subscribers"
                   v-model="settings.snapchat_gta_subscribers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Snapchat Gamelancer Subscribers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Snapchat Gamelancer Subscribers"
                   v-model="settings.snapchat_gamelancer_subscribers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Snapchat Gaming Subscribers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Snapchat Gaming Subscribers"
                   v-model="settings.snapchat_gaming_subscribers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>Snapchat Game Gear Subscribers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="Snapchat Game Gear Subscribers"
                   v-model="settings.snapchat_game_gear_subscribers">
          </div>
        </div>

        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Gaming Followers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Gaming Followers"
                   v-model="settings.tiktok_gaming_followers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Gaming Likes</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Gaming Likes"
                   v-model="settings.tiktok_gaming_likes">
          </div>
        </div>

        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Gamelancer Followers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Gamelancer Followers"
                   v-model="settings.tiktok_gamelancer_followers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Gamelancer Likes</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Gamelancer Likes"
                   v-model="settings.tiktok_gamelancer_likes">
          </div>
        </div>

        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Gamer Followers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Gamer Followers"
                   v-model="settings.tiktok_gamer_followers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Gamer Likes</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Gamer Likes"
                   v-model="settings.tiktok_gamer_likes">
          </div>
        </div>

        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Egirl Followers</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Egirl Followers"
                   v-model="settings.tiktok_egirl_followers">
          </div>
        </div>
        <div class="block clearfix">
          <div class="setting-title label-url"><span>TikTok Egirl Likes</span></div>
          <div class="input-custom">
            <input class="form-control"
                   type="text"
                   name="total_followers"
                   v-validate.disable="'required'"
                   @focus="resetError"
                   placeholder="TikTok Egirl Likes"
                   v-model="settings.tiktok_egirl_likes">
          </div>
        </div>

        <div class="clearfix"></div>
        <button type="button" class="btn btn_save_setting" :id="TARGET_BUTTON.SOCIAL_SETTING" @click.stop="onClickSaveSettings($event)">
          Save
        </button>
      </div>
    </div>
    <div id="vip-setting" class="setting-row col-lg-6 col-xs-12">
      <div class="wrap-content">
        <h4>Clear Cache</h4>
        <div class="clearfix"></div>
        <button type="button" class="btn btn_save_setting" :id="TARGET_BUTTON.CLEAR" @click.stop="onClickClearCache()">
          Clear
        </button>
      </div>
    </div>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';
  const TARGET_BUTTON = {
    VIP: 'btnSaveVip',
    VIDEO: 'btnSaveVideo',
    BOUNTY: 'btnSaveBounty',
    SOCIAL_SETTING: 'btnSaveSocialSetting'
  }

  export default {
    mixins: [RemoveErrorsMixin],

    data () {
      return {
        titlePage: 'General',
        settings: {
          bounty_fee: 0,
          session_fee: 0,
          vip_link_approve: 0,
          background_video_url: ''
        },
        targetButton: '',
        params: {},
        TARGET_BUTTON
      }
    },

    mounted () {
      this.$emit('EVENT_PAGE_CHANGE', this)
      this.getSettingsData()
    },

    methods: {
      getSettingsData () {
        return rf.getRequest('SettingRequest').getSiteSettings()
          .then(res => {
            this.settings = res.data
          })
      },

      async onClickSaveSettings (e) {
        this.targetButton = e.currentTarget.id
        if (this.targetButton === TARGET_BUTTON.VIDEO) {
          await this.$validator.validateAll()
          if (this.errors.any()) {
            return
          }
        }

        window.ConfirmationModal.show({
          type        : 'confirm',
          title       : '',
          content     : 'Do you want to save this setting?',
          onConfirm   :  () => {
            this.updateSettingsData()
          },
          onCancel    : () => {}
        });
      },

      updateSettingsData () {
        switch(this.targetButton) {
          case TARGET_BUTTON.VIP:
            this.params = {
              vip_link_approve: this.settings.vip_link_approve ? 1 : 0
            }
            break
          case TARGET_BUTTON.VIDEO:
            this.params = {
              background_video_url: this.settings.background_video_url
            }
            break
          case TARGET_BUTTON.BOUNTY:
            this.params = {
              visible_bounty_feature: this.settings.visible_bounty_feature ? 1 : 0
            }
            break
        }
        this.params = this.settings

        return rf.getRequest('SettingRequest').updateSiteSettings(this.params)
          .then(res => {
            this.showSuccess(this.$t('common.update_sucessful'));
          })
          .catch(error => {
            if (!error.response) {
              this.showError(window.i18n.t("common.message.network_error"));
              return;
            }
            this.convertRemoteErrors(error);
          })
      },

      onClickClearCache () {
        return rf.getRequest('AdminRequest').clearCache()
          .then(res => {
            this.showSuccess(this.$t('common.update_sucessful'));
          })
          .catch(error => {
            this.showError(window.i18n.t("common.message.network_error"));
          })
      }
    }
  }
</script>

<style lang="scss" scoped>
  #site-setting {
    .setting-row {
      padding: 20px;
    }

    .wrap-content {
      background-color: #fff;
      padding: 20px;
      min-height: 190px;
    }

    .block {
      .setting-title {
        width: 40%;
      }
    }

    .setting-title {
      padding: 10px;
      line-height: 20px;
      vertical-align: middle;
      margin: 0 15px;

      &.label-url {
        float: left;
      }
    }

    .input-custom {
      width: 350px;
      display: inline-block;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 40px;
      height: 20px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      -webkit-transition: .4s;
      transition: .4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      background-color: #f1f1f1;
      -webkit-transition: .4s;
      transition: .4s;
    }

    input:checked + .slider {
      background-color: #54cc75;
    }

    input:focus + .slider {
      box-shadow: 0 0 1px #54cc75;
    }

    input:checked + .slider:before {
      -webkit-transform: translateX(25px);
      -ms-transform: translateX(25px);
      transform: translateX(25px);
    }

    .slider.round {
      border-radius: 34px;
    }

    .slider.round:before {
      border-radius: 50%;
    }

    .btn_save_setting {
      margin-top: 20px;
      color: #fff;
      background-color: #54cc75;
    }

    .error {
      &.has-error {
        color: #e2221e;
      }
    }
  }
</style>
