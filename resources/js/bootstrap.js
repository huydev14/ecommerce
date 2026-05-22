import axios from 'axios';
import $ from 'jquery';
window.$ = window.jQuery = $;

import select2 from 'select2';
select2();

import 'select2/dist/css/select2.min.css';

window.$ = window.jQuery = $;

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

window.$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': csrfToken,
    },
});
