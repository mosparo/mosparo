import './scss/mosparo-frontend.scss';

/*
const $ = require('jquery');

let displayMosparoBox = function (el, form, url, publicKey)
{
    let obj = $(el);
    let formObj = $(form);

    obj.addClass('mosparo__container');

    let square = $('<div></div>').addClass('mosparo__checkbox');
    obj.append(square);

    let input = $('<input />').attr('type', 'text').attr('name', '__mosparo_token').prop('required', true);
    obj.append(input);

    let resBox = $('<div></div>').css('border', '1px solid blue').css('padding', '10px');
    obj.append(resBox);

    $('#' + formObj.attr('id') + ' input, #' + formObj.attr('id') + ' textarea, #' + formObj.attr('id') + ' select').change(function (ev) {
        if ($(this).attr('name') == '__mosparo_token') {
            return;
        }

        input.val('');
    });

    square.click(function () {
        $.post(url + '/api/frontend/check', { publicKey: publicKey, data: formObj.serializeArray() }, function (response) {
            resBox.html(JSON.stringify(response));
        }, 'json');
    });
}
*/

function mosparo(containerId, url, publicKey, options)
{
    var _this = this;
    this.containerId = containerId;
    this.url = url;
    this.publicKey = publicKey;
    this.defaultOptions = {

    };
    this.options = {...this.defaultOptions, ...options};

    this.formElement = null;
    this.containerElement = null;
    this.checkboxElement = null;
    this.submitTokenElement = null;
    this.validationTokenElement = null;
    this.labelElement = null;

    this.init = function () {
        this.containerElement = document.getElementById(this.containerId);

        if (!this.containerElement) {
            this.debug('Cannot find the mosparo container.');

            return;
        }

        this.containerElement.classList.add('mosparo__container');

        // Find the form
        let forms = document.querySelectorAll('form');
        let currentElement = this.containerElement.parentNode;
        while (currentElement && !this.isTargetElement(forms, currentElement)) {
            currentElement = currentElement.parentNode;
        }

        this.formElement = currentElement;

        // Create the row
        let rowElement = document.createElement('div');
        rowElement.classList.add('mosparo__row');
        this.containerElement.appendChild(rowElement);

        // Create the checkbox
        this.checkboxElement = document.createElement('div');
        this.checkboxElement.classList.add('mosparo__checkbox');
        rowElement.appendChild(this.checkboxElement);

        this.labelElement = document.createElement('div');
        this.labelElement.classList.add('mosparo__label');
        this.labelElement.textContent = 'I agree that my data will be checked for spam. I accept that my data will be stored for 14 days.';
        rowElement.appendChild(this.labelElement);

        // Create the error message
        this.errorMessageElement = document.createElement('div');
        this.errorMessageElement.classList.add('mosparo__error-message');
        this.containerElement.appendChild(this.errorMessageElement);

        // Create the submit token field
        this.submitTokenElement = document.createElement('input');
        this.submitTokenElement.setAttribute('name', '_mosparo_submitToken');
        this.submitTokenElement.setAttribute('type', 'text');
        this.submitTokenElement.setAttribute('required', true);
        this.submitTokenElement.classList.add('mosparo__submit-token');
        this.checkboxElement.appendChild(this.submitTokenElement);

        // Create the validation token field
        this.validationTokenElement = document.createElement('input');
        this.validationTokenElement.setAttribute('name', '_mosparo_validationToken');
        this.validationTokenElement.setAttribute('type', 'text');
        this.validationTokenElement.setAttribute('required', true);
        this.validationTokenElement.classList.add('mosparo__validation-token');
        this.checkboxElement.appendChild(this.validationTokenElement);

        // Set up the event listener
        this.formElement.querySelectorAll('input, textarea, select').forEach(function (el, index) {
            if (el === _this.validationTokenElement) {
                return;
            }

            el.addEventListener('change', function () {
                _this.resetState();
            });
        });

        this.checkboxElement.addEventListener('click', function () {
            _this.checkForm();
        });

        this.requestSubmitToken();
    }

    this.requestSubmitToken = function () {
        this.checkboxElement.classList.add('mosparo__loading');

        this.send('/api/frontend/request-submit-token', {}, function (response) {
            _this.checkboxElement.classList.remove('mosparo__loading');
            _this.submitTokenElement.value = response.submitToken;
        }, function () {
            _this.checkboxElement.classList.remove('mosparo__loading');
            _this.checkboxElement.classList.add('mosparo__invalid');

            this.errorMessageElement.classList.add('mosparo__error-message-visible');
            this.errorMessageElement.textContent = 'mosparo returned no submit token.';
        });
    }

    this.checkForm = function () {
        if (this.submitTokenElement.value == '') {
            this.checkboxElement.classList.add('mosparo__invalid');

            this.errorMessageElement.classList.add('mosparo__error-message-visible');
            this.errorMessageElement.textContent = 'No submit token available. Validation of this form is not possible.';
            return;
        }

        this.checkboxElement.classList.remove('mosparo__invalid');
        this.checkboxElement.classList.remove('mosparo__checked');
        this.errorMessageElement.classList.remove('mosparo__error-message-visible');

        this.checkboxElement.classList.add('mosparo__loading');

        let data = {
            formData: JSON.stringify(this.getFormData()),
            _mosparo_submitToken: this.submitTokenElement.value
        };

        this.send('/api/frontend/check-form-data', data, function (response) {
            _this.checkboxElement.classList.remove('mosparo__loading');

            if (response.valid) {
                _this.checkboxElement.classList.add('mosparo__checked');
                _this.validationTokenElement.value = response.validationToken;
            } else {
                _this.checkboxElement.classList.add('mosparo__invalid');
                _this.validationTokenElement.value = '';

                _this.errorMessageElement.classList.add('mosparo__error-message-visible');
                _this.errorMessageElement.textContent = 'Your data got catched by our spam protection.';
            }

        }, function () {
            _this.checkboxElement.classList.remove('mosparo__loading');
            _this.checkboxElement.classList.add('mosparo__invalid');

            _this.errorMessageElement.classList.add('mosparo__error-message-visible');
            _this.errorMessageElement.textContent = 'Your data are not valid.';
        });
    }

    this.getFormData = function () {
        let formData = [];
        this.formElement.querySelectorAll('input, textarea, select').forEach(function (el, index) {
            let name = el.getAttribute('name');
            if (name === '_mosparo_submitToken' || name === '_mosparo_validationToken') {
                return;
            }

            let fieldPath = el.tagName.toLowerCase();

            if (fieldPath === 'input') {
                fieldPath += '[' + el.getAttribute('type') + ']'
            }

            fieldPath += '.' + name;

            formData.push({
                name: name,
                value: el.value,
                fieldPath: fieldPath
            });
        });

        return formData;
    }

    this.resetState = function () {
        this.checkboxElement.classList.remove('mosparo__checked');
        this.checkboxElement.classList.remove('mosparo__invalid');
        this.errorMessageElement.classList.remove('mosparo__error-message-visible');
        this.validationTokenElement.value = '';
    }

    this.send = function (endpoint, data, callbackSuccess, callbackError) {
        let url = this.url + endpoint;
        data._mosparo_publicKey = this.publicKey;

        let request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.responseText);
                callbackSuccess(response);
            } else if (this.readyState === 4) {
                callbackError(this.responseText);
            }
        }

        request.open('POST', url, true);
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        request.send(this.stringifyData(data));
    }

    this.stringifyData = function (data) {
        let pairs = [];
        for (let name in data) {
            pairs.push(encodeURIComponent(name) + '=' + encodeURIComponent(data[name]));
        }

        return pairs.join('&');
    }

    this.isTargetElement = function (availableElements, targetElement) {
        for (let i = 0, len = availableElements.length; i < len; i++) {
            if (availableElements[i] == targetElement) {
                return true;
            }
        }

        return false;
    }

    this.debug = function (message) {
        if (console.log) {
            console.log(message);
        }
    }

    // Initialize the mosparo checkboxes
    this.init();
}

global.mosparo = mosparo;