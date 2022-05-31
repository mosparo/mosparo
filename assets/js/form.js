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

let updateCssVariable = function (variableName, value, type) {
    if (type === 'color' && !value) {
        value = 'transparent';
    } else if (type === 'number') {
        value = value + 'px';
    }

    document.documentElement.style.setProperty(variableName, value);
};

window.collectionToggleRemoveButton = collectionToggleRemoveButton;
window.collectionGetRandomHash = collectionGetRandomHash;
window.updateCssVariable = updateCssVariable;

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

    let updateVariable = function (el, value, type) {
        if (el.data('variable')) {
            let variableName = el.data('variable');

            if (type === 'checkbox') {
                if (el.is(':checked')) {
                    value = el.data('variable-value');
                } else {
                    value = null;
                }
            }

            updateCssVariable(variableName, value, type);
        }
    };
    $('input.colorpicker').wrap('<div class="colorpicker-container"></div>').spectrum({
        preferredFormat: "rgb",
        allowEmpty: true,
        showInitial: true,
        showButtons: false,
        showAlpha: true,
        clickoutFiresChange: true,
        move: function (color) {
            $(this).val(color);

            updateVariable($(this), color, 'color');
        }
    }).on('change', function () {
        $(this).spectrum('set', $(this).val());

        updateVariable($(this), $(this).val(), 'color');
    });

    $('input[data-variable!=""]:not(.colorpicker)').change(function () {
        let type = 'number';
        let val = $(this).val();

        if ($(this).is('input[type="checkbox"]')) {
            type = 'checkbox';
        }

        updateVariable($(this), val, type);
    });

    $('input[data-variable!=""][data-variable]').each(function () {
        let type = 'number';
        let val = $(this).val();

        if ($(this).hasClass('colorpicker')) {
            type = 'color';
        } else if ($(this).is('input[type="checkbox"]')) {
            type = 'checkbox';
        }

        updateVariable($(this), val, type);
    });

    $('.btn-decrease-value').click(function () {
        let inputGroup = $(this).parents('.input-group');
        let input = inputGroup.find('input');

        let val = parseInt(input.val());
        val -= 1;

        if (val < 0) {
            val = 0;
        }

        input.val(val).trigger('change');
    });

    $('.btn-increase-value').click(function () {
        let inputGroup = $(this).parents('.input-group');
        let input = inputGroup.find('input');

        let val = parseInt(input.val());
        val += 1;

        if (val < 0) {
            val = 0;
        }

        input.val(val).trigger('change');
    });
});