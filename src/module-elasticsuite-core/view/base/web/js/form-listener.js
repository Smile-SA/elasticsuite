define(["jquery"], function ($) {

    var FormListener = function (formId, formChangedEvent, listenFormElements) {
        this.form               = $('#' + formId);
        this.formChangedEvent   = formChangedEvent;
        this.listenFormElements = listenFormElements;
    };

    FormListener.prototype.startListener = function () {
        if (this.timer === undefined || this.timer === null) {
            this.hash  = this.getFormHash();
            this.timer = setInterval(this.detectChanges.bind(this), 1000);
        }
    };

    FormListener.prototype.stopListener = function () {
        if (this.timer === undefined || this.timer === null) {
            clearInterval(this.timer);
            delete this.timer; 
        }
    };

    FormListener.prototype.getFormHash = function () {
        var serializedElements = this.form.serializeArray();
        
        var filterElementFunction = this.getFilterFunction();
        if (filterElementFunction) {
            serializedElements = serializedElements.filter(filterElementFunction);
        }
        
        return serializedElements.map(function (formElement) { return formElement.name + formElement.value; }).join('|');
    }

    FormListener.prototype.getFilterFunction = function () {
        var filterFunction = null;

        var isElementTargeted = function (elementName, targetName) {
            var isElementTargeted = elementName === targetName;
            if (targetName.match(/.*\[.*\]$/)) {
                isElementTargeted = elementName.startsWith(targetName);
            }
            return isElementTargeted;
        }

        if (Object.prototype.toString.call(this.listenFormElements) === '[object Array]') {
            filterFunction = function(element) {
                var addElement = false;
                for (var i = 0; i < this.listenFormElements.length; i++) {
                    addElement = addElement || isElementTargeted(element.name, this.listenFormElements[i]);
                }
                return addElement;
            };
        } else if (typeof this.listenFormElements === 'string') {
            filterFunction = function (element) {
                return isElementTargeted(element.name, this.listenFormElements);
            };
        } else if (typeof this.listenFormElements === 'object') {
            filterFunction = function (element) {
                return this.listenFormElements.name === element.name;
            };
        }

        return filterFunction.bind(this);
    };

    FormListener.prototype.detectChanges = function () {
        var currentHash = this.getFormHash();

        if (currentHash !== this.hash) {
            $(document).trigger(this.formChangedEvent, [this.form]);
        }

        this.hash = currentHash;
    }

    FormListener.prototype.serialize = function () {
        return this.form.serialize();
    }

    FormListener.prototype.serializeArray = function () {
        return this.form.serializeArray();
    }

    return FormListener;
});