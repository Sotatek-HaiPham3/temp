export default {
  onGetUsdTransactions (state, data) {
    state.usdTransactions = data;
  },

  onGetKycs (state, data) {
    state.kycs = data;
  },

  onGetNotices (state, data) {
    state.notices = data;
  },

  onMasterdata (state, data) {
    state.masterdata = data;
  }
}
