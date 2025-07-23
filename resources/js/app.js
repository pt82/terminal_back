require('./bootstrap');
window.Vue = require('vue');


// import VueAdsTableTree from 'vue-ads-table-tree';
// import 'vue-ads-table-tree/dist/vue-ads-table-tree.css';
// Vue.component('vue-ads-table-tree', VueAdsTableTree);
//
// import { BootstrapVue, IconsPlugin } from 'bootstrap-vue'
//
// // Import Bootstrap an BootstrapVue CSS files (order is important)
// import 'bootstrap/dist/css/bootstrap.css'
// import 'bootstrap-vue/dist/bootstrap-vue.css'
//
// // Make BootstrapVue available throughout your project
// Vue.use(BootstrapVue)
// // Optionally install the BootstrapVue icon components plugin
// Vue.use(IconsPlugin)
//
// import Datetime from 'vue-datetime'
// import 'vue-datetime/dist/vue-datetime.css'
// Vue.component('datetime', Datetime);
// Vue.use (Datetime)
//
// import VueMomentLib from 'vue-moment-lib';
//
//
// Vue.use(VueMomentLib)


// import '../../node_modules/@fortawesome/fontawesome-free/css/all.css';

import App from './components/App'

const app = new Vue({
    el: '#app',
    render: h => h(App)
});
