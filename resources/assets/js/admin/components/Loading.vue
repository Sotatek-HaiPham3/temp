<template>
  <div class="loading" v-if="isLoading">
    <div class="loading-content">
      <i class="fa fa-spinner fa-spin"></i>
    </div>
  </div>
</template>
<script>
  export default {
    props: {
      isLoading: {type: Boolean, default: false}
    }
  }
</script>
<style lang="scss" scoped>
  .loading {
    width: 100%;
    height: 100%;
    top: 0px;
    left: 0px;
    position: fixed;
    display: block;
    opacity: 0.7;
    background-color: #fff;
    z-index: 9999;
    text-align: center;

    .loading-content {
      position: absolute;
      top: 50%;
      left: 50%;
      text-align: center;
      z-index: 100;
      i {
        font-size: 40px;
      }
    }
  }
</style>
