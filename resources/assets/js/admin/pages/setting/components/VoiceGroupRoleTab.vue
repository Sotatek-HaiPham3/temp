<template>
  <div id="voice-room-setting">
    <div class="filter_container clearfix">
      <button class="btn btn_creat_new" @click="openFormCreate()">
        <span class="icon-plus"></span> Create New
      </button>
      <div v-if="isCreateNew" class="create-new-form row">
        <div class="col-xs-4">
          <label>Username</label>
          <select v-model="params.user_id" class="form-control" v-validate="'required'">
            <option v-for="i in users" :value="i.id">
              {{ i.username }}
            </option>
          </select>
        </div>

        <div class="col-xs-4">
          <label>Role</label>
          <select class="form-control" v-model="params.role" v-validate="'required'" name="type">
            <option v-for="item in roleType" :value="item.value">{{ item.title }}</option>
          </select>
        </div>

        <div class="col-xs-4">
          <label>Action</label>
          <div class="clearfix"></div>
          <button class="btn" @click.stop="cancelCreateNew()">
            <i class="fa fa-times"></i>
          </button>
          <button class="btn" @click.stop="createRole()">
            <i class="fa fa-floppy-o"></i>
          </button>
        </div>

        <div class="clearfix"></div>

        <div class="col-xs-4">
          <span v-show="errors.has('user_id')" class="error has-error">
            {{ errors.first('user_id') }}
          </span>
        </div>

      </div>
    </div>

    <div class="datatable">
      <data-table :getData="getVoiceGroupRole"
                  :limit="limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @DataTable:finish="onDatatableFinish">
        <th class="col1 text-left">No.</th>
        <th class="col2 text-center">Username</th>
        <th class="col3 text-center">Role</th>
        <th class="col8 text-right">{{ $t('game.action') }}</th>

        <template slot="body" slot-scope="props">
          <tr v-if="!rows[props.index].editable">
            <td class="col1 text-left col-md-2">
              {{ props.realIndex }}
            </td>
            <td class="col2 text-center col-md-6">
              {{ props.item.username }}
            </td>
            <td class="col3 text-center col-md-2">
              {{ props.item.role }}
            </td>
            <td class="col4 text-right col-md-2">
              <button type="button" class="btn btn_save_user" @click="editRole(props.index)">
                <i class="fa fa-pencil"></i>
              </button>
              <button class="btn" @click.stop="deleteRole(props.item.user_id)">
                <i class="fa fa-trash-o"></i>
              </button>
            </td>
          </tr>

          <tr v-else>
            <td class="col1 text-left col-md-2">
              {{ props.realIndex }}
            </td>
            <td class="col2 text-center col-md-6">
              {{ props.item.username }}
            </td>
            <td class="col3 text-center col-md-2">
              <select class="form-control" v-model="params.role" v-validate="'required'" name="type">
                <option v-for="item in roleType" :value="item.value">{{ item.title }}</option>
              </select>
              <span v-show="errors.has('role')" class="error has-error">
                  {{ errors.first('role') }}
                </span>
            </td>
            <td class="col4 text-right col-md-2">
              <button class="btn" @click.stop="cancelEdit()">
                <i class="fa fa-times"></i>
              </button>
              <button class="btn" @click.stop="createRole()">
                <i class="fa fa-floppy-o"></i>
              </button>
            </td>
          </tr>
        </template>
      </data-table>
    </div>
  </div>
</template>

<script>
  import { mapGetters } from 'vuex'
  import rf from '../../../lib/RequestFactory'
  import RemoveErrorsMixin from '../../../common/RemoveErrorsMixin'
  import Const from '../../../common/Const'
  import { isEmpty, first } from "lodash";

  export default {
    mixins: [RemoveErrorsMixin],

    data () {
      return {
        titlePage: 'Voice Group Role',
        users: [],
        limit: 10,
        params: {
          user_id: null,
          role: Const.VOICE_GROUP_ROLE_ADMIN
        },
        column: 4,
        rows: [],
        isCreateNew: false,
        isSubmitting: false
      }
    },

    computed: {
      roleType () {
        return [
          { title: 'Admin', value: Const.VOICE_GROUP_ROLE_ADMIN },
          { title: 'Global Admin', value: Const.VOICE_GROUP_ROLE_GLOBAL_ADMIN }
        ]
      }
    },

    mounted () {
      this.$emit('EVENT_PAGE_CHANGE', this)
      this.getUSers()
    },

    methods: {
      getUSers () {
        return rf.getRequest('UserRequest').getUsernames()
          .then(res => {
            this.users = res.data || []
          })
      },

      onCancel () {
        this.isCreateNew = false
        this.params = {}
      },

      getVoiceGroupRole (params) {
        return rf.getRequest('SettingRequest').getVoiceGroupRole(params)
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

      openFormCreate () {
        this.isCreateNew = !this.isCreateNew
        const defaultUser = first(this.users) || {}

        this.params = {
          role: Const.VOICE_GROUP_ROLE_ADMIN,
          user_id: defaultUser?.id
        }
      },

      editRole (index) {
        this.isCreateNew = false
        this.rows[index].editable = true
        for(let i = 0; i < this.rows.length; i++) {
          if(i !== index) {
            this.rows[i].editable = false
          }
        }
        this.params = JSON.parse(JSON.stringify(this.rows[index]))
      },

      cancelCreateNew () {
        this.isCreateNew = false
        this.params = {}
      },

      cancelEdit () {
        for(let i = 0; i < this.rows.length; i++) {
          this.rows[i].editable = false
        }
        this.params = {}
      },

      async createRole () {
        if (this.isSubmitting) {
          return
        }
        let formData = new FormData()
        formData.append('user_id', this.params.user_id)
        formData.append('role', this.params.role)
        return this.requestHandler(rf.getRequest('SettingRequest').createVoiceGroupRole(formData))
      },

      deleteRole (user_id) {
        return this.requestHandler(rf.getRequest('SettingRequest').deleteVoiceGroupRole({ user_id }))
      },

      requestHandler(promise) {
        promise.then(res => {
          this.cancelCreateNew()
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
      }
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../../sass/common";
  #voice-room-setting {
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
    .user-option {
      background: #FFF;
      display: block;
      position: absolute;
      width: 200px;
      max-height: 200px;
      z-index: 10;
      border: 1px solid #cfcfcf;
      user-select: none;
      overflow-y: scroll;
      .option {
        width: 100%;
        display: block;
        padding-left: 10px;
        cursor: pointer;
        &:hover {
          background: #1ea1f2;
          color: white;
        }
      }
    }
  }
</style>
