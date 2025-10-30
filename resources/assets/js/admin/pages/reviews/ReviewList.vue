<template>
  <div id="games_setting" class="boxCore">
    <div class="tab">
      <button class="tablinks"
        :class="{ active: params.object_type === TABS.SESSION }"
        @click="params.object_type = TABS.SESSION"
      >Session</button>

      <button class="tablinks"
        :class="{ active: params.object_type === TABS.BOUNTY }"
        @click="params.object_type = TABS.BOUNTY"
      >Bounty</button>
    </div>
    <div class="games_list">
      <div class="filter_container clearfix">
        <span class="left-el">
          <label>Game:</label>
          <select v-model="params.filter_game" @change="filter">
            <option v-for="(item, key) in games" :value="item.value">
              {{ item.label }}
            </option>
          </select>
        </span>
        <span class="right-el">
          <select v-model="params.search_type" @change="doSearch">
            <option v-for="(item, key) in SEARCH_TYIES" :value="item">
              {{ $t(`review.search_type.${item}`)}}
            </option>
          </select>

          <input title="Search"
            type="text"
            :placeholder="'Search username'"
            v-on:keyup.enter="search"
            @input="search"
            class="form-control search_input"
            name="searchKey"
            v-model="params.search_key"/>
        </span>
      </div>
    </div>
    <div class="datatable">
      <data-table :getData="getReviews"
                  class="overflow-x-auto"
                  :limit="params.limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @onPageChange="params.page = $event"
                  @onLimitChange="params.limit = $event"
                  @DataTable:finish="onDatatableFinish">
        <th class="col1 text-left">Review ID</th>
        <th class="col2 text-left">Game Title</th>
        <th class="col3 text-left">Reviewer Name</th>
        <th class="col4 text-left">Username</th>
        <th class="col4 text-left">Gamelancer</th>
        <th class="col5 text-left" data-sort-field="session_reviews.rate">Rate</th>
        <th class="col6 text-left">Description</th>
        <th class="col7 text-left">{{ $t('session.action') }}</th>
        <template slot="body" slot-scope="props">
          <tr>
            <td class="col1 text-left">
              {{ props.item.id }}
            </td>
            <td class="col2 text-left">
              {{ props.item.game_title }}
            </td>
            <td class="col3 text-left">
              <a :href="redriectUserProfile(props.item.reviewer_name)" target="_blank">{{ props.item.reviewer_name }}</a>
            </td>
            <td class="col4 text-left">
              <a :href="redriectUserProfile(props.item.username)" target="_blank">{{ props.item.username }}</a>
            </td>
            <td class="col4 text-left">
              <a :href="redriectUserProfile(props.item.gamelancer_name)" target="_blank">{{ props.item.gamelancer_name }}</a>
            </td>
            <td class="col5 text-left">
              {{ props.item.rate | formatCurrencyAmount('0', 2) }}
            </td>
            <td class="col6 text-left" :title="props.item.description">
              <span class="three-dots">
                {{ props.item.description }}
              </span>
            </td>
            <td class="col7 text-left">
              <button type="button" class="btn view-request" :title="$t('bounty_page.modal_view_detail_bounty')" @click="clickViewReviewDetail(props.item)">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
              <button class="btn" @click.stop="onClickRemove(props.item.id)">
                <i class="fa fa-trash-o"></i>
              </button>
            </td>
          </tr>
        </template>
      </data-table>
    </div>
    <modal name="viewReviewDetail" width="560" :title="`Review Detail - ${selectedReview.id}`">
      <div slot="body" class="body-popup">
        <ul class="list_property_bounty">
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">Game Title</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedReview.game_title | upperFirst }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">Reviewer Name</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">
                  <a :href="redriectUserProfile(selectedReview.reviewer_name)" target="_blank">{{ selectedReview.reviewer_name }}</a>
                </div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">Username</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">
                  <a :href="redriectUserProfile(selectedReview.username)" target="_blank">{{ selectedReview.username }}</a>
                </div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">Gamelancer</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">
                  <a :href="redriectUserProfile(selectedReview.gamelancer_name)" target="_blank">{{ selectedReview.gamelancer_name }}</a>
                </div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-6">
                <div class="text-left text-bold">Rate</div>
              </div>
              <div class="col-xs-6">
                <div class="text-right">{{ selectedReview.rate | formatCurrencyAmount('0', 2) }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-12">
                <div class="text-left text-bold">Description</div>
              </div>
              <div class="col-xs-12">
                <div class="text-right description">{{ selectedReview.description }}</div>
              </div>
            </div>
          </li>
          <li>
            <div class="row">
              <div class="col-xs-12">
                <div class="text-left text-bold">Tags</div>
              </div>
              <div class="col-xs-12">
                <div class="text-right description">
                  <span v-for="tag, i in selectedReview.tags">
                    {{ tag.tag_name.content }}
                    <span v-if="i < (selectedReview.tags.length - 1)">,&nbsp;</span>
                  </span>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </modal>
  </div> 
</template>
<script>
  import rf from '../../lib/RequestFactory';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';
  import { debounce, isEmpty, map, concat } from 'lodash';
  import { mapGetters } from 'vuex'
  import Const from '../../common/Const'
  import Modal from '../../components/Modal';

  const SEARCH_TYIES = {
    ALL: 'all',
    USERNAME: 'users.username',
    REVIEWER_NAME: 'reviewer.username'
  }

  const TABS = {
    SESSION: 'session',
    BOUNTY: 'bounty'
  }

  export default {
    data() {
      return {
        titlePage: 'Reviews List',
        params: {
          object_type: TABS.SESSION,
          search_key: '',
          search_type: SEARCH_TYIES.ALL,
          filter_game: '',
          filter_status: ''
        },
        column: 7,
        rows: [],
        SEARCH_TYIES,
        SESSION_STATUS: Const.SESSION_STATUS,
        TABS,
        selectedReview: {}
      }
    },

    components: {
      Modal
    },

    mixins: [RemoveErrorsMixin],

    computed: {
      ...mapGetters([
        'masterdata'
      ]),

      games () {
        const games = this.masterdata.games || {}

        const defaultOption = { value: '', label: 'All' }
        return concat([defaultOption], map(games, i => {
          return { value: i.slug, label: i.title }
        }))
      }
    },

    created () {
      this.initParams()
    },

    watch: {
      'params.object_type': function () {
        this.search()
      }
    },

    methods: {
      onDatatableFinish() {
        delete this.params.sort
        delete this.params.sort_type

        const query = {
          page: this.params.page,
          limit: this.params.limit,
          object_type: this.params.object_type,
          search_type: this.params.search_type,
          search_key: this.params.search_key,
          game: this.params.filter_game,
          status: this.params.filter_status
        }
        this.$router.replace({ name: this.$route.name, query })
      },

      doSearch () {
        if (isEmpty(this.params.search_key)) {
          return
        }

        this.search()
      },

      search () {
        this.params = {
          ...this.params,
          page: 1
        }

        this.filter()
      },

      filter: debounce(function () {
        this.$refs.datatable.filter(this.params)
      }, 400),

      getReviews(params) {
        this.params = {
          ...params,
          ...this.params
        }

        return rf.getRequest('AdminRequest').getReviews(this.params);
      },

      onClickRemove (reviewId) {
        window.ConfirmationModal.show({
          type        : 'confirm',
          title       : '',
          content     : 'Do you want to remove this review?',
          onConfirm   :  () => {
            return this.requestHandler(rf.getRequest('AdminRequest').deleteReivew({ id: reviewId }))
          },
          onCancel    : () => {}
        })
      },

      requestHandler (promise) {
        this.startSubmit()
        promise.then(res => {
          this.endSubmit()
          this.showSuccess('Successful')
          this.refresh()
        })
        .catch(error => {
          this.endSubmit()
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"))
            return
          }
          this.convertRemoteErrors(error)
        })
      },

      refresh() {
        this.$refs.datatable.refresh()
      },

      initParams () {
        const query = this.$route.query
        this.params.object_type = query.object_type || this.params.object_type
        this.params.search_type = query.search_type || this.params.search_type
        this.params.search_key = query.search_key || this.params.search_key
        this.params.filter_game = query.game || this.params.filter_game
        this.params.filter_status = query.status || this.params.filter_status

        this.params = {
          ...this.params,
          page: query.page || 1,
          limit: parseInt(query.limit) || 10
        }
      },

      clickViewReviewDetail(data) {
        this.selectedReview = data
        CommonModal.show('viewReviewDetail', {
          position: 'center',
          mask: false,
          enableClose: true,
        });
      },

      redriectUserProfile(username) {
        const webAppUrl = process.env.MIX_WEB_APP_URL
        return `${webAppUrl}/${username}`
      }
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this);
    }
  }
</script>
<style lang="scss" scoped>
@import "../../../../sass/variables";
.games_list {
  .btn_creat_game {
    border: 1px solid $color_eden;
    line-height: 20px;
    padding: 3px 12px;
    font-size: $font_root;
    font-weight: bold;
    border-radius: 22px;
    text-align: center;
    color: $color_eden;
    transition: 0.5s;
    min-width: 86px;
    cursor: pointer;
    text-transform: uppercase;
    &:focus,
    &:active,
    &:hover {
      background-color: $color_eden;
      border-color: $color_eden;
      color: $color_white;
      transition: 0.5s;
    }
    .icon-plus {
      font-size: $font_mini_mini;
      float: left;
      margin-right: 5px;
      line-height: 20px;
    }
  }
  .filter_container {
    margin: 12px 0px;
    .title_item {
      color: $color_mine_shaft;
      font-size: $font_big_20;
      font-weight: 500;
      line-height: 28px;
      float: left;
    }
    .left-el {
      display: inline-block;
      float: left;
      height: 28px;
      max-width: 100%;
      select{
        font-size: 13px;
        height: 100%;
        background: $color_white;
      }
      .search_input {
        background-color: transparent;
        height: 100%;
        border: 1px solid $color_alto;
        padding: 4px 15px;
        line-height: 20px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: $font-small;
        display: inline-block;
        width: auto;
      }
    }
    .right-el {
      display: inline-block;
      float: right;
      height: 28px;
      max-width: 100%;
      select{
        font-size: 13px;
        height: 100%;
        background: $color_white;
      }
      .search_input {
        background-color: transparent;
        height: 100%;
        border: 1px solid $color_alto;
        padding: 4px 15px;
        line-height: 20px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: $font-small;
        display: inline-block;
        width: auto;
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
.datatable {
  .overflow-x-auto {
    overflow-x: auto;
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
      .description {
        word-break: break-all;
        text-align: left;
        max-height: 300px;
        overflow-x: hidden;
      }
    }
  }
  .content, .action {
    text-align: center;
  }
  .content {
    min-height: 50px;
  }
}
#games_setting {
  max-width: 1700px;
  .tab {
    overflow: hidden;
    background-color: #f1f1f1;
    border-bottom: 1px solid #CFCFCF;
    button {
      padding: 0 20px;
      height: 40px;
      box-sizing: border-box;
      line-height: 40px;
      display: inline-block;
      list-style: none;
      font-size: 14px;
      font-weight: 500;
      background: transparent;
      border: none;
      &:hover {
        color: inherit;
      }
    }
    .active {
      border-bottom: 2px solid #12575f;
    }
  }

  table {
    .col1, .col7 {
      width: 50px;
    }
    .col2, .col3, .col4, .col5 {
      width: 100px;
    }
    .col6 {
      width: 250px;
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
        .btn_edit_user, .btn_save_user {
          background-color: $color_champagne;
        }
      }
      tr {
        .btn_edit_user:active,.btn_edit_user:hover, 
        .btn_save_user:active, .btn_save_user:hover {
          background-color: $color_eden;
          color: $color_white;
        }
      }
    }
  }
}
</style>
<style lang="scss">
  #games_setting {
    .modal {
      .modal-dialog {
        .modal-content {
          .modal-title {
            text-align: center;
          }
        }
      }
    }
  }
</style>
