/* global document: true */
/* global window: true */
/* global console: true */
/* exported smileTracker */

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
var smileTracker = (function () {

    "use strict";

    var guid = (function() {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        }
        return function() {
            return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
                s4() + '-' + s4() + s4() + s4();
        };
    }());

    function getCookie(cookieName) {
        var name = cookieName + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1);
            if (c.indexOf(name) !== -1) return c.substring(name.length, c.length);
        }
        return null;
    }

    function setCookie(cookieName, cookieValue, expiresAt, path) {
        var expires = "expires=" + expiresAt.toUTCString();
        document.cookie = cookieName + "=" + cookieValue + "; " + expires + "; path=" + path;
    }

    // Retrieve values for a param into URL
    function getQueryStringParameterByName(name) {
        var results = null;

        if (name && name.replace) {
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
            results = regex.exec(window.location.search);
        }

        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    // Add page title and page URL to the tracked variables
    function addStandardPageVars() {
        // Website base url tracking (eg. mydomain.com)
        this.addPageVar("site", window.location.hostname);

        // Page URL tracking (eg. home.html)
        this.addPageVar("url", window.location.pathname);

        // Page title tracking
        this.addPageVar("title", document.title);
    }

    // Append GA campaign variable to the tracked variables
    // Following URL variables are used :
    // - utm_source
    // - utm_campaign
    // - utm_medium
    function addCampaignVars() {

        // GA variables to be fetched from URI
        var urlParams = ['utm_source', 'utm_campaign', 'utm_medium', 'utm_term'];

        urlParams.forEach(function (element) {
            var paramName = element;
            var paramValue = getQueryStringParameterByName(paramName);
            if (paramValue) {
                // Append the GA param to the tracker
                this.addPageVar(paramName, paramValue);
            }
        }.bind(this));
    }

    function addReferrerVars() {
        if (document.referrer) {
            var parser = document.createElement('a');
            parser.href = document.referrer;
            this.addPageVar('referrer.domain', parser.hostname);
            this.addPageVar('referrer.page', parser.pathname);
        }
    }

    function addResolutionVars() {
        this.addPageVar('resolution.x', window.screen.availWidth);
        this.addPageVar('resolution.y', window.screen.availHeight);
        return this;
    }

    function addCustomerVars(customerData) {
        getCustomerDataCodeToTrack().forEach(function (customerDataCode) {
            if (customerData.hasOwnProperty('tracking') && customerData.tracking.hasOwnProperty(customerDataCode)) {
                this.addCustomerVar(customerDataCode, customerData.tracking[customerDataCode]);
            }
        }.bind(this));
    }

    function addMetaPageVars() {
        var metaTags = document.getElementsByTagName('meta');
        for (var tagIndex = 0; tagIndex < metaTags.length; tagIndex++) {
            if (metaTags[tagIndex].getAttribute('name')) {
                var components = metaTags[tagIndex].getAttribute('name').split(':');
                if (components.length === 2 && components[0] === 'sct') {
                    var varName = components[1];
                    this.addPageVar(varName, metaTags[tagIndex].getAttribute('content'));
                }
            }
        }
    }

    function getTrackerVars() {
        if (this.trackerVarsAdded === false) {
            addStandardPageVars.bind(this)();
            addReferrerVars.bind(this)();
            addCampaignVars.bind(this)();
            addMetaPageVars.bind(this)();
            addResolutionVars.bind(this)();
            this.trackerVarsAdded = true;
        }

        var urlParams = [];

        for (var currentVar in this.vars) {
            if ({}.hasOwnProperty.call(this.vars, currentVar)) {
                urlParams.push(currentVar + "=" + this.vars[currentVar]);
            }
        }

        return urlParams;
    }

    function getTrackerUrl() {
        let urlParams = getTrackerVars.bind(this)();
        return this.baseUrl + "?" + urlParams.join('&');
    }

    function getCustomerDataCodeToTrack() {
        return ['age', 'gender', 'zipcode', 'state', 'country'];
    }

    function setTrackerStyle(imgNode) {
        imgNode.setAttribute('style', 'position: absolute; top: 0; left: 0; visibility: hidden;');
    }

    // Send the tag to the remote server
    // Append a transparent pixel to the body
    function sendTag() {
        initSession.bind(this)();

        if (this.trackerSent === false) {
            let bodyNode = document.getElementsByTagName('body')[0];
            buildTrackingImg.bind(this)(bodyNode, getTrackerUrl.bind(this)());
            this.trackerSent = true;
        }
    }

    function sendTelemetry() {
        initSession.bind(this)();
        initCustomerData.bind(this)();
        getTrackerVars.bind(this);

        let vars = bracketVarsToJson(this.vars);

        if (this.telemetryEnabled && this.telemetryTrackerSent === false) {
            var request = new XMLHttpRequest();
            request.open('POST', this.telemetryUrl, true);
            request.setRequestHeader('Content-Type', 'application/json');
            request.send(JSON.stringify(vars));

            this.telemetryTrackerSent = true;
        }
    }

    function bracketVarsToJson(vars) {
        let result = {};

        for (const i in vars) {
            let a = i.match(/([^\[\]]+)(\[[^\[\]]+[^\]])*?/g),
                p = vars[i];
            let j = a.length;
            while (j--) {
                let q = {};
                q[a[j]] = p;
                p = q;
            }

            let k = Object.keys(p)[0],
                o = result;

            while (k in o) {
                p = p[k];
                o = o[k];
                k = Object.keys(p)[0];
            }

            o[k] = p[k];
        }

        return result;
    }

    function buildTrackingImg(bodyNode, trackingUrl) {
        let imgNode = document.createElement('img');
        imgNode.setAttribute('src', trackingUrl);
        imgNode.setAttribute('alt', '');
        setTrackerStyle(imgNode);
        bodyNode.appendChild(imgNode);
    }

    // Append a variable to the page
    function addVariable(varName, value) {
        this.vars[varName] = encodeURIComponent(value);
        return this;
    }

    function addSessionVar(varName, value) {
        addVariable.bind(this)(transformVarName.bind(this)(varName, 'session'), value);
    }

    function addPageVar(varName, value) {
        addVariable.bind(this)(transformVarName.bind(this)(varName , 'page'), value);
    }

    function addCustomerVar(varName, value) {
        addVariable.bind(this)(transformVarName.bind(this)(varName , 'customer'), value);
    }

    function transformVarName(varName, prefix) {
        return prefix + "[" + varName.replace(/[.]/g, "][") + "]";
    }

    function initSession() {
        if (this.config && this.config.hasOwnProperty('sessionConfig') && !this.sessionInitialized) {
            var config   = this.config.sessionConfig;
            var expireAt = new Date();
            var path     = config['path'] || '/';

            if (getCookie(config['visit_cookie_name']) === null) {
                expireAt.setSeconds(expireAt.getSeconds() + parseInt(config['visit_cookie_lifetime'], 10));
                setCookie(config['visit_cookie_name'], guid(), expireAt, path);
            } else {
                expireAt.setSeconds(expireAt.getSeconds() + parseInt(config['visit_cookie_lifetime'], 10));
                setCookie(config['visit_cookie_name'], getCookie(config['visit_cookie_name']), expireAt, path);
            }

            if (getCookie(config['visitor_cookie_name']) === null) {
                expireAt.setDate(expireAt.getDate() + parseInt(config['visitor_cookie_lifetime'], 10));
                setCookie(config['visitor_cookie_name'], guid(), expireAt, path);
            }

            addSessionVar.bind(this)('uid', getCookie(config['visit_cookie_name']));
            addSessionVar.bind(this)('vid', getCookie(config['visitor_cookie_name']));
            this.sessionInitialized = true;
        }
    }

    function initCustomerData() {
        try {
            let mageStorage = localStorage.getItem('mage-cache-storage');
            if (mageStorage !== null) {
                mageStorage = JSON.parse(mageStorage);
                if ((mageStorage.customer !== undefined)) {
                    addCustomerVars.bind(this)(mageStorage.customer);
                }
            }
        } catch (e) {
            // Nothing.
        }
    }

    // Implementation of the tracker
    var SmileTrackerImpl = function() {
        this.vars = {};
        this.trackerSent = false;
        this.telemetryTrackerSent = false;
        this.trackerVarsAdded = false;
        this.sessionInitialized = false;
        this.customerData = {};
    };

    SmileTrackerImpl.prototype.sendTag = function () {
        if (document.readyState != 'loading') {
            sendTag.bind(this)();
            sendTelemetry.bind(this)();
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                sendTag.bind(this)();
                sendTelemetry.bind(this)();
            }.bind(this));
        }
    }

    SmileTrackerImpl.prototype.setConfig = function (config) {
        this.config           = config;
        this.baseUrl          = config.beaconUrl;
        this.telemetryEnabled = config.telemetryEnabled;
        this.telemetryUrl     = config.telemetryUrl;
    }

    SmileTrackerImpl.prototype.addPageVar = function (varName, value) {
        addPageVar.bind(this)(varName, value);
    }

    SmileTrackerImpl.prototype.addCustomerVar = function (varName, value) {
        addCustomerVar.bind(this)(varName, value);
    }

    SmileTrackerImpl.prototype.addSessionVar = function (varName, value) {
        addSessionVar.bind(this)(varName, value);
    }

    return new SmileTrackerImpl();
}());
