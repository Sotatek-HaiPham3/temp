<template>
  <div class="boxCore" id="sms_setting">
    <section class="clearfix">
      <div class="filter_container clearfix">
        <div class="datatable">
          <data-table :getData="getSmsSetting"
                      :limit="limit"
                      :column="column"
                      :widthTable="'1200px'"
                      @DataTable:finish="onDatatableFinish"
                      ref="datatable">
            <th class="text-left">Max Price</th>
            <th class="text-left">Rate Limit Price</th>
            <th class="text-left">Rate Limit TTl</th>
            <th class="text-left">Rate Limit</th>
            <th class="text-left">White List</th>
            <th class="text-left">Rate List</th>
            <th class="text-right">Actions</th>
            <template slot="body" slot-scope="props">
              <template v-if="rows[ props.index ].editable === false">
                <tr>
                  <td class="text-left">
                    {{ props.item.max_price | formatCurrencyAmount }}
                  </td>
                  <td class="text-left">
                    {{ props.item.rate_limit_price | formatCurrencyAmount }}
                  </td>
                  <td class="text-left">
                    {{ props.item.rate_limit_ttl }}
                  </td>
                  <td class="text-left">
                    {{ props.item.rate_limit | formatCurrencyAmount }}
                  </td>
                  <td class="text-left">
                    {{ props.item.white_list | uppercase }}
                  </td>
                  <td class="text-left">
                    {{ props.item.rate_list | uppercase }}
                  </td>
                  <td class="text-right">
                    <button class="btn" @click.stop="onClickEdit(props.index)">
                      <i class="fa fa-pencil"></i>
                    </button>
                  </td>
                </tr>
              </template>
              <template v-else>
                <tr>
                  <td class="text-left">
                    <input-only-number class="form-control" 
                           type="text"
                           maxlength="10"
                           name="max_price"
                           data-vv-as="max price"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           @focus="resetError"
                           v-model="params.max_price" />
                    <span v-show="errors.has('max_price')" class="error has-error">
                      {{ errors.first('max_price') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input-only-number class="form-control" 
                           type="text"
                           maxlength="10"
                           name="rate_limit_price"
                           data-vv-as="rate limit price"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           @focus="resetError"
                           v-model="params.rate_limit_price" />
                    <span v-show="errors.has('rate_limit_price')" class="error has-error">
                      {{ errors.first('rate_limit_price') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input-only-number class="form-control" 
                           type="text"
                           maxlength="10"
                           name="rate_limit_ttl"
                           data-vv-as="rate limit ttl"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           without-decimals
                           @focus="resetError"
                           v-model="params.rate_limit_ttl" />
                    <span v-show="errors.has('rate_limit_ttl')" class="error has-error">
                      {{ errors.first('rate_limit_ttl') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input-only-number class="form-control" 
                           type="text"
                           maxlength="10"
                           name="rate_limit"
                           data-vv-as="rate limit"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           without-decimals
                           @focus="resetError"
                           v-model="params.rate_limit" />
                    <span v-show="errors.has('rate_limit')" class="error has-error">
                      {{ errors.first('rate_limit') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input class="form-control" 
                           type="text"
                           name="white_list"
                           data-vv-as="white list"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           @focus="resetError"
                           v-model="params.white_list" />
                    <span v-show="errors.has('white_list')" class="error has-error">
                      {{ errors.first('white_list') }}
                    </span>
                  </td>
                  <td class="text-left">
                    <input class="form-control" 
                           type="text"
                           name="rate_list"
                           data-vv-as="rate list"
                           v-validate="'required|max:255'"
                           data-vv-validate-on="none"
                           @focus="resetError"
                           v-model="params.rate_list" />
                    <span v-show="errors.has('rate_list')" class="error has-error">
                      {{ errors.first('rate_list') }}
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
        titlePage: 'Sms Setting',
        isLoading: false,
        users: {}
      }
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this)
    },

    methods: {
      getSmsSetting (params) {
        return rf.getRequest('SettingRequest').getSmsSetting(params)
      },

      onDatatableFinish () {
        this.rows = this.$refs.datatable.rows
        window._.each(this.rows, item => {
          this.$set(item, 'editable', false)
        })
      },

      onClickEdit(index) {
        this.rows[index].editable = true
        for(let i = 0; i < this.rows.length; i++) {
          if(i !== index) {
            this.rows[i].editable = false
          }
        }
        this.params = JSON.parse(JSON.stringify(this.rows[index]))
      },

      async onClickSubmit() {
        if (this.isSubmitting) {
          return
        }

        await this.$validator.validateAll()
        if (this.errors.any()) {
          return
        }

        return this.requestHandler(rf.getRequest('SettingRequest').updateSmsSetting(this.params))
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
        this.$refs.datatable.refresh()
      },

      onCancel () {
        this.params = {}
      }
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/common";
</style>
