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
      <slot name="body" v-for="(row, index) in rowsDisplay" :item="row" :index="index" :realIndex="index + 1"/>
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
  </div>
</template>

<script>
const DEFAULT_DISPLAY = 20

export default {
  props: {
    getData: {
      type: Function,
    },

    column: {
      type: Number,
      default: 0
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
      fetching: false,
      recordsDisplay: DEFAULT_DISPLAY,
      rowsDisplay: [],
      rows: [],
      params: {},
      orderBy: null,
      sortedBy: null,
    };
  },

  watch: {
    recordsDisplay (newValue) {
      return this.computeDisplay();
    },

    rows: {
      deep: true,
      handler(val) {
        if(!_.size(this.rows)) {
          this.rowsDisplay = []
          return;
        }
        return this.computeDisplay()
      }
    }
  },

  methods: {
    computeDisplay () {
      this.recordsDisplay = DEFAULT_DISPLAY > this.recordsDisplay ? DEFAULT_DISPLAY : this.recordsDisplay
      if(this.recordsDisplay > _.size(this.rows)) {
        this.recordsDisplay = _.size(this.rows);
      }
      this.rowsDisplay = this.rows.slice(0, this.recordsDisplay);
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
      this.fetching = true;
      this.getData({...this.params}).then((res) => {
        const data = res.data;
        if (!data) {
          return;
        }
        if (!data.data) {
          this.rows = data;
          this.$emit('DataTable:finish');
          return;
        }
        this.rows = data.data;
        this.$emit('DataTable:finish');
      }).then((res) => {
        this.fetching = false;
      }).finally(() => {
        this.computeDisplay();
      });
    },

    refresh() {
      this.fetch();
    },

    filter(params) {
      this.page = 1;
      this.params = params;
      this.fetch();
    }
  },
  created() {
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
