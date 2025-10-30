import rf from '../../admin/lib/RequestFactory';

export const getUsdTransactions = ({ commit }, payload) => {
  return new Promise(resolve => {
    rf.getRequest('AdminRequest').getUsdTransaction(payload).then(res => {
      commit('onGetUsdTransactions', res.data);
      resolve(res);
    })
  })
};

export const getKycs = ({ commit }, payload) => {
  return new Promise(resolve => {
    rf.getRequest('AdminRequest').getUserKyc(payload).then(res => {
      commit('onGetKycs', res.data);
      resolve(res);
    })
  })
};

export const getMasterdata = ({ commit }) => {
  return new Promise(resolve => {
    rf.getRequest('AdminRequest').getMasterdata().then(res => {
      commit('onMasterdata', res.data);
      resolve(res);
    })
  })
};
