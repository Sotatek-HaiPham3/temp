<template>
  <div class="boxCore" id="banner_setting">
    <section class="clearfix">
      <div class="filter_container clearfix">
        <button class="btn-create-new" @click="onClickCreateNew()"><span class="icon-plus"></span> Create New File</button>
        <div class="form-create-new row" v-if="isCreateNew">
          <div class="col-xs-4">
            <label class="game-form-label">Gallery Web <sup>*</sup>:</label>
            <div class="clearfix"></div>
            <div class="custom-file">
              <label class="custom-file-label" for="web_url">
                Choose File
              </label>
              <div class="clearfix"></div>
              <div class="error-box">
                <span v-show="errors.has('web_url')" class="f-left is-error">{{ errors.first('web_url') }}</span>
              </div>
              <input type="file"
                     id="web_url"
                     ref="inputLogo"
                     accept="image/x-png,image/jpg,image/jpeg"
                     @change="onChangeGalleryWeb"
                     @focus="resetError"
                     class="input-file">
            </div>
            <div class="clearfix"></div>
            <div class="review_image">
              <div class="group-thumbnail" v-if="imgPathWeb || params.web_url">
                <img :src="imgPathWeb ? imgPathWeb : params.web_url">
              </div>
            </div>
          </div>
          <div class="col-xs-4">
            <label class="game-form-label">Gallery App <sup>*</sup>:</label>
            <div class="clearfix"></div>
            <div class="custom-file">
              <label class="custom-file-label" for="app_url">
                Choose File
              </label>
              <div class="clearfix"></div>
              <div class="error-box">
                <span v-show="errors.has('app_url')" class="f-left is-error">{{ errors.first('app_url') }}</span>
              </div>
              <input type="file"
                     id="app_url"
                     ref="inputLogo"
                     accept="image/x-png,image/jpg,image/jpeg"
                     @change="onChangeGalleryApp"
                     @focus="resetError"
                     class="input-file">
            </div>
            <div class="clearfix"></div>
            <div class="review_image">
              <div class="group-thumbnail" v-if="imgPathApp || params.app_url">
                <img :src="imgPathApp ? imgPathApp : params.app_url">
              </div>
            </div>
          </div>

          <div class="col-xs-4 row">
            <label class="game-form-label">Action:</label>
            <div class="clearfix"></div>
            <button class="btn col-xs-2" @click.stop="onClickCancel()">
              <i class="fa fa-times"></i>
            </button>
            <button class="btn col-xs-2" @click.stop="onClickUpdate()">
              <i class="fa fa-floppy-o"></i>
            </button>
          </div>
          <div class="clearfix"></div>
          <div class="col-xs-3">
            <span v-show="errors.has('description')" class="error has-error">
              {{ errors.first('description') }}
            </span>
          </div>
          <div class="col-xs-3">
            <span v-show="errors.has('btn_caption')" class="error has-error">
              {{ errors.first('btn_caption') }}
            </span>
          </div>
        </div>

        <div class="datatable">
          <data-table :getData="getData"
                      :limit="limit"
                      :column="column"
                      @DataTable:finish="onDatatableFinish"
                      ref="datatable">
            <th class="text-left">No.</th>
            <th class="text-left">Gallery Web</th>
            <th class="text-left">Gallery App</th>
            <th class="text-right">Actions</th>

            <template slot="body" slot-scope="props">
              <template v-if="rows[ props.index ].editable === false">
                <tr>
                  <td class="text-left">
                    {{ props.realIndex }}
                  </td>
                  <td class="text-left">
                    <img class="thumbnail" :src="props.item.web_url">
                  </td>
                  <td class="text-left">
                    <img class="thumbnail" :src="props.item.app_url">
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
                  <td class="text-left">
                    <img class="thumbnail" :src="imgPathWeb ? imgPathWeb : params.web_url">
                    <label class="custom-file-label" for="web_url">
                      Choose File
                    </label>
                    <input type="file"
                      id="web_url"
                      ref="inputLogo"
                      accept="image/x-png,image/jpg,image/jpeg"
                      @change="onChangeGalleryWeb"
                      @focus="resetError"
                      class="input-file">
                    <span v-show="errors.has('web_url')" class="error has-error">
                      {{ errors.first('web_url') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <img class="thumbnail" :src="imgPathApp ? imgPathApp : params.app_url">
                    <label class="custom-file-label" for="app_url">
                      Choose File
                    </label>
                    <input type="file"
                           id="app_url"
                           ref="inputLogo"
                           accept="image/x-png,image/jpg,image/jpeg"
                           @change="onChangeGalleryApp"
                           @focus="resetError"
                           class="input-file">
                    <span v-show="errors.has('app_url')" class="error has-error">
                      {{ errors.first('app_url') }}
                    </span>
                  </td>

                  <td class="text-right">
                    <button class="btn" @click.stop="onClickCancel()">
                      <i class="fa fa-times"></i>
                    </button>
                    <button class="btn" @click.stop="onClickUpdate()">
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

  const MAXIMUM_FILESIZE = 10 * 1024 * 1024
  const ALLOW_FILETYPE = ['image/jpg', 'image/jpeg', 'image/png']

  export default {
    mixins: [RemoveErrorsMixin],

    data () {
      return {
        limit: 10,
        column: 9,
        rows: [],
        params: {},
        titlePage: 'Gallery Management',
        imgPathWeb: null,
        inputImageWeb: null,
        imgNameWeb: null,
        imgPathApp: null,
        inputImageApp: null,
        imgNameApp: null,
        isLoading: false,
        isCreateNew: false
      }
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this)
    },

    methods: {
      getData (params) {
        return rf.getRequest('CommunityRequest').getGallery(params)
      },

      onDatatableFinish () {
        this.rows = this.$refs.datatable.rows
        window._.each(this.rows, item => {
          this.$set(item, 'editable', false)
        })
      },

      onClickEdit(index) {
        this.resetImage()
        this.isCreateNew = false
        this.rows[index].editable = true
        for(let i = 0; i < this.rows.length; i++) {
          if(i !== index) {
            this.rows[i].editable = false
          }
        }
        this.params = JSON.parse(JSON.stringify(this.rows[index]))
      },

      onClickCancel() {
        this.isCreateNew = false
        this.refresh()
      },

      onClickCreateNew () {
        this.isCreateNew = true
        window._.forEach(this.rows, item => {
          item.editable = false
        })

        this.resetImage()
      },

      async onClickUpdate() {
        if (this.isSubmitting) {
          return
        }

        await this.$validator.validateAll()
        if (!this.inputImageWeb && window._.isEmpty(this.params.web_url)) {
          this.errors.add({
            field: 'web',
            msg: 'The gallery web file is required.'
          })
        }

        if (!this.inputImageApp && window._.isEmpty(this.params.app_url)) {
          this.errors.add({
            field: 'app',
            msg: 'The gallery for app file is required.'
          })
        }

        if (this.errors.any()) {
          return
        }

        let formData = new FormData()
        formData.append('id', this.params.id ? this.params.id : null)
        formData.append('web_url', this.inputImageWeb ? this.inputImageWeb : this.params.web_url)
        formData.append('app_url', this.inputImageApp ? this.inputImageApp : this.params.app_url)

        if (this.params.id) {
          return this.requestHandler(rf.getRequest('CommunityRequest').updateGallery(formData))
        }
        return this.requestHandler(rf.getRequest('CommunityRequest').createGallery(formData))
      },

      onClickRemove (id) {
        window.ConfirmationModal.show({
          type        : 'confirm',
          title       : '',
          content     : 'Do you want to remove this gallery?',
          onConfirm   :  () => {
            return this.requestHandler(rf.getRequest('CommunityRequest').deleteGallery({ id: id }))
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
          this.endSubmit()
          this.isLoading = false
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error);
        })
      },

      onChangeGalleryWeb(e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        let fileType = files[0].type
        if (!ALLOW_FILETYPE.includes(fileType)) {
          this.errors.add({
            field: 'web',
            msg: 'The gallery pc file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'web',
            msg: 'The gallery pc file size can not exceed 10MB!'
          })
          return
        }

        this.imgPathWeb = URL.createObjectURL(e.target.files[0])
        this.inputImageWeb = files[0]
        this.imgNameWeb = files[0].name
      },

      onChangeGalleryApp(e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        let fileType = files[0].type
        if (!ALLOW_FILETYPE.includes(fileType)) {
          this.errors.add({
            field: 'app',
            msg: 'The gallery smartphone file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'app',
            msg: 'The gallery smartphone file size can not exceed 10MB!'
          })
          return
        }

        this.imgPathApp = URL.createObjectURL(e.target.files[0])
        this.inputImageApp = files[0]
        this.imgNameApp = files[0].name
      },

      resetImage () {
        this.resetError()
        this.inputImageWeb = null
        this.imgPathWeb = null
        this.imgNameWeb = null
        this.inputImageApp = null
        this.imgPathApp = null
        this.imgNameApp = null
      },

      refresh() {
        this.resetImage()
        this.params = {}
        this.isCreateNew = false
        this.$refs.datatable.refresh()
      }
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/common";
  #banner_setting {
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

    .thumbnail {
      width: 200px;
    }

    .choose-file {
      width: 200px;
    }

    .col1 {
      width: 290px;
      display: inline-block;
    }

    .col2 {
      width: 280px;
      display: inline-block;
    }

    .col3 {
      width: 100px;
      display: inline-block;
    }

    .col4 {
      width: 118px;
      display: inline-block;
      text-align: right;
    }

    .col-description {
      width: 200px;
    }

    .col-link {
      width: 120px;
    }

    .col-title {
      width: 120px;
    }

    .col-caption {
      width: 120px;
    }

    .three-dots {
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 3;
      overflow: hidden;
      text-overflow: ellipsis;
      word-break: break-word;
      /* autoprefixer: off */
    }

    .error {
      &.has-error {
        color: #e2221e;
      }
    }

    .btn {
      margin: 3px 0px;
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

      .review_image {
        img {
          border-radius: 10px;
          width: 100%;
          max-width: 300px;
        }
        .group-thumbnail {
          width: 100%;
          max-width: 300px;
          position: relative;
          .thumbnail {
            width: 260px;
            height: auto;
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
