<template>
  <div id="list-bounty" class="boxCore">
    <div class="bouny-list">
      <div class="filter_container clearfix">
        <span class="right-el">
          <input title="Search" type="text" :placeholder="$t('bounty_page.bounty_search')"
            class="form-control search_input" name="searchKey" v-model="searchKey"/>
        </span>
      </div>
    </div>
    <div class="datatable">
      <data-table :getData="getBounties"
                  :limit="limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @DataTable:finish="onDatatableFinish">
        <th class="col1 text-left" data-sort-field="id">No.</th>
        <th class="col2 text-left">{{ $t('bounty_page.user_create_bounty')}}</th>
        <th class="col2 text-left">Game</th>
        <th class="col3 text-left" data-sort-field="title">{{ $t('bounty_page.title') }}</th>
        <th class="col4 text-center" data-sort-field="description">{{ $t('bounty_page.description') }}</th>
        <th class="col5 text-center" data-sort-field="price">{{ $t('bounty_page.price') }}</th>
        <th class="col8 text-center" data-sort-field="status">{{ $t('bounty_page.status') }}</th>
        <th class="col9 text-center">{{ $t('game.action') }}</th>
        <template slot="body" slot-scope="props">
          <tr>
            <td class="col1 text-left">
              {{ props.realIndex }}
            </td>
            <td class="col2 text-left">
              <span class="three-dots">
                {{ props.item.user.username }}
              </span>
            </td>
            <td class="col2 text-left">
              <span class="three-dots">
                {{ props.item.game.title }}
              </span>
            </td>
            <td class="col3 text-left" :title="props.item.title">
              <span class="three-dots">
                {{ props.item.title }}
              </span>
            </td>
            <td class="col4 text-left" :title="props.item.description">
              <span class="three-dots">
                {{ props.item.description }}
              </span>
            </td>
            <td class="col5 text-center">
              {{ props.item.price | formatCurrencyAmount }} {{ props.item.price > 1 ? 'Coins' : 'Coin' }}
            </td>
            <td class="col8 text-center">
              {{ props.item.status | upperFirst }}
            </td>
            <td class="col9 text-center">
              <button type="button" class="btn view-request" :title="$t('bounty_page.modal_view_bounty_request')" @click="clickViewBountyRequest(props.item.id)">
                <i class="fa fa-tasks" aria-hidden="true"></i>
              </button>
              <button type="button" class="btn view-request" :title="$t('bounty_page.modal_view_detail_bounty')" @click="clickViewDetailBounty(props.item)">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </td>
          </tr>
        </template>
      </data-table>
    </div>
    <modal name="viewDetailBounty" width="560" :title="selectedBounty.title">
      <div slot="body" class="body-popup">
        <ul class="list_property_bounty">
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.user_create_bounty') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedBounty.user.username | upperFirst }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.bounty_claim_request') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ clamerName(selectedBounty.claim_bounty_request) | valueOrNo }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.price') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedBounty.price | formatCurrencyAmount }} {{ selectedBounty.price > 1 ? 'Coins' : 'Coin' }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.escrow_balance') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedBounty.escrow_balance | formatCurrencyAmount }} {{ selectedBounty.escrow_balance > 1 ? 'Coins' : 'Coin' }}</div>
              </div>
            </div>
          </li>
          <!-- <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.fee') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedBounty.fee | valueOrNo  }}</div>
              </div>
            </div>
          </li> -->
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.status') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedBounty.status | upperFirst }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.user_has_review') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedBounty.user_has_review | valueOrNotYet }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.claimer_has_review') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedBounty.claimer_has_review | valueOrNotYet }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">{{ $t('bounty_page.reason') }}</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ reasion(selectedBounty.user_report) | valueOrNo }}</div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </modal>
    <modal name="viewBountyRequest" width="560" :title="$t('bounty_page.modal_view_bounty_request')">
      <div slot="body" class="body-popup">
        <data-table :getData="getRequests"
                  :limit="limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @DataTable:finish="onDatatableFinish">
          <th class="col1 text-left" data-sort-field="id">{{ $t('bounty_page.id') }}</th>
          <th class="col2 text-left">{{ $t('bounty_page.claimer_info') }}</th>
          <th class="col3 text-center" data-sort-field="description">{{ $t('bounty_page.description') }}</th>
          <th class="col5 text-center" data-sort-field="status">{{ $t('bounty_page.status') }}</th>
          <template slot="body" slot-scope="props">
            <tr>
              <td class="col1 text-left">
                {{ props.index + 1 }}
              </td>
              <td class="col2 text-left">
                {{ props.item.claimer_info.username | upperFirst }}
              </td>
              <td class="col3 text-center">
                {{ props.item.description | upperFirst }}
              </td>
              <td class="col5 text-center">
                {{ props.item.status | upperFirst }}
              </td>
            </tr>
          </template>
        </data-table>
      </div>
    </modal>
  </div> 
</template>
<script>
  import rf from '../../lib/RequestFactory';
  import Modal from '../../components/Modal';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';
  import { debounce } from 'lodash'
  export default {
    components: {
      Modal
    },
    data() {
      return {
        titlePage: this.$t('bounties.title'),
        searchKey: '',
        limit: 10,
        params: {},
        column: 9,
        rows: [],
        selectedBounty: {
          user_level_meta: {},
          user: {},
          claim_bounty_request: {
            claimer_info: {}
          },
          rank: {},
          user_report: {
            reason: {}
          }
        },
        selectBountyRequestId: null
      }
    },
    mixins: [RemoveErrorsMixin],
    watch: {
      searchKey: debounce(function () {
        this.search()
      }, 400)
    },
    methods: {
      refresh() {
        this.$refs.datatable.refresh();
      },
      onDatatableFinish() {
        this.rows = this.$refs.datatable.rows;
        window._.each(this.rows, item => {
          this.$set(item, 'editable', false);
        });
      },
      search() {
        this.$refs.datatable.filter({...this.params, search_key: this.searchKey});
      },
      getBounties(params) {
        this.params = params;
        return rf.getRequest('BountyRequest').getBounties(params);
      },
      clamerName(claimRequest) {
        return claimRequest?.claimer_info?.username
      },
      reasion(userReport) {
        return userReport?.reason
      },
      clickViewDetailBounty(data) {
        this.selectedBounty = data
        CommonModal.show('viewDetailBounty', {
          position: 'center',
          mask: false,
          enableClose: true,
        });
      },
      clickViewBountyRequest(data) {
        this.selectBountyRequestId = data
        CommonModal.show('viewBountyRequest', {
          position: 'center',
          mask: false,
          enableClose: true,
        });
      },
      getRequests(params) {
        this.params = {...params, ...{id: this.selectBountyRequestId}};
        return rf.getRequest('BountyRequest').getBountyClaim(this.params);
      }
    },
    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this);
    }
  }
</script>
<style lang="scss" scoped>
@import "../../../../sass/variables";
.bouny-list {
  .filter_container {
    margin: 12px 0px;
    .title_item {
      color: $color_mine_shaft;
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
    .input-radio {
      label {
        display: block;
        font-weight: normal;
      }
    } 
  }
}
#list-bounty {
  max-width: 1700px;
  table {
    .col1 {
      width: 80px;
    }
    .col2, .col9 {
      width: 150px;
    }
    .col3 {
      width: 200px;
    }
    .col4 {
      min-width: 250px;
    }
    .col5, .col6, .col7 {
      width: 100px;
    }
    .col8 {
      width: 110px;
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
    thead {
      th {
        padding: 5px 0px 5px 10px; 
      }
    }
    td {
      word-break: break-word;
      img {
        height: 100px;
      }
    }
    tbody {
      tr:hover {
        .view-request {
          background-color: $color_champagne;
        }
      }
      tr {
        .view-request:active,.view-request:hover {
          background-color: $color_eden;
          color: $color_white;
        }
      }
    }
  }
  .modal {
    .body-popup {
      .col1 {
        width: 100px;
      }
      .col2, .col4 {
        width: 150px;
      }
    }
    .list_property_bounty {
      list-style: none;
      padding: 0;
      margin: 0;
      li {
        min-height: 30px;
        margin-bottom: 5px;
        border-bottom: 1px solid #e4e4e4;
      }
    }
    .content, .action {
      text-align: center;
    }
    .content {
      min-height: 50px;
    }
  }
}
</style>
