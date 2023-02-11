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
    $('.collection-widget.add-allowed .add-item-button').click(function () {
        let collectionObj = $(this).parents('.collection-widget');
        let list = collectionObj.find('.collection-list');
        let newWidget = list.attr('data-prototype');

        newWidget = newWidget.replace(/__name__/g, collectionGetRandomHash());

        let newElem = $(list.attr('data-widget-tags'));
        let containerEl = newElem;
        if (newElem.find('.input-group').length > 0) {
            containerEl = newElem.find('.input-group');
        }
        containerEl.append(newWidget);
        containerEl.append($('<button></button>').attr('type', 'button').addClass('btn btn-danger btn-icon-only remove-item-button').html('<i class="ti ti-circle-minus"></i>'));
        newElem.appendTo(list);

        collectionToggleRemoveButton(list);
    });

    $('.collection-widget.add-allowed').each(function () {
        let list = $(this).find('.collection-list');

        if (list.find('li').length === 0) {
            $('.add-item-button').trigger('click');
        }
    });

    $('.collection-widget.remove-allowed').on('click', '.remove-item-button', function () {
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
                if (el.is(':checked') && !el.prop('readonly')) {
                    value = el.data('variable-value');
                } else {
                    value = el.data('disabled-variable-value');
                }
            }

            updateCssVariable(variableName, value, type);
        }
    };
    $('input.colorpicker').wrap('<div class="colorpicker-container"></div>').each(function () {
        let showAlpha = $(this).data('colorpicker-allow-alpha-value');
        if (showAlpha == null) {
            showAlpha = true;
        }

        $(this).spectrum({
            preferredFormat: "rgb",
            allowEmpty: true,
            showInitial: true,
            showButtons: false,
            showAlpha: showAlpha,
            clickoutFiresChange: true,
            move: function (color) {
                $(this).val(color);

                updateVariable($(this), color, 'color');
                $(this).trigger('color-change');
            }
        });
    }).on('change', function () {
        $(this).spectrum('set', $(this).val());

        updateVariable($(this), $(this).val(), 'color');
        $(this).trigger('color-change');
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

    $('.btn-copy-input-value').click(function () {
        let button = $(this);
        let inputGroup = $(this).parents('.input-group');
        if (inputGroup.length === 0) {
            return;
        }

        button.removeClass('text-success text-danger').find('i').addClass('ti-copy').removeClass('ti-clipboard-check ti-clipboard-x');

        let inputField = inputGroup.find('input');

        navigator.clipboard.writeText(inputField.val()).then(function () {
            button.addClass('text-success').find('i').removeClass('ti-copy').addClass('ti-clipboard-check');
        }, function () {
            button.addClass('text-success').find('i').removeClass('ti-copy').addClass('ti-clipboard-x');
        });
    });

    var changeTimeout = null;
    var changeDirection = '';
    var changeInputField = null;
    var changeIntervalTime = 500;
    var changeValueCallback = function () {
        clearTimeout(changeTimeout);
        changeTimeout = null;

        if (changeDirection === '' || changeInputField === null) {
            return;
        }

        let val = parseInt(changeInputField.val());

        let max = 0;
        if (changeInputField.attr('max')) {
            max = parseInt(changeInputField.attr('max'));
        }

        if (changeDirection === '-') {
            val -= 1;
        } else if (changeDirection === '+') {
            val += 1;
        }

        if (val < 0) {
            val = 0;
        }

        if (max > 0 && val > max) {
            val = max;
        }

        changeInputField.val(val).trigger('change');

        changeIntervalTime = changeIntervalTime * 0.85;
        if (changeIntervalTime < 50) {
            changeIntervalTime = 50;
        }

        changeTimeout = setTimeout(changeValueCallback, changeIntervalTime);
    }

    $('.btn-decrease-value, .btn-increase-value').on('mousedown mouseup', function (ev) {
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
            clearTimeout(changeTimeout);
            changeTimeout = null;

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
            changeIntervalTime = 500;
            changeValueCallback();
        } else if (ev.type === 'keyup') {
            changeDirection = '';
            changeInputField = null;
        }

        ev.preventDefault();
        return false;
    }).attr("autocomplete", "off");
});