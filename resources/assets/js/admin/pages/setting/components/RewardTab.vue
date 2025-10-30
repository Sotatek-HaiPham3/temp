<template>
  <div class="tab-wrapper">
    <button class="btn-create-new" @click="onClickCreateNew()"><span class="icon-plus"></span> Create New Reward</button>

    <div class="datatable">
      <data-table 
        :getData="getData"
        :limit="limit"
        :column="column"
        @DataTable:finish="onDatatableFinish"
        ref="datatable">
        <th class="text-left">No.</th>
        <th class="text-left col-type">Type</th>
        <th class="text-left col-level">Level (Reward Ranking)</th>
        <th class="text-left col-quantity">Quantity</th>
        <th class="text-left col-currency">Currency</th>
        <th class="text-right">Actions</th>

        <template slot="body" slot-scope="props">
          <template v-if="rows[ props.index ].editable === false">
            <tr>
              <td class="text-left">{{ props.realIndex }}</td>
              <td class="text-left">{{ props.item.type | upperFirst }}</td>
              <td class="text-left">{{ props.item.level }}</td>
              <td class="text-left">{{ props.item.quantity | price }}</td>
              <td class="text-left">{{ props.item.currency | upperFirst }}</td>
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
                <select name="type" v-model="params.type" class="custom-select">
                  <option :value="REWARD_TYPE.DAILY">{{ REWARD_TYPE.DAILY | upperFirst }}</option>
                  <option :value="REWARD_TYPE.INTRO">{{ REWARD_TYPE.INTRO | upperFirst }}</option>
                  <option :value="REWARD_TYPE.DAILY_CHECKIN">{{ REWARD_TYPE.DAILY_CHECKIN | upperFirst }}</option>
                </select>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="level"
                  v-validate="'required'"
                  data-vv-validate-on="none"
                  @focus="resetError"
                  v-model="params.level">
                <span v-show="errors.has('level')" class="error has-error">
                  {{ errors.first('level') }}
                </span>
              </td>
              <td class="text-left">
                <input class="form-control"
                  type="text"
                  name="quantity"
                  v-validate="'required'"
                  data-vv-validate-on="none"
                  @focus="resetError"
                  v-model="params.quantity">
                <span v-show="errors.has('quantity')" class="error has-error">
                  {{ errors.first('quantity') }}
                </span>
              </td>
              <td class="text-left">
                <select name="currency" v-model="params.currency" class="custom-select">
                  <option :value="REWARD_CURRENCY.EXP">{{ REWARD_CURRENCY.EXP | upperFirst }}</option>
                  <option :value="REWARD_CURRENCY.COIN">{{ REWARD_CURRENCY.COIN | upperFirst }}</option>
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

    <modal name="createRewardModal" width="560" :title="'Create New Reward'">
      <div slot="body" class="body-popup">
        <div class="form-create-new row">
          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Type
            </label>
            <select name="type" v-model="params.type" class="custom-select form-control">
              <option :value="REWARD_TYPE.DAILY">{{ REWARD_TYPE.DAILY | upperFirst }}</option>
              <option :value="REWARD_TYPE.INTRO">{{ REWARD_TYPE.INTRO | upperFirst }}</option>
              <option :value="REWARD_TYPE.DAILY_CHECKIN">{{ REWARD_TYPE.DAILY_CHECKIN | upperFirst }}</option>
            </select>
            <div class="clearfix"></div>
            <span v-show="errors.has('type')" class="error has-error">
              {{ errors.first('type') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Level (Reward Ranking)
            </label>
            <input class="form-control"
              type="text"
              name="level"
              placeholder="Enter level"
              v-validate="'required'"
              data-vv-validate-on="none"
              @focus="resetError"
              v-model="params.level">
            <div class="clearfix"></div>
            <span v-show="errors.has('level')" class="error has-error">
              {{ errors.first('level') }}
            </span>
          </div>
          
          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Quantity
            </label>
            <input class="form-control"
              type="text"
              name="quantity"
              placeholder="Enter quantity"
              v-validate="'required'"
              data-vv-validate-on="none"
              @focus="resetError"
              v-model="params.quantity">
            <div class="clearfix"></div>
            <span v-show="errors.has('quantity')" class="error has-error">
              {{ errors.first('quantity') }}
            </span>
          </div>

          <div class="clearfix"></div>

          <div class="col-xs-12 form-group">
            <label class="custom-label create-label">
              Currency
            </label>
            <select name="currency" v-model="params.currency" class="custom-select form-control">
              <option :value="REWARD_CURRENCY.EXP">{{ REWARD_CURRENCY.EXP | upperFirst }}</option>
              <option :value="REWARD_CURRENCY.COIN">{{ REWARD_CURRENCY.COIN | upperFirst }}</option>
            </select>
            <div class="clearfix"></div>
            <span v-show="errors.has('currency')" class="error has-error">
              {{ errors.first('currency') }}
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

const REWARD_TYPE = {
  DAILY: 'daily',
  INTRO: 'intro',
  DAILY_CHECKIN: 'daily-checkin'
}

const REWARD_CURRENCY = {
  EXP: 'exp',
  COIN: 'coin'
}

export default {
  components: {
    Modal
  },

  mixins: [RemoveErrorsMixin],

  data () {
    return {
      limit: 10,
      column: 6,
      params: {},
      rows: [],
      imgPath: null,
      inputImage: null,
      imgName: null,
      isCreateNew: false,
      REWARD_TYPE,
      REWARD_CURRENCY
    }
  },

  methods: {
    getData (params) {
      return rf.getRequest('SettingRequest').getLevelingRewards(params)
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
        type: this.REWARD_TYPE.DAILY,
        currency: this.REWARD_CURRENCY.EXP,
      }
      CommonModal.show('createRewardModal', {
        position: 'center',
        mask: false,
        enableClose: true,
      });
    },

    onClickCancel() {
      this.isCreateNew = false
      this.refresh()
      CommonModal.hide('createRewardModal')
    },

    onClickEdit(index) {
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
      formData.append('level', this.params.level ? this.params.level : '')
      formData.append('quantity', this.params.quantity ? parseFloat(this.params.quantity) : '')
      formData.append('currency', this.params.currency ? this.params.currency : '')

      if (this.params.id) {
        return this.requestHandler(rf.getRequest('SettingRequest').updateReward(formData))
      }
      return this.requestHandler(rf.getRequest('SettingRequest').createReward(formData).then(res => CommonModal.hide('createRewardModal')))
    },

    onClickRemove (id) {
      window.ConfirmationModal.show({
        type        : 'confirm',
        title       : '',
        content     : 'Do you want to remove this level?',
        onConfirm   :  () => {
          return this.requestHandler(rf.getRequest('SettingRequest').deleteReward({ id: id }))
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

    refresh() {
      this.params = {}
      this.isCreateNew = false
      this.$refs.datatable.refresh()
    }
    }
}
</script>

<style lang="scss" scoped>
@import "../../../../../sass/common";

.datatable {
  tbody {
    td {
      vertical-align: baseline;
      .custom-select {
        min-width: 100px;
        min-height: 30px;
      }
    }
  }
  .col-type {
    width: 350px;
  }
  .col-level {
    width: 300px;
  }
  .col-quantity {
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
  .modal-header {

  }
  .modal-body {
    .body-popup {
      padding: 0 30px;
      .custom-select {
        display: block;
        min-height: 30px;
        width: 100%;
      }
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
