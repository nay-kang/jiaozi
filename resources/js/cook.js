(function(){
/****************************************************************************************************************
 * Thirdpart Libs
 */

/**
 * UUID.core.js: A small subset of UUID.js, the RFC-compliant UUID generator for JavaScript.
 *
 * @fileOverview
 * @author  LiosK
 * @version v3.3.0
 * @license The MIT License: Copyright (c) 2010-2016 LiosK.
 */

/** @constructor */
var UUID;

UUID = (function(overwrittenUUID) {
"use strict";

/** @lends UUID */
function UUID() {}

/**
 * The simplest function to get an UUID string.
 * @returns {string} A version 4 UUID string.
 */
UUID.generate = function() {
	return  hex(rand(32), 8)          // time_low
				+ "-"
				+ hex(rand(16), 4)          // time_mid
				+ "-"
				+ hex(0x4000 | rand(12), 4) // time_hi_and_version
				+ "-"
				+ hex(0x8000 | rand(14), 4) // clock_seq_hi_and_reserved clock_seq_low
				+ "-"
				+ hex(rand(48), 12);        // node
};

/**
 * Returns an unsigned x-bit random integer.
 * @param {int} x A positive integer ranging from 0 to 53, inclusive.
 * @returns {int} An unsigned x-bit random integer (0 <= f(x) < 2^x).
 */
function rand(x) {  // _getRandomInt
	if (x <   0) return NaN;
	if (x <= 30) return (0 | Math.random() * (1 <<      x));
	if (x <= 53) return (0 | Math.random() * (1 <<     30))
										+ (0 | Math.random() * (1 << x - 30)) * (1 << 30);
	return NaN;
}

/**
 * Converts an integer to a zero-filled hexadecimal string.
 * @param {int} num
 * @param {int} length
 * @returns {string}
 */
function hex(num, length) { // _hexAligner
	var str = num.toString(16), i = length - str.length, z = "0";
	for (; i > 0; i >>>= 1, z += z) { if (i & 1) { str = z + str; } }
	return str;
}

/**
 * Preserves the value of 'UUID' global variable set before the load of UUID.js.
 * @since core-1.1
 * @type object
 */
UUID.overwrittenUUID = overwrittenUUID;

return UUID;

})(UUID);


/*!
 * JavaScript Cookie v2.1.2
 * https://github.com/js-cookie/js-cookie
 *
 * Copyright 2006, 2015 Klaus Hartl & Fagner Brack
 * Released under the MIT license
 */
;(function (factory) {
	if (typeof define === 'function' && define.amd) {
		define(factory);
	} else if (typeof exports === 'object') {
		module.exports = factory();
	} else {
		var OldCookies = window.Cookies;
		var api = window.Cookies = factory();
		api.noConflict = function () {
			window.Cookies = OldCookies;
			return api;
		};
	}
}(function () {
	function extend () {
		var i = 0;
		var result = {};
		for (; i < arguments.length; i++) {
			var attributes = arguments[ i ];
			for (var key in attributes) {
				result[key] = attributes[key];
			}
		}
		return result;
	}

	function init (converter) {
		function api (key, value, attributes) {
			var result;
			if (typeof document === 'undefined') {
				return;
			}

			// Write

			if (arguments.length > 1) {
				attributes = extend({
					path: '/'
				}, api.defaults, attributes);

				if (typeof attributes.expires === 'number') {
					var expires = new Date();
					expires.setMilliseconds(expires.getMilliseconds() + attributes.expires * 864e+5);
					attributes.expires = expires;
				}

				try {
					result = JSON.stringify(value);
					if (/^[\{\[]/.test(result)) {
						value = result;
					}
				} catch (e) {}

				if (!converter.write) {
					value = encodeURIComponent(String(value))
						.replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g, decodeURIComponent);
				} else {
					value = converter.write(value, key);
				}

				key = encodeURIComponent(String(key));
				key = key.replace(/%(23|24|26|2B|5E|60|7C)/g, decodeURIComponent);
				key = key.replace(/[\(\)]/g, escape);

				return (document.cookie = [
					key, '=', value,
					attributes.expires ? '; expires=' + attributes.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
					attributes.path ? '; path=' + attributes.path : '',
					attributes.domain ? '; domain=' + attributes.domain : '',
					attributes.secure ? '; secure' : ''
				].join(''));
			}

			// Read

			if (!key) {
				result = {};
			}

			// To prevent the for loop in the first place assign an empty array
			// in case there are no cookies at all. Also prevents odd result when
			// calling "get()"
			var cookies = document.cookie ? document.cookie.split('; ') : [];
			var rdecode = /(%[0-9A-Z]{2})+/g;
			var i = 0;

			for (; i < cookies.length; i++) {
				var parts = cookies[i].split('=');
				var cookie = parts.slice(1).join('=');

				if (cookie.charAt(0) === '"') {
					cookie = cookie.slice(1, -1);
				}

				try {
					var name = parts[0].replace(rdecode, decodeURIComponent);
					cookie = converter.read ?
						converter.read(cookie, name) : converter(cookie, name) ||
						cookie.replace(rdecode, decodeURIComponent);

					if (this.json) {
						try {
							cookie = JSON.parse(cookie);
						} catch (e) {}
					}

					if (key === name) {
						result = cookie;
						break;
					}

					if (!key) {
						result[name] = cookie;
					}
				} catch (e) {}
			}

			return result;
		}

		api.set = api;
		api.get = function (key) {
			return api.call(api, key);
		};
		api.getJSON = function () {
			return api.apply({
				json: true
			}, [].slice.call(arguments));
		};
		api.defaults = {};

		api.remove = function (key, attributes) {
			api(key, '', extend(attributes, {
				expires: -1
			}));
		};

		api.withConverter = init;

		return api;
	}

	return init(function () {});
}));



/***************************
 * Self Code
 */

var Jiaozi = function(){
	this.init();
};
Jiaozi.prototype = {
	'init':function(){
		var COOKIE_KEY = '_jiaozi_uid';
		var baseHost = "jiaozi.stylewe.com";
		//set Cookie
		var cookieStore = Cookies.noConflict();
		var uuid = cookieStore.get(COOKIE_KEY);
		if(!uuid){
			uuid = UUID.generate();
			uuid = uuid.replace(/-/g,'');
		}

		/*get domain*/
		var domain = window.location.hostname;
		domain = domain.split(".");
		domain = domain.slice(-2,domain.length);
		domain = "."+domain.join(".");

		cookieStore.set(COOKIE_KEY,uuid,{path:"/",expires:365,'domain':domain});

		/*pid*/
		var pid = null;
		var scripts = document.querySelectorAll('script');
		for(i in scripts){
			var src = scripts[i].src;
			if(src.search(baseHost)>-1){
				var r = null;
				if(r = src.match(/pid\=(\w+)&?/)){
					pid = r[1];
					break;	
				}
			}
		}
		
		
		this.info = {
			"baseHost":baseHost,
			'host':"https://"+baseHost,
			'cookieKey':COOKIE_KEY,
			'uuid':uuid,
			'pid':pid
		};
	},
	'paramsToUrl':function(params){
		var url = '';
		for(var key in params){
			if(url != ''){
				url += '&';
			}
			url += encodeURIComponent(key) + '=' + encodeURIComponent(params[key])
		}
		return url;
	},
	'send':function(type,data){
		data[this.info.cookieKey] = this.info.uuid;
		data['pid'] = this.info.pid;
		data['type'] = type;
		
		var url = this.info.host+"/collect_img.gif?"+this.paramsToUrl(data);
		var img = window.document.createElement('img');
		img.setAttribute("src",url);
		img.setAttribute("style","display:none");
		window.document.body.appendChild(img);
	},
	'pageview':function(){
		var params = {
			"referer":window.document.referrer,
			"url":window.location.href,
		};
		this.send('pageview',params);
	},
	'event':function(data){
		this.send('event',data);
	}
};

var jz = new Jiaozi();

window._JZ = {
	"send":jz.send.bind(jz),
	"pageview":jz.pageview.bind(jz),
	"event":jz.event.bind(jz),
};

})();