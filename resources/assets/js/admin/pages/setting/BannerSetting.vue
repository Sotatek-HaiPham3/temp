<template>
  <div class="boxCore" id="banner_setting">
    <section class="clearfix">
      <div class="filter_container clearfix">
        <button class="btn-create-new" @click="onClickCreateNew()"><span class="icon-plus"></span> Create New Banner</button>
        <div class="form-create-new row" v-if="isCreateNew">
          <div class="col-xs-4">
            <img v-if="imgPath || params.thumbnail"
              class="thumbnail"
              :src="imgPath ? imgPath : params.thumbnail">
            <label class="custom-file-label create-label" for="thumbnail">
              Choose File
            </label>
            <input type="file"
              id="thumbnail"
              ref="inputLogo"
              accept="image/x-png,image/jpg,image/jpeg"
              @change="onChangeThumbnail"
              @focus="resetError"
              class="input-file">
            <div class="clearfix"></div>
          </div>
          <div class="col-xs-4">
            <input class="form-control"
              type="text"
              name="link"
              placeholder="Enter redirect link"
              v-model="params.link">
          </div>
          <div class="col-xs-4">
            <input class="form-control"
              type="text"
              name="title"
              v-validate="'max:50'"
              maxlength="50"
              placeholder="Enter banner title"
              v-model="params.title">
          </div>
          <div class="clearfix"></div>
          <div class="col-xs-4">
            <span v-show="errors.has('thumbnail')" class="error has-error">
              {{ errors.first('thumbnail') }}
            </span>
          </div>
          <div class="col-xs-4">
            <span v-show="errors.has('link')" class="error has-error">
              {{ errors.first('link') }}
            </span>
          </div>
          <div class="col-xs-4">
            <span v-show="errors.has('title')" class="error has-error">
              {{ errors.first('title') }}
            </span>
          </div>
          <div class="clearfix"></div>
          <div class="col-xs-4">
            <textarea class="form-control"
              type="text"
              name="description"
              v-validate="'max:500'"
              maxlength="500"
              placeholder="Enter banner description"
              v-model="params.description" />
          </div>
          <div class="col-xs-4">
            <input class="form-control"
              type="text"
              vv-validate-as="button caption"
              name="btn_caption"
              v-validate="'max:50'"
              maxlength="50"
              placeholder="Enter button caption"
              v-model="params.btn_caption">
          </div>
          <div class="col-xs-4 row">
            <div class="col-xs-4">
              <select name="is_active" v-model="params.type">
                <option :value="BANNER_TYPES.DEFAULT">{{ BANNER_TYPES.DEFAULT | upperFirst }}</option>
                <option :value="BANNER_TYPES.PROMO">{{ BANNER_TYPES.PROMO | upperFirst }}</option>
              </select>
            </div>
            <div class="col-xs-4">
              <select name="is_active" v-model="params.is_active">
                <option value="0">Draft</option>
                <option value="1">Publish</option>
              </select>
            </div>
            <div class="col-xs-4">
              <button class="btn" @click.stop="onClickCancel()">
                <i class="fa fa-times"></i>
              </button>
              <button class="btn" @click.stop="onClickUpdate()">
                <i class="fa fa-floppy-o"></i>
              </button>
            </div>
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
            <th class="text-left">Thumbnail</th>
            <th class="text-left col-link">Link</th>
            <th class="text-left col-title">Title</th>
            <th class="text-left col-description">Description</th>
            <th class="text-left col-caption">Button Caption</th>
            <th class="text-left col-type">Type</th>
            <th class="text-left">Status</th>
            <th class="text-right">Actions</th>

            <template slot="body" slot-scope="props">
              <template v-if="rows[ props.index ].editable === false">
                <tr>
                  <td class="text-left">
                    {{ props.realIndex }}
                  </td>
                  <td class="text-left">
                    <img class="thumbnail" :src="props.item.thumbnail">
                  </td>
                  <td class="text-left">
                    {{ props.item.link }}
                  </td>
                  <td class="text-left">
                    <span class="three-dots">{{ props.item.title }}</span>
                  </td>
                  <td class="text-left">
                    <span class="three-dots">{{ props.item.description }}</span>
                  </td>
                  <td class="text-left">
                    <span class="three-dots">{{ props.item.btn_caption }}</span>
                  </td>
                  <td class="text-left">
                    <span class="three-dots">
                      {{ props.item.type | upperFirst }}
                    </span>
                  </td>
                  <td class="text-left">
                    {{ props.item.is_active ? 'Publish' : 'Draft' }}
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
                    <img class="thumbnail" :src="imgPath ? imgPath : params.thumbnail">
                    <label class="custom-file-label" for="thumbnail">
                      Choose File
                    </label>
                    <input type="file"
                      id="thumbnail"
                      ref="inputLogo"
                      accept="image/x-png,image/jpg,image/jpeg"
                      @change="onChangeThumbnail"
                      @focus="resetError"
                      class="input-file">
                    <span v-show="errors.has('thumbnail')" class="error has-error">
                      {{ errors.first('thumbnail') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input class="form-control"
                      type="text"
                      name="link"
                      v-model="params.link">
                    <span v-show="errors.has('link')" class="error has-error">
                      {{ errors.first('link') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input class="form-control"
                      type="text"
                      name="title"
                      v-validate="'max:50'"
                      maxlength="50"
                      v-model="params.title">
                    <span v-show="errors.has('title')" class="error has-error">
                      {{ errors.first('title') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <textarea class="form-control"
                      type="text"
                      name="description"
                      v-validate="'max:500'"
                      maxlength="500"
                      v-model="params.description" />
                    <span v-show="errors.has('description')" class="error has-error">
                      {{ errors.first('description') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input class="form-control"
                      type="text"
                      vv-validate-as="button caption"
                      v-validate="'max:50'"
                      maxlength="50"
                      name="btn_caption"
                      v-model="params.btn_caption">
                    <span v-show="errors.has('btn_caption')" class="error has-error">
                      {{ errors.first('btn_caption') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <select name="type" v-model="params.type">
                      <option :value="BANNER_TYPES.DEFAULT">{{ BANNER_TYPES.DEFAULT | upperFirst }}</option>
                      <option :value="BANNER_TYPES.PROMO">{{ BANNER_TYPES.PROMO | upperFirst }}</option>
                    </select>
                  </td>
                  <td class="text-left">
                    <select name="is_active" v-model="params.is_active">
                      <option value="0">Draft</option>
                      <option value="1">Publish</option>
                    </select>
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
  const BANNER_TYPES = {
    DEFAULT: 'default',
    PROMO: 'promo'
  }

  export default {
    mixins: [RemoveErrorsMixin],

    data () {
      return {
        limit: 10,
        column: 9,
        rows: [],
        params: {},
        titlePage: 'Banners',
        imgPath: null,
        inputImage: null,
        imgName: null,
        isLoading: false,
        isCreateNew: false,
        BANNER_TYPES
      }
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this)
    },

    methods: {
      getData (params) {
        return rf.getRequest('SettingRequest').getBanners(params)
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
        this.params = {
          is_active: 1,
          type: BANNER_TYPES.DEFAULT
        }
      },

      async onClickUpdate() {
        if (this.isSubmitting) {
          return
        }

        await this.$validator.validateAll()
        if (!this.inputImage && window._.isEmpty(this.params.thumbnail)) {
          this.errors.add({
            field: 'thumbnail',
            msg: 'The thumbnail file is required.'
          })
        }

        if (this.errors.any()) {
          return
        }

        let formData = new FormData()
        formData.append('id', this.params.id ? this.params.id : null)
        formData.append('thumbnail', this.inputImage ? this.inputImage : this.params.thumbnail)
        formData.append('link', this.params.link ? this.params.link : '')
        formData.append('title', this.params.title ? this.params.title : '')
        formData.append('description', this.params.description ? this.params.description : '')
        formData.append('btn_caption', this.params.btn_caption ? this.params.btn_caption : '')
        formData.append('type', this.params.type ? this.params.type : BANNER_TYPES.DEFAULT)
        formData.append('is_active', this.params.is_active)

        if (this.params.id) {
          return this.requestHandler(rf.getRequest('SettingRequest').updateBanner(formData))
        }
        return this.requestHandler(rf.getRequest('SettingRequest').createBanner(formData))
      },

      onClickRemove (id) {
        window.ConfirmationModal.show({
          type        : 'confirm',
          title       : '',
          content     : 'Do you want to remove this banner?',
          onConfirm   :  () => {
            return this.requestHandler(rf.getRequest('SettingRequest').deleteBanner({ id: id }))
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

      onChangeThumbnail(e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        let fileType = files[0].type
        if (!ALLOW_FILETYPE.includes(fileType)) {
          this.errors.add({
            field: 'thumbnail',
            msg: 'The thumbnail file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'thumbnail',
            msg: 'The thumbnail file size can not exceed 10MB!'
          })
          return
        }

        this.imgPath = URL.createObjectURL(e.target.files[0])
        this.inputImage = files[0]
        this.imgName = files[0].name
      },

      resetImage () {
        this.resetError()
        this.inputImage = null
        this.imgPath = null
        this.imgName = null
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
