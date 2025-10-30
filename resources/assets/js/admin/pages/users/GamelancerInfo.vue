<template>
  <div class="boxCore" id="gamelancer_information">
    <section class="clearfix">
      
    <div class="filter_container clearfix">
      <span class="search_box">
        <input type="text" placeholder="Search" class="form-control search_input" name="searchKey" @input="search" v-model="searchKey"/>
      </span>
    </div>

    <div class="clearfix"></div>

    <div class="datatable">
        <data-table
          :getData="getData"
          ref="datatable"
          :limit="params.limit"
          :column="8"
          @DataTable:finish="onDatatableFinish"
          @onPageChange="params.page = $event"
          @onLimitChange="params.limit = $event"
          class="scroll">
          <th class="cl0 text-left">No.</th>
          <th class="cl1 text-left" data-sort-field="username">{{ $t('user.username') }}</th>
          <th class="cl2 text-left" data-sort-field="email">{{ $t('user.email') }}</th>
          <th class="cl3 text-left" data-sort-field="sex">{{ $t('gamelancer_page.gender') }}</th>
          <th class="cl4 text-left">{{ $t('gamelancer_page.social_account') }}</th>
          <th class="cl5 text-left">{{ $t('gamelancer_page.status') }}</th>
          <th class="cl6 text-right">View Detail</th>
          <template slot="body" slot-scope="props">
            <tr>
              <td class="cl0 text-left">
                {{ props.realIndex }}
              </td>
              <td class="cl1 text-left">
                {{ props.item.user.username }}
              </td>
              <td class="cl2 text-left">
                {{ props.item.user.email }}
              </td>
              <td class="cl3 text-left" v-if="props.item.user.sex === 0">Female</td>
              <td class="cl3 text-left" v-else-if="props.item.user.sex === 1">Male</td>
              <td class="cl3 text-left" v-else>Non-binary</td>
              <td class="cl4 text-left" :title="props.item.social_link ? props.item.social_link.url : ''">
                <a class="three-dots" href="javascript:void(0)" @click.stop="gotoSocialLink(props.item)">{{ props.item.social_link ? props.item.social_link.url : '' }}</a>
              </td>
              <td class="cl5 text-left capitalize">
                {{ $t(`gamelancer_page.status.${props.item.status}`) }}
              </td>
              <td class="cl6 text-right">
                <button type="button" class="btn btn_save_user" @click="$router.push({name: 'Gamelancer Info Detail', params: {id: props.item.id}})">
                  <i class="fa fa-eye" aria-hidden="true"></i>
                </button>
              </td>
            </tr>
          </template>
        </data-table>
      </div>

      <loading :isLoading="isLoading"/>
    </section>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import { debounce } from 'lodash';

  export default {
    data() {
      return {
        titlePage: this.$t('gamelancer_page.title'),
        searchKey: '',
        params: {},
        isLoading: false
      }
    },
    methods: {
      search: debounce(function () {
        this.params = {
          ...this.params,
          page: 1,
          search_key: this.searchKey
        }

        this.$refs.datatable.filter(this.params)
      }, 400),

      getData(params) {
        this.params = {
          ...params,
          ...this.params
        }

        return rf.getRequest('AdminRequest').getGamelancerForms(this.params);
      },

      onDatatableFinish () {
        delete this.params.sort
        delete this.params.sort_type

        const query = {
          page: this.params.page,
          limit: this.params.limit,
          search_key: this.params.search_key
        }
        this.$router.replace({ name: this.$route.name, query })
      },

      initParams () {
        const query = this.$route.query
        this.searchKey = query.search_key || this.searchKey

        this.params = {
          ...this.params,
          page: query.page || 1,
          limit: parseInt(query.limit) || 10,
          search_key: this.searchKey
        }
      },

      gotoSocialLink(item) {
        if (!item.social_link) {
          return
        }

        let url = item.social_link.url;
        url = url.match(/^https?:/) ? url : '//' + url;
        window.open(url, '_blank');
      }
    },

    created () {
      this.initParams()
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this);
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/variables";

  .cl0 {
    width: 55px;
  }
  .cl1 {
    width: 300px;
  }
  .cl2 {
    width: 450px;
  }
  .cl3 {
    width: 150px;
  }
  .cl4 {
    width: 300px;
  }
  .cl5 {
    width: 150px;
  }
  .cl6 {
    width: 200px;
  }
  .capitalize {
    text-transform: capitalize;
  }
  .text-left, .text-right{
    &:after {
      content: none;
    }
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

  #gamelancer_information {
    max-width: 1200px;
    .filter_container {
      margin: 12px 0px;
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
    table {
      thead {
      }
      td {
        word-break: break-word;
        img {
          height: 100px;
        }
      }
      tbody {
        tr {
          .btn_update_user, .btn_save_user {
            margin: 3px 0;
          }
        }
      }
    }
  }
</style>
