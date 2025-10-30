<template>
  <div class="tableContainer" :style="{width: widthTable}">
    <table>
      <thead>
      <tr @click="onSort">
        <slot/>
      </tr>
      </thead>
      <tbody>
      <slot name="first_row"/>
      <slot name="body" v-for="(row, index) in rows" :item="row" :index="index" :realIndex="(page - 1) * limit + index + 1"/>
      <!-- <template v-for="row in emptyRow">
        <tr>
          <template v-for="col in column">
            <td></td>
          </template>
        </tr>
      </template> -->
      <template v-if="this.rows.length === 0">
        <tr class="empty-data text-center"><td :colspan="column">
          <p v-if="fetching">Loading...</p>
          <p v-else>
            <span class="icon-notfound3"></span>
            <span>{{ msgEmptyData || $t('You have no data.') }}</span>
            </p>
            
        </td></tr>
      </template>
      <slot name="end_row"/>
      </tbody>
    </table>
    <template v-if="lastPage > 1 || visiblePagination">
      <pagination ref="pagination"
                  class="text-center"
                  :per-page="perPage"
                  :records="totalRecord"
                  :chunk="chunk"
                  @change-limit="onChangeLimit($event)"
                  @Pagination:page="onPageChange"
                  :pageParent="page"
                  :limit="internalLimit">
      </pagination>
    </template>
  </div>
</template>

<script>
  import Pagination from './Pagination';

  export default {
    components: {
      Pagination
    },
    props: {
      getData: {
        type: Function,
      },
      limit: {
        type: Number,
        default: 10
      },
      column: {
        type: Number,
        default: 0
      },
      chunk: {
        type: Number,
        default: 6
      },
      widthTable: {
        type: String,
        default: '100%'
      },
      msgEmptyData: {
        type: String,
        default: null
      }
    },
    data() {
      return {
        visiblePagination: false,
        internalLimit: 0,
        maxPageWidth: 10,
        totalRecord: 0,
        lastPage: 0,
        page: 1,
        perPage: 10,
        fetching: false,
        rows: [],
        params: {},

        orderBy: null,
        sortedBy: null,
      };
    },
    computed: {
      emptyRow() {
        let emptyRowCount = Math.max(this.internalLimit - _.size(this.rows), 0);
        return Math.min(emptyRowCount, this.internalLimit);
      }
    },
    watch: {
      limit(newValue) {
        this.internalLimit = newValue;
      },
      page(newValue) {
        this.$emit('onPageChange', newValue)
      }
    },
    methods: {
      onChangeLimit(limit) {
        this.visiblePagination = true;
        this.internalLimit = limit;
        this.$emit('onLimitChange', limit)
        this.refresh();
      },

      onPageChange(page) {
        this.page = page;
        this.fetch();
      },

      getTarget(target) {
        let node = target;
        while (node.parentNode.nodeName !== 'TR') {
          node = node.parentNode;
        }
        return node;
      },

      getSortOrder(target) {
        let sortOrder = target.dataset.sortOrder;
        switch (sortOrder) {
          case 'asc':
            sortOrder = '';
            break;
          case 'desc':
            sortOrder = 'asc';
            break;
          default:
            sortOrder = 'desc';
        }
        return sortOrder;
      },

      setSortOrders(target, sortOrder) {
        let iterator = target.parentNode.firstChild;
        while (iterator) {
          iterator.dataset.sortOrder = '';
          iterator = iterator.nextElementSibling;
        }
        target.dataset.sortOrder = sortOrder;
      },

      onSort(event) {
        const target = this.getTarget(event.target);
        const orderBy = target.dataset.sortField;
        if (!orderBy) {
          return
        }
        this.sortedBy = this.getSortOrder(target);
        this.orderBy = this.sortedBy ? orderBy : '';
        Object.assign(this.params, {sort: this.orderBy, sort_type: this.sortedBy});
        this.setSortOrders(target, this.sortedBy);
        this.fetch();
      },

      fetch() {
        const meta = {
          page: this.page,
          limit: this.internalLimit
        };

        this.fetching = true;
        this.getData({...this.params, ...meta}).then((res) => {
          const data = res.data;
          if (!data) {
            return;
          }
          if (!data.data) {
            this.rows = data;
            this.page = parseInt(data.current_page) ? parseInt(data.current_page) : parseInt(res.current_page);
            this.totalRecord = parseInt(data.total) ? parseInt(data.total) : parseInt(res.total) ;
            this.lastPage = parseInt(data.last_page) ? parseInt(data.last_page) :  parseInt(res.last_page);
            this.perPage = parseInt(data.per_page) ? parseInt(data.per_page) : parseInt(res.per_page);
            this.$emit('DataTable:finish');
            return;
          }
          this.page = parseInt(data.current_page);
          this.totalRecord = parseInt(data.total);
          this.lastPage = parseInt(data.last_page);
          this.perPage = parseInt(data.per_page);
          this.rows = data.data;
          this.$emit('DataTable:finish');
        }).then((res) => {
          this.fetching = false;
        });
      },
      refresh() {
        this.page = 1;
        this.params = {};
        this.fetch();
      },

      filter(params) {
        this.page = 1;
        this.params = params;
        this.fetch();
      }
    },
    created() {
      this.internalLimit = this.limit;
      this.fetch();
      this.$on('DataTable:filter', (params) => {
        this.filter(params);
      });
    }
  };
</script>

<style lang="scss" scoped>
  @import '../../../../assets/sass/admin/data-table.scss';
</style>
