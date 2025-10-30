<template>
  <div id="games_setting" class="boxCore">
    <div class="games_list">
      <div class="filter_container clearfix">
        <span class="left-el">
          <label>Game:</label>
          <select v-model="params.filter_game" @change="filter">
            <option v-for="(item, key) in games" :value="item.value">
              {{ item.label }}
            </option>
          </select>

          <label>Status:</label>
          <select v-model="params.filter_status" @change="filter">
            <option value="">All</option>
            <option v-for="(item, key) in SESSION_STATUS" :value="item">
              {{ item | uppercaseFirst }}
            </option>
          </select>
        </span>
        <span class="right-el">
          <select v-model="params.search_type" @change="doSearch">
            <option v-for="(item, key) in SEARCH_TYIES" :value="item">
              {{ $t(`session.search_type.${item}`)}}
            </option>
          </select>

          <input title="Search"
            type="text"
            :placeholder="$t('session.search_session')"
            v-on:keyup.enter="search"
            @input="search"
            class="form-control search_input"
            name="searchKey"
            v-model="params.search_key"/>
        </span>
      </div>
    </div>
    <div class="datatable">
      <data-table :getData="getGames"
                  class="overflow-x-auto"
                  :limit="params.limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @onPageChange="params.page = $event"
                  @onLimitChange="params.limit = $event"
                  @DataTable:finish="onDatatableFinish">
        <th class="col1 text-left">Session ID</th>
        <th class="col2 text-left">{{ $t('session.game_title') }}</th>
        <th class="col3 text-left">{{ $t('session.gamelancer_name') }}</th>
        <th class="col4 text-left">{{ $t('session.claimer_name') }}</th>
        <th class="col5 text-left" data-sort-field="game_profile_offers.price">{{ $t('session.game_offer') }}</th>
        <th class="col6 text-left" data-sort-field="quantity">{{ $t('session.quantity') }}</th>
        <th class="col7 text-left">{{ $t('session.status') }}</th>
        <th class="col8 text-left">{{ $t('session.action') }}</th>
        <template slot="body" slot-scope="props">
          <tr>
            <td class="col1 text-left">
              {{ props.item.id }}
            </td>
            <td class="col2 text-left">
              <span class="three-dots">
                {{ props.item.game_title }}
              </span>
            </td>
            <td class="col3 text-left">
              {{ props.item.gamelancer_name }}
            </td>
            <td class="col4 text-left">
              {{ props.item.claimer_name }}
            </td>
            <td class="col5 text-left">
              {{ props.item.price | formatGameOffer(props.item.type) }}
            </td>
            <td class="col6 text-left">
              {{ props.item.quantity | formatCurrencyAmount('0', 2) }}{{ gameType(props.item.type) }}
            </td>
            <td class="col7 text-left">
              {{ props.item.status | upperFirst }}
            </td>
            <td class="col8 text-left">
              <button type="button"
                class="btn view-request"
                :title="$t('session.view_detail_session')"
                @click="$router.push({name: 'Session Detail', params: {id: props.item.id}})"
              >
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </td>
          </tr>
        </template>
      </data-table>
    </div>
  </div> 
</template>
<script>
  import rf from '../../lib/RequestFactory';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';
  import { debounce, isEmpty, map, concat } from 'lodash';
  import { mapGetters } from 'vuex'
  import Const from '../../common/Const'

  const SEARCH_TYIES = {
    ALL: 'all',
    GAMELANCER_NAME: 'gamelancer.username',
    CLAIMER_NAME: 'claimer.username'
  }

  export default {
    data() {
      return {
        titlePage: this.$t('sessions.title'),
        params: {
          search_key: '',
          search_type: SEARCH_TYIES.ALL,
          filter_game: '',
          filter_status: ''
        },
        column: 7,
        rows: [],
        SEARCH_TYIES,
        SESSION_STATUS: Const.SESSION_STATUS
      }
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

    methods: {
      onDatatableFinish() {
        delete this.params.sort
        delete this.params.sort_type

        const query = {
          page: this.params.page,
          limit: this.params.limit,
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

      getGames(params) {
        this.params = {
          ...params,
          ...this.params
        }

        return rf.getRequest('SessionRequest').getSession(this.params);
      },

      initParams () {
        const query = this.$route.query
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

      gameType (type) {
        if (type === 'per_game') {
          return 'g'
        }

        return 'h'
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
#games_setting {
  max-width: 1700px;
  table {
    .col1, .col8 {
      width: 70px;
    }
    .col2, .col3, .col4 {
      width: 150px;
    }
    .col5, .col6, .col7 {
      width: 100px;
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
