<template>
  <div class="boxCore" id="flatform_setting">
    <section class="clearfix">
      <div class="filter_container clearfix">
        <span class="title_item">
          <button type="button" class="btn btn-create-platform" @click.stop="onClickedCreatePlatform()">
            <span class="icon-plus"></span> Create New Platform
          </button>
        </span>
        <span class="search_box">
          <input type="text" placeholder="Search"v-on:keyup.enter="search" class="form-control search_input" name="searchKey" v-model="searchKey"/>
        </span>
      </div>

      <div class="clearfix"></div>

      <div class="datatable">
        <data-table :getData="getData"
                    :limit="limit"
                    :widthTable="'100%'"
                    :column="column"
                    ref="datatable"
                    @DataTable:finish="onDatatableFinish">
          <th class="col1 text-left">ID</th>
          <th class="col2 text-left" data-sort-field="name">Name</th>
          <th class="col3 text-left" data-sort-field="code">Code</th>
          <th class="col4 text-left" data-sort-field="icon">Icon</th>
          <th class="col5 text-right">Actions</th>
          
          <template slot="body" slot-scope="props">
            <template v-if="rows[ props.index ].editable === false">
              <tr>
                <td class="col1 text-left">
                  {{ props.realIndex }}
                </td>
                <td class="col2 text-left">
                  {{ props.item.name }}
                </td>
                <td class="col3 text-left">
                  {{ props.item.code }}
                </td>
                <td class="col4 text-left">
                  <img :src="props.item.icon">
                </td>
                <td class="col5 text-right">
                  <button type="button" class="btn btn_edit_user" @click.stop="onClickRemove(rows[ props.index ].id)">
                    <i class="fa fa-trash-o"></i>
                  </button>
                  <button type="button" class="btn btn_save_user" @click.stop="onClickEdit(props.index)">
                    <i class="fa fa-pencil"></i>
                  </button>
                </td>
              </tr>
            </template>
            <template v-else>
              <td class="col1"></td>
              <td class="col2 text-left">
                <input class="form-control" 
                       type="text"
                       maxlength="15"
                       name="name"
                       data-vv-as="name"
                       v-validate="'required'"
                       data-vv-validate-on="none"
                       @focus="resetError"
                       v-model="params.name" />
                <span v-show="errors.has('name')" class="error has-error">
                  {{ errors.first('name') }}
                </span>
              </td>
              <td class="col3 text-left">
                <input class="form-control" 
                       type="text"
                       maxlength="6"
                       name="code"
                       data-vv-as="code"
                       v-validate="'required'"
                       data-vv-validate-on="none"
                       @focus="resetError"
                       v-model="params.code" />
                <span v-show="errors.has('code')" class="error has-error">
                  {{ errors.first('code') }}
                </span>
              </td>
              <td class="col4-edit text-left">
                <template v-if="!isUpdate">
                  <input type="file" 
                         accept="image/x-png,image/gif,image/jpeg"
                         @change="onChangePlatformIcon"
                         v-validate="'image|size:2048|required'"
                         name="icon" 
                         data-vv-validate-on="none"
                         @focus="resetError"
                         class="choose-file form-control"
                         ref="fileInput"
                         id="customFile">
                </template>
                <template v-else>
                  <input type="file" 
                         accept="image/x-png,image/gif,image/jpeg"
                         @change="onChangePlatformIcon"
                         v-validate="'image|size:2048'"
                         name="icon" 
                         data-vv-validate-on="none"
                         @focus="resetError"
                         class="choose-file form-control"
                         ref="fileInput"
                         id="customFile">
                </template>
                <label class="custom-file-label" for="customFile">{{ inputImage ? inputImage.name : 'Choose File' }}</label>
                <span v-show="errors.has('icon')" class="error has-error" id="icon-fixed">
                  {{ errors.first('icon') }}
                </span>
                <div class="preview-image">
                  <img :src="imgPath ? imgPath : params.icon" v-if="isUpdate || imgPath">
                </div>
              </td>
              <td class="col5 text-right">
                <button type="button" class="btn btn_edit_user" @click.stop="onClickCancel()">
                  <i class="icon-close"></i>
                </button>
                <button type="button" class="btn btn_save_user" @click.stop="onClickSubmit()">
                  <i class="icon-save"></i>
                </button>
              </td>
            </template>
          </template>
        </data-table>
      </div>
    </section>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import Modal from '../../components/Modal';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';

  export default {
    components: {
      Modal
    },
    mixins: [RemoveErrorsMixin],
    data() {
      return {
        limit: 10,
        column: 5,
        titlePage: this.$t('setting.platform'),
        searchKey: '',
        params: {},
        rows: [],
        platformModal: 'PlatformModal',
        isUpdate: false,
        imgPath: null,
        inputImage: '',
      }
    },
    methods: {
      onDatatableFinish() {
        this.rows = this.$refs.datatable.rows;
        window._.each(this.rows, item => {
          this.$set(item, 'editable', false);
        });
      },

      search() {
        this.$refs.datatable.$emit('DataTable:filter', Object.assign(this.params, {search_key: this.searchKey}));
      },

      getData(params) {
        return rf.getRequest('SettingRequest').getPlatform(params);
      },

      onClickedCreatePlatform() {
        this.inputImage = '';
        this.imgPath = null;
        this.isUpdate = false;
        for(let i = 0; i < this.rows.length; i++) {
          this.rows[i].editable = false;
        }
        this.params = {};
        if(this.rows[this.rows.length -1].id) {
          this.rows.push({ editable: true });
        }
        else {
          this.rows.splice(this.rows.length -1 , 1);
          this.rows.push({ editable: true });
        }
      },
      onClickEdit(index) {
        this.imgPath = null;
        this.rows[index].editable = true;
        this.isUpdate = true;
        for(let i = 0; i < this.rows.length; i++) {
          if(i !== index) {
            this.rows[i].editable = false;
          }
          if( !this.rows[i].id) {
            this.rows.splice(i, 1);
          }
        }
        this.params = JSON.parse(JSON.stringify(this.rows[index]));
      },

      onClickCancel() {
        this.refresh();
      },

      onChangePlatformIcon(e) {
        this.imgPath = null;
        this.inputImage = '';
        let files = e.target.files || e.dataTransfer.files;
        if (!files.length)
          return;
        this.imgPath = URL.createObjectURL(e.target.files[0]);
        this.inputImage = files[0];
      },
      async onClickSubmit() {

        if (this.isSubmitting) {
          return;
        }

        this.resetError();

        await this.$validator.validate('name');
        await this.$validator.validate('code');
        await this.$validator.validate('icon');
        if (this.errors.any()) {
          return;
        }
        this.createNewOrUpdatePlatform();

      },
      createNewOrUpdatePlatform() {
        let formData = new FormData();
        if (window._.isEmpty(this.params.id)) {
          formData.append('id', this.params.id);
        }
        formData.append('name', this.params.name);
        formData.append('code', this.params.code);
        if(this.inputImage){
          formData.append('icon', this.inputImage, this.inputImage.name);
        }
        if (!this.isUpdate) {
          return this.requestHandler(rf.getRequest('SettingRequest').createNewPlatform(formData));
        }
        return this.requestHandler(rf.getRequest('SettingRequest').updatePlatform(formData));
      },

      requestHandler(promise) {
        this.startSubmit();
        promise.then(res => {
          this.endSubmit();
          this.hideModal();

          if (!this.isUpdate) {
            this.showSuccess(this.$t('common.create_sucessful'));
            this.refresh();
          } else {
            this.showSuccess(this.$t('common.update_sucessful'));
            this.refresh();
          }
        })
        .catch(error => {
          this.endSubmit();
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error);
        });
      },
      onClickRemove(params) {
        this.params = params;
        window.ConfirmationModal.show({
          type        : 'confirm',
          title       : '',
          content     : 'Do you want to remove this platform?',
          onConfirm   :  () => {
            this.onClickedRemovePlatform();
          },
          onCancel    : () => {}
        });
      },
      onClickedRemovePlatform() {
        this.startSubmit();
        rf.getRequest('SettingRequest').removePlatform({id: this.params}).then(res => {
          this.endSubmit();
          this.showSuccess(this.$t('common.remove_sucessful'));
          this.refresh();
        })
        .catch(error => {
          this.endSubmit();
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error);
          if (this.errors.has('error')) {
            this.showError(error.response.data.message);
          }
        });
      },
      refresh() {
        this.$refs.datatable.refresh();
      },
      hideModal() {
        window.CommonModal.hide(this.platformModal);
      }
    },
    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this);
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/common";

  .col3-edit {
    display: inline-flex;
    input {
      position: relative;
      top: 3px;
    }
  }

  .custom-file-label {
    @include custom-file-input();
  }
  input[type='file'] {
    visibility: hidden;
  }

  .btn-create-platform {
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

  #flatform_setting {
    width: 750px;
    .filter_container {
      margin: 12px 0px;
      width: 100%;
      .title_item {
        color: $color_mine_shaft;
        font-size: $font_big_20;
        font-weight: 500;
        line-height: 28px;
        float: left;
      }
      .search_box {
        display: inline-block;
        float: right;
        width: 215px;
        max-width: 100%;
        .search_input {
          background-color: transparent;
          height: 28px;
          border: 1px solid $color_alto;
          padding: 4px 15px;
          line-height: 20px;
          width: 100%;
          overflow: hidden;
          white-space: nowrap;
          text-overflow: ellipsis;
          font-size: $font-small;
        }
      }
    }
    table {
      thead {
      }
      tbody {
        tr:hover {
          .btn_edit_user, .btn_save_user {
            background-color: $color_champagne;
          }
        }
        tr {
          .btn_edit_user:active,.btn_edit_user:hover, 
          .btn_save_user:active, .btn_save_user:hover {
            background-color: $color_eden;
            color: $color_white;
          }
        }
      }
      td {
        vertical-align: initial;
        .btn_edit_user, .btn_save_user {
          padding: 5px;
          height: 32px;
          width: 29px;
        }
        .btn_edit_user:hover > i:before, .btn_save_user:hover > i:before {
          color: white;
        }
      }
      .col1 {
        width: 65px;
      }
      .col2 {
        min-width: 150px;
      }
      .col3 {
        min-width: 120px;
      }
      .col4 {
        img {
          width: 48px;
        }
      }
      .col5 {
        min-width: 120px;
        .icon-close {
          font-size: 11px;
        }
        .icon-save {
          font-size: 12px;
        }
      }
      #icon-fixed {
        position: relative;
        top: -45px;
      }

      .preview-image {
        position: relative;
        top: -36px;
        margin-bottom: -36px;
        img {
          width: 72px;
          height: auto;
          max-height: 80px;
          overflow: hidden;
        }
      }
    }

    .modal-title {
      padding-bottom: 10px;
    }

    .error {
      color: $red;
    }
    .row-item {
      margin-bottom: 15px;
      .required {
        color: red;
      }
    }
    .submit {
      text-align: center;
      margin-top: 30px;
      margin-bottom: 10px;
      cursor: pointer;
      a {
        border: 3px solid #55d184;
        border-radius: 50px;
        color: $color_white;
        background: #55d184;
        margin: 0px 15px 0px 10px;
        cursor: pointer;
        &:hover, active, focus {
          color: $deep_sapphire;
          background: $color_white;
          text-decoration: none;
        }
        width: 60%;
        height: 40px;
        padding: 10px 50px;
      }
    }

  }
</style>
