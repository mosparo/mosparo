// Laod the Inter font
import '@fontsource/inter/300.css';
import "@fontsource/inter/400.css";
import "@fontsource/inter/500.css";
import "@fontsource/inter/600.css";
import "@fontsource/inter/700.css";

// Load the mosparo scss
import './scss/mosparo.scss';

import '@tabler/core';

const $ = require('jquery');
global.$ = global.jQuery = $;

import 'spectrum-colorpicker2';

const apexCharts = require('apexcharts');
global.ApexCharts = apexCharts;

const dt = require('datatables.net');
const dtR = require('datatables.net-responsive');

const tabulator = require('tabulator-tables');
global.Tabulator = tabulator;

const papa = require('papaparse');
global.papa = papa;

import './js/ui.js';
import './js/form.js';
import './js/color.js';
import './js/table.js';
import './js/project.js';
import './js/tooltip.js';
