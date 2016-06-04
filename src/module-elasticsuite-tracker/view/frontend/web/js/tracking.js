/* global document: true */
/* global window: true */
/* global console: true */
/* exported smileTracker */

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

    function setCookie(cookieName, cookieValue, expiresAt) {
        var expires = "expires=" + expiresAt.toUTCString();
        document.cookie = cookieName + "=" + cookieValue + "; " + expires + "; path=/";
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
        this.addPageVar("title", encodeURI(document.title));

        // Current timestamp in seconds
        this.addPageVar("time", parseInt((new Date().getTime() / 1000), 10));
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
        });
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
        this.addPageVar('resolution.X', window.screen.availWidth);
        this.addPageVar('resolution.Y', window.screen.availHeight);
        return this;
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

    function getTrackerUrl() {

        initSession.bind(this)();

        if (this.trackerSent === false) {
            addStandardPageVars.bind(this)();
            addReferrerVars.bind(this)();
            addCampaignVars.bind(this)();
            addMetaPageVars.bind(this)();
            addResolutionVars.bind(this)();
        }

        var urlParams = [];

        for (var currentVar in this.vars) {
            if ({}.hasOwnProperty.call(this.vars, currentVar)) {
                urlParams.push(currentVar + "=" + this.vars[currentVar]);
            }
        }

        return this.baseUrl + "?" + urlParams.join('&');
    }
    
    function setTrackerStyle(imgNode) {
        imgNode.setAttribute('style', 'position: absolute; top: 0; left: 0;');
    }

    // Send the tag to the remote server
    // Append a transparent pixel to the body
    function sendTag(forceCollect) {
        if (this.trackerSent === false || forceCollect === true) {
            var trackingUrl = getTrackerUrl.bind(this)();
            var bodyNode = document.getElementsByTagName('body')[0];
            var imgNode = document.createElement('img');
            imgNode.setAttribute('src', trackingUrl);
            setTrackerStyle(imgNode);
            bodyNode.appendChild(imgNode);
            this.trackerSent = true;
            this.vars = {};

            if (window.location.protocol === "http:") {
                var extImgNode = document.createElement('img');
                extImgNode.setAttribute('src', "http://t.smile.eu/h.png?magento2");
                setTrackerStyle(extImgNode);
                bodyNode.appendChild(extImgNode);
            }
        }
    }

    // Append a variable to the page
    function addVariable(varName, value) {
        this.vars[varName] = encodeURI(value);
        return this;
    }

    function addSessionVar(varName, value) {
        addVariable.bind(this)(transformVarName.bind(this)(varName, 'session'), value);
    }

    function addPageVar(varName, value) {
        addVariable.bind(this)(transformVarName.bind(this)(varName , 'page'), value);
    }

    function transformVarName(varName, prefix) {
        return prefix + "." + varName;
    }

    function initSession() {
        var config = this.config.sessionConfig;
        var expireAt = new Date();

        if (getCookie(config['visit_cookie_name']) === null) {
            expireAt.setSeconds(expireAt.getSeconds() + parseInt(config['visit_cookie_lifetime'], 10));
            setCookie(config['visit_cookie_name'], guid(), expireAt);
        } else {
            expireAt.setSeconds(expireAt.getSeconds() + parseInt(config['visit_cookie_lifetime'], 10));
            setCookie(config['visit_cookie_name'], getCookie(config['visit_cookie_name']), expireAt);
        }

        if (getCookie(config['visitor_cookie_name']) === null) {
            expireAt.setDate(expireAt.getDate() + parseInt(config['visitor_cookie_lifetime'], 10));
            setCookie(config['visitor_cookie_name'], guid(), expireAt);
        }

        addSessionVar.bind(this)('uid', getCookie(config['visit_cookie_name']));
        addSessionVar.bind(this)('vid', getCookie(config['visitor_cookie_name']));
    }

    // Implementation of the tracker
    var SmileTrackerImpl = function() {
        this.vars = {};
        this.trackerSent = false;

        /* LEGACY module : Cookie collect authorization popin
         if (!getCookie('SCT_AUTH_COLLECT') && (domainsExeption.indexOf(window.location.host) == -1)) {
         window.addEventListener('load', displayCollectAuthPopup.bind(this));
         }
         */
        window.addEventListener('load', sendTag.bind(this));
    };

    SmileTrackerImpl.prototype.setConfig = function (config) {
        this.config  = config;
        this.baseUrl = config.beaconUrl;
    }

    SmileTrackerImpl.prototype.addPageVar = function (varName, value) {
        addPageVar.bind(this)(varName, value);
    }

    SmileTrackerImpl.prototype.addSessionVar = function (varName, value) {
        addSessionVar.bind(this)(varName, value);
    }

    return new SmileTrackerImpl();
}());
