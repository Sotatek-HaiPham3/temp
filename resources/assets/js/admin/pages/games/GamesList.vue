<template>
  <div id="games_setting" class="boxCore">
    <div class="games_list">
      <div class="filter_container clearfix">
        <span class="title_item">
          <router-link class="btn btn_creat_game" :to="{ name: 'Add Game'}">
            <span class="icon-plus"></span> {{ $t('game.create_new_game') }}
          </router-link>
        </span>
        <button class="btn sort-btn" @click.stop="orderGames()">Auto Arrange Games</button>
        <span class="right-el">
          <input title="Search" type="text" :placeholder="$t('game.search_game')"
          v-on:keyup.enter="search" class="form-control search_input" name="searchKey" v-model="searchKey"/>
        </span>
      </div>
    </div>
    <div class="datatable">
      <data-table :getData="getGames"
                  :limit="limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @DataTable:finish="onDatatableFinish">
        <th class="col0 text-left">No.</th>
        <th class="col0 text-center">Id</th>
        <th class="col1 text-center" data-sort-field="title">{{ $t('game.name') }}</th>
        <th class="col2 text-center">Logo</th>
        <th class="col3 text-center">Heading Background</th>
        <th class="col0 text-center">{{ $t('game.order') }}</th>
        <th class="col5 text-center">Status</th>
        <th class="col6 text-center">{{ $t('game.action') }}</th>

        <template slot="body" slot-scope="props">
          <tr>
            <td class="col0 text-left">
              {{ props.realIndex }}
            </td>
            <td class="col1 text-center">
              {{ props.item.id }}
            </td>
            <td class="col1 text-center">
              {{ props.item.title }}
            </td>
            <td class="col1 text-center">
              <img class="logo" :src="props.item.logo">
            </td>
            <td class="col3 text-center">
              <img :src="props.item.thumbnail">
            </td>
            <td class="col0 text-center">
              {{ props.item.order}}
            </td>
            <td class="col5 text-center">
              {{ props.item.is_active ? 'Publish' : 'Draft' }}
            </td>
            <td class="col6 text-center">
              <button type="button" class="btn btn_save_user" @click="goToGameEditPage(props.item.id)">
                <i class="fa fa-pencil"></i>
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
  import Modal from '../../components/Modal';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';
  import { debounce } from 'lodash';

  export default {
    data() {
      return {
        titlePage: this.$t('games.title'),
        searchKey: '',
        limit: 10,
        params: {},
        column: 9,
        rows: [],
      }
    },
    components: {
      Modal
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
        this.$refs.datatable.$emit('DataTable:filter', Object.assign(this.params, {search_key: this.searchKey}));
      },
      getGames(params) {
        this.params = params;
        return rf.getRequest('GameRequest').getGames(params);
      },

      goToGameEditPage(id) {
        return this.$router.push({name: 'Edit Game', params: { id: id}, props:true});
      },

      orderGames () {
        return rf.getRequest('GameRequest').orderGames().then(() => {
          this.showSuccess('Successfull!')
          this.refresh()
        }).catch(err => {
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
        })
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
    .sort-btn {
      background-color: #12575f;
      border-color: #12575f;
      color: white;
      line-height: 16px;
      height: 30px;
      padding: 7px 35px;
      margin-left: 15px;
      min-width: 90px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      text-align: center;
      text-transform: uppercase;
      transition: 0.5s;
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
#games_setting {
  max-width: 1700px;
  table {
    .col0 {
      width: 50px;
    }
    .col1 {
      width: 150px;
    }
    .col2, .col3, .col4 {
      width: 200px;
    }
    .col5, .col6 {
      width: 100px;
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
        &.logo {
          height: 40px;
        }
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
  .modal {
    .content, .action {
      text-align: center;
    }
    .content {
      min-height: 50px;
    }
  }
}
</style>
