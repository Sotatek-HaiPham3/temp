<template>
  <div class="boxCore" id="restrict_pricing_setting">
    <section class="clearfix">
      <div class="filter_container clearfix">
        <button class="btn-create-new" @click="onClickCreateNew()"><span class="icon-plus"></span> Create New User Restrict pricing</button>
        <div class="form-create-new" v-if="isCreateNew">
          <div class="col1">
            <label>Username</label>
            <input class="form-control" 
                   type="text"
                   maxlength="150"
                   name="username"
                   data-vv-as="username"
                   v-validate="'required|max:255'"
                   data-vv-validate-on="none"
                   without-decimals
                   @focus="resetError"
                   @input="searchUser"
                   v-model="params.username" />
            <div v-if="isShowUserOption" class="user-option">
              <span v-for="user in users" class="option" @click="onSelectUser(user.username)">{{ user.username }}</span>
            </div>
          </div>
          <div class="col2">
            <label>Min</label>
            <input-only-number class="form-control" 
                   type="text"
                   maxlength="10"
                   name="min"
                   data-vv-as="min"
                   v-validate="'required|max:255'"
                   data-vv-validate-on="none"
                   without-decimals
                   @input="users = {}"
                   @focus="resetError"
                   v-model="params.min" />
          </div>
          <div class="col3">
            <label>Max</label>
            <input-only-number class="form-control" 
                   type="text"
                   maxlength="10"
                   name="max"
                   data-vv-as="max"
                   v-validate="'required|max:255'"
                   data-vv-validate-on="none"
                   without-decimals
                   @input="users = {}"
                   @focus="resetError"
                   v-model="params.max" />
          </div>
          <div class="col4">
            <button class="btn" @click.stop="onCancel()">
              <i class="fa fa-times"></i>
            </button>
            <button class="btn" @click.stop="onClickSubmit()">
              <i class="fa fa-floppy-o"></i>
            </button>
          </div>
          <div class="clearfix"></div>
          <div class="col1">
            <span v-show="errors.has('username')" class="error has-error">
              {{ errors.first('username') }}
            </span>
          </div>
          <div class="col2">
            <span v-show="errors.has('min')" class="error has-error">
              {{ errors.first('min') }}
            </span>
          </div>
          <div class="col3">
            <span v-show="errors.has('max')" class="error has-error">
              {{ errors.first('max') }}
            </span>
          </div>
        </div>
        <div class="datatable">
          <data-table :getData="getUserRestrictPricing"
                      :limit="limit"
                      :column="column"
                      :widthTable="'800px'"
                      @DataTable:finish="onDatatableFinish"
                      ref="datatable">
            <th class="text-left">No.</th>
            <th class="text-left">Username</th>
            <th class="text-left">Min</th>
            <th class="text-left">Max</th>
            <th class="text-right">Actions</th>

            <template slot="body" slot-scope="props">
              <template v-if="rows[ props.index ].editable === false">
                <tr>
                  <td class="text-left">
                    {{ props.realIndex }}
                  </td>
                  <td class="text-left">
                    {{ props.item.username }}
                  </td>
                  <td class="text-left">
                    {{ props.item.min }}
                  </td>
                  <td class="text-left">
                    {{ props.item.max }}
                  </td>
                  <td class="text-right">
                    <button class="btn" @click.stop="onClickEdit(props.index)">
                      <i class="fa fa-pencil"></i>
                    </button>
                    <button class="btn" @click.stop="onClickRemove(props.item.id)">
                      <i class="fa fa-trash-o"></i>
                    </button>
                  </td>
                </tr>
              </template>
              <template v-else>
                <tr>
                  <td class="text-left">
                    {{ props.realIndex }}
                  </td>
                  <td>
                    {{ rows[ props.index ].username }}
                  </td>
                  <td>
                    <input-only-number class="form-control" 
                           type="text"
                           maxlength="10"
                           name="min"
                           data-vv-as="min"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           without-decimals
                           @focus="resetError"
                           v-model="params.min" />
                    <span v-show="errors.has('min')" class="error has-error">
                      {{ errors.first('min') }}
                    </span>
                  </td>
                  <td>
                    <input-only-number class="form-control" 
                           type="text"
                           maxlength="10"
                           name="max"
                           data-vv-as="max"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           without-decimals
                           @focus="resetError"
                           v-model="params.max" />
                    <span v-show="errors.has('max')" class="error has-error">
                      {{ errors.first('max') }}
                    </span>
                  </td>
                  <td class="text-right">
                    <button type="button" class="btn btn_edit_user cancel" @click.stop="rows[props.index].editable = false">
                      <i class="fa fa-times"></i>
                    </button>
                    <button type="button" class="btn btn_edit_user" @click.stop="onClickSubmit()">
                      <i class="fa fa-floppy-o"></i>
                    </button>
                  </td>
                </tr>
              </template>
            </template>
          </data-table>
        </div>
      </div>

      <loading :isLoading="isLoading"/>
    </section>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';
  import { debounce, isEmpty } from 'lodash';
  import InputOnlyNumber from '../../common/InputOnlyNumber';
  export default {
    mixins: [RemoveErrorsMixin],
    components: {
      InputOnlyNumber
    },
    data () {
      return {
        limit: 10,
        column: 5,
        rows: [],
        params: {},
        titlePage: 'User Restrict Pricings',
        isLoading: false,
        isCreateNew: false,
        users: {}
      }
    },
    computed: {
      isShowUserOption() {
        return !isEmpty(this.users);
      }
    },
    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this)
    },
    methods: {
      getUserRestrictPricing (params) {
        return rf.getRequest('UserRequest').getUserRestrictPricing(params)
      },
      onDatatableFinish () {
        this.rows = this.$refs.datatable.rows
        window._.each(this.rows, item => {
          this.$set(item, 'editable', false)
        })
      },
      onClickEdit(index) {
        this.isCreateNew = false
        this.rows[index].editable = true
        for(let i = 0; i < this.rows.length; i++) {
          if(i !== index) {
            this.rows[i].editable = false
          }
        }
        this.params = JSON.parse(JSON.stringify(this.rows[index]))
      },
      onClickCreateNew () {
        this.params = {}
        this.isCreateNew = true
        window._.forEach(this.rows, item => {
          item.editable = false
        })
      },
      async onClickSubmit() {
        this.users = {}
        if (this.isSubmitting) {
          return
        }
        await this.$validator.validateAll()
        if (this.errors.any()) {
          return
        }
        if (this.params.id) {
          return this.requestHandler(rf.getRequest('UserRequest').updateUserRestrictPricing(this.params))
        }
        return this.requestHandler(rf.getRequest('UserRequest').createUserRestrictPricing(this.params))
      },
      onClickRemove (id) {
        window.ConfirmationModal.show({
          type        : 'confirm',
          title       : '',
          content     : 'Do you want to remove this user restrict pricing?',
          onConfirm   :  () => {
            return this.requestHandler(rf.getRequest('UserRequest').deleteUserRestrictPricing({ id: id }))
          },
          onCancel    : () => {}
        })
      },
      requestHandler(promise) {
        this.startSubmit()
        this.isLoading = true
        promise.then(res => {
          this.endSubmit()
          this.isLoading = false
          this.showSuccess('Successful')
          this.refresh()
        })
        .catch(error => {
          this.isLoading = false
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error);
        })
        .finally(() => {
          this.endSubmit()
        })
      },
      refresh() {
        this.params = {}
        this.isCreateNew = false
        this.$refs.datatable.refresh()
      },
      searchUser: debounce(function () {
        return rf.getRequest('UserRequest').getUsernames({search_key: this.params.username})
          .then(res => {
            this.users = res.data
          })
      }, 400),
      onSelectUser (username) {
        this.users = {};
        this.params.username = username;
      },
      onCancel () {
        this.isCreateNew = false
        this.params = {}
      }
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/common";
  #restrict_pricing_setting {
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
    }
    .col1 {
      width: 200px;
      display: inline-block;
    }
    .col2, .col3 {
      width: 225px;
      display: inline-block;
    }
    .col4 {
      width: 118px;
      display: inline-block;
      text-align: right;
    }
    .error {
      &.has-error {
        color: #e2221e;
      }
    }
    .btn-create-new {
      border: 1px solid #12575f;
      line-height: 20px;
      padding: 3px 12px;
      font-size: 14px;
      font-weight: bold;
      border-radius: 22px;
      text-align: center;
      color: #12575f;
      transition: 0.5s;
      min-width: 86px;
      cursor: pointer;
      text-transform: uppercase;
      margin: 12px 0 20px;
      &:hover {
        background-color: #12575f;
        border-color: #12575f;
        color: white;
        transition: 0.5s;
      }
      .icon-plus {
        font-size: 10px;
        line-height: 20px;
      }
    }
    .form-create-new {
      margin-bottom: 20px;
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
    select {
      height: 35px;
      border: thin solid #d2d6de;
      background-color: #fff;
      width: 80px;
      cursor: pointer;
    }
  }
</style>
