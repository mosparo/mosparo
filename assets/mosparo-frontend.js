import './scss/mosparo-frontend.scss';

function mosparo(containerId, url, uuid, publicKey, options)
{
    let _this = this;
    this.containerId = containerId;
    this.url = url;
    this.uuid = uuid;
    this.publicKey = publicKey;
    this.defaultOptions = {
        name: '',
        allowBrowserValidation: true,
        inputFieldSelector: '[name]:not(.mosparo__ignored-field)',
        designMode: false,
        loadCssResource: false,
        cssResourceUrl: '',
        requestSubmitTokenOnInit: true,

        // Callbacks
        onCheckForm: null
    };
    this.options = {...this.defaultOptions, ...options};

    this.id = '';
    this.formElement = null;
    this.containerElement = null;
    this.checkboxElement = null;
    this.checkboxFieldElement = null;
    this.submitTokenElement = null;
    this.validationTokenElement = null;
    this.labelElement = null;
    this.accessibleStatusElement = null;
    this.hpFieldElement = null;

    this.countdownInterval = null;
    this.countdownSeconds = 0;
    this.isLocked = false;
    this.checkedFormData = null;

    this.messages = {
        label: 'I accept that the form entries are checked for spam and stored encrypted for 14 days.',

        accessibilityCheckingData: 'We\'re checking your data. Please wait.',
        accessibilityDataValid: 'Your data are valid. You can submit the form.',

        errorGotNoToken: 'mosparo returned no submit token.',
        errorInternalError: 'An error occurred. Please try again.',
        errorNoSubmitTokenAvailable: 'No submit token available. Validation of this form is not possible.',
        errorSpamDetected: 'Your data got catched by our spam protection.',
        errorLockedOut: 'You are locked out. Please try again after %datetime%',
        errorDelay: 'Your request was delayed. Please wait for %seconds% seconds.',

        hpLeaveEmpty: 'Leave this field blank',
    };

    this.init = function () {
        this.id = this.getRandomHash();
        this.containerElement = document.getElementById(this.containerId);

        if (!this.containerElement) {
            this.debug('Cannot find the mosparo container.');

            return;
        }

        // Load the css resource, if needed
        if (this.options.loadCssResource) {
            this.loadCssResource();
        }

        this.containerElement.classList.add('mosparo__container', 'mosparo__' + this.uuid);

        // Find the form
        let forms = document.querySelectorAll('form');
        let currentElement = this.containerElement.parentNode;
        while (currentElement && !this.isTargetElement(forms, currentElement)) {
            currentElement = currentElement.parentNode;
        }

        if (currentElement === null) {
            this.debug('Cannot find the form container.');

            return;
        }

        this.formElement = currentElement;
        if (this.options.allowBrowserValidation && this.formElement.hasAttribute('novalidate')) {
            this.formElement.removeAttribute('novalidate');
        }

        // Create the row
        let rowElement = document.createElement('div');
        rowElement.classList.add('mosparo__row');
        this.containerElement.appendChild(rowElement);

        // Create the checkbox column
        let checkboxColumnElement = document.createElement('div');
        checkboxColumnElement.classList.add('mosparo__checkbox_column');
        rowElement.appendChild(checkboxColumnElement);

        // Create the real checkbox element
        let fieldId = '_mosparo_checkboxField_' + this.id;
        this.checkboxFieldElement = document.createElement('input');
        this.checkboxFieldElement.setAttribute('type', 'checkbox');
        this.checkboxFieldElement.setAttribute('required', 'required');
        this.checkboxFieldElement.setAttribute('value', '1');
        this.checkboxFieldElement.setAttribute('id', fieldId);
        this.checkboxFieldElement.setAttribute('name', (this.options.name !== '') ? this.options.name : fieldId);
        checkboxColumnElement.appendChild(this.checkboxFieldElement);

        // Create the visual checkbox element
        this.checkboxElement = document.createElement('div');
        this.checkboxElement.classList.add('mosparo__checkbox');
        checkboxColumnElement.appendChild(this.checkboxElement);

        // Create the icons for the checkbox element
        let checkmarkIconElement = document.createElement('div');
        checkmarkIconElement.classList.add('mosparo__icon-checkmark');
        this.checkboxElement.appendChild(checkmarkIconElement);

        let failureIconElement = document.createElement('div');
        failureIconElement.classList.add('mosparo__icon-failure');
        this.checkboxElement.appendChild(failureIconElement);

        let contentColumnElement = document.createElement('div');
        contentColumnElement.classList.add('mosparo__content_column');
        rowElement.appendChild(contentColumnElement);

        this.labelElement = document.createElement('label');
        this.labelElement.classList.add('mosparo__label');
        this.labelElement.setAttribute('for', fieldId);
        contentColumnElement.appendChild(this.labelElement);

        // Create the error message
        this.errorMessageElement = document.createElement('div');
        this.errorMessageElement.classList.add('mosparo__error-message');
        contentColumnElement.appendChild(this.errorMessageElement);

        // Create the accessible status message
        this.accessibleStatusElement = document.createElement('div');
        this.accessibleStatusElement.classList.add('mosparo__accessible-message');
        contentColumnElement.appendChild(this.accessibleStatusElement);

        // Create the submit token field
        this.submitTokenElement = document.createElement('input');
        this.submitTokenElement.setAttribute('name', '_mosparo_submitToken');
        this.submitTokenElement.setAttribute('type', 'hidden');
        this.submitTokenElement.classList.add('mosparo__submit-token');
        this.checkboxElement.appendChild(this.submitTokenElement);

        // Create the validation token field
        this.validationTokenElement = document.createElement('input');
        this.validationTokenElement.setAttribute('name', '_mosparo_validationToken');
        this.validationTokenElement.setAttribute('type', 'hidden');
        this.validationTokenElement.classList.add('mosparo__validation-token');
        this.checkboxElement.appendChild(this.validationTokenElement);

        // Do not set up the event listener in design mode
        if (this.options.designMode) {
            this.containerElement.classList.add('mosparo__design-mode');
            this.checkboxFieldElement.removeAttribute('required');
            this.labelElement.textContent = this.messages.label;
        } else {
            // Set up the event listener
            this.formElement.querySelectorAll(this.options.inputFieldSelector).forEach(function (el) {
                if (el === _this.validationTokenElement) {
                    return;
                }

                el.addEventListener('change', function () {
                    _this.resetState();
                });
            });

            this.formElement.addEventListener('submit', function (ev) {
                if (!_this.verifyCheckedFormData()) {
                    ev.preventDefault();
                    ev.stopImmediatePropagation();

                    _this.formElement.dispatchEvent(new CustomEvent('submit-aborted', { bubbles: true }));

                    _this.resetState();
                }
            });

            this.formElement.addEventListener('reset', function () {
                _this.resetState();
                _this.requestSubmitToken();
            });

            this.checkboxFieldElement.addEventListener('change', function () {
                _this.checkForm();
            });
            this.checkboxFieldElement.addEventListener('focus', function () {
                _this.containerElement.classList.add('mosparo__focus');
            });
            this.checkboxFieldElement.addEventListener('blur', function () {
                _this.containerElement.classList.remove('mosparo__focus');
            });

            this.checkboxElement.addEventListener('click', function () {
                _this.checkForm();
            });

            if (this.options.requestSubmitTokenOnInit) {
                this.requestSubmitToken();
            }
        }
    }

    this.loadCssResource = function () {
        let url = this.options.cssResourceUrl;
        if (url === '') {
            url = this.url + '/resources/' + this.uuid + '.css';
        }

        let link  = document.createElement('link');
        link.rel  = 'stylesheet';
        link.type = 'text/css';
        link.href = url;
        link.media = 'all';

        let head  = document.getElementsByTagName('head')[0];
        head.appendChild(link);
    }

    this.getRandomHash = function () {
        return Math.random().toString().substring(2, 16);
    }

    this.requestSubmitToken = function () {
        this.errorMessageElement.classList.remove('mosparo__error-message-visible');
        this.containerElement.classList.add('mosparo__loading');

        let data = {
            pageTitle: document.title,
            pageUrl: document.location.href
        };

        this.send('/api/v1/frontend/request-submit-token', data, function (response) {
            if (response.submitToken) {
                _this.containerElement.classList.remove('mosparo__loading');
                _this.submitTokenElement.value = response.submitToken;
                _this.updateMessages(response.messages);

                if ('honeypotFieldName' in response && response.honeypotFieldName) {
                    _this.addHoneypotField(response.honeypotFieldName);
                }
            } else if (response.security) {
                _this.processSecurityResponse(response);

                if (response.type === 'delay') {
                    setTimeout(function () { _this.requestSubmitToken() }, (response.forSeconds + 1) * 1000);
                }
            } else {
                _this.containerElement.classList.remove('mosparo__loading');
                _this.containerElement.classList.add('mosparo__invalid');

                _this.showError(_this.messages.errorGotNoToken);
            }
        }, function () {
            _this.containerElement.classList.remove('mosparo__loading');
            _this.containerElement.classList.add('mosparo__invalid');

            _this.showError(_this.messages.errorInternalError);
        });
    }

    this.checkForm = function () {
        if (this.isLocked) {
            return;
        }

        if (this.submitTokenElement.value === '') {
            this.containerElement.classList.add('mosparo__invalid');

            this.showError(this.messages.errorNoSubmitTokenAvailable);
            return;
        }

        this.containerElement.classList.remove('mosparo__invalid');
        this.containerElement.classList.remove('mosparo__checked');
        this.errorMessageElement.classList.remove('mosparo__error-message-visible');

        this.containerElement.classList.add('mosparo__loading');
        this.updateAccessibleStatus(this.messages.accessibilityCheckingData);

        let formData = JSON.stringify(this.getFormData());
        let data = {
            formData: formData,
            submitToken: this.submitTokenElement.value
        };

        this.checkedFormData = formData;

        this.send('/api/v1/frontend/check-form-data', data, function (response) {
            _this.containerElement.classList.remove('mosparo__loading');

            if (response.valid) {
                _this.checkboxFieldElement.checked = true;
                _this.setHpFieldElementDisabled(true);
                _this.containerElement.classList.add('mosparo__checked');
                _this.validationTokenElement.value = response.validationToken;

                _this.updateAccessibleStatus(_this.messages.accessibilityDataValid);
            } else if (response.security) {
                _this.checkboxFieldElement.checked = false;
                _this.setHpFieldElementDisabled(false);
                _this.processSecurityResponse(response);

                if (response.type === 'delay') {
                    setTimeout(function () { _this.requestSubmitToken() }, (response.forSeconds + 1) * 1000);
                }
            } else {
                _this.checkboxFieldElement.checked = false;
                _this.setHpFieldElementDisabled(false);
                _this.containerElement.classList.add('mosparo__invalid');
                _this.validationTokenElement.value = '';

                _this.showError(_this.messages.errorSpamDetected);
            }

            if (_this.options.onCheckForm !== null) {
                _this.options.onCheckForm();
            }
        }, function () {
            _this.checkboxFieldElement.checked = false;
            _this.setHpFieldElementDisabled(false);
            _this.containerElement.classList.remove('mosparo__loading');
            _this.containerElement.classList.add('mosparo__invalid');

            _this.showError(_this.messages.errorInternalError);
        });
    }

    this.verifyCheckedFormData = function () {
        let formData = JSON.stringify(this.getFormData());

        return (this.checkedFormData === formData);
    }

    this.getFormData = function () {
        let fields = [];
        let ignoredFields = [];
        let processedFields = [];
        this.formElement.querySelectorAll(this.options.inputFieldSelector).forEach(function (el) {
            let name = el.getAttribute('name');
            // Ignore mosparo fields
            if (name.indexOf('_mosparo_') === 0) {
                return;
            }

            processedFields.push(name);
            let tagName = el.tagName.toLowerCase();
            let fieldPath = tagName;

            if (fieldPath === 'input' || fieldPath === 'button') {
                let type = el.getAttribute('type');

                if (type === 'submit' || type === 'reset' || type === 'button') {
                    return;
                }

                if (type === 'password' || type === 'file' || type === 'hidden' || type === 'checkbox' || type === 'radio') {
                    if (ignoredFields.indexOf(name) === -1) {
                        ignoredFields.push(name);
                    }

                    return;
                }

                fieldPath += '[' + type + ']'
            }

            fieldPath += '.' + name;

            let value = el.value;
            if (name.indexOf('[]') === name.length - 2) {
                name = name.substring(0, name.length - 2);
                if (value === '') {
                    value = [];
                } else {
                    value = [value];
                }
            }

            if (tagName === 'select' && el.getAttribute('multiple') !== null) {
                value = [];
                el.querySelectorAll('option:checked').forEach(function (el) {
                    value.push(el.value);
                });
            }

            let fieldData = fields.find(function (element, index) {
                if (element.name === name) {
                    return true;
                }
            });
            if (fieldData === undefined) {
                fields.push({
                    name: name,
                    value: value,
                    fieldPath: fieldPath
                });
            } else {
                if (typeof fieldData.value !== 'object') {
                    fieldData.value = [fieldData.value];
                }

                fieldData.value = fieldData.value.concat(value);
            }
        });

        // Add the ignored fields to the list of the ignored fields
        this.formElement.querySelectorAll('[name]').forEach(function (el) {
            let name = el.getAttribute('name');

            // Only add non-mosparo or not processed fields
            if (name.indexOf('_mosparo_') !== 0 && processedFields.indexOf(name) === -1 && ignoredFields.indexOf(name) === -1) {
                ignoredFields.push(name);
            }
        });

        return { fields: fields, ignoredFields: ignoredFields };
    }

    this.resetState = function () {
        this.checkboxFieldElement.removeAttribute('checked');
        this.setHpFieldElementDisabled(false);
        this.containerElement.classList.remove('mosparo__checked');
        this.validationTokenElement.value = '';

        if (!this.isLocked) {
            this.containerElement.classList.remove('mosparo__invalid');
            this.errorMessageElement.classList.remove('mosparo__error-message-visible');
        }

        _this.formElement.dispatchEvent(new CustomEvent('state-reset'));
    }

    this.send = function (endpoint, data, callbackSuccess, callbackError) {
        let url = this.url + endpoint;
        data.publicKey = this.publicKey;

        let request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    let response = JSON.parse(this.responseText);
                    callbackSuccess(response);
                } else {
                    callbackError(this.responseText);
                }
            }
        }

        request.open('POST', url, true);
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        request.send(this.stringifyData(data));
    }

    this.addHoneypotField = function (fieldName) {
        if (this.hpFieldElement !== null) {
            return;
        }

        this.hpFieldElement = document.createElement('input');
        this.hpFieldElement.setAttribute('type', 'text');
        this.hpFieldElement.setAttribute('name', fieldName);
        this.hpFieldElement.setAttribute('style', 'position: absolute !important; left: -1000px !important; top: -1000px !important;');
        this.hpFieldElement.setAttribute('autocomplete', 'one-time-code');
        this.hpFieldElement.setAttribute('tabindex', '-1');
        this.hpFieldElement.setAttribute('title', this.messages.hpLeaveEmpty);
        this.formElement.appendChild(this.hpFieldElement);

        this.hpFieldElement.addEventListener('change', function () {
            _this.resetState();
        });
    }

    this.setHpFieldElementDisabled = function (disabled) {
        if (this.hpFieldElement !== null) {
            this.hpFieldElement.disabled = disabled;
        }
    }

    this.processSecurityResponse = function (response) {
        this.isLocked = true;

        if ('messages' in response) {
            this.updateMessages(response.messages);
        }

        if (response.type === 'lockout') {
            this.containerElement.classList.remove('mosparo__loading');
            this.containerElement.classList.add('mosparo__invalid');

            let date = new Date(response.until);
            this.showError(this.messages.errorLockedOut.replace('%datetime%', date.toLocaleString()));
        } else if (response.type === 'delay') {
            let timeVal = '<span>%val%</span>'.replace('%val%', response.forSeconds);
            this.showError(this.messages.errorDelay.replace('%seconds%', timeVal), false, true);
            this.updateAccessibleStatus(this.messages.errorDelay.replace('%seconds%', response.forSeconds));

            this.countdownSeconds = response.forSeconds;
            this.countdownInterval = setInterval(function () { _this.countDown() }, 1000);
        }
    }

    this.countDown = function () {
        if (this.countdownSeconds === 0) {
            this.isLocked = false;

            this.containerElement.classList.remove('mosparo__loading');
            this.errorMessageElement.classList.remove('mosparo__error-message-visible');

            clearInterval(this.countdownInterval);
            return;
        }

        this.countdownSeconds--;

        this.errorMessageElement.querySelectorAll('span')[0].textContent = this.countdownSeconds;
    }

    this.updateMessages = function (messages) {
        this.messages = messages;

        this.labelElement.textContent = this.messages.label;
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
            if (availableElements[i] === targetElement) {
                return true;
            }
        }

        return false;
    }

    this.showError = function (error, withAccessible, withHtml)
    {
        this.errorMessageElement.classList.add('mosparo__error-message-visible');

        if (withHtml) {
            this.errorMessageElement.innerHTML = error;
        } else {
            this.errorMessageElement.textContent = error;

            if (withAccessible === undefined || withAccessible) {
                this.updateAccessibleStatus(error);
            }
        }
    }

    this.updateAccessibleStatus = function (status) {
        this.accessibleStatusElement.setAttribute('role', 'alert');
        this.accessibleStatusElement.textContent = status;
    }

    this.debug = function (message) {
        if (console.log) {
            console.log(message);
        }
    }

    // Initialize the mosparo checkboxes
    this.init();

    return this;
}

global.mosparo = mosparo;