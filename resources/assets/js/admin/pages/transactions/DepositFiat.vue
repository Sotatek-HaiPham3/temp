<template>
  <div class="deposit_pages boxCore">
    <div class="pending_transaction">
      <div class="filter_container clearfix">
        <span class="title_item">Pending Transactions</span>
        <span class="search_box">
          <input title="조회" type="text" placeholder="Search" v-on:keyup.enter="searchPending" class="form-control search_input" name="searchKey" v-model="pendingSearchKey"/>
        </span>
      </div>
      <div class="datatable">
        <data-table :getData="getPendingTransactions" ref="pending_transaction" :limit="10" :column="7" class="scroll">
          <th class="text-left" data-sort-field="transactions.created_at">Time</th>
          <th class="text-left" data-sort-field="users.email">User</th>
          <th class="text-left" data-sort-field="transactions.deposit_code">Code</th>
          <th class="text-left" data-sort-field="users.email">Bank</th>
          <th class="text-left" data-sort-field="users.email">Account</th>
          <th class="text-left" data-sort-field="transactions.amount">Amount</th>
          <th class="text-right"></th>
          <template slot="body" slot-scope="props">
            <tr>
              <td class="text-left" >
                <div>{{ props.item.created_at | formatTimeStamp('YYYY-MM-DD') }} {{ props.item.created_at | formatTimeStamp('HH:mm:ss') }}</div>
                <div></div>
              </td>
              <td class="text-left">
                <div class="item_email_user">
                  <span class="txt_email_user">{{props.item.email}}</span>
                  <span class="tooltip_email_user">{{props.item.email}}</span>
                </div>
              </td>
              <td class="text-left" >
                {{props.item.code}}
              </td>
              <td class="text-left">
                {{props.item.bank_name}}
              </td>
              <td class="text-left">
                {{props.item.account_name}} / {{props.item.account_no}}
              </td>
              <td class="text-left" >
                {{props.item.amount | formatUsdAmount('0')}}
              </td>
              <td class="text-right" >
                <button class="btn btn_Reject" @click="rejectTransaction(props.item)">Reject</button>
                <button class="btn btn_Confirm" @click="confirmTransaction(props.item)">Confirm</button>
              </td>
            </tr>
          </template>
        </data-table>
      </div>
    </div>

    <div class="clearfix clearfix-40"></div>

    <div class="transaction_history">
      <div class="filter_container clearfix">
        <span class="title_item">History Transactions</span>
        <span class="search_box">
          <input title="조회" type="text" placeholder="Search" v-on:keyup.enter="searchHistory" class="form-control search_input" name="searchKey" v-model="historySearchKey"/>
        </span>
      </div>
      <div class="datatable">
        <data-table :getData="getTransactionHistories" ref="transaction_history" :limit="10" :column="7" class="scroll">
          <th class="text-left" data-sort-field="transactions.created_at">Time</th>
          <th class="text-left" data-sort-field="users.email">User</th>
          <th class="text-left" data-sort-field="transactions.deposit_code">Code</th>
          <th class="text-left" data-sort-field="users.email">Bank</th>
          <th class="text-left" data-sort-field="users.email">Account</th>
          <th class="text-left" data-sort-field="transactions.amount">Amount</th>
          <th class="text-right" data-sort-field="transactions.amount">Status</th>
          <template slot="body" slot-scope="props">
            <tr>
              <td class="text-left" >
                <div>{{ props.item.created_at | formatTimeStamp('YYYY-MM-DD') }} {{ props.item.created_at | formatTimeStamp('HH:mm:ss') }}</div>
                <div></div>
              </td>
              <td class="text-left">
                <div class="item_email_user">
                  <span class="txt_email_user">{{props.item.email}}</span>
                  <span class="tooltip_email_user">{{props.item.email}}</span>
                </div>
              </td>
              <td class="text-left" >
                {{props.item.code}}
              </td>
              <td class="text-left">
                {{props.item.bank_name}}
              </td>
              <td class="text-left">
                {{props.item.account_name}} / {{props.item.account_no}}
              </td>
              <td class="text-left" >
                {{props.item.amount | formatUsdAmount('0')}}
              </td>
              <td class="text-right" >
                {{props.item.status}}
              </td>
            </tr>
          </template>
        </data-table>
      </div>
    </div>

    <div class="clearfix clearfix-40"></div>

    <modal name="confirmTransaction" width="460" title="">
      <div slot="body" class="body-popup">

        <ul class="list_modal_deposit">
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left">User</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{selectedTransaction.email}}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left">Account Name</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{selectedTransaction.account_name}}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left">Amount</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{selectedTransaction.amount | abs | formatCurrencyAmount}} {{'usd' | currencyName}}</div>
              </div>
            </div>
          </li>
        </ul>
        <div class="content_modal_deposit">Would you like to confirm this transaction?</div>
        
      </div>
    </modal>


    <modal name="rejectTransaction" width="460" >
      <div slot="body" class="body-popup">

        <ul class="list_modal_deposit">
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left">User</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{selectedTransaction.email}}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left">Account Name</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{selectedTransaction.account_name}}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left">Amount</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{selectedTransaction.amount | abs | formatCurrencyAmount}} {{'usd' | currencyName}}</div>
              </div>
            </div>
          </li>
        </ul>
        <div class="content_modal_deposit">Would you like to reject this transaction?</div>
      </div>
    </modal>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import Modal from '../../components/Modal';
  import { mapGetters, mapActions } from 'vuex';

  export default {
    components: {
      Modal,
    },
    data() {
      return {
        titlePage: 'Deposit',
        pendingSearchKey: '',
        historySearchKey: '',
        selectedTransaction: {}
      }
    },
    
    methods: {
      ...mapActions([
        'getUsdTransactions'
      ]),

      searchPending() {
        this.$refs.pending_transaction.refresh();
      },

      searchHistory() {
        this.$refs.transaction_history.refresh();
      },

      refresh() {
        this.$refs.pending_transaction.refresh();
        this.$refs.transaction_history.refresh();
      },

      listenForNotification() {
        window.Echo.channel('App.Models.Admin')
          .listen('AdminNotificationUpdated', () => {
            this.$refs.pending_transaction.refresh();
          });
      },

      getPendingTransactions(params) {
        let meta = {
          type: 'deposit',
          status: 'pending'
        };
        if (this.pendingSearchKey) {
          meta.search_key = this.pendingSearchKey;
        }
        return this.getUsdTransactions(Object.assign({}, params, meta));
      },

      getTransactionHistories(params) {
        let meta = {
          type: 'deposit',
          status: 'confirm'
        };
        if (this.historySearchKey) {
          meta.search_key = this.historySearchKey;
        }
        return this.getUsdTransactions(Object.assign({}, params, meta));
      },

      rejectTransaction(transaction) {
        this.selectedTransaction = transaction;
        CommonModal.show('rejectTransaction', {
          position: 'center',
          mask: false,
          buttons: [
            {
              label: 'Cancel',
              style: {
                background: 'rgb(191, 191, 191);'
              },
              callback: () => {
                CommonModal.hide('rejectTransaction');
              }
            },
            {
              label: 'Confirm',
              focused: true,
              style: {
                background: 'rgb(0, 112, 192)'
              },
              callback: () => {
                CommonModal.hide('rejectTransaction');
                let data = {
                  transaction_id: transaction.id
                };
                return rf.getRequest('AdminRequest').rejectUsdTransaction(data).then(res => {
                  this.refresh();
                  this.$broadcast('UsdTransactionUpdated');
                });
              }
            }
          ]
        });
      },

      confirmTransaction(transaction) {
        this.selectedTransaction = transaction;
        CommonModal.show('confirmTransaction', {
          position: 'center',
          mask: false,
          buttons: [
            {
              label: 'Cancel',
              style: {
                background: 'rgb(191, 191, 191);'
              },
              callback: () => {
                CommonModal.hide('confirmTransaction');
              }
            },
            {
              label: 'Confirm',
              focused: true,
              style: {
                background: 'rgb(0, 112, 192)'
              },
              callback: () => {
                CommonModal.hide('confirmTransaction');
                let data = {
                  transaction_id: transaction.id
                };
                return rf.getRequest('AdminRequest').confirmUsdTransaction(data).then(res => {
                  this.refresh();
                  this.$broadcast('UsdTransactionUpdated');
                });
              }
            }
          ]
        });
      },
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this);
      this.listenForNotification();
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/variables";

  ul {
    list-style-type: none;
    padding: 0px;
  }
  .clearfix-40 {
    display: block;
    clear: both;
    height: 40px;
  }
  .deposit_pages {

    .filter_container {
      margin: 12px 0px;
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
    .btn_Confirm,
    .btn_Reject {
        float: right;
        background-color: transparent;
        text-transform: uppercase;
        width: 70px;
        height: 23px;
        line-height: 20px;
        padding: 0px 9px;
        text-align: center;
        font-size: $font-smaller;
        font-weight: 600;
        border: 1px solid;
        border-radius: 20px;
        transition: 0.5s;
        &:hover {
          background-color: $color_corn;
          border-color: $color_corn;
          color: $color_white;
          transition: 0.5s;
        }
    }
    .btn_Reject {
      margin-left: 10px;
      color: $color_dove_gray;
      border-color: $color_silver;
    }
    .btn_Confirm{
      color: $color_corn_pale;
      border-color: $color_corn_pale;
    }
    .list_modal_deposit {
      margin-bottom: 25px;
      padding: 0px;

      li {
        line-height: 20px;
        margin-bottom: 10px;
        color: $color_dove_gray;
        font-size: $font_root;
        font-weight: 500;  
        .text-right {
          font-weight: 600;
          color: $color_mine_shaft;
        }
      }
    }
    .content_modal_deposit {
      color: $color_mine_shaft;
      font-size: $font_big_20;
      font-weight: 500;
      text-align: center;
      line-height: 24px;
    }
  }
</style>
