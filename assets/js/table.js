const $ = require('jquery');

$(document).ready(function () {
    $('body').on('init.dt', 'table', function () {
        $(this).parents('.dataTables_wrapper').find('.dataTables_length select').wrap('<div class="form-select-container"></div>');
        $(this).find('th.sorting').each(function () {
            let button = $('<button></button>').addClass('table-sort').text($(this).text());
            $(this).html(button);
        });
    }).on('processing.dt', 'table', function (e, settings, processing) {
        let tableProcessing = $(this).parents('.dataTables_wrapper').find('.processing-container')
        if (processing) {
            tableProcessing.addClass('visible');
        } else {
            tableProcessing.removeClass('visible');
        }
    }).on('order.dt', 'table', function (e, settings, order) {
        $(this).find('.table-sort').removeClass('asc desc');
        if (order.length === 0) {
            return;
        }

        let columnIndex = order[0].col;
        let direction = order[0].dir;

        let table = $(this).DataTable();
        let columnHeader = $(table.column(columnIndex).header());
        let button = columnHeader.find('.table-sort');
        if (button.length === 0) {
            return;
        }

        if (direction === 'desc') {
            button.addClass('desc');
        } else {
            button.addClass('asc');
        }
    });
});