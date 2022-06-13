const $ = require('jquery');

let collectionToggleRemoveButton = function (list) {
    if (list.find('li').length > 1) {
        list.find('.remove-item-button').prop('disabled', false);
    } else {
        list.find('.remove-item-button').prop('disabled', true);
    }
};

let collectionGetRandomHash = function () {
    return Math.random().toString().substring(2, 16);
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
                    value = el.data('disabled-variable-value');
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

    var changeDirection = '';
    var changeInputField = null;
    var changeIntervalTime = 500;
    var changeValueCallback = function ()
    {
        console.log(changeIntervalTime);
        if (changeDirection === '' || changeInputField === null) {
            return;
        }

        let val = parseInt(changeInputField.val());

        if (changeDirection === '-') {
            val -= 1;
        } else if (changeDirection === '+') {
            val += 1;
        }

        if (val < 0) {
            val = 0;
        }

        changeInputField.val(val).trigger('change');

        changeIntervalTime = changeIntervalTime * 0.85;
        if (changeIntervalTime < 50) {
            changeIntervalTime = 50;
        }

        setTimeout(changeValueCallback, changeIntervalTime);
    }

    $('.btn-decrease-value, .btn-increase-value').click(function () {
        let inputGroup = $(this).parents('.input-group');
        let input = inputGroup.find('input');

        let val = parseInt(input.val());

        if ($(this).hasClass('btn-decrease-value')) {
            val -= 1;
        } else if ($(this).hasClass('btn-increase-value')) {
            val += 1;
        }

        if (val < 0) {
            val = 0;
        }

        input.val(val).trigger('change');
    }).on('mousedown mouseup', function (ev) {
        if (ev.type === 'mousedown') {
            if (changeDirection !== '') {
                return;
            }

            if ($(this).hasClass('btn-decrease-value')) {
                changeDirection = '-';
            } else if ($(this).hasClass('btn-increase-value')) {
                changeDirection = '+';
            }

            changeInputField = $(this).parents('.input-group').find('input');
            changeIntervalTime = 400;
            changeValueCallback();
        } else if (ev.type === 'mouseup') {
            changeDirection = '';
            changeInputField = null;

            ev.preventDefault();
            return false;
        }
    });

    $('.btn-decrease-value').parents('.input-group').find('input').on('keydown keyup', function (ev) {
        if (ev.keyCode !== 37 && ev.keyCode !== 38 && ev.keyCode !== 39 && ev.keyCode !== 40) {
            return;
        }

        if (ev.type === 'keydown') {
            if (changeDirection !== '') {
                return;
            }

            changeDirection = '';
            if (ev.keyCode === 37 || ev.keyCode === 40) {
                changeDirection = '-';
            } else if (ev.keyCode === 38 || ev.keyCode === 39) {
                changeDirection = '+';
            }

            changeInputField = $(this);
            changeIntervalTime = 400;
            changeValueCallback();
        } else if (ev.type === 'keyup') {
            changeDirection = '';
            changeInputField = null;
        }

        ev.preventDefault();
        return false;
    }).attr("autocomplete", "off");
});