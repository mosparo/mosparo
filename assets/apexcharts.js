/**
 * This file is only needed to store the apexcharts.css file into a seperated file.
 * The source code of apexcharts checks for an HTML element with the id "apexcharts-css". If there is no
 * such element, apexcharts includes the styles directly in the JavaScript codes, which invalidates the
 * CSP header.
 */
require('apexcharts/dist/apexcharts.css');