<template>
  <div class="tab-wrapper">
    <div class="col-xs-4 form-group group-period">
      <label class="custom-label create-label">
        Daily Check-in Period
      </label>
      <div class="clearfix"></div>
      <input class="form-control"
        type="text"
        name="period"
        v-validate="'required|numeric'"
        v-model="period">
      <span v-show="errors.has('period')" class="error has-error">
        {{ errors.first('period') }}
      </span>
    </div>

    <div class="clearfix"></div>

    <div class="datatable" ref="dataList">
      <div class="wrap">
        <data-list
          :getData="getData"
          :column="column"
          @DataTable:finish="onDatatableFinish"
          ref="dataScrollList">
          <th class="text-left">No.</th>
          <th class="text-left col-day">Day</th>
          <th class="text-left col-exp">Exp</th>
          <th class="text-right">Actions</th>

          <template slot="body" slot-scope="props">
            <template v-if="rows[ props.index ].editable === false">
              <tr>
                <td class="text-left">{{ props.realIndex }}</td>
                <td class="text-left">{{ props.item.day }}</td>
                <td class="text-left">{{ props.item.exp | price }}</td>
                <td class="text-right">
                  <button class="btn" @click.stop="onClickEdit(props.index)">
                    <i class="fa fa-pencil"></i>
                  </button>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr>
                <td class="text-left">{{ props.realIndex }}</td>
                <td class="text-left">{{ props.item.day }}</td>

                <td class="text-left">
                  <input class="form-control"
                    type="text"
                    :name="`exp_${props.index}`"
                    v-validate="'required|numeric'"
                    data-vv-as="exp"
                    v-model="params[paramIndex(props.item.id)].exp">

                  <div class="clearfix"></div>

                  <span v-show="errors.has(`exp_${props.index}`)" class="error has-error">
                    {{ errors.first(`exp_${props.index}`) }}
                  </span>
                </td>

                <td class="text-right">
                  <button class="btn" @click.stop="onClickCancelInline(props.index, props.item.id)">
                    <i class="fa fa-times"></i>
                  </button>
                  <button class="btn" @click.stop="onClickUpdateInline(props.index, props.item.id)">
                    <i class="fa fa-floppy-o"></i>
                  </button>
                </td>
              </tr>
            </template>
          </template>
        </data-list>
      </div>
    </div>

    <button class="btn btn-save" @click.stop="onClickUpdate()">
      Save
    </button>

  </div>
</template>

<script>
import rf from '../../../lib/RequestFactory';
import RemoveErrorsMixin from '../../../common/RemoveErrorsMixin';

const THRESHOLD_SCROLL = 300
const LOADMORE_ELEMENTS = 20
const POINT_STEP = 10

export default {

  mixins: [RemoveErrorsMixin],

  data () {
    return {
      column: 4,
      params: [],
      rows: [],
      period: null,
      originCheckinPoints: []
    }
  },

  watch: {
    period: _.debounce( function (newVal, oldVal) {
      if (newVal) {
        this.buildCheckinPoints()
      }
    }, 500)
  },

  mounted() {
    this.getDailyCheckinPeriod()
    this.$nextTick(() => {
      if (this.$refs.dataList) {
        this.$refs.dataList.addEventListener('scroll', this.onScroll)
      }
    })
  },

  destroyed() {
    if (this.$refs.dataList) {
      this.$refs.dataList.removeEventListener('scroll', this.onScroll)
    }
  },

  methods: {
    onScroll () {
      const divElement = this.$refs.dataList
      const isLoadmore = divElement.scrollHeight - divElement.scrollTop - divElement.clientHeight < THRESHOLD_SCROLL
      if (isLoadmore && this.$refs.dataScrollList) {
        this.$refs.dataScrollList.recordsDisplay += LOADMORE_ELEMENTS
      }
    },

    buildCheckinPoints () {
      if (this.originCheckinPoints.length < this.period) {
        this.appendCheckinList()
      }
      this.filterCheckinList()
    },

    appendCheckinList() {
      const largestCheckin = _.last(this.originCheckinPoints)
      _.times(this.period - this.originCheckinPoints.length, key => {
        const number = key + 1
        this.originCheckinPoints.push({
          day: largestCheckin.day + number,
          exp: parseFloat(largestCheckin.exp) + number * POINT_STEP,
          editable: false,
          visible: true
        })
      })
    },

    filterCheckinList () {
      _.map(this.originCheckinPoints, (item, key) => {
        item.visible = key < this.period ? true : false
        return item
      })
      this.$refs.dataScrollList.rows = _.filter(this.originCheckinPoints, item => item.visible)
      this.onScroll()
    },

    getData (params) {
      return rf.getRequest('SettingRequest').getDailyCheckinPoints(params)
    },

    getDailyCheckinPeriod () {
      rf.getRequest('SettingRequest').getDailyCheckinPeriod()
        .then(res => {
          this.period = res.data
        })
    },

    onDatatableFinish () {
      this.rows = this.$refs.dataScrollList.rows
      window._.each(this.rows, item => {
        this.$set(item, 'editable', false)
      })

      // temperature save periods
      this.originCheckinPoints = this.$refs.dataScrollList.rows
      _.each(this.originCheckinPoints, item => {
        this.$set(item, 'visible', true)
      })
    },

    paramIndex (id) {
      const index = window._.findIndex(this.params, i => {
        return i.id == id
      })
      return index || 0
    },

    removeError (key) {
      if (this.errors.has(key)) {
        this.errors.remove(key)
      }
    },

    onClickCancelInline(index, id) {
      this.$set(this.rows[index], 'editable', false)
      window._.remove(this.params, i => {
        return i.id === id
      })
      this.removeError(`exp_${index}`)
    },

    onClickEdit(index) {
      const raw_params = JSON.parse(JSON.stringify(this.rows[index]))
      this.params.push({
        ...raw_params,
        exp: parseFloat(raw_params.exp).toString()
      })
      this.rows[index].editable = true
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

      this.updateGeneral()
    },

    async onClickUpdateInline (index, id) {
      if (this.isSubmitting) {
        return
      }

      this.resetError()
      await this.$validator.validateAll()

      if (this.errors.has(`exp_${index}`)) {
        return
      }
      this.updateSinglePoint(id)
    },

    async updateGeneral () {
      if (this.params.length) {
        _.each(this.params, item => {
          const index = _.findIndex(this.originCheckinPoints, i => i.day === item.day)
          this.onClickUpdateInline(index, id)
        })
      }
      const params = _.filter(this.originCheckinPoints, item => item.visible)
      rf.getRequest('SettingRequest').updateMultipleDailyCheckinPoints(params)
        .then(() => {
          this.requestHandler(rf.getRequest('SettingRequest').updateDailyCheckinPeriod({'period': this.period}))
        })
    },

    updateSinglePoint (id) {
      const data = window._.find(this.params, i => {
        return id === i.id
      })
      if (window._.isEmpty(data)) return

      let formData = new FormData()
      formData.append('id', data.id ? data.id : null)
      formData.append('day', data.day ? data.day : '')
      formData.append(`exp`, data.exp ? data.exp : '')

      if (data.id) {
        return this.requestHandler(rf.getRequest('SettingRequest').updateDailyCheckinPoint(formData))
      }
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
      window._.each(this.rows, item => {
        this.$set(item, 'editable', false)
      })
      this.params = []
      this.$refs.dataScrollList.refresh()
    }
  }
}
</script>

<style lang="scss" scoped>
@import "../../../../../sass/common";
.group-period {
  padding: 0;
  margin: 20px 0;
}
.btn-save {
  background-color: #12575f;
  padding: 10px 20px;
  color: white;
  margin-top: 15px
}
.datatable {
  max-height: 70vh;
  overflow-y: auto;
  .wrap {
    
  }
  tbody {
    td {
      vertical-align: baseline;
    }
  }
  .col-day {
    width: 350px;
  }
  .col-exp {
    width: 300px;
  }
}
.error {
  &.has-error {
    color: #e2221e;
  }
}
</style>
