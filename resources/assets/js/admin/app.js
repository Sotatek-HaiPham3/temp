/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
import './common/bootstrap';
import Vue from 'vue';
import VueBroadcast from 'common/VueBroadcast';
import router from './routes';
import VeeValidate from 'vee-validate';
import App from './App.vue';
import './common/Filters.js';
import ClickOutside from 'vue-click-outside';
import store from './store/index.js';
import CKEditor from '@ckeditor/ckeditor5-vue';
import VTooltip from 'v-tooltip';
import Multiselect from 'vue-multiselect';
import DataTable from './components/DataTable';
import Loading from './components/Loading';
import DataList from './components/DataList';
import 'vue-multiselect/dist/vue-multiselect.min.css';
import './../custom_rules.js';

Vue.use( CKEditor );

Vue.use(VueBroadcast);
Vue.use(VeeValidate);
Vue.use(VTooltip);

Vue.component('multiselect', Multiselect);
Vue.component('data-table', DataTable);
Vue.component('loading', Loading);
Vue.component('data-list', DataList);
Vue.directive('click-outside', ClickOutside);

const i18n = window.i18n;
router.beforeEach((to, from, next) => {
  return next();
});

Vue.mixin({
  data () {
    return {
      isSubmitting: false,
    };
  },
  methods: {
    startSubmit () {
      this.isSubmitting = true;
    },

    endSubmit () {
      this.isSubmitting = false;
    },

    getSubmitName (name) {
      return this.isSubmitting ? this.$t('common.processing') : name;
    },

    showError(message) {
      window.Message.error(message, {}, { position: "bottom_left" });
    },

    showSuccess(message) {
      window.Message.success(message, {}, { position: "bottom_left" });
    },
  }
});

window.app = new Vue({
  i18n,
  router,
  store,
  created() {
    this.$store.dispatch('getMasterdata')
  },
  render: h => h(App)
}).$mount('#app');
