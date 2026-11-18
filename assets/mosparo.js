// Laod the Inter font
import '@fontsource/inter/300.css';
import "@fontsource/inter/400.css";
import "@fontsource/inter/500.css";
import "@fontsource/inter/600.css";
import "@fontsource/inter/700.css";

// Load the mosparo scss
import './scss/mosparo.scss';

import '@tabler/core';

import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import spectrum from 'spectrum-colorpicker2';
window.spectrum = spectrum;

import apexcharts from 'apexcharts';
window.ApexCharts = apexcharts;

import { TabulatorFull } from 'tabulator-tables';
window.Tabulator = TabulatorFull;

import papa from 'papaparse';
window.papa = papa;

import QRCode from 'qrcode';
window.QRCode = QRCode;

import './js/ui.js';
import './js/form.js';
import './js/color.js';
import './js/grid-table.js';
import './js/project.js';
import './js/chart.js';
import './js/password.js';
