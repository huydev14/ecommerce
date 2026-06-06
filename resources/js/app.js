import './bootstrap';
import * as bootstrap from 'bootstrap';
import './adminlte-widgets';
import './helpers';
import './toast';

import Alpine from 'alpinejs';
import DataTable from 'datatables.net-bs5';
import Lang from 'lang.js';
import messages from './messages.js';

// Global Objects
window.bootstrap = bootstrap;
window.DataTable = DataTable;
window.Lang = new Lang();
window.Lang.setMessages(messages);

window.Alpine = Alpine;
Alpine.start();
