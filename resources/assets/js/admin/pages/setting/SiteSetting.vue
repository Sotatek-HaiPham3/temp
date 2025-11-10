<template>
  <div id="site-setting">
    <div id="vip-setting" class="setting-row col-lg-6 col-xs-12">
      <div class="wrap-content">
        <h4>Gamelancer Vip</h4>
        <span class="setting-title">Auto Approve Gamelancer</span>
        <label class="switch">
          <input type="checkbox"
                v-model="settings.vip_link_approve"
                :checked="settings.vip_link_approve == 1 ? true : false">
          <span class="slider round"></span>
        </label>
        <div class="clearfix"></div>
        <button type="button" class="btn btn_save_setting" :id="TARGET_BUTTON.VIP" @click.stop="onClickSaveSettings($event)">
          Save
        </button>
      </div>
    </div>

    <div id="video-setting" class="setting-row col-lg-6 col-xs-12">
      <div class="wrap-content">
        <h4>Background Video</h4>
        <div class="setting-title label-url"><span>Video url</span></div>
        <div class="input-custom">
          <input class="form-control"
            type="text"
            name="url"
            v-validate.disable="'required'"
            @focus="resetError"
            placeholder="https://www.youtube.com/"
            v-model="settings.background_video_url">

          <span v-show="errors.has('url')" class="error has-error">
            {{ errors.first('url') }}
          </span>
        </div>
        <div class="clearfix"></div>
        <button type="button" class="btn btn_save_setting" :id="TARGET_BUTTON.VIDEO" @click.stop="onClickSaveSettings($event)">
          Save
        </button>
      </div>
    </div>

    <div id="vip-setting" class="setting-row col-lg-6 col-xs-12">
      <div class="wrap-content">
        <h4>Bounty Feature</h4>
        <span class="setting-title">Configure Pull Down/Go Live</span>
        <label class="switch">
          <input type="checkbox"
                v-model="settings.visible_bounty_feature"
                :checked="settings.visible_bounty_feature == 1 ? true : false">
          <span class="slider round"></span>
        </label>
        <div class="clearfix"></div>
        <button type="button" class="btn btn_save_setting" :id="TARGET_BUTTON.BOUNTY" @click.stop="onClickSaveSettings($event)">
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
