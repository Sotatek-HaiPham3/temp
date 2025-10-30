<template>
  <div id="community-change-name-request">
    <div class="datatable">
      <data-table :getData="getListRequestNameChange"
                  :limit="limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @DataTable:finish="onDatatableFinish">
        <th class="col1 text-left">No.</th>
        <th class="col2 text-center">Community Id</th>
        <th class="col3 text-center">User Request</th>
        <th class="col4 text-center">Reason</th>
        <th class="col5 text-center">Old Name</th>
        <th class="col6 text-center">New Name</th>
        <th class="col6 text-center">Status</th>

        <template slot="body" slot-scope="props">
          <tr>
            <td class="col1 text-left">
              {{ props.realIndex }}
            </td>
            <td class="col1 text-left">
              <template>
                {{ props.item.community_id }}
              </template>
            </td>
            <td class="col3 text-center">
              <template>
                {{ props.item.username }}
              </template>
            </td>
            <td class="col6 text-center">
              <template>
                {{ props.item.reason }}
              </template>
            </td>
            <td class="col6 text-center">
              <template>
                {{ props.item.old_name }}
              </template>
            </td>
            <td class="col6 text-center">
              <template>
                {{ props.item.new_name }}
              </template>
            </td>
            <td class="col8 text-center">
              <template v-if="props.item.status === Const.COMMUNITY_STATUS_PENDING">
                  <button class="btn btn-success" @click.stop="approveRequestNameChange(props.item)">
                    <span>Approve</span>
                  </button>
                  <button class="btn btn-warning" @click.stop="rejectRequestNameChange(props.item)">
                    <span>Reject</span>
                  </button>
              </template>
              <template v-else>
                <button type="button" :class="props.item.status === Const.COMMUNITY_STATUS_APPROVED ? 'btn btn-success' : 'btn btn-warning'">{{ props.item.status }}</button>
              </template>
            </td>
          </tr>
        </template>
      </data-table>
    </div>
  </div>
</template>

<script>

import RemoveErrorsMixin from "../../common/RemoveErrorsMixin";
import rf from "../../lib/RequestFactory";
import Const from "../../common/Const";

export default {
  mixins: [RemoveErrorsMixin],

  components: {
  },

  data() {
    return {
      Const,
      titlePage: 'Request Name Change',
      limit: 10,
      params: {},
      column: 6,
      rows: [],
      isSubmitting: false
    }
  },

  created() {
    this.$emit('EVENT_PAGE_CHANGE', this);
  },

  methods: {
    getListRequestNameChange (params) {
      return rf.getRequest('CommunityRequest').getListRequestNameChange(params)
    },

    async approveRequestNameChange (params) {
      if (this.isSubmitting) {
        return
      }
      let formData = new FormData()
      formData.append('id', params.id)
      formData.append('new_name', params.new_name)
      formData.append('status', Const.COMMUNITY_STATUS_APPROVED)
      formData.append('request_user_id', params.request_user_id)
      formData.append('community_id', params.community_id)

      return this.requestHandler(rf.getRequest('CommunityRequest').approveRequestNameChange(formData))
    },

    async rejectRequestNameChange (params) {
      if (this.isSubmitting) {
        return
      }

      let formData = new FormData()
      formData.append('id', params.id)
      formData.append('status', Const.COMMUNITY_STATUS_REJECT)
      formData.append('request_user_id', params.request_user_id)

      return this.requestHandler(rf.getRequest('CommunityRequest').rejectRequestNameChange(formData))
    },

    onDatatableFinish () {
      this.rows = this.$refs.datatable.rows
      _.each(this.rows, item => {
        this.$set(item, 'editable', false)
      })
    },

    refresh () {
      this.$refs.datatable.refresh()
    },

    requestHandler(promise) {
      promise.then(res => {
        this.refresh()
        this.showSuccess('Successful')
      })
      .catch(error => {
        if (!error.response) {
          this.showError(window.i18n.t("common.message.network_error"));
          return;
        }
        this.convertRemoteErrors(error);
      })
      .finally(() => {
        this.isSubmitting = false
      })
    },

    async validateForm () {
      await this.$validator.validateAll()

      if (!this.inputImage && window._.isEmpty(this.params.image)) {
        this.errors.add({
          field: 'image',
          msg: 'The image file is required.'
        })
      }

      const size = _.uniq(this.params.size_range.split(','))
      _.forEach(size, num => {
        if (!_.isInteger(parseInt(num))) {
          this.errors.add({
            field: 'size_range',
            msg: 'The size room is invalid.'
          })
          return false
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
  @import "../../../../sass/variables";
  @import "../../../../sass/common";
  #community-change-name-request {
    .custom-file-label {
      @include custom-file-input();
      max-width: 300px;
      padding: 0 12px;
      top: 1px;
      line-height: 34px;
      vertical-align: middle;
      &:after {
        padding: 0 12px;
        line-height: 34px;
      }
      &.create-label {
        max-width: unset;
      }
    }
    input[type='file'] {
      display: none;
    }
    .error {
      &.has-error {
        color: #e2221e;
      }
    }
    .filter_container {
      margin: 0px 0px 15px;
      .btn_creat_new {
        margin: 12px 0 20px;
        border: 1px solid $color_eden;
        line-height: 20px;
        padding: 3px 12px;
        font-size: $font_root;
        font-weight: bold;
        border-radius: 22px;
        text-align: center;
        color: $color_eden;
        transition: 0.5s;
        min-width: 86px;
        cursor: pointer;
        text-transform: uppercase;
        &:focus,
        &:active,
        &:hover {
          background-color: $color_eden;
          border-color: $color_eden;
          color: $color_white;
          transition: 0.5s;
        }
        .icon-plus {
          font-size: $font_mini_mini;
          float: left;
          margin-right: 5px;
          line-height: 20px;
        }
      }
      .create-new-form {
        background-color: $color_white;
        margin: 10px 0px;
        .col-xs-4 {
          margin-top: 10px;
        }
      }
    }
    .datatable {
      .col1 {
        width: 50px;
      }
      .col3 {
        width: 150px;
      }
      .col4, .col5 {
        width: 150px;
      }
      .col6 {
        width: 270px;
      }
      .col7 {
        width: 250px;
      }
    }
    .btn_save_setting {
      margin-top: 20px;
      color: #fff;
      background-color: #54cc75;
    }
    .btn_reject_setting {
      margin-top: 20px;
      color: #fff;
      background-color: #54cc75;
    }
  }
</style>
