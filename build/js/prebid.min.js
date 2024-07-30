/******/ (() => { // webpackBootstrap
/*!*****************************!*\
  !*** ./assets/js/prebid.js ***!
  \*****************************/
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
!function e(t, a, r) {
  function o(n, d) {
    if (!a[n]) {
      if (!t[n]) {
        var s = undefined;
        if (!d && s) return require(n, !0);
        if (i) return i(n, !0);
        var m = new Error("Cannot find module '" + n + "'");
        throw m.code = "MODULE_NOT_FOUND", m;
      }
      var c = a[n] = {
        exports: {}
      };
      t[n][0].call(c.exports, function (e) {
        return o(t[n][1][e] || e);
      }, c, c.exports, e, t, a, r);
    }
    return a[n].exports;
  }
  for (var i = undefined, n = 0; n < r.length; n++) o(r[n]);
  return o;
}({
  1: [function (e, t, a) {
    var r = window.pbjs = window.pbjs || {};
    r.que = r.que || [], r.que.unshift(function () {
      r.rp.wrapperLoaded = !0;
    });
    try {
      e("./lib/dm-web-vitals/trackWebVitals.js")(100);
    } catch (e) {
      console.log("DM error loading DM Web Vitals", e);
    }
    r.que.unshift(function () {}), r.que.unshift(function () {
      r.rp.applyPrebidSetConfig();
    }), r.que.unshift(function () {
      r.rp.mergeConfig({
        gptPreAuction: {
          enabled: !0,
          useDefaultPreAuction: !0
        }
      });
    }), r.que.unshift(function () {
      var e = r.getConfig("rubicon") || {};
      e.int_type = "dmpbjs", e.wrapperName = "26298_Bizbudding_Desktop", e.wrapperFamily = "26298_Bizbudding_Desktop", e.waitForGamSlots = !0, e.analyticsBatchTimeout = 5e3, e.singleRequest = !0, e.dmBilling = {
        enabled: !1,
        vendors: [],
        waitForAuction: !0
      }, e.wrapperModels = void 0, e.accountId = 26298, r.rp.mergeConfig({
        rubicon: e
      });
    }), r.que.unshift(function () {
      function e(e) {
        return !(!e || !Object.keys(e).length);
      }
      var t = {
          cmpApi: "iab",
          timeout: 50
        },
        a = {
          timeout: 50,
          allowAuctionWithoutConsent: !1,
          defaultGdprScope: !0,
          rules: [{
            purpose: "storage",
            enforceVendor: !0,
            enforcePurpose: !0
          }, {
            purpose: "basicAds",
            enforceVendor: !0,
            enforcePurpose: !0
          }, {
            purpose: "measurement",
            enforceVendor: !1,
            enforcePurpose: !1
          }],
          cmpApi: "iab"
        },
        o = {
          timeout: 50
        },
        i = {};
      e(a) && function () {
        if (r.rp && r.rp.hasCustomCmp) return !1;
        if ("function" == typeof (window.__cmp || window.__tcfapi) || window.$sf && window.$sf.ext && "function" == typeof window.$sf.ext.cmp) return !0;
        try {
          if ("function" == typeof (window.top.__cmp || window.top.__tcfapi)) return !0;
        } catch (e) {}
        for (var e = window; e !== window.top;) {
          e = e.parent;
          try {
            if (e.frames.__cmpLocator) return !0;
          } catch (e) {}
          try {
            if (e.frames.__tcfapiLocator) return !0;
          } catch (e) {}
        }
        return !1;
      }() && (i.gdpr = a), !e(o) || r.rp && r.rp.hasCustomUspCmp || (i.usp = o), e(t) && function () {
        var e = !1,
          t = !1,
          a = window;
        for (r.rp && r.rp.hasCustomGppCmp && (e = !0); !e && !t;) {
          try {
            "function" == typeof a.__gpp && (t = !0);
          } catch (e) {}
          try {
            a.frames.__gppLocator && (t = !0);
          } catch (e) {}
          a === window.top && (e = !0), a = a.parent;
        }
        return t;
      }() && (i.gpp = t), e(i) && r.rp.mergeConfig({
        consentManagement: i
      });
    }), r.que.unshift(function () {
      r.enableAnalytics([{
        options: {
          endpoint: "https://prebid-a.rubiconproject.com/event",
          accountId: 26298
        },
        provider: "magnite"
      }]);
    }), r.que.unshift(function () {
      r.rp.mtoConfigMap = {
        14916: {
          mediaTypes: {
            banner: {
              sizes: [[300, 100], [300, 250], [300, 50], [320, 50], [468, 60], [728, 90], [750, 100], [970, 90], [336, 280], [580, 400], [750, 300], [750, 200], [120, 600], [160, 600], [300, 600], [970, 250]]
            }
          }
        }
      }, r.ppi && (r.ppi.mtoConfigMap = r.rp.mtoConfigMap);
    }), r.que.unshift(function () {
      var e = [{
        slotPattern: "tvnewscheck.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491568,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "tvnewscheck.com/rectangle-medium"
      }, {
        slotPattern: "tvnewscheck.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491570,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "tvnewscheck.com/leaderboard-wide"
      }, {
        slotPattern: "tvnewscheck.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491572,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "tvnewscheck.com/leaderboard"
      }, {
        slotPattern: "tvnewscheck.com/billboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491574,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "tvnewscheck.com/billboard-wide"
      }, {
        slotPattern: "tvnewscheck.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491576,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "tvnewscheck.com/billboard"
      }, {
        slotPattern: "thepaleomom.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491578,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245184"
          }
        }],
        mtoRevId: 14916,
        aupname: "thepaleomom.com/rectangle-medium"
      }, {
        slotPattern: "thepaleomom.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491580,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245185"
          }
        }],
        mtoRevId: 14916,
        aupname: "thepaleomom.com/leaderboard-wide"
      }, {
        slotPattern: "thepaleomom.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491582,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245186"
          }
        }],
        mtoRevId: 14916,
        aupname: "thepaleomom.com/leaderboard"
      }, {
        slotPattern: "thepaleomom.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491584,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245187"
          }
        }],
        mtoRevId: 14916,
        aupname: "thepaleomom.com/billboard"
      }, {
        slotPattern: "theheelhook.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491586,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245188"
          }
        }],
        mtoRevId: 14916,
        aupname: "theheelhook.com/rectangle-medium"
      }, {
        slotPattern: "theheelhook.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491588,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245189"
          }
        }],
        mtoRevId: 14916,
        aupname: "theheelhook.com/leaderboard-wide"
      }, {
        slotPattern: "theheelhook.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491590,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245190"
          }
        }],
        mtoRevId: 14916,
        aupname: "theheelhook.com/leaderboard"
      }, {
        slotPattern: "theheelhook.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491592,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245191"
          }
        }],
        mtoRevId: 14916,
        aupname: "theheelhook.com/billboard"
      }, {
        slotPattern: "thatorganicmom.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491594,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "thatorganicmom.com/rectangle-medium"
      }, {
        slotPattern: "thatorganicmom.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491596,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "thatorganicmom.com/leaderboard-wide"
      }, {
        slotPattern: "thatorganicmom.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491598,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "thatorganicmom.com/leaderboard"
      }, {
        slotPattern: "sugarmakers.org-2/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491600,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245195"
          }
        }],
        mtoRevId: 14916,
        aupname: "sugarmakers.org-2/rectangle-medium"
      }, {
        slotPattern: "sugarmakers.org-2/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491602,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245196"
          }
        }],
        mtoRevId: 14916,
        aupname: "sugarmakers.org-2/leaderboard-wide"
      }, {
        slotPattern: "sugarmakers.org-2/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491604,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245197"
          }
        }],
        mtoRevId: 14916,
        aupname: "sugarmakers.org-2/leaderboard"
      }, {
        slotPattern: "sugarmakers.org-2/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491606,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245198"
          }
        }],
        mtoRevId: 14916,
        aupname: "sugarmakers.org-2/billboard"
      }, {
        slotPattern: "recipehippie.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491608,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "recipehippie.com/rectangle-medium"
      }, {
        slotPattern: "recipehippie.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491610,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "recipehippie.com/leaderboard-wide"
      }, {
        slotPattern: "recipehippie.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491612,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "recipehippie.com/billboard"
      }, {
        slotPattern: "postindustrial.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491614,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "postindustrial.com/rectangle-medium"
      }, {
        slotPattern: "postindustrial.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491616,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "postindustrial.com/leaderboard-wide"
      }, {
        slotPattern: "postindustrial.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491618,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "postindustrial.com/leaderboard"
      }, {
        slotPattern: "postindustrial.com/billboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491620,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "postindustrial.com/billboard-wide"
      }, {
        slotPattern: "postindustrial.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491622,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "postindustrial.com/billboard"
      }, {
        slotPattern: "naturesoma.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491624,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245207"
          }
        }],
        mtoRevId: 14916,
        aupname: "naturesoma.com/rectangle-medium"
      }, {
        slotPattern: "naturesoma.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491626,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245208"
          }
        }],
        mtoRevId: 14916,
        aupname: "naturesoma.com/leaderboard-wide"
      }, {
        slotPattern: "mywellbalancedlife.com/skyscraper",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491628,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245210"
          }
        }],
        mtoRevId: 14916,
        aupname: "mywellbalancedlife.com/skyscraper"
      }, {
        slotPattern: "mywellbalancedlife.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491630,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245211"
          }
        }],
        mtoRevId: 14916,
        aupname: "mywellbalancedlife.com/rectangle-medium"
      }, {
        slotPattern: "mywellbalancedlife.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491632,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245212"
          }
        }],
        mtoRevId: 14916,
        aupname: "mywellbalancedlife.com/leaderboard-wide"
      }, {
        slotPattern: "mywellbalancedlife.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491634,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245213"
          }
        }],
        mtoRevId: 14916,
        aupname: "mywellbalancedlife.com/leaderboard"
      }, {
        slotPattern: "mywellbalancedlife.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491636,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245214"
          }
        }],
        mtoRevId: 14916,
        aupname: "mywellbalancedlife.com/billboard"
      }, {
        slotPattern: "motorcitymouse.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491638,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245215"
          }
        }],
        mtoRevId: 14916,
        aupname: "motorcitymouse.com/rectangle-medium"
      }, {
        slotPattern: "motorcitymouse.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491640,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245216"
          }
        }],
        mtoRevId: 14916,
        aupname: "motorcitymouse.com/leaderboard-wide"
      }, {
        slotPattern: "motorcitymouse.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491642,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245217"
          }
        }],
        mtoRevId: 14916,
        aupname: "motorcitymouse.com/billboard"
      }, {
        slotPattern: "moneypit.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491644,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245218"
          }
        }],
        mtoRevId: 14916,
        aupname: "moneypit.com/rectangle-medium"
      }, {
        slotPattern: "moneypit.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491646,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245219"
          }
        }],
        mtoRevId: 14916,
        aupname: "moneypit.com/leaderboard-wide"
      }, {
        slotPattern: "moneypit.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491648,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245220"
          }
        }],
        mtoRevId: 14916,
        aupname: "moneypit.com/leaderboard"
      }, {
        slotPattern: "moneypit.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491650,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245221"
          }
        }],
        mtoRevId: 14916,
        aupname: "moneypit.com/billboard"
      }, {
        slotPattern: "methodshop.com/skyscraper-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491652,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "methodshop.com/skyscraper-wide"
      }, {
        slotPattern: "methodshop.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491654,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "methodshop.com/rectangle-medium"
      }, {
        slotPattern: "methodshop.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491656,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "methodshop.com/leaderboard-wide"
      }, {
        slotPattern: "methodshop.com/leaderboard-small",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491658,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "methodshop.com/leaderboard-small"
      }, {
        slotPattern: "methodshop.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491660,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "methodshop.com/leaderboard"
      }, {
        slotPattern: "methodshop.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491662,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "methodshop.com/billboard"
      }, {
        slotPattern: "jeepfan.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491664,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "jeepfan.com/rectangle-medium"
      }, {
        slotPattern: "jeepfan.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491666,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "jeepfan.com/leaderboard-wide"
      }, {
        slotPattern: "jeepfan.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491668,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "jeepfan.com/leaderboard"
      }, {
        slotPattern: "jeepfan.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491670,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "jeepfan.com/billboard"
      }, {
        slotPattern: "insidetailgating.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491672,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245232"
          }
        }],
        mtoRevId: 14916,
        aupname: "insidetailgating.com/leaderboard-wide"
      }, {
        slotPattern: "insidetailgating.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491674,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245233"
          }
        }],
        mtoRevId: 14916,
        aupname: "insidetailgating.com/leaderboard"
      }, {
        slotPattern: "insidetailgating.com/billboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491676,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245234"
          }
        }],
        mtoRevId: 14916,
        aupname: "insidetailgating.com/billboard-wide"
      }, {
        slotPattern: "insidetailgating.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491678,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245235"
          }
        }],
        mtoRevId: 14916,
        aupname: "insidetailgating.com/billboard"
      }, {
        slotPattern: "healthy-foodie.com/skyscraper",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491680,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "healthy-foodie.com/skyscraper"
      }, {
        slotPattern: "healthy-foodie.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491732,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "healthy-foodie.com/rectangle-medium"
      }, {
        slotPattern: "healthy-foodie.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491734,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "healthy-foodie.com/leaderboard-wide"
      }, {
        slotPattern: "healthy-foodie.com/billboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491736,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "healthy-foodie.com/billboard-wide"
      }, {
        slotPattern: "healthy-foodie.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491738,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "healthy-foodie.com/billboard"
      }, {
        slotPattern: "healingambassadors.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491740,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245241"
          }
        }],
        mtoRevId: 14916,
        aupname: "healingambassadors.com/leaderboard-wide"
      }, {
        slotPattern: "grandstrandlocal.com/skyscraper-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491742,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245242"
          }
        }],
        mtoRevId: 14916,
        aupname: "grandstrandlocal.com/skyscraper-wide"
      }, {
        slotPattern: "grandstrandlocal.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491744,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245244"
          }
        }],
        mtoRevId: 14916,
        aupname: "grandstrandlocal.com/rectangle-medium"
      }, {
        slotPattern: "grandstrandlocal.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491746,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245246"
          }
        }],
        mtoRevId: 14916,
        aupname: "grandstrandlocal.com/leaderboard-wide"
      }, {
        slotPattern: "grandstrandlocal.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491748,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245247"
          }
        }],
        mtoRevId: 14916,
        aupname: "grandstrandlocal.com/leaderboard"
      }, {
        slotPattern: "frugallysustainable.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491750,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "frugallysustainable.com/rectangle-medium"
      }, {
        slotPattern: "frugallysustainable.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491752,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "frugallysustainable.com/leaderboard-wide"
      }, {
        slotPattern: "frugallysustainable.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491754,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "frugallysustainable.com/billboard"
      }, {
        slotPattern: "dogwheelchairlife.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491756,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245251"
          }
        }],
        mtoRevId: 14916,
        aupname: "dogwheelchairlife.com/rectangle-medium"
      }, {
        slotPattern: "dogwheelchairlife.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491758,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245252"
          }
        }],
        mtoRevId: 14916,
        aupname: "dogwheelchairlife.com/leaderboard"
      }, {
        slotPattern: "dogwheelchairlife.com/billboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491760,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245253"
          }
        }],
        mtoRevId: 14916,
        aupname: "dogwheelchairlife.com/billboard-wide"
      }, {
        slotPattern: "dogwheelchairlife.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491762,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245254"
          }
        }],
        mtoRevId: 14916,
        aupname: "dogwheelchairlife.com/billboard"
      }, {
        slotPattern: "crunchybetty.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491764,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "crunchybetty.com/leaderboard-wide"
      }, {
        slotPattern: "crunchybetty.com/billboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491766,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "crunchybetty.com/billboard-wide"
      }, {
        slotPattern: "collegemagazine.com/skyscraper-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491768,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245257"
          }
        }],
        mtoRevId: 14916,
        aupname: "collegemagazine.com/skyscraper-wide"
      }, {
        slotPattern: "collegemagazine.com/skyscraper",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491770,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245258"
          }
        }],
        mtoRevId: 14916,
        aupname: "collegemagazine.com/skyscraper"
      }, {
        slotPattern: "collegemagazine.com/rectangle-medium",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491772,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245178"
          }
        }],
        mtoRevId: 14916,
        aupname: "collegemagazine.com/rectangle-medium"
      }, {
        slotPattern: "collegemagazine.com/leaderboard-wide",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491774,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245259"
          }
        }],
        mtoRevId: 14916,
        aupname: "collegemagazine.com/leaderboard-wide"
      }, {
        slotPattern: "collegemagazine.com/leaderboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491776,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245260"
          }
        }],
        mtoRevId: 14916,
        aupname: "collegemagazine.com/leaderboard"
      }, {
        slotPattern: "collegemagazine.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491778,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245261"
          }
        }],
        mtoRevId: 14916,
        aupname: "collegemagazine.com/billboard"
      }, {
        slotPattern: "bizbudding.com/billboard",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491780,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "bizbudding.com/billboard"
      }, {
        slotPattern: "bizbudding.com",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491782,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "bizbudding.com"
      }, {
        slotPattern: "healingambassadors.com",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491784,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "healingambassadors.com"
      }, {
        slotPattern: "theheelhook.com",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491786,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }],
        mtoRevId: 14916,
        aupname: "theheelhook.com"
      }, {
        slotPattern: ".*",
        bids: [{
          bidder: "rubicon",
          params: {
            siteId: 556630,
            zoneId: 3491788,
            accountId: 26298,
            bidonmultiformat: !1
          }
        }, {
          bidder: "sovrn",
          params: {
            tagid: "1245300"
          }
        }],
        mtoRevId: 14916,
        aupname: ".*"
      }];
      r.ppi && r.ppi.addAdUnitPatterns && r.ppi.addAdUnitPatterns(e), r.rp.addAdunitPatterns(e);
    }), r.que.unshift(function () {
      r.rp.mergeConfig({
        cpmRoundingFunction: Math.floor
      });
    }), r.que.unshift(function () {
      r.rp.mergeConfig({
        allowEarlyBids: !0
      }), r.onEvent("bidResponse", function (e) {
        r.setTargetingForGPTAsync(e.adUnitCode);
      });
    }), r.que.unshift(function () {
      r.rp.mergeConfig({
        mediaTypePriceGranularity: {
          banner: "dense",
          "video-outstream": "dense"
        },
        userSync: {
          syncDelay: 3e3,
          syncEnabled: !0,
          filterSettings: {
            iframe: {
              filter: "include",
              bidders: "*"
            }
          },
          syncsPerBidder: 3
        },
        currency: {
          adServerCurrency: "USD"
        },
        bidderTimeout: 2e3,
        enableSendAllBids: !0,
        useBidCache: !1,
        coppa: !1,
        auctionOptions: {
          secondaryBidders: []
        },
        enableTIDs: !0
      });
    }), r.que.unshift(function () {
      e("./lib/hpbv2.js")(r);
    }), e("./lib/dmHelpers.js")(r);
  }, {
    "./lib/dm-web-vitals/trackWebVitals.js": 3,
    "./lib/dmHelpers.js": 5,
    "./lib/hpbv2.js": 6
  }],
  2: [function (e, t, a) {
    var r = function r(e, t) {
      var a = e.auctionId;
      if (e && Array.isArray(e.adUnits) && e.adUnits.length > 0) return t.trackNewAuction(e), t.takeTime(a, "requestBids", pbjs.rp.latestRequestBids), t.takeTime(a, "auctionInit"), !0;
    };
    var o = {};
    var i = {};
    var n = [];
    t.exports = {
      trackPrebidEvents: function trackPrebidEvents(e) {
        var t = (window.pbjs = window.pbjs || {}, window.pbjs.rp = window.pbjs.rp || {}, window.pbjs.que = window.pbjs.que || [], window.pbjs);
        var a = ["auctionInit", "bidRequested", "noBid", "bidResponse", "auctionEnd", "bidWon"];
        t.que.unshift(function () {
          a.forEach(function (a) {
            t.onEvent(a, function t(i) {
              o[a] = t, "auctionInit" === a ? r(i, e) : e.takeTime(i.auctionId, a);
            });
          });
        });
      },
      trackGamEvents: function trackGamEvents(e) {
        var t = [{
          event: "slotRequested",
          pbaEventName: "gamSlotRequested"
        }, {
          event: "slotResponseReceived",
          pbaEventName: "gamSlotResponseReceived"
        }, {
          event: "slotRenderEnded",
          pbaEventName: "gamSlotRenderEnded"
        }];
        var a = (window.googletag = window.googletag || {}, window.googletag.cmd = window.googletag.cmd || [], window.googletag);
        a.cmd.push(function () {
          t.forEach(function (t) {
            a.pubads().addEventListener(t.event, function a(r) {
              i[t.event] = a;
              var o = e.getAuctions();
              Object.entries(o).forEach(function (_ref) {
                var _ref2 = _slicedToArray(_ref, 2),
                  a = _ref2[0],
                  o = _ref2[1];
                o.divIds.some(function (e) {
                  return function (e, t) {
                    return (e && e.slot && e.slot.getSlotElementId && e.slot.getSlotElementId()) === t;
                  }(r, e);
                }) && e.takeTime(a, t.pbaEventName);
              });
            });
          });
        });
      },
      trackCWVEvents: function trackCWVEvents(e) {
        (function () {
          var e = 0;
          return [{
            eventName: "largest-contentful-paint",
            pbaEventName: "lcp",
            handler: function handler(e) {
              var t = e.getEntries(),
                a = t[t.length - 1];
              return Math.round(a.startTime);
            }
          }, {
            eventName: "first-input",
            pbaEventName: "fid",
            handler: function handler(e) {
              var t = e.getEntries(),
                a = t[t.length - 1];
              return Math.round(100 * (a.processingStart - a.startTime)) / 100;
            }
          }, {
            eventName: "layout-shift",
            pbaEventName: "cls",
            handler: function handler(t) {
              return t.getEntries().forEach(function (t) {
                t.hadRecentInput || (e += t.value);
              }), parseFloat(e.toFixed(6));
            }
          }];
        })().forEach(function (t) {
          var a = new PerformanceObserver(function (a) {
            var r = t.handler(a);
            r && e.setCwvValue(t.pbaEventName, r);
          });
          a.observe({
            type: t.eventName,
            buffered: !0
          }), n.push(a);
        });
      },
      unsubscribeToListeners: function unsubscribeToListeners() {
        n.forEach(function (e) {
          e && "function" == typeof e.disconnect && e.disconnect();
        }), Object.keys(o).forEach(function (e) {
          pbjs.offEvent(e, o[e]);
        }), Object.keys(i).forEach(function (e) {
          googletag.pubads().removeEventListener(e, i[e]);
        }), delete window.pbjs.rp.getDmWebVitals;
      }
    };
  }, {}],
  3: [function (e, t, a) {
    var r = e("../logUtils.js")("DM Web Vitals"),
      o = e("./webVitals");
    t.exports = function (e) {
      if (!function (e) {
        return e < Math.ceil(100 * Math.random()) ? (r.info("Not tracking - Sampled Out"), !1) : (r.info("Initialized"), !0);
      }(e)) return;
      var t = o();
      t.initEventListeners(t), window.pbjs.rp.getDmWebVitals = t.getEventPayload;
    };
  }, {
    "../logUtils.js": 7,
    "./webVitals": 4
  }],
  4: [function (e, t, a) {
    var r = e("../logUtils.js")("DM Web Vitals"),
      _e = e("./events"),
      o = _e.trackPrebidEvents,
      i = _e.trackGamEvents,
      n = _e.trackCWVEvents,
      d = _e.unsubscribeToListeners;
    t.exports = function () {
      var e = Math.round(performance.now());
      var t = {},
        a = {},
        s = !1,
        m = 0;
      setTimeout(function () {
        s = !0, m > 1 && d();
      }, 3e4);
      return {
        setCwvValue: function setCwvValue(e, t) {
          return a[e] = t;
        },
        getEventPayload: function getEventPayload(e, o) {
          if (o && m++, !t.hasOwnProperty(e)) return void r.warn("No data for Auction ID ".concat(e));
          s && m > 1 && setTimeout(d, 0), o && setTimeout(function () {
            return delete t[e];
          }, 0);
          var i = _objectSpread({}, t[e].eventPayload);
          return Object.keys(a).length && (i.coreWebVitals = a), i;
        },
        takeTime: function takeTime(a, o, i) {
          if (!t.hasOwnProperty(a)) return;
          var n = t[a].eventPayload;
          n.timeSincePageLoadMillis || (n.timeSincePageLoadMillis = {
            wrapperLoaded: e
          }), n.timeSincePageLoadMillis[o] || (n.timeSincePageLoadMillis[o] = Math.round(i || performance.now()), r.debug("First ".concat(o, " occured ").concat(n.timeSincePageLoadMillis[o], " after page load")));
        },
        initEventListeners: function initEventListeners(e) {
          o(e), i(e);
          try {
            n(e);
          } catch (e) {
            r.warn("Unable to subscribe to performance observers");
          }
        },
        trackNewAuction: function trackNewAuction(e) {
          var a;
          t[e.auctionId] = {
            divIds: (a = e.adUnits, a.map(function (e) {
              return e.ortb2Imp && e.ortb2Imp.ext && e.ortb2Imp.ext.data && e.ortb2Imp.ext.data.elementid ? Array.isArray(e.ortb2Imp.ext.data.elementid) ? e.ortb2Imp.ext.data.elementid[0] : e.ortb2Imp.ext.data.elementid : e.code;
            })),
            eventPayload: {}
          };
        },
        getAuctions: function getAuctions() {
          return t;
        }
      };
    };
  }, {
    "../logUtils.js": 7,
    "./events": 2
  }],
  5: [function (e, t, a) {
    var _e2 = e("./utils.js"),
      r = _e2.deepAccess,
      o = _e2.isGptDefined,
      i = _e2.mergeDeep,
      n = _e2.isPlainObject;
    var d = e("./logUtils.js")("DM");
    t.exports = function (e) {
      e.rp = e.rp || {}, d.info("GPT was ".concat(o() ? "" : "NOT", " found and ready on the page")), e.rp.setCustomPbAdSlotFunction = function (t) {
        function a(e, a) {
          try {
            var r = window.googletag.pubads().getSlots().filter(function (t) {
              return t.getSlotElementId() === e.code;
            });
            return 0 === r.length ? void d.warn("Could not find gpt slot on page for adServerAdSlot: ".concat(a, " and adUnit: "), e) : 1 === r.length ? t(e, r[0]) : void 0;
          } catch (e) {
            d.error("Error occured trying to run custom slot function: ", e);
          }
        }
        if (e.rp.hasAppliedPrebidSetConfig) {
          var r = e.getConfig("gptPreAuction") || {};
          r.customPreAuction = a, e.setConfig({
            gptPreAuction: r
          });
        } else e.rp.mergeConfig({
          gptPreAuction: {
            customPreAuction: a
          }
        });
      };
      var t = {};
      function a() {
        return Object.assign({}, t);
      }
      e.rp.hasAppliedPrebidSetConfig = !1;
      var s = {};
      e.rp.getConfig = function (e, t) {
        return "function" == typeof t ? (s[e] = s[e] || [], void s[e].push(t)) : e ? r(a(), e) : a();
      }, e.rp.mergeConfig = function (a) {
        n(a) ? e.rp.hasAppliedPrebidSetConfig ? d.warn("Demand Manager Config already applied to prebid - Use pbjs.setConfig") : (t = i(t, a), function (e) {
          Object.entries(s).forEach(function (_ref3) {
            var _ref4 = _slicedToArray(_ref3, 2),
              t = _ref4[0],
              a = _ref4[1];
            e[t] && a.forEach(function (a) {
              return a(e[t]);
            });
          });
        }(a)) : d.error("Demand Manager mergeConfig input must be an object");
      }, e.rp.applyPrebidSetConfig = function () {
        e.rp.hasAppliedPrebidSetConfig ? d.warn("Demand Manager Config already applied to prebid. Skipping") : (d.info("Setting the following Demand Manager Config for Prebid.js: ", JSON.parse(JSON.stringify(t))), e.setConfig(t), e.rp.hasAppliedPrebidSetConfig = !0);
      };
    };
  }, {
    "./logUtils.js": 7,
    "./utils.js": 8
  }],
  6: [function (e, t, a) {
    var _e3 = e("./utils.js"),
      r = _e3.mergeDeep;
    var o = e("./logUtils.js")("DM");
    o.info("Loading"), t.exports = function (e) {
      try {
        var _a2 = function _a(e) {
          return e ? (e ^ 16 * Math.random() >> e / 4).toString(16) : ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, _a2);
        };
        var i = function i(e, t) {
          if (Array.isArray(e)) for (var a = 0; a < e.length; a++) if (t(e[a])) return e[a];
        };
        var n = function n(e) {
          try {
            return JSON.parse(JSON.stringify(e));
          } catch (t) {
            return e;
          }
        };
        var d = function d(e) {
          return Array.isArray(e) && "number" == typeof e[0] && (e = [e]), e;
        };
        var s = function s() {
          var t = e.getConfig("rubicon") || {};
          "dmpbjs" !== t.int_type && (t.int_type = "dmpbjs", e.setConfig({
            rubicon: t
          }));
        };
        var m = function m(e) {
          var t = k(),
            a = e.getSizes(t[0], t[1]);
          if (a) return a.filter(function (t) {
            return "function" == typeof t.getHeight && "function" == typeof t.getWidth || (o.warn('skipping "fluid" ad size for gpt slot:', u(e, !0)), !1);
          }).map(function (e) {
            return [e.getWidth(), e.getHeight()];
          });
        };
        var c = function c(e) {
          return (e.sizes || []).map(function (e) {
            return [e.w, e.h];
          });
        };
        var l = function l(e) {
          var t = e.getTargetingMap(),
            a = {};
          for (var r in t) a[r] = t[r].map(function (e) {
            return e;
          });
          return a;
        };
        var u = function u(e, t) {
          return t ? e.getAdUnitPath() + "&" + e.getSlotElementId() : e.divId ? e.name + "&" + e.divId : e.name;
        };
        var _p = function _p(a, r, i, n, d) {
          var s,
            l = d && (n && d[a.getSlotElementId()] || d[a.name] || d.__global__) || n && m(a) || c(a);
          if (l && l.length) s = l, l = b(l = Array.isArray(s) ? s.map(function (e) {
            return Array.isArray(e) && t[e.join("x")] || e;
          }) : s, r);else {
            if (function (t, a) {
              return e.rp.sizeMappings && (a && e.rp.sizeMappings[t.getSlotElementId()] || !a && e.rp.sizeMappings[t.name] || e.rp.sizeMappings.__global__);
            }(a, n) || !r || !r.length) return o.warn("slot:", u(a, n), "does not have any sizes or sizeMapping sizes defined"), [];
            l = r;
          }
          return l = b(l, i);
        };
        var b = function b(e, t) {
          return t ? function (e, t) {
            return e.filter(function (e) {
              return function (e, t) {
                return t.some(function (t) {
                  return e[0] === t[0] && e[1] === t[1];
                });
              }(e, t);
            });
          }(e, t) : e;
        };
        var f = function f(e, t) {
          return e.hasOwnProperty("mediaTypes") && e.mediaTypes.hasOwnProperty(t);
        };
        var g = function g(t) {
          if (!t.mediaTypes) {
            try {
              t.mediaTypes = JSON.parse(JSON.stringify(e.rp.mtoConfigMap[t.mtoRevId].mediaTypes)), e.rp.nativeThemeMap && function (t) {
                if (t.mediaTypes && t.mediaTypes.nativeTheme && Array.isArray(t.mediaTypes.nativeTheme.nativeThemeRefs)) {
                  var a = JSON.parse(JSON.stringify(t.mediaTypes.nativeTheme));
                  delete a.nativeThemeRefs;
                  var r = t.mediaTypes.nativeTheme.nativeThemeRefs.reduce(function (t, r) {
                    var o = e.rp.nativeThemeMap[r.nativeThemeRevId];
                    if (o) {
                      var i = {
                        isNds: !0,
                        sendTargetingKeys: !1,
                        rendererUrl: o.rendererUrl,
                        ortb: _objectSpread(_objectSpread({}, a), o.ortb)
                      };
                      t.push(i);
                    }
                    return t;
                  }, []);
                  r.length > 0 && (t.mediaTypes["native"] = 1 === r.length ? r[0] : r, delete t.mediaTypes.nativeTheme);
                }
              }(t);
            } catch (e) {
              return o.error("Unable to resolve the mediaTypes for adUnitPattern:", t.aupname, e), !1;
            }
            delete t.mtoRevId;
          }
          return !0;
        };
        var I = function I(t, a, r, s, b) {
          return i(a, function (a) {
            var i = !1,
              I = r ? t.getAdUnitPath() : t.name,
              v = r ? t.getSlotElementId() : b ? t.divId : "",
              h = !a.slotPattern || a.slotPattern.test(I),
              y = !a.divPattern || a.divPattern.test(v),
              w = k();
            if ((i = h && y) && (i = g(a)), i && j) try {
              (i = j({
                gptSlot: t,
                adUnitPattern: a
              })) || o.warn("adUnitPattern:", a.aupname, "did not match slot:", u(t, r), "because filtered out by custom mapping function\ncustom mapping params:", a.customMappingParams, "ad server targeting:", r ? l(t) : {}, "ortb2Imp:", t.ortb2Imp || {});
            } catch (e) {
              o.warn("custom mapping function error:", e);
            }
            if (i && f(a, "native")) {
              var P = a.mediaTypes["native"].isNds || Array.isArray(a.mediaTypes["native"]) && a.mediaTypes["native"].length && a.mediaTypes["native"][0].isNds;
              if (i = function (e, t, a) {
                if (a) return !0;
                var r = k(),
                  o = t && e.getSizes(r[0], r[1]) || e.sizes;
                return !!o && function (e, t) {
                  return e.some(function (e) {
                    var a = t ? "function" == typeof e.getWidth : e && "number" == typeof e.w,
                      r = t ? "function" == typeof e.getHeight : e && "number" == typeof e.h;
                    return t ? !a || !r : "fluid" === e;
                  });
                }(o, t);
              }(t, r, P), !i) return o.warn("adUnitPattern:", a.aupname, 'excluded because "fluid" size not found for native slot:', u(t, r), "\non-page sizes:", r ? t.getSizes() : c(t)), !1;
            }
            if (i) {
              if (f(a, "banner") && (a.filteredSizes = _p(t, function (e) {
                return e && e.mediaTypes && e.mediaTypes.banner && e.mediaTypes.banner.responsiveSizes ? R(e.mediaTypes.banner.responsiveSizes, k()) : d(e && e.mediaTypes && e.mediaTypes.banner && e.mediaTypes.banner.sizes);
              }(a), d(e.rp.sizes), r, s), 0 === a.filteredSizes.length)) return o.warn("adUnitPattern:", a.aupname, "did not match slot:", u(t, r), "because all slot sizes filtered out for viewport:", w[0] + "x" + w[1], "\nDM expected sizes:", a.mediaTypes.banner.responsiveSizes ? R(a.mediaTypes.banner.responsiveSizes, w) : n(a.mediaTypes.banner.sizes), "on-page sizes:", r ? t.getSizes(w[0], w[1]) : n(c(t))), !1;
              o.debug("adUnitPattern/slot match found for adUnitPattern:", a.aupname, "slot:", u(t, r), "\nDM adUnitPattern Object:", n(a), "\nslot Object:", function (e, t) {
                return {
                  name: u(e, t),
                  sizes: t ? m(e) : c(e),
                  targeting: t ? l(e) : e.ortb2Imp || {}
                };
              }(t, r));
            }
            return i;
          });
        };
        var v = function v(e, t) {
          e.ortb2Imp = e.ortb2Imp || {}, t && t.hasOwnProperty("ortb2Imp") && (e.ortb2Imp = r(e.ortb2Imp, t.ortb2Imp)), e.ortb2Imp.ext = e.ortb2Imp.ext || {}, e.ortb2Imp.ext.data = e.ortb2Imp.ext.data || {}, e.ortb2Imp.ext.data.aupname = e.aupname;
        };
        var h = function h(e, t, r, i) {
          if (t) try {
            var n = JSON.parse(JSON.stringify(t));
            return n.code = r ? e.getSlotElementId() : e.divId || e.name, n.mediaTypes && n.mediaTypes.banner && (n.mediaTypes.banner.sizes = n.filteredSizes), n.bids.forEach(function (e) {
              _P(e, i);
            }), n.transactionId || (n.transactionId = _a2()), v(n, e), delete n.filteredSizes, delete n.responsiveSizes, delete n.slotPattern, delete n.divPattern, delete n.aupname, n;
          } catch (e) {
            o.error("error parsing adUnit:", e);
          } else o.warn("createAdUnit: no adUnitPattern found for slot:", u(e, r));
        };
        var _P = function P(e, t, a) {
          var r = null == e[a] ? e : e[a];
          !a && 0 !== a || "string" != typeof r ? "object" == _typeof(r) && (Array.isArray(r) ? r.forEach(function (e, a) {
            _P(r, t, a);
          }) : Object.keys(r).forEach(function (e) {
            _P(r, t, e);
          })) : function (e, t, a, r) {
            if (y.lastIndex = 0, !y.test(r)) return;
            var o = w.exec(r);
            if (o) return void (t && t.hasOwnProperty(o[1]) ? e[a] = t[o[1]] : delete e[a]);
            var i = r.replace(y, function (e, a) {
              return "object" == _typeof(t[a]) ? JSON.stringify(t[a]) : void 0 === t[a] ? "" : t[a];
            });
            e[a] = i || "";
          }(e, t, a, r);
        };
        var z = function z(e) {
          return "object" == _typeof(e) ? JSON.parse(JSON.stringify(e)) : e;
        };
        var R = function R(e, t) {
          var a;
          try {
            a = i(e.sort(S), function (e) {
              return t[0] >= e.minViewPort[0] && t[1] >= e.minViewPort[1];
            }).sizes;
          } catch (t) {
            o.error("error parsing sizeMappings:", e, t);
          }
          return a;
        };
        var S = function S(e, t) {
          var a = e.minViewPort,
            r = t.minViewPort;
          return r[0] * r[1] - a[0] * a[1] || r[0] - a[0] || r[1] - a[1];
        };
        var k = function k() {
          return [window.innerWidth, window.innerHeight];
        };
        var T = function T(e, t, a, r, i) {
          if (a) try {
            var n = JSON.parse(JSON.stringify(a));
            n.mediaTypes || (n.mediaTypes = {}), n.mediaTypes.video || (n.mediaTypes.video = {}), n.mediaTypes.video.playerSize = t, n.code = e;
            var d = z(r);
            return n.bids.forEach(function (e) {
              _P(e, function (e, t) {
                if (e && "rubicon" === t) {
                  var a = JSON.parse(JSON.stringify(e));
                  return ["inventory", "visitor"].forEach(function (e) {
                    "object" == _typeof(a[e]) && Object.keys(a[e]).forEach(function (t) {
                      var r = a[e][t];
                      "string" != typeof r && "number" != typeof r || (a[e][t] = [r]);
                    });
                  }), a;
                }
                return e;
              }(d, e.bidder));
            }), v(n), delete n.slotPattern, delete n.divPattern, delete n.filteredSizes, delete n.aupname, n;
          } catch (e) {
            o.error("error parsing video adUnit", e);
          } else o.warn("createVideoAdUnit: no adUnitPattern found for slot:", e);
        };
        var t = {
          "300x251": [300, 250],
          "300x252": [300, 250],
          "300x601": [300, 600],
          "300x602": [300, 600],
          "160x601": [160, 600],
          "728x91": [728, 90],
          "728x92": [728, 90],
          "970x91": [970, 90]
        };
        var y = /##data\.(.+?)##/g,
          w = /^##data\.([^#\s]+)##$/;
        e.rp = e.rp || {}, e.rp.featuresUsed = {
          wrapper: {},
          page: {}
        };
        var U = function U(t) {
            try {
              var _a3 = e.rp.wrapperLoaded ? "page" : "wrapper";
              e.rp.featuresUsed[_a3][t] = e.rp.featuresUsed[_a3][t] || 0, e.rp.featuresUsed[_a3][t] += 1;
            } catch (e) {
              o.warn("Unable to log feature ".concat(t, ": "), e);
            }
          },
          A = function A() {
            e.rp.sizes && U("rp.sizes"), e.rp.hasCustomCmp && U("rp.hasCustomCmp"), e.rp.sizeMappings && Object.keys(e.rp.sizeMappings).length && U("rp.sizeMappings");
          };
        var j;
        e.rp.adUnitPatterns = [], e.rp.addAdunitPatterns = function (t) {
          U("addAdunitPatterns"), o.debug("addAdUnitPatterns:", t), e.rp.adUnitPatterns = e.rp.adUnitPatterns.concat(function (e) {
            return e.filter(function (e) {
              if (void 0 !== e.slotPattern) try {
                e.slotPattern = new RegExp(e.slotPattern, "i");
              } catch (t) {
                return o.error("error converting slot pattern: ('" + e.slotPattern + "'); adUnitPattern excluded"), !1;
              }
              if (void 0 !== e.divPattern) try {
                e.divPattern = new RegExp(e.divPattern, "i");
              } catch (t) {
                return o.error("error converting div pattern: ('" + e.divPattern + "'); adUnitPattern excluded"), !1;
              }
              return !0;
            });
          }(t));
        }, e.rp.requestBids = function (t) {
          var a = "function" == typeof t.callback;
          U("requestBids"), Object.keys(t).forEach(function (e) {
            return U("requestBids-".concat(e));
          }), U("callback" + (a ? "Used" : "NotUsed")), A(), o.info("requestBids called with config:", t), o.info('turn on "All Levels" logging in the console to see more detailed logs');
          var r = t.hasOwnProperty("gptSlotObjects") || !t.slotMap && "undefined" != typeof googletag,
            i = Array.isArray(t.slotMap) && t.slotMap || r && (t.gptSlotObjects || googletag.pubads().getSlots()) || [];
          r ? (U("gptUsed"), U("" + (t.gptSlotObjects ? "slotsPassed" : "slotsNotPassed"))) : U("slotMapUsed"), e.rp.addSizeMappings(t.sizeMappings, !0);
          var n = function (e, t) {
              try {
                e = e || {};
                var a = {};
                for (var r in e) if (e.hasOwnProperty(r)) {
                  var i = R(e[r], t);
                  i && (a[r] = i);
                }
              } catch (e) {
                o.error("error getting all sizeMapping sizes:", e);
              }
              return a;
            }(e.rp.sizeMappings, k()),
            d = !0 === t.divPatternMatching && !r;
          !r && d && U("divPatternMatching");
          var m = function (e, t, a, r, o, i) {
            var n = z(r);
            return e.reduce(function (e, r) {
              var d = I(r, t, a, o, i);
              if (d && d.mediaTypes && Array.isArray(d.mediaTypes["native"])) {
                for (var s = JSON.parse(JSON.stringify(d.mediaTypes)), m = 0; m < s["native"].length; m++) d.mediaTypes["native"] = s["native"][m], e.push(h(r, d, a, n)), d.mediaTypes = {};
                d.mediaTypes = s;
              } else e.push(h(r, d, a, n));
              return e;
            }, []).filter(function (e) {
              return e;
            });
          }(i, e.rp.adUnitPatterns, r, t.data, n, d);
          if (o.debug("requestBids adUnits:", m), m.length) {
            s();
            var c = "boolean" == typeof t.setTargeting ? t.setTargeting : -1 === ("" + t.callback).indexOf("setTargetingForGPTAsync");
            if (U("dm".concat(c ? "" : "Not", "CallSetTargeting")), e.getConfig("sizeConfig") && U("pbjsSizeConfig"), function (t) {
              e.rp.bt && e.rp.bt.loaded && (e.rp.bt.adUnits = e.rp.bt.adUnits || [], t.forEach(function (t) {
                var a = e.rp.bt.adUnits.findIndex(function (e) {
                  return e.code === t.code;
                });
                -1 !== a ? e.rp.bt.adUnits[a] = t : e.rp.bt.adUnits.push(t);
              }));
            }(m), e.rp.useBt) return void o.info("requestBids: skipping Magnite auction because BlockThrough has been loaded");
            e.rp.latestRequestBids = performance.now(), e.requestBids({
              bidsBackHandler: function bidsBackHandler(n, d, s) {
                "function" == typeof e.rp.drCallback && (o.info("Executing direct render module"), i = e.rp.drCallback({
                  isGptSlot: r,
                  slots: i
                })), r ? (c && e.setTargetingForGPTAsync(m.map(function (e) {
                  return e.code;
                })), a ? (o.debug("bidsBackHandler execute callback"), t.callback(i)) : (o.debug("callback undefined, refresh gpt slots"), googletag.pubads().refresh(i))) : a ? (o.debug("bidsBackHandler execute callback"), t.callback(n, d, s)) : o.debug("callback undefined");
              },
              adUnits: m
            });
          } else o.debug("requestBids cancelled: no adUnits available for auction"), a ? t.callback(r ? i : {}) : r && (o.debug("refresh gpt slots"), googletag.pubads().refresh(i));
        }, e.rp.addSizeMappings = function (t, a) {
          for (var r in !a && U("addSizeMappings"), t = t || {}, e.rp.sizeMappings = e.rp.sizeMappings || {}, t) t.hasOwnProperty(r) && (e.rp.sizeMappings[r] = t[r]);
        }, e.rp.requestVideoBids = function (t) {
          if (U("requestVideoBids"), A(), Object.keys(t).forEach(function (e) {
            return U("requestVideoBids-".concat(e));
          }), "string" == typeof t.adSlotName) {
            if ("function" == typeof t.callback) {
              if (Array.isArray(t.playerSize)) {
                t.adServer = t.adServer || "gam";
                var a,
                  r,
                  n = T(t.adSlotName, t.playerSize, (a = t.adSlotName, r = t.playerSize, i(e.rp.adUnitPatterns, function (t) {
                    var i = !1;
                    if (g(t), function (e) {
                      return "video" === e.mediaType || "object" == _typeof(e.mediaTypes) && e.mediaTypes.hasOwnProperty("video");
                    }(t) && void 0 !== t.slotPattern && (i = t.slotPattern.test(a)), i && j) try {
                      (i = j({
                        gptSlot: a,
                        adUnitPattern: t
                      })) || o.warn("adUnitPattern:", t.aupname, "did not match slot:", a, "because filtered out by custom mapping function\ncustom mapping params:", t.customMappingParams);
                    } catch (e) {
                      o.warn("custom mapping function error:", e);
                    }
                    return !!i && (function (e, t, a) {
                      var r = !0;
                      return a && (r = a.some(function (t) {
                        return e[0] === t[0] && e[1] === t[1];
                      })), r && t && (r = t.some(function (t) {
                        return e[0] === t[0] && e[1] === t[1];
                      })), r;
                    }(r, [t.mediaTypes.video.playerSize], e.rp.sizes) ? (o.debug("adUnitPattern/video match found for adUnitPattern:", t, "slot:", a), !0) : (o.warn("adUnitPattern:", t.aupname, "did not match slot:", a, "because all video slot sizes filtered out", "\nDM expected sizes:", t.mediaTypes.video.playerSize, "on-page sizes:", r), !1));
                  })), t.data, t.adServer);
                if (n) {
                  if (s(), e.rp.latestRequestBids = performance.now(), e.rp.useBt) return void o.info("requestBids: skipping Magnite requestVideoBids because BlockThrough has been loaded");
                  e.requestBids({
                    adUnits: [n],
                    bidsBackHandler: function bidsBackHandler(a, r, o) {
                      if ("gam" === t.adServer) {
                        var i = {
                            adTagUrl: void 0,
                            vastUrl: void 0
                          },
                          d = t.adSlotName;
                        if (a && a[d] && Array.isArray(a[d].bids) && a[d].bids.length) {
                          i.adTagUrl = e.adServers.dfp.buildVideoUrl({
                            adUnit: n,
                            params: {
                              iu: t.adSlotName
                            }
                          });
                          var s = e.getHighestCpmBids(t.adSlotName);
                          i.vastUrl = void 0 !== s[0] ? s[0].vastUrl : void 0;
                        }
                        t.callback(i, a);
                      } else t.callback(a, r, o);
                    }
                  });
                } else t.callback();
              } else o.error("requestVideoBids called without playerSize");
            } else o.error("requestVideoBids called without a callback");
          } else o.error("requestVideoBids called without adSlotName");
        }, e.rp.setCustomMappingFunction = function (e) {
          U("setCustomMappingFunction"), j = e;
        }, o.info("Ready");
      } catch (e) {
        window.console && console.error && "function" == typeof console.error && console.error(e);
      }
    };
  }, {
    "./logUtils.js": 7,
    "./utils.js": 8
  }],
  7: [function (e, t, a) {
    var r = window && window.location && window.location.href && window.location.href.indexOf("pbjs_debug=true") > -1;
    function o(e, t) {
      return e = [].slice.call(e), t && e.unshift(t), e.unshift("display: inline-block; color: #fff; background: #4dc33b; padding: 1px 4px; border-radius: 3px;"), e.unshift("%cPrebid-DM"), e;
    }
    t.exports = function () {
      var e = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "";
      for (var t = ["debug", "info", "warn", "error"], a = {}, i = 0; i < t.length; i++) {
        var n = t[i];
        a[n] = function (t) {
          return function () {
            var a = window && window.pbjs && window.pbjs.logging,
              i = window && window.pbjs && "function" == typeof window.pbjs.getConfig && window.pbjs.getConfig("debug");
            if (r || a || i) try {
              var n = "".concat(t.toUpperCase(), ":").concat(e ? " ".concat(e) : "");
              window.console[t].apply(window.console, o(arguments, n));
            } catch (e) {}
          };
        }(n);
      }
      return a;
    };
  }, {}],
  8: [function (e, t, a) {
    function r(e) {
      return "[object Object]" === toString.call(e);
    }
    function o(e, t) {
      var _ref5 = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {},
        _ref5$checkTypes = _ref5.checkTypes,
        a = _ref5$checkTypes === void 0 ? !1 : _ref5$checkTypes;
      if (e === t) return !0;
      if ("object" != _typeof(e) || null === e || "object" != _typeof(t) || null === t || a && e.constructor !== t.constructor) return !1;
      if (Object.keys(e).length !== Object.keys(t).length) return !1;
      for (var _r in e) {
        if (!t.hasOwnProperty(_r)) return !1;
        if (!o(e[_r], t[_r], {
          checkTypes: a
        })) return !1;
      }
      return !0;
    }
    t.exports = {
      pick: function pick(e, t) {
        return "object" != _typeof(e) ? {} : t.reduce(function (a, r, o) {
          if ("function" == typeof r) return a;
          var i = r,
            n = r.match(/^(.+?)\sas\s(.+?)$/i);
          n && (r = n[1], i = n[2]);
          var d = e[r];
          return "function" == typeof t[o + 1] && (d = t[o + 1](d, a)), void 0 !== d && (a[i] = d), a;
        }, {});
      },
      deepAccess: function deepAccess(e, t) {
        for (t = t.split ? t.split(".") : t, p = 0; p < t.length; p++) e = e ? e[t[p]] : void 0;
        return void 0 === e ? void 0 : e;
      },
      mergeDeep: function e(t) {
        for (var _len = arguments.length, a = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
          a[_key - 1] = arguments[_key];
        }
        if (!a.length) return t;
        var i = a.shift();
        if (r(t) && r(i)) {
          var _loop = function _loop(_a4) {
            r(i[_a4]) ? (t[_a4] || Object.assign(t, _defineProperty({}, _a4, {})), e(t[_a4], i[_a4])) : Array.isArray(i[_a4]) ? t[_a4] ? Array.isArray(t[_a4]) && i[_a4].forEach(function (e) {
              var r = 1;
              for (var _i = 0; _i < t[_a4].length; _i++) if (o(t[_a4][_i], e)) {
                r = 0;
                break;
              }
              r && t[_a4].push(e);
            }) : Object.assign(t, _defineProperty({}, _a4, _toConsumableArray(i[_a4]))) : Object.assign(t, _defineProperty({}, _a4, i[_a4]));
          };
          for (var _a4 in i) {
            _loop(_a4);
          }
        }
        return e.apply(void 0, [t].concat(a));
      },
      isGptDefined: function isGptDefined() {
        return window.googletag && window.googletag.pubads && "function" == typeof window.googletag.pubads().getSlots;
      },
      deepEqual: o,
      isPlainObject: r
    };
  }, {}]
}, {}, [1]);
/******/ })()
;