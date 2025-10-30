<template>
  <div id="base_deposit_withdraw" class="boxCore">
    <div class="transaction">
      <div class="filter_container clearfix">
        <span class="title_item">Transactions</span>
        <span class="right-el">
          <input type="text" placeholder="Search"v-on:keyup.enter="search" class="form-control search_input" name="searchKey" v-model="searchKey"/>
        </span>
        <div class="right-el input-radio">
          <label>
            <input name="transaction_status" type="radio" value="pending" v-model="transactionStatus"></input>
            <span> Pending Transaction </span>
          </label>
          <label>
            <input name="transaction_status" type="radio" value="history" v-model="transactionStatus"></input>
            <span>History Transaction</span>
          </label>
        </div>
      </div>
      <div class="datatable">
        <data-table :getData="getData"
                    :limit="10"
                    :column="isHistoryTransacstion ? 10: 9"
                    @DataTable:finish="onDatatableFinish"
                    ref="datatable">
          <th class="text-left title_name cl-time" data-sort-field="created_at">Time</th>
          <th class="text-left title_name cl-name" data-sort-field="name">Username</th>
          <th class="text-left title_name cl-transaction-id">Transaction Id</th>
          <th class="text-left title_name cl-payment-type" data-sort-field="payment_type">Payment Type</th>
          <th class="text-left title_name cl-real-amount">From</th>
          <th class="text-left title_name cl-real-currency">To</th>
          <th class="text-left title_name cl-status" data-sort-field="status">Status</th>
          <th class="text-right title_name cl-action" v-if="!isHistoryTransacstion">Action</th>
          <th class="text-right title_name cl-action" v-else>Errors</th>
          <template slot="body" slot-scope="props">
            <tr>
              <td class="text-left cl-time">
                <div>
                  {{ props.item.created_at | timestampToDate }}
                </div>
              </td>
              <td class="text-left cl-name">
                <div class="text-break">
                  {{ props.item.name }}
                </div>
              </td>

              <td class="text-left">
                <div class="text-break">
                  {{ props.item.transaction_id }}
                </div>
              </td>

              <td class="text-left cl-payment-type">
                <div class="text-break">
                  {{ props.item.payment_type }}
                </div>
              </td>
              <td class="text-left cl-real-amount" >
                <div class="text-break">
                  {{ props.item.real_amount | formatCurrencyAmount('0', 2) }} {{ props.item.real_currency | uppercaseFirst }}
                </div>
              </td>

              <td class="text-left cl-amount">
                <div class="text-break">
                  {{ props.item.amount | formatCurrencyAmount('0', 2) }} {{ props.item.currency | uppercaseFirst }}
                </div>
              </td>

              <td class="text-left cl-status">
                <div class="text-break">
                  {{ props.item | filterStatusTransaction | uppercaseFirst }}
                </div>
              </td>

              <td class="text-left">
                <button class="btn btn_Reject"
                    @click="onClickedShowErrorDetail(props.item.error_detail)"
                    v-if="props.item.error_detail">Errors Detail</button>

                <template v-if="visibleRejectAprrovedButton(props.item)">
                  <button class="btn btn_Reject"
                      @click="onClickedUpdateTransaction(props.item.id, REJECTED)">Reject</button>
                  <button class="btn btn_Confirm"
                      @click="onClickedUpdateTransaction(props.item.id, APPROVED)">Approved</button>
                </template>
              </td>
            </tr>
          </template>
        </data-table>
      </div>
    </div>
    <!-- Begin Modal -->
    <modal  :name="modalName"
            title="Error Details"
            :hasModalFooter="false"
            :enableClose="true">
      <template slot="body">
        <div class="error-detail">{{ transactionErrorDetail }}</div>
      </template>
    </modal>
    <!-- End Modal -->

    <loading :isLoading="isLoading"/>
  </div>
</template>

<script>
  import BigNumber from 'bignumber.js';
  import rf from '../../lib/RequestFactory';
  import Modal from '../../components/Modal';
  import { debounce } from 'lodash';

  const APPROVED = 1;
  const REJECTED = 0;
  const TRANSACTION_STATUS_CREATED = 'created';

  export default {
    components: {
      Modal
    },
    props: {
      type: {
        type: String,
        default: 'deposit'
      }
    },
    data() {
      return {
        searchKey: '',
        transactionStatus: 'pending',
        params: {},
        modalName: 'DetailErrorModal',
        transactionErrorDetail: '',
        APPROVED,
        REJECTED,

        isLoading: false,
      }
    },
    watch: {
      transactionStatus() {
        this.$refs.datatable.refresh();
      },

      searchKey: debounce(function () {
        this.search()
      }, 400)
    },
    computed: {
      isHistoryTransacstion() {
        return this.transactionStatus === 'history';
      },

      isWithdrawalPage() {
        return this.type === 'withdraw';
      }
    },
    methods: {
      search() {
          this.$refs.datatable.$emit('DataTable:filter', Object.assign(this.params, {search_key: this.searchKey}));
      },

      getPendingTransactions(params) {
        const meta = {
          type: this.type,
          status: this.transactionStatus
        }
        if (!window._.isEmpty(this.searchKey)) {
          meta.search_key = this.searchKey;
        }
        params = Object.assign(params, meta);
        return rf.getRequest('AdminRequest').getTransactions(params);
      },

      onClickedUpdateTransaction(transactionId, isApproved) {
        const params = {
          id: transactionId,
          is_approved: isApproved
        };

        this.isLoading = true;

        rf.getRequest('AdminRequest').updateExcuteTransaction(params).then(res => {
          this.$refs.datatable.refresh();
          this.showSuccess('Success');
        })
        .catch(error => {
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.showError(error.response.data.message);
        })
        .finally(() => {
          this.isLoading = false;
        })
      },

      getData(params) {
        const meta = {
          type: this.type,
          status: this.transactionStatus
        };
        return rf.getRequest('AdminRequest').getUserTransactions(Object.assign({}, params, meta));
      },

      onClickedShowErrorDetail(error) {
        this.transactionErrorDetail = error;
        window.CommonModal.show(this.modalName);
      },

      onDatatableFinish() {
        const datatable = this.$refs.datatable;
        // Only sort 'amount' field a again. Because amount field can be negative (withdraw) or positive (deposit).
        if (datatable.params.sort !== 'amount') {
          return;
        }
        const result = window._.chain(datatable.rows)
          .map(item => {
            item.amount = Math.abs(parseFloat(item.amount));
            return item;
          })
          .orderBy([datatable.params.sort], [datatable.params.sort_type])
          .value();
        this.$refs.datatable.rows = result;
      },

      visibleRejectAprrovedButton(item) {
        return this.isWithdrawalPage && item.status === TRANSACTION_STATUS_CREATED;
      }
    },
  }
</script>

<style lang="scss">
  #base_deposit_withdraw {
    .modal-dialog {
      width: 700px;
    }
  }
</style>

<style lang="scss" scoped>
  @import "../../../../sass/variables";
  table {
    thead {
      th {
        padding: 5px 7px !important;
      }
    }
    tbody {
      td {
        padding: 5px 7px !important;
      }
    }
  }
  .cl-time {
    min-width: 100px;
    width: 100px;
  }
  .cl-id {
    min-width: 60px;
  }
  .cl-name {
    min-width: 60px;
    width: 100px;
  }
  .cl-transaction-id {
    min-width: 60px;
    width: 150px;
  }
  .cl-payment-type {
    min-width: 60px;
    width: 150px;
    text-transform: capitalize;
  }
  .cl-currency {
    min-width: 100px;
    width: 100px;
  }
  .cl-real-amount {
    min-width: 100px;
    width: 125px;
  }
  .cl-real-currency {
    width: 125px;
  }
  .cl-amount {
    min-width: 60px;
    width: 100px;
  }
  .cl-status {
    min-width: 90px;
    width: 100px;
  }
  .cl-action {
    width: 100px;
  }

  .clearfix-40 {
    display: block;
    clear: both;
    height: 40px;
  }
  #base_deposit_withdraw {
    max-width: 1700px;
    .filter_container {
      margin: 12px 0px;
      .title_item {
        color: #043164;
        font-size: $font_big_20;
        font-weight: 500;
        line-height: 28px;
        float: left;
      }
      .right-el {
        display: inline-block;
        float: right;
        width: 215px;
        max-width: 100%;
        .search_input {
          position: relative;
          top: 20px;
          background-color: transparent;
          height: 28px;
          border: 1px solid $color_alto;
          padding: 4px 15px;
          line-height: 20px;
          width: 100%;
          overflow: hidden;
          white-space: nowrap;
          // text-overflow: ellipsis;
          font-size: $font-small;
        }
      }
      .input-radio {
        // width: 300px;
        label {
          display: block;
          font-weight: normal;
          input[name="transaction_status"] ~ span {
            vertical-align: top;
          }
        }
      } 
    }
    .title_name {
      font-size: 14px;
      font-weight: normal;
    }
    .item_email_user {
      display: inline-block;
      float: left;
      position: relative;
      .txt_email_user {
        display: block;
        max-width: 110px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
      }
      .tooltip_email_user {
        position: absolute;
        top: 0px;
        left: 0px;
        line-height: 20px;
        padding: 5px 20px;
        left: 100%;
        background-color: $color_white;
        white-space: nowrap;
        width: auto;
        z-index: 10;
        font-size: $font_root;
        font-weight: 500;
        color: $color_mine_shaft;
        transition: 0.5s;
        display: none;
        box-shadow: 1px 1px 15px rgba(0, 0, 0, 0.4);
        &:after {
          right: 100%;
          top: 50%;
          border: solid transparent;
          content: " ";
          height: 0;
          width: 0;
          position: absolute;
          pointer-events: none;
          border-color: rgba(136, 183, 213, 0);
          border-right-color: $color_white;
          border-width: 5px;
          margin-top: -5px;
        }
      }
      &:hover {
        .tooltip_email_user {
          display: block;
          transition: 0.5s;
        }
      }
    }
    .text-break {
      word-break: break-word;
      max-width: 250px;
      font-size: $font-small;
    }
    .btn_Confirm,
    .btn_Reject {
        float: right;
        background-color: transparent;
        text-transform: uppercase;
        // width: 70px;
        height: 23px;
        line-height: 20px;
        padding: 0px 9px;
        display: block;
        margin-bottom: 5px;
        text-align: center;
        font-size: $font-smaller;
        font-weight: 600;
        border: 1px solid;
        border-radius: 20px;
        transition: 0.5s;
        &:hover {
          background-color: $color_red_text;
          border-color: $color_red_text;
          color: $color_white;
          transition: 0.5s;
        }
    }
    .btn_Reject {
      color: $color_dove_gray;
      border-color: $color_silver;
    }
    .btn_Confirm{
      color: $color_corn_pale;
      border-color: $color_corn_pale;
      &:hover {
        background-color: #55d184;
        border-color: #55d184;
      }
    }
    .error-detail {
      max-height: 500px;
      overflow: auto;
    }
  }
</style>
