<template>
  <div class="tab-wrapper">
    <button class="btn-create-new" @click="onClickCreateNew()"><span class="icon-plus"></span> Create New Task</button>

    <div class="datatable">
      <data-table 
        :getData="getData"
        :limit="limit"
        :column="column"
        @DataTable:finish="onDatatableFinish"
        ref="datatable">
        <th class="text-left col-level">No.</th>
        <th class="text-left col-level">Type</th>
        <th class="text-left col-level">Title</th>
        <th class="text-left col-level">Description</th>
        <th class="text-left col-level" v-if="editing">Short Title</th>
        <th class="text-left col-level" v-if="editing">Short Description</th>
        <th class="text-left col-level">Exp</th>
        <th class="text-left col-level">Threshold</th>
        <th class="text-left col-level">Bonus</th>
        <th class="text-left col-level">Bonus Currency</th>
        <th class="text-left col-level" v-if="editing">Image</th>
        <th class="text-left col-level">Order</th>
        <th class="text-right">Actions</th>

        <template slot="body" slot-scope="props">
          <template v-if="rows[ props.index ].editable === false">
            <tr>
              <td class="text-left">{{ props.item.id }}</td>
              <td class="text-left">{{ props.item.type | upperFirst }}</td>
              <td class="text-left">{{ props.item.title }}</td>
              <td class="text-left">{{ props.item.description }}</td>
              <td class="text-left" v-if="editing">{{ props.item.short_title }}</td>
              <td class="text-left" v-if="editing">{{ props.item.short_description }}</td>
              <td class="text-left">{{ props.item.exp | price }}</td>
              <td class="text-left">{{ props.item.threshold_exp_in_day | price }}</td>
              <td class="text-left">{{ props.item.bonus_value | price }}</td>
              <td class="text-left">{{ props.item.bonus_currency }}</td>
              <td class="text-left" v-if="editing"><img v-if="props.item.url" class="icon" :src="props.item.url" alt="icon"></td>
              <td class="text-left">{{ props.item.order }}</td>
              <td class="text-right">
                <button class="btn" @click.stop="onClickEdit(props.index)">
                  <i class="fa fa-pencil"></i>
                </button>
                <!-- <button class="btn" @click.stop="onClickRemove(props.item.id)">
                  <i class="fa fa-trash-o"></i>
                </button> -->
              </td>
            </tr>
          </template>
          <template v-else>
            <tr>
              <td class="text-left">{{ props.item.id }}</td>
              <td class="text-left">
                <select name="type" v-model="params.type" class="custom-select form-control">
                  <option v-for="(v,k) in TASK_TYPE" :key="k" :value="v">{{ v | upperFirst }}</option>
                </select>
              </td>
              <td class="text-left">
                <input class="form-control title"
                  type="text"
                  name="title"
                  v-validate="'required'"
                  data-vv-validate-on="none"
                  @focus="resetError"
                  v-model="params.title">
                <span v-show="errors.has('title')" class="error has-error">
                  {{ errors.first('title') }}
                </span>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="description"
                  @focus="resetError"
                  v-model="params.description">
                <span v-show="errors.has('description')" class="error has-error">
                  {{ errors.first('description') }}
                </span>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="short_title"
                  @focus="resetError"
                  v-model="params.short_title">
                <span v-show="errors.has('short_title')" class="error has-error">
                  {{ errors.first('short_title') }}
                </span>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="short_description"
                  @focus="resetError"
                  v-model="params.short_description">
                <span v-show="errors.has('short_description')" class="error has-error">
                  {{ errors.first('short_description') }}
                </span>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="number"
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
                <input class="form-control"
                  type="number"
                  name="threshold_exp_in_day"
                  @focus="resetError"
                  v-model="params.threshold_exp_in_day">
                <span v-show="errors.has('threshold_exp_in_day')" class="error has-error">
                  {{ errors.first('threshold_exp_in_day') }}
                </span>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="number"
                  name="bonus_value"
                  @focus="resetError"
                  v-model="params.bonus_value">
                <span v-show="errors.has('bonus_value')" class="error has-error">
                  {{ errors.first('bonus_value') }}
                </span>
              </td>
              <td class="text-left">
                <select name="bonus_currency" v-model="params.bonus_currency" class="custom-select form-control">
                  <option :value="null">none</option>
                  <option v-for="(v,k) in REWARD_CURRENCY" :key="k" :value="v">{{ v | upperFirst }}</option>
                </select>
                <span v-show="errors.has('bonus_currency')" class="error has-error">
                  {{ errors.first('bonus_currency') }}
                </span>
              </td>
              <td class="text-left">
                <label class="custom-file-label" for="url">
                  &nbsp;
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
                  name="order"
                  v-validate="'required'"
                  data-vv-validate-on="none"
                  @focus="resetError"
                  v-model="params.order">
                <span v-show="errors.has('order')" class="error has-error">
                  {{ errors.first('order') }}
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

    <modal name="createTaskModal" width="560" :title="'Create New Task'">
      <div slot="body" class="body-popup">
        <div class="form-create-new row">
          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Type
            </label>
            <select
              name="type"
              v-model="params.type"
              v-validate="'required'"
              class="custom-select form-control">
              <option v-for="(v,k) in TASK_TYPE" :key="k" :value="v">{{ v | upperFirst }}</option>
            </select>
            <div class="clearfix"></div>
            <span v-show="errors.has('type')" class="error has-error">
              {{ errors.first('type') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Order
            </label>
            <input class="form-control"
              type="number"
              name="order"
              placeholder="Enter order"
              v-validate="'required'"
              data-vv-validate-on="none"
              @focus="resetError"
              v-model="params.order">
            <div class="clearfix"></div>
            <span v-show="errors.has('order')" class="error has-error">
              {{ errors.first('order') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Title
            </label>
            <input class="form-control"
              type="text"
              name="title"
              placeholder="Enter title"
              v-validate="'required'"
              data-vv-validate-on="none"
              @focus="resetError"
              v-model="params.title">
            <div class="clearfix"></div>
            <span v-show="errors.has('title')" class="error has-error">
              {{ errors.first('title') }}
            </span>
          </div>
          
          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Exp
            </label>
            <input class="form-control"
              type="number"
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
              Bonus Value
            </label>
            <input class="form-control"
              type="number"
              name="bonus_value"
              placeholder="Enter bonus"
              @focus="resetError"
              v-model="params.bonus_value">
            <div class="clearfix"></div>
            <span v-show="errors.has('bonus_value')" class="error has-error">
              {{ errors.first('bonus_value') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Bonus Currency
            </label>
            <select name="bonus_currency" v-model="params.bonus_currency" class="custom-select form-control">
              <option :value="null">none</option>
              <option v-for="(v,k) in REWARD_CURRENCY" :key="k" :value="v">{{ v | upperFirst }}</option>
            </select>
            <div class="clearfix"></div>
            <span v-show="errors.has('bonus_currency')" class="error has-error">
              {{ errors.first('bonus_currency') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Description
            </label>
            <textarea class="form-control"
              type="text"
              name="description"
              placeholder="Enter description"
              @focus="resetError"
              v-model="params.description">
            </textarea>
            <div class="clearfix"></div>
            <span v-show="errors.has('description')" class="error has-error">
              {{ errors.first('description') }}
            </span>
          </div>
          
          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Image
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

const TASK_TYPE = {
  DAILY: 'daily',
  INTRO: 'intro',
  DAILY_CHECKIN: 'daily-checkin'
}

const REWARD_CURRENCY = {
  EXP: 'exp',
  COIN: 'coin'
}

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
      column: 13,
      params: {},
      rows: [],
      imgPath: null,
      inputImage: null,
      imgName: null,
      isCreateNew: false,
      TASK_TYPE,
      REWARD_CURRENCY,
      editing: false
    }
  },

  methods: {
    getData (params) {
      return rf.getRequest('SettingRequest').getLevelingTaskings(params)
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

      this.params = {
        type: this.TASK_TYPE.DAILY,
        url: ''
      }
      CommonModal.show('createTaskModal', {
        position: 'center',
        mask: false,
        enableClose: true,
      });
    },

    onClickCancel() {
      this.isCreateNew = false
      this.editing = false
      this.refresh()
      CommonModal.hide('createTaskModal')
    },

    onClickEdit(index) {
      this.isCreateNew = false
      this.editing = true
      this.rows[index].editable = true
      for(let i = 0; i < this.rows.length; i++) {
        if(i !== index) {
          this.rows[i].editable = false
        }
      }

      const raw_params = JSON.parse(JSON.stringify(this.rows[index]))
      raw_params.url = raw_params.url === 'null' || raw_params.url === null ? '' : raw_params.url
      this.params = {
        ...raw_params,
        quantity: parseFloat(raw_params.quantity).toString()
      }
    },

    async onClickUpdate() {
      if (this.isSubmitting) {
        return
      }
      this.resetError()
      await this.$validator.validateAll()

      if (this.errors.any()) {
        return
      }

      let formData = new FormData()
      formData.append('id', this.params.id ? this.params.id : null)
      formData.append('type', this.params.type ? this.params.type : '')
      formData.append('order', this.params.order ? this.params.order : '')
      formData.append('title', this.params.title ? this.params.title : '')
      formData.append('description', this.params.description ? this.params.description : '')
      formData.append('short_title', this.params.short_title ? this.params.short_title : '')
      formData.append('short_description', this.params.short_description ? this.params.short_description : '')
      formData.append('exp', this.params.exp ? parseFloat(this.params.exp) : '')
      formData.append('threshold_exp_in_day', this.params.threshold_exp_in_day ? parseFloat(this.params.threshold_exp_in_day) : '')
      formData.append('bonus_value', this.params.bonus_value ? parseFloat(this.params.bonus_value) : '')
      formData.append('bonus_currency', this.params.bonus_currency ? this.params.bonus_currency : '')
      formData.append('url', this.inputImage ? this.inputImage : this.params.url)

      if (this.params.id) {
        this.editing = false
        return this.requestHandler(rf.getRequest('SettingRequest').updateTasking(formData))
      }
      return this.requestHandler(rf.getRequest('SettingRequest').createTasking(formData).then(res => CommonModal.hide('createRewardModal')))
    },

    onClickRemove (id) {
      window.ConfirmationModal.show({
        type        : 'confirm',
        title       : '',
        content     : 'Do you want to remove this task?',
        onConfirm   :  () => {
          return this.requestHandler(rf.getRequest('SettingRequest').deleteTasking({ id: id }))
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
      this.params = {}
      this.imgPath = null
      this.inputImage = null
      this.imgName = null
      this.isCreateNew = false
      CommonModal.hide('createTaskModal')
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
.form-control {
  &.title {
    min-width: 200px;
  }
}
</style>
