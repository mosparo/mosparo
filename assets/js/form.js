const $ = require('jquery');

let collectionToggleRemoveButton = function (list) {
    if (list.find('li').length > 1) {
        list.find('.remove-item-button').prop('disabled', false);
    } else {
        list.find('.remove-item-button').prop('disabled', true);
    }
};
let collectionGetRandomHash = function () {
    // Source: https://gist.github.com/6174/6062387
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
}

window.collectionToggleRemoveButton = collectionToggleRemoveButton;
window.collectionGetRandomHash = collectionGetRandomHash;

$(document).ready(function () {
    $('.collection-widget.add-allowed .add-item-button').click(function (e) {
        let collectionObj = $(this).parents('.collection-widget');
        let list = collectionObj.find('.collection-list');
        let newWidget = list.attr('data-prototype');

        newWidget = newWidget.replace(/__name__/g, collectionGetRandomHash());

        let newElem = $(list.attr('data-widget-tags'));
        newElem.find('.input-group').append(newWidget);
        newElem.find('.input-group').append($('<button></button>').attr('type', 'button').addClass('btn btn-danger btn-icon-only remove-item-button').html('<i class="ti ti-circle-minus"></i>'));
        newElem.appendTo(list);

        collectionToggleRemoveButton(list);
    });

    $('.collection-widget.add-allowed').each(function () {
        let list = $(this).find('.collection-list');

        if (list.find('li').length === 0) {
            $('.add-item-button').trigger('click');
        }
    });

    $('.collection-widget.remove-allowed').on('click', '.remove-item-button', function (e) {
        let list = $(this).parents('.collection-list');

        if (list.find('li').length > 1) {
            $(this).parents('li').remove();
        }

        collectionToggleRemoveButton(list);
    });

    $('.card-field-switch').on('change', function () {
        let cardBody = $(this).parents('.card-body');
        let fields = cardBody.find('input, textarea, select').not($(this));
        let status = $(this).is(':checked');

        if (status) {
            fields.prop('disabled', false);
        } else {
            fields.prop('disabled', true);
        }
    }).trigger('change');
});