import axios from 'axios';
import $ from 'jquery';
window.$ = window.jQuery = $;

import select2 from 'select2';
select2();

import 'select2/dist/css/select2.min.css';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

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

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
