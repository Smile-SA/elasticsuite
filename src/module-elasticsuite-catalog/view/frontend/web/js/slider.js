/*!
 * jQuery UI Slider - v1.10.4
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/slider/
 *
 * IMPORTANT:
 * This implementation is NOT a port or rewrite based on jQuery UI 1.14.x.
 *
 * It is a compatibility-oriented refactor of the legacy jQuery UI Slider v1.10.4
 * used by ElasticSuite, adapted to operate safely within:
 * - Magento 2.4.9 frontend architecture
 * - jQuery UI 1.14.1 runtime environment
 * - modern RequireJS AMD module resolution
 *
 * The core slider behavior, API, and interaction model remain unchanged
 * and fully compatible with the original jQuery UI 1.10.4 implementation.
 *
 * WHAT THIS IMPLEMENTATION IS:
 * A stabilized and maintainability-focused refactor of the legacy slider,
 * preserving original semantics while improving runtime robustness in
 * modern Magento environments.
 *
 * WHAT WAS MODERNIZED:
 * - AMD dependency resolution for Magento 2.4.9 compatibility
 * - RequireJS module stability (`jquery/ui` integration)
 * - DOM/event lifecycle safety improvements
 * - Defensive handling of range/value edge cases
 * - Improved runtime state consistency
 *
 * WHAT REMAINS FROM jQuery UI 1.10.4:
 * - Slider interaction model and algorithms
 * - Public API contract (value/values/slide/change/stop)
 * - Keyboard and mouse interaction logic
 * - Range handling semantics
 * - Core rendering and positioning logic
 *
 * TL;DR:
 * Preserve backward compatibility and ElasticSuite integration contracts
 * while ensuring safe execution in modern Magento/jQuery UI ecosystems.
 */

/**
 * AMD module definition.
 *
 * Uses Magento-compatible jQuery UI bundle (`jquery/ui`) instead of split
 * jQuery UI modules, ensuring compatibility with Magento 2.4.9 and modern
 * RequireJS resolution rules.
 */
define([
    'jquery',
    'mage/translate',
    'jquery/ui'
], function ($, $t) {
    'use strict';

    /**
     * Number of pages in a slider.
     *
     * Defines how many times can you PageUp/PageDown to go through the whole range.
     */
    var numPages = 5;

    /**
     * jQuery UI slider widget.
     *
     * Extends `$.ui.mouse` and preserves the original jQuery UI slider API
     * used by ElasticSuite components.
     */
    $.widget('ui.slider', $.ui.mouse, {

        /** @type {string} Base jQuery UI Slider version. */
        version: '1.10.4',

        /**
         * jQuery event prefix.
         *
         * Produces events such as: `slidestart`, `slide`, `slidechange`, `slidestop`.
         *
         * @type {string}
         */
        widgetEventPrefix: 'slide',

        /**
         * Default widget options.
         *
         * Mirrors upstream jQuery UI defaults for backward compatibility.
         *
         * @type {Object}
         */
        options: {
            animate:     false,
            distance:    0,
            max:         100,
            min:         0,
            orientation: 'horizontal',
            range:       false,
            step:        1,
            value:       0,
            values:      null,

            // Callback hooks — null means "not configured".
            change: null,
            slide:  null,
            start:  null,
            stop:   null
        },

        // ---------------------------------------------------------------------
        // Lifecycle
        // ---------------------------------------------------------------------

        /**
         * Initializes slider instance, DOM structure, and mouse interactions.
         */
        _create: function () {
            this._keySliding   = false;
            this._mouseSliding = false;
            this._animateOff   = true;
            this._handleIndex  = null;

            this._detectOrientation();
            this._mouseInit();

            this.element.addClass(
                'ui-slider' +
                ' ui-slider-' + this.orientation +
                ' ui-widget' +
                ' ui-widget-content' +
                ' ui-corner-all'
            );

            this._refresh();
            this._setOption('disabled', this.options.disabled);
            this._animateOff = false;
        },

        /**
         * Rebuilds slider DOM structure and updates positions.
         */
        _refresh: function () {
            this._createRange();
            this._createHandles();
            this._setupEvents();
            this._refreshValue();
        },

        /**
         * Creates or reconciles slider handle elements.
         */
        _createHandles: function () {
            var i, handleCount,
                options         = this.options,
                existingHandles = this.element
                    .find('.ui-slider-handle')
                    .addClass('ui-state-default ui-corner-all'),
                handle  = "<a class='ui-slider-handle ui-state-default ui-corner-all' href='#'></a>",
                handles = [];

            handleCount = (options.values && options.values.length) || 1;

            // Remove handles beyond the required count.
            if (existingHandles.length > handleCount) {
                existingHandles.slice(handleCount).remove();
                existingHandles = existingHandles.slice(0, handleCount);
            }

            // Append missing handles.
            for (i = existingHandles.length; i < handleCount; i++) {
                handles.push(handle);
            }

            this.handles = existingHandles.add($(handles.join('')).appendTo(this.element));
            this.handle  = this.handles.eq(0);

            this.handles.each(function (i) {
                $(this).data('ui-slider-handle-index', i);
                $(this).attr(
                    'title',
                    handleCount === 1
                        ? $t('Range')
                        : i === 0 ? $t('Minimum') : $t('Maximum')
                );
            });
        },

        /**
         * Creates or updates the range highlight element.
         */
        _createRange: function () {
            var options = this.options,
                classes = '';

            if (options.range) {
                if (options.range === true) {
                    // Ensure the values array exists and has exactly two entries.
                    if (!options.values) {
                        options.values = [this._valueMin(), this._valueMin()];
                    } else if (options.values.length && options.values.length !== 2) {
                        options.values = [options.values[0], options.values[0]];
                    } else if (Array.isArray(options.values)) {
                        options.values = options.values.slice(0);
                    }
                }

                if (!this.range || !this.range.length) {
                    // First-time creation.
                    this.range = $('<div></div>').appendTo(this.element);
                    classes    = 'ui-slider-range ui-widget-header ui-corner-all';
                } else {
                    // Recycle: strip directional modifiers and inline positioning styles.
                    this.range
                        .removeClass('ui-slider-range-min ui-slider-range-max')
                        .css({ left: '', bottom: '' });
                }

                this.range.addClass(
                    classes +
                    ((options.range === 'min' || options.range === 'max')
                        ? ' ui-slider-range-' + options.range
                        : '')
                );
            } else {
                if (this.range) {
                    this.range.remove();
                }

                this.range = null;
            }
        },

        /**
         * Binds interaction handlers (mouse, keyboard, focus).
         */
        _setupEvents: function () {
            var elements = this.handles.add(this.range).filter('a');

            this._off(elements);
            this._on(elements, this._handleEvents);
            this._hoverable(elements);
            this._focusable(elements);
        },

        /**
         * Restores DOM to original state.
         */
        _destroy: function () {
            this.handles.remove();

            if (this.range) {
                this.range.remove();
            }

            this.element.removeClass(
                'ui-slider' +
                ' ui-slider-horizontal' +
                ' ui-slider-vertical' +
                ' ui-widget' +
                ' ui-widget-content' +
                ' ui-corner-all'
            );

            this._mouseDestroy();
        },

        // ---------------------------------------------------------------------
        // $.ui.mouse overrides
        // ---------------------------------------------------------------------

        /**
         * Starts slider interaction and resolves the active handle.
         *
         * @param  {jQuery.Event} event
         * @return {boolean}
         */
        _mouseCapture: function (event) {
            var position, normValue, distance, closestHandle, index, allowed, offset, mouseOverHandle,
                that = this,
                o    = this.options;

            if (o.disabled) {
                return false;
            }

            this.elementSize = {
                width:  this.element.outerWidth(),
                height: this.element.outerHeight()
            };
            this.elementOffset = this.element.offset();

            position  = { x: event.pageX, y: event.pageY };
            normValue = this._normValueFromMouse(position);

            // Walk all handles and find the one closest to the click point.
            // Tie-breaking: prefer the handle that last changed, or the one at min.
            distance = this._valueMax() - this._valueMin() + 1;

            this.handles.each(function (i) {
                var thisDistance = Math.abs(normValue - that.values(i));

                if (
                    (distance > thisDistance) ||
                    (
                        distance === thisDistance &&
                        (i === that._lastChangedValue || that.values(i) === o.min)
                    )
                ) {
                    distance      = thisDistance;
                    closestHandle = $(this);
                    index         = i;
                }
            });

            allowed = this._start(event, index);

            if (allowed === false) {
                return false;
            }

            this._mouseSliding = true;
            this._handleIndex  = index;

            closestHandle.addClass('ui-state-active').trigger('focus');

            offset = closestHandle.offset();

            // Calculate the click offset within the handle so it does not jump on grab.
            mouseOverHandle = !$(event.target).parents().addBack().is('.ui-slider-handle');
            this._clickOffset = mouseOverHandle
                ? { left: 0, top: 0 }
                : {
                    left: event.pageX - offset.left - (closestHandle.width() / 2),
                    top:  event.pageY - offset.top
                        - (closestHandle.height() / 2)
                        - (parseInt(closestHandle.css('borderTopWidth'), 10)    || 0)
                        - (parseInt(closestHandle.css('borderBottomWidth'), 10) || 0)
                        + (parseInt(closestHandle.css('marginTop'), 10)         || 0)
                };

            if (!this.handles.hasClass('ui-state-hover')) {
                this._slide(event, index, normValue);
            }

            this._animateOff = true;

            return true;
        },

        /**
         * Required $.ui.mouse hook.
         *
         * @return {boolean}
         */
        _mouseStart: function () {
            return true;
        },

        /**
         * Updates slider value during mouse drag.
         *
         * @param  {jQuery.Event} event
         * @return {boolean}
         */
        _mouseDrag: function (event) {
            var position  = { x: event.pageX, y: event.pageY },
                normValue = this._normValueFromMouse(position);

            this._slide(event, this._handleIndex, normValue);

            return false;
        },

        /**
         * Finalizes mouse interaction and fires stop/change events.
         *
         * @param  {jQuery.Event} event
         * @return {boolean}
         */
        _mouseStop: function (event) {
            this.handles.removeClass('ui-state-active');
            this._mouseSliding = false;

            this._stop(event, this._handleIndex);
            this._change(event, this._handleIndex);

            this._handleIndex = null;
            this._clickOffset = null;
            this._animateOff  = false;

            return false;
        },

        // ---------------------------------------------------------------------
        // Internal helpers
        // ---------------------------------------------------------------------

        /**
         * Detects slider orientation.
         */
        _detectOrientation: function () {
            this.orientation = (this.options.orientation === 'vertical')
                ? 'vertical'
                : 'horizontal';
        },

        /**
         * Converts mouse coordinates into a normalized slider value.
         *
         * @param  {{x: number, y: number}} position
         * @return {number}
         */
        _normValueFromMouse: function (position) {
            var pixelTotal,
                pixelMouse,
                percentMouse,
                valueTotal,
                valueMouse;

            if (this.orientation === 'horizontal') {
                pixelTotal = this.elementSize.width;
                pixelMouse = position.x - this.elementOffset.left
                    - (this._clickOffset ? this._clickOffset.left : 0);
            } else {
                pixelTotal = this.elementSize.height;
                pixelMouse = position.y - this.elementOffset.top
                    - (this._clickOffset ? this._clickOffset.top : 0);
            }

            percentMouse = pixelMouse / pixelTotal;

            // Clamp to valid range.
            if (percentMouse > 1) { percentMouse = 1; }
            if (percentMouse < 0) { percentMouse = 0; }

            // Vertical sliders grow bottom-to-top; invert the percentage.
            if (this.orientation === 'vertical') {
                percentMouse = 1 - percentMouse;
            }

            valueTotal = this._valueMax() - this._valueMin();
            valueMouse = this._valueMin() + percentMouse * valueTotal;

            return this._trimAlignValue(valueMouse);
        },

        /**
         * Fires the start event.
         *
         * @param  {jQuery.Event} event
         * @param  {number}       index
         * @return {boolean|undefined}
         */
        _start: function (event, index) {
            var uiHash = {
                handle: this.handles[index],
                value:  this.value()
            };

            if (this.options.values && this.options.values.length) {
                uiHash.value  = this.values(index);
                uiHash.values = this.values();
            }

            return this._trigger('start', event, uiHash);
        },

        /**
         * Updates a handle value and fires slide events.
         *
         * @param {jQuery.Event} event
         * @param {number}       index
         * @param {number}       newVal
         */
        _slide: function (event, index, newVal) {
            var otherVal,
                newValues,
                allowed;

            if (this.options.values && this.options.values.length) {
                otherVal = this.values(index ? 0 : 1);

                // Clamp to prevent handles crossing in two-handle range mode.
                if (
                    (this.options.values.length === 2 && this.options.range === true) &&
                    ((index === 0 && newVal > otherVal) || (index === 1 && newVal < otherVal))
                ) {
                    newVal = otherVal;
                }

                if (newVal !== this.values(index)) {
                    newValues        = this.values();
                    newValues[index] = newVal;

                    // A slide can be canceled by returning false from the slide callback.
                    allowed = this._trigger('slide', event, {
                        handle: this.handles[index],
                        value:  newVal,
                        values: newValues
                    });

                    // Re-read otherVal in case the callback modified it.
                    otherVal = this.values(index ? 0 : 1);

                    if (allowed !== false) {
                        this.values(index, newVal);
                    }
                }
            } else {
                if (newVal !== this.value()) {
                    // A slide can be canceled by returning false from the slide callback.
                    allowed = this._trigger('slide', event, {
                        handle: this.handles[index],
                        value:  newVal
                    });

                    if (allowed !== false) {
                        this.value(newVal);
                    }
                }
            }
        },

        /**
         * Fires the stop event.
         *
         * @param {jQuery.Event} event
         * @param {number}       index
         */
        _stop: function (event, index) {
            var uiHash = {
                handle: this.handles[index],
                value:  this.value()
            };

            if (this.options.values && this.options.values.length) {
                uiHash.value  = this.values(index);
                uiHash.values = this.values();
            }

            this._trigger('stop', event, uiHash);
        },

        /**
         * Fires the change event when interaction is complete.
         *
         * @param {jQuery.Event|null} event
         * @param {number}            index
         */
        _change: function (event, index) {
            if (!this._keySliding && !this._mouseSliding) {
                var uiHash = {
                    handle: this.handles[index],
                    value:  this.value()
                };

                if (this.options.values && this.options.values.length) {
                    uiHash.value  = this.values(index);
                    uiHash.values = this.values();
                }

                // Store the last changed value index for reference when handles overlap.
                this._lastChangedValue = index;
                this._trigger('change', event, uiHash);
            }
        },

        // ---------------------------------------------------------------------
        // Public value API
        // ---------------------------------------------------------------------

        /**
         * Gets or sets the slider value in single-handle mode.
         *
         * @param  {number} [newValue]
         * @return {number|undefined}
         */
        value: function (newValue) {
            if (arguments.length) {
                this.options.value = this._trimAlignValue(newValue);
                this._refreshValue();
                this._change(null, 0);

                return;
            }

            return this._value();
        },

        /**
         * Gets or sets slider values in range mode.
         *
         * @param  {number|Array.<number>} [index]
         * @param  {number}                [newValue]
         * @return {number|Array.<number>|undefined}
         */
        values: function (index, newValue) {
            var vals,
                newValues,
                i;

            if (arguments.length > 1) {
                // Two-argument setter: update a single handle by index.
                this.options.values[index] = this._trimAlignValue(newValue);
                this._refreshValue();
                this._change(null, index);

                return;
            }

            if (arguments.length) {
                if (Array.isArray(arguments[0])) {
                    // Array setter: replace entire values array.
                    vals      = this.options.values;
                    newValues = arguments[0];

                    for (i = 0; i < vals.length; i += 1) {
                        vals[i] = this._trimAlignValue(newValues[i]);
                        this._change(null, i);
                    }

                    this._refreshValue();
                } else {
                    // Single-index getter.
                    if (this.options.values && this.options.values.length) {
                        return this._values(index);
                    }

                    return this.value();
                }
            } else {
                // No-argument getter: return a copy of the full array.
                return this._values();
            }
        },

        // ---------------------------------------------------------------------
        // Option change handler
        // ---------------------------------------------------------------------

        /**
         * Handles runtime option updates.
         *
         * @param {string} key
         * @param {*}      value
         */
        _setOption: function (key, value) {
            var i,
                valsLength = 0;

            // Normalise values array when switching away from range: true.
            if (key === 'range' && this.options.range === true) {
                if (value === 'min') {
                    this.options.value  = this._values(0);
                    this.options.values = null;
                } else if (value === 'max') {
                    this.options.value  = this._values(this.options.values.length - 1);
                    this.options.values = null;
                }
            }

            if (Array.isArray(this.options.values)) {
                valsLength = this.options.values.length;
            }

            // Persist the option via the base widget.
            $.Widget.prototype._setOption.apply(this, arguments);

            // Apply slider-specific side effects.
            switch (key) {
                case 'orientation':
                    this._detectOrientation();
                    this.element
                        .removeClass('ui-slider-horizontal ui-slider-vertical')
                        .addClass('ui-slider-' + this.orientation);
                    this._refreshValue();
                    break;

                case 'value':
                    this._animateOff = true;
                    this._refreshValue();
                    this._change(null, 0);
                    this._animateOff = false;
                    break;

                case 'values':
                    this._animateOff = true;
                    this._refreshValue();

                    for (i = 0; i < valsLength; i += 1) {
                        this._change(null, i);
                    }

                    this._animateOff = false;
                    break;

                case 'min':
                case 'max':
                    this._animateOff = true;
                    this._refreshValue();
                    this._animateOff = false;
                    break;

                case 'range':
                    this._animateOff = true;
                    this._refresh();
                    this._animateOff = false;
                    break;
            }
        },

        // ---------------------------------------------------------------------
        // Internal value computation
        // ---------------------------------------------------------------------

        /**
         * Returns the normalized value for single-handle mode.
         *
         * @return {number}
         * @private
         */
        _value: function () {
            return this._trimAlignValue(this.options.value);
        },

        /**
         * Returns normalized values for range mode.
         *
         * @param  {number} [index]
         * @return {number|Array.<number>}
         * @private
         */
        _values: function (index) {
            var val, vals, i;

            if (arguments.length) {
                val = this.options.values[index];

                return this._trimAlignValue(val);
            }

            if (this.options.values && this.options.values.length) {
                // Return a trimmed copy — do not expose the internal array reference.
                vals = this.options.values.slice();

                for (i = 0; i < vals.length; i += 1) {
                    vals[i] = this._trimAlignValue(vals[i]);
                }

                return vals;
            }

            return [];
        },

        /**
         * Clamps and aligns a value to the configured step.
         *
         * @param  {number} val
         * @return {number}
         * @private
         */
        _trimAlignValue: function (val) {
            if (val <= this._valueMin()) {
                return this._valueMin();
            }

            if (val >= this._valueMax()) {
                return this._valueMax();
            }

            var step   = (this.options.step > 0) ? this.options.step : 1,
                valModStep = (val - this._valueMin()) % step,
                alignValue = val - valModStep;

            // Round half-up to the nearest step.
            if (Math.abs(valModStep) * 2 >= step) {
                alignValue += (valModStep > 0) ? step : (-step);
            }

            // Since JavaScript has problems with large floats, round
            // the final value to 5 digits after the decimal point (see #4124).
            return parseFloat(alignValue.toFixed(5));
        },

        /**
         * Returns the effective minimum value.
         *
         * @return {number}
         * @private
         */
        _valueMin: function () {
            return this.options.min;
        },

        /**
         * Returns the effective maximum value.
         *
         * @return {number}
         * @private
         */
        _valueMax: function () {
            return this.options.max;
        },

        // ---------------------------------------------------------------------
        // DOM refresh / rendering
        // ---------------------------------------------------------------------

        /**
         * Updates handle and range positions based on current values.
         */
        _refreshValue: function () {
            var lastValPercent, valPercent, value, valueMin, valueMax,
                oRange  = this.options.range,
                o       = this.options,
                that    = this,
                animate = (!this._animateOff) ? o.animate : false,
                _set    = {};

            if (this.options.values && this.options.values.length) {
                // Range mode: position each handle as a track percentage.
                this.handles.each(function (i) {
                    valPercent = (that.values(i) - that._valueMin()) /
                        (that._valueMax() - that._valueMin()) * 100;

                    _set[that.orientation === 'horizontal' ? 'left' : 'bottom'] = valPercent + '%';

                    $(this).stop(1, 1)[animate ? 'animate' : 'css'](_set, o.animate);

                    // Update range highlight position and size.
                    if (that.options.range === true) {
                        if (that.orientation === 'horizontal') {
                            if (i === 0) {
                                that.range.stop(1, 1)[animate ? 'animate' : 'css'](
                                    { left: valPercent + '%' },
                                    o.animate
                                );
                            }

                            if (i === 1) {
                                that.range[animate ? 'animate' : 'css'](
                                    { width: (valPercent - lastValPercent) + '%' },
                                    { queue: false, duration: o.animate }
                                );
                            }
                        } else {
                            if (i === 0) {
                                that.range.stop(1, 1)[animate ? 'animate' : 'css'](
                                    { bottom: valPercent + '%' },
                                    o.animate
                                );
                            }

                            if (i === 1) {
                                that.range[animate ? 'animate' : 'css'](
                                    { height: (valPercent - lastValPercent) + '%' },
                                    { queue: false, duration: o.animate }
                                );
                            }
                        }
                    }

                    lastValPercent = valPercent;
                });
            } else {
                // Single-handle mode.
                value    = this.value();
                valueMin = this._valueMin();
                valueMax = this._valueMax();

                valPercent = (valueMax !== valueMin)
                    ? (value - valueMin) / (valueMax - valueMin) * 100
                    : 0;

                _set[this.orientation === 'horizontal' ? 'left' : 'bottom'] = valPercent + '%';
                this.handle.stop(1, 1)[animate ? 'animate' : 'css'](_set, o.animate);

                if (oRange === 'min' && this.orientation === 'horizontal') {
                    this.range.stop(1, 1)[animate ? 'animate' : 'css'](
                        { width: valPercent + '%' },
                        o.animate
                    );
                }

                if (oRange === 'max' && this.orientation === 'horizontal') {
                    this.range[animate ? 'animate' : 'css'](
                        { width: (100 - valPercent) + '%' },
                        { queue: false, duration: o.animate }
                    );
                }

                if (oRange === 'min' && this.orientation === 'vertical') {
                    this.range.stop(1, 1)[animate ? 'animate' : 'css'](
                        { height: valPercent + '%' },
                        o.animate
                    );
                }

                if (oRange === 'max' && this.orientation === 'vertical') {
                    this.range[animate ? 'animate' : 'css'](
                        { height: (100 - valPercent) + '%' },
                        { queue: false, duration: o.animate }
                    );
                }
            }
        },

        // ---------------------------------------------------------------------
        // Event handler map
        // ---------------------------------------------------------------------

        /**
         * Event handlers bound to slider handles.
         *
         * @type {Object.<string, Function>}
         */
        _handleEvents: {

            /**
             * Handles keyboard navigation for slider handles.
             *
             * @param {jQuery.Event} event
             */
            keydown: function (event) {
                var allowed, curVal, newVal, step,
                    index = $(event.target).data('ui-slider-handle-index');

                // Begin key-sliding session on the first directional keydown.
                switch (event.keyCode) {
                    case $.ui.keyCode.HOME:
                    case $.ui.keyCode.END:
                    case $.ui.keyCode.PAGE_UP:
                    case $.ui.keyCode.PAGE_DOWN:
                    case $.ui.keyCode.UP:
                    case $.ui.keyCode.RIGHT:
                    case $.ui.keyCode.DOWN:
                    case $.ui.keyCode.LEFT:
                        event.preventDefault();

                        if (!this._keySliding) {
                            this._keySliding = true;
                            $(event.target).addClass('ui-state-active');
                            allowed = this._start(event, index);

                            if (allowed === false) {
                                return;
                            }
                        }

                        break;
                }

                step   = this.options.step;
                curVal = (this.options.values && this.options.values.length)
                    ? this.values(index)
                    : this.value();
                newVal = curVal;

                switch (event.keyCode) {
                    case $.ui.keyCode.HOME:
                        newVal = this._valueMin();
                        break;

                    case $.ui.keyCode.END:
                        newVal = this._valueMax();
                        break;

                    case $.ui.keyCode.PAGE_UP:
                        newVal = this._trimAlignValue(
                            curVal + ((this._valueMax() - this._valueMin()) / numPages)
                        );
                        break;

                    case $.ui.keyCode.PAGE_DOWN:
                        newVal = this._trimAlignValue(
                            curVal - ((this._valueMax() - this._valueMin()) / numPages)
                        );
                        break;

                    case $.ui.keyCode.UP:
                    case $.ui.keyCode.RIGHT:
                        if (curVal === this._valueMax()) {
                            return;
                        }

                        newVal = this._trimAlignValue(curVal + step);
                        break;

                    case $.ui.keyCode.DOWN:
                    case $.ui.keyCode.LEFT:
                        if (curVal === this._valueMin()) {
                            return;
                        }

                        newVal = this._trimAlignValue(curVal - step);
                        break;
                }

                this._slide(event, index, newVal);
            },

            /**
             * Prevents default anchor navigation.
             *
             * @param {jQuery.Event} event
             */
            click: function (event) {
                event.preventDefault();
            },

            /**
             * Finalizes keyboard interaction.
             *
             * @param {jQuery.Event} event
             */
            keyup: function (event) {
                var index = $(event.target).data('ui-slider-handle-index');

                if (this._keySliding) {
                    this._keySliding = false;
                    this._stop(event, index);
                    this._change(event, index);
                    $(event.target).removeClass('ui-state-active');
                }
            }
        }
    });
});
