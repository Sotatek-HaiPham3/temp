<template>
  <div class="tab-wrapper">
    <button class="btn-create-new" @click="onClickCreateNew()"><span class="icon-plus"></span> Create New Level</button>

    <div class="datatable">
      <data-table 
        :getData="getData"
        :limit="limit"
        :column="column"
        @DataTable:finish="onDatatableFinish"
        ref="datatable">
        <th class="text-left">No.</th>
        <th class="text-left col-name">Name</th>
        <th class="text-left col-exp">Exp</th>
        <th class="text-left col-icon">Icon</th>
        <th class="text-left col-threshold">Threshold</th>
        <th class="text-right">Actions</th>

        <template slot="body" slot-scope="props">
          <template v-if="rows[ props.index ].editable === false">
            <tr>
              <td class="text-left">{{ props.realIndex }}</td>
              <td class="text-left">{{ props.item.name }}</td>
              <td class="text-left">{{ props.item.exp | price }}</td>
              <td class="text-left"><img class="icon" :src="props.item.url" alt="icon"></td>
              <td class="text-left">{{ props.item.threshold_exp_in_day | price }}</td>
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
              <td class="text-left">{{ props.realIndex }}</td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="name"
                  v-validate="'required|max:50'"
                  data-vv-validate-on="none"
                  @focus="resetError"
                  v-model="params.name">
                <span v-show="errors.has('name')" class="error has-error">
                  {{ errors.first('name') }}
                </span>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="exp"
                  v-validate="'required'"
                  data-vv-validate-on="none"
                  @focus="resetError"
                  v-model="params.exp">
                <span v-show="errors.has('exp')" class="error has-error">
                  {{ errors.first('exp') }}
                </span>
              </td>
              <td class="text-left">
                <label class="custom-file-label" for="url">
                  Choose File
                </label>
                <input type="file"
                  id="url"
                  ref="inputLogo"
                  accept="image/x-png,image/jpg,image/jpeg"
                  @change="onChangeUrl"
                  @focus="resetError"
                  class="input-file">
                <span v-show="errors.has('url')" class="error has-error">
                  {{ errors.first('url') }}
                </span>
                <img class="icon" :src="imgPath ? imgPath : params.url">
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="threshold"
                  v-validate="'required'"
                  data-vv-validate-on="none"
                  @focus="resetError"
                  v-model="params.threshold_exp_in_day">
                <span v-show="errors.has('threshold')" class="error has-error">
                  {{ errors.first('threshold') }}
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

    <modal name="createLevelModal" width="560" :title="'Create New Level'">
      <div slot="body" class="body-popup">
        <div class="form-create-new row">
          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Name
            </label>
            <input class="form-control"
              type="text"
              name="name"
              v-validate="'required|max:50'"
              data-vv-validate-on="none"
              @focus="resetError"
              maxlength="50"
              placeholder="Enter level name"
              v-model="params.name">
            <div class="clearfix"></div>
            <span v-show="errors.has('name')" class="error has-error">
              {{ errors.first('name') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Exp
            </label>
            <input class="form-control"
              type="text"
              name="exp"
              placeholder="Enter exp"
              v-validate="'required'"
              data-vv-validate-on="none"
              @focus="resetError"
              v-model="params.exp">
            <div class="clearfix"></div>
            <span v-show="errors.has('exp')" class="error has-error">
              {{ errors.first('exp') }}
            </span>
          </div>
          
          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Icon
            </label>
            <label class="custom-file-label create-label" for="url">
              Choose File
            </label>
            <input type="file"
              id="url"
              ref="inputLogo"
              accept="image/x-png,image/jpg,image/jpeg"
              @change="onChangeUrl"
              @focus="resetError"
              class="input-file">
            <img v-if="imgPath || params.url"
              class="url"
              :src="imgPath ? imgPath : params.url">
            <div class="clearfix"></div>
            <span v-show="errors.has('url')" class="error has-error">
              {{ errors.first('url') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Threshold in a day
            </label>
            <input class="form-control input-threshold"
              type="text"
              name="threshold"
              v-validate="'required'"
              data-vv-validate-on="none"
              @focus="resetError"
              placeholder="Enter threshold"
              v-model="params.threshold_exp_in_day">
            <div class="clearfix"></div>
            <span v-show="errors.has('threshold')" class="error has-error">
              {{ errors.first('threshold') }}
            </span>
          </div>
          <div class="col-xs-12 form-group group-button">
            <button class="btn btn-cancel" @click.stop="onClickCancel()">
              Cancel
            </button>
            <button class="btn btn-create" @click.stop="onClickUpdate()">
              Save
            </button>
          </div>
        </div>
      </div>
    </modal>
  </div>
</template>

<script>
import rf from '../../../lib/RequestFactory';
import RemoveErrorsMixin from '../../../common/RemoveErrorsMixin';
import Modal from '../../../components/Modal'

const MAXIMUM_FILESIZE = 10 * 1024 * 1024
const ALLOW_FILETYPE = ['image/jpg', 'image/jpeg', 'image/png']

export default {
  components: {
    Modal
  },

  mixins: [RemoveErrorsMixin],

  data () {
    return {
      limit: 10,
      column: 5,
      params: {},
      rows: [],
      imgPath: null,
      inputImage: null,
      imgName: null,
      isCreateNew: false,
    }
  },

  methods: {
    getData (params) {
      return rf.getRequest('SettingRequest').getLevelingRankings(params)
    },

    onDatatableFinish () {
      this.rows = this.$refs.datatable.rows
      window._.each(this.rows, item => {
        this.$set(item, 'editable', false)
      })
    },

    onClickCreateNew () {
      this.isCreateNew = true
      window._.forEach(this.rows, item => {
        item.editable = false
      })

      this.resetImage()
      this.params = {}
      CommonModal.show('createLevelModal', {
        position: 'center',
        mask: false,
        enableClose: true,
      });
    },

    onClickCancel() {
      this.isCreateNew = false
      this.refresh()
      CommonModal.hide('createLevelModal')
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

      const raw_params = JSON.parse(JSON.stringify(this.rows[index]))
      this.params = {
        ...raw_params,
        exp: parseFloat(raw_params.exp).toString(),
        threshold_exp_in_day: parseFloat(raw_params.threshold_exp_in_day).toString()
      }
    },

    async onClickUpdate() {
      if (this.isSubmitting) {
        return
      }
      this.resetError()
      await this.$validator.validateAll()

      if (!this.inputImage && window._.isEmpty(this.params.url)) {
        this.errors.add({
          field: 'url',
          msg: 'The url field is required.'
        })
      }

      if (this.errors.any()) {
        return
      }

      let formData = new FormData()
      formData.append('id', this.params.id ? this.params.id : null)
      formData.append('url', this.inputImage ? this.inputImage : this.params.url)
      formData.append('name', this.params.name ? this.params.name : '')
      formData.append('exp', this.params.exp ? parseFloat(this.params.exp) : '')
      formData.append('threshold_exp_in_day', this.params.threshold_exp_in_day ? parseFloat(this.params.threshold_exp_in_day) : '')

      if (this.params.id) {
        return this.requestHandler(rf.getRequest('SettingRequest').updateRanking(formData))
      }
      return this.requestHandler(rf.getRequest('SettingRequest').createRanking(formData).then(res => CommonModal.hide('createLevelModal')))
    },

    onClickRemove (id) {
      window.ConfirmationModal.show({
        type        : 'confirm',
        title       : '',
        content     : 'Do you want to remove this level?',
        onConfirm   :  () => {
          return this.requestHandler(rf.getRequest('SettingRequest').deleteRanking({ id: id }))
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

    resetImage () {
      this.resetError()
      this.inputImage = null
      this.imgPath = null
      this.imgName = null
    },

    onChangeUrl(e) {
      this.resetError()

      let files = e.target.files || e.dataTransfer.files
      if (!files.length) {
        return
      }

      let fileType = files[0].type
      if (!ALLOW_FILETYPE.includes(fileType)) {
        this.errors.add({
          field: 'url',
          msg: 'The url file type is invalid.'
        })
        return
      }

      if (files[0].size > MAXIMUM_FILESIZE) {
        this.errors.add({
          field: 'url',
          msg: 'The url file size can not exceed 10MB!'
        })
        return
      }

      this.imgPath = URL.createObjectURL(e.target.files[0])
      this.inputImage = files[0]
      this.imgName = files[0].name
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
@import "../../../../../sass/common";
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
.datatable {
  tbody {
    td {
      vertical-align: baseline;
    }
  }
  .col-name {
    width: 350px;
  }
  .col-exp {
    width: 300px;
  }
  .col-icon {
    width: 350px;
  }
}
img {
  width: 80px;
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
  margin-bottom: 15px;
}
.modal-dialog {
  .modal-body {
    .body-popup {
      padding: 0 30px;
      .group-button {
        margin-bottom: 0;
        text-align: left;
        .btn-cancel {
          background-color: #e9ecef;
        }
        .btn-create {
          background-color: #12575f;
          color: white;
        }
      }
    }
  }
}
</style>
