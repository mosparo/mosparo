import { Tooltip } from 'bootstrap';

let initializeTooltips = function () {
    /**
     * This is the original code from Tabler to initialize the tooltips.
     *
     * Source: https://github.com/tabler/tabler/blob/59b6f73a06d61b0088d61caccc06c5550b0b3f0c/core/js/src/tooltip.js
     * License: MIT
     *
     * Copyright (c) 2018-2025 The Tabler Authors
     */
    let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        let options = {
            delay: {show: 50, hide: 50},
            html: tooltipTriggerEl.getAttribute("data-bs-html") === "true" ?? false,
            placement: tooltipTriggerEl.getAttribute('data-bs-placement') ?? 'auto'
        };
        return new Tooltip(tooltipTriggerEl, options);
    });
};

global.initializeTooltips = initializeTooltips;