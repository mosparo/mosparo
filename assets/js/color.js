const $ = require('jquery');
const { normal } = require('color-blend');
const tinycolor = require("tinycolor2");

const calculateTextScore = function (backgroundColor, foregroundColor) {
    return Math.round(tinycolor.readability(backgroundColor, foregroundColor) * 100) / 100;
};

const getSpectrumValue = function (el, format)
{
    if (!el.val()) {
        return false;
    }

    let spec = el.spectrum('get');

    if (format === 'hex') {
        return spec.toHex();
    }

    return spec.toRgb();
}

const getContrastValueColor = function (groupEl, key, format)
{
    let el = groupEl.find('input[data-contrast-value="' + key + '"]');
    return getSpectrumValue(el, format);
}

const updateContrastRatioValues = function (el, score, aaMin, aaaMin)
{
    el.find('.contrast-ratio-value').text(score.toFixed(2));

    if (score >= aaMin) {
        el
            .find('.contrast-ratio-aa')
            .removeClass('text-danger')
            .addClass('text-success')
            .find('.ti')
                .removeClass('ti-x')
                .addClass('ti-check')
                .end()
            .find('.cr-min-value')
                .text(aaMin.toFixed(1));
    } else {
        el
            .find('.contrast-ratio-aa')
            .removeClass('text-success')
            .addClass('text-danger')
            .find('.ti')
                .removeClass('ti-check')
                .addClass('ti-x')
                .end()
            .find('.cr-min-value')
                .text(aaMin.toFixed(1));
    }

    if (score >= aaaMin) {
        el
            .find('.contrast-ratio-aaa')
            .removeClass('text-danger')
            .addClass('text-success')
            .find('.ti')
                .removeClass('ti-x')
                .addClass('ti-check')
                .end()
            .find('.cr-min-value')
                .text(aaaMin.toFixed(1));
    } else {
        el
            .find('.contrast-ratio-aaa')
            .removeClass('text-success')
            .addClass('text-danger')
            .find('.ti')
                .removeClass('ti-check')
                .addClass('ti-x')
                .end()
            .find('.cr-min-value')
                .text(aaaMin.toFixed(1));
    }
};

const recalculateContrastRatioForGroup = function (groupEl)
{
    let aaMin = 4.5;
    let aaaMin = 7.0;
    if ($('input[name="design_settings_form[boxSize]"]:checked').val() === 'large') {
        aaMin = 3.0;
        aaaMin = 4.5;
    }

    let backgroundColor = getContrastValueColor(groupEl, 'background', 'rgb');
    let bodyBackgroundColor = getSpectrumValue($('#page_body_backgroundColor'), 'rgb');

    if (bodyBackgroundColor === false) {
        bodyBackgroundColor = {r: 255, g: 255, b: 255, a: 1};
    }

    if (backgroundColor === false) {
        backgroundColor = bodyBackgroundColor;
    } else if (backgroundColor.a < 1) {
        backgroundColor = normal(bodyBackgroundColor, backgroundColor);
    }

    backgroundColor = tinycolor(backgroundColor).toHex();

    // Define the text contrast ratio
    let textColor = getContrastValueColor(groupEl, 'text', 'hex');
    let scoreText = calculateTextScore(backgroundColor, textColor);

    updateContrastRatioValues(groupEl.find('tr.cr-text'), scoreText, aaMin, aaaMin);

    // Define the error text contrast ratio
    let errorTextColor = null;
    let scoreErrorText = null;
    if (groupEl.find('input[data-contrast-value="error-text"]').length > 0) {
        errorTextColor = getContrastValueColor(groupEl, 'error-text', 'hex');
        scoreErrorText = calculateTextScore(backgroundColor, errorTextColor);

        updateContrastRatioValues(groupEl.find('tr.cr-text-error'), scoreErrorText, aaMin, aaaMin);
    }

    // Update the button
    let generalScore = scoreText;
    if (scoreText > scoreErrorText && scoreErrorText !== null) {
        generalScore = scoreErrorText;
    }

    updateContrastRatioValues(groupEl.find('.accordion-button'), generalScore, aaMin, aaaMin);
};

const recalculateContrastRatioForAllGroups = function ()
{
    $('.contrast-ratio-group').each(function () {
        recalculateContrastRatioForGroup($(this));
    });
};

const defineContrastValue = function (col) {
    let val = col / 255.0;

    if (val <= 0.04045) {
        val = val / 12.92;
    } else {
        val = Math.pow((val + 0.055) / 1.055, 2.4);
    }

    return val;
};

const determineAdjustmentMode = function (backgroundColor) {
    let col = tinycolor(backgroundColor).toRgb();

    let red = defineContrastValue(col.r);
    let green = defineContrastValue(col.g);
    let blue = defineContrastValue(col.b);

    let contrastRatio = (0.2126 * red) + (0.7152 * green) + (0.0722 * blue);
    if (contrastRatio < 0.179) {
        return 'dark';
    }

    return 'light';
};

const searchColorWithBestContrast = function (backgroundColor, targetColor, mode, doNotCheckForAlternative) {
    if (typeof mode === 'undefined' || !mode) {
        mode = determineAdjustmentMode(backgroundColor);
    }

    let adjustedColor = tinycolor(targetColor.toString());
    let textScore = 0;
    let tries = 30;
    while (textScore < 7) {
        textScore = calculateTextScore(backgroundColor, adjustedColor.toHexString());

        if (textScore >= 7) {
            return adjustedColor;
        }

        if (tries === 0) {
            break;
        }

        if (mode === 'light') {
            adjustedColor.darken(1);
        } else {
            adjustedColor.lighten(1);
        }

        tries--;
    }

    if (typeof doNotCheckForAlternative === 'undefined' || !doNotCheckForAlternative) {
        let alternativeColor = searchColorWithBestContrast(backgroundColor, tinycolor(targetColor.toString()), (mode === 'light') ? 'dark' : 'light', true);
        let alternativeTextScore = calculateTextScore(backgroundColor, alternativeColor.toHexString());

        if (alternativeTextScore > textScore) {
            return alternativeColor;
        }
    }

    return adjustedColor;
};

$(document).ready(function () {
    $('input.colorpicker').on('color-change', function () {
        if ($(this).is('#page_body_backgroundColor')) {
            recalculateContrastRatioForAllGroups();
        } else if ($(this).parents('.contrast-ratio-group').length > 0) {
            recalculateContrastRatioForGroup($(this).parents('.contrast-ratio-group'));
        }
    });

    $('input[name="design_settings_form[boxSize]"]').change(function () {
        recalculateContrastRatioForAllGroups();
    });

    recalculateContrastRatioForAllGroups();
});

window.searchColorWithBestContrast = searchColorWithBestContrast;