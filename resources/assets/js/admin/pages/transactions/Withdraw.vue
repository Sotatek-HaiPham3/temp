<template>
  <div id="withdraw_pages">
    <base-deposit-withdraw-history type="withdraw" />
  </div>
</template>

<script>
  import BaseDepositWithdrawHistory from './BaseDepositWithdrawHistory';

  export default {
    components: {
      BaseDepositWithdrawHistory,
    },
    data() {
      return {
        titlePage: 'Withdrawals',
      }
    },
    created() {
      this.$emit('EVENT_PAGE_CHANGE', this);
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/variables";
</style>