<template>
  <div id="deposit_pages">
    <base-deposit-withdraw-history type="deposit" />
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
        titlePage: 'Deposit',
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