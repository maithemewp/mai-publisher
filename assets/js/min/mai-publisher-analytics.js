/******/ (() => { // webpackBootstrap
/*!**********************************************!*\
  !*** ./assets/js/mai-publisher-analytics.js ***!
  \**********************************************/
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
/**
 * Run Matomo instance.
 *
 * @since 0.1.0
 */
(function () {
  var _paq = window._paq = window._paq || [];
  var analytics = maiPubAnalyticsVars.analytics;
  var analyticsPrimary = analytics[0];
  var analyticsMore = analytics.slice(1);

  /**
   * Sets up trackers.
   */
  (function () {
    _paq.push(['setTrackerUrl', analyticsPrimary.url + 'matomo.php']);
    _paq.push(['setSiteId', analyticsPrimary.id]);
    for (var key in analyticsMore) {
      _paq.push(['addTracker', analyticsMore[key].url + 'matomo.php', analyticsMore[key].id]);
    }
    var d = document,
      g = d.createElement('script'),
      s = d.getElementsByTagName('script')[0];
    g.async = true;
    g.src = analyticsPrimary.url + 'matomo.js';
    s.parentNode.insertBefore(g, s);
  })();

  /**
   * Handles all trackers asyncronously.
   */
  window.matomoAsyncInit = function () {
    for (var tracker in analytics) {
      try {
        var matomoTracker = Matomo.getTracker(analytics[tracker].url + 'matomo.php', analytics[tracker].id);

        // Loop through and push items.
        for (var key in analytics[tracker].toPush) {
          var func = analytics[tracker].toPush[key][0];
          var vals = analytics[tracker].toPush[key].slice(1);
          vals = vals ? vals : null;
          if (vals) {
            matomoTracker[func].apply(matomoTracker, _toConsumableArray(vals));
          } else {
            matomoTracker[func]();
          }
        }

        // If we have an ajax url and body, pdate the views.
        if (analytics[tracker].ajaxUrl && analytics[tracker].body) {
          // Send ajax request.
          fetch(analytics[tracker].ajaxUrl, {
            method: "POST",
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'Cache-Control': 'no-cache'
            },
            body: new URLSearchParams(analytics[tracker].body)
          }).then(function (response) {
            if (!response.ok) {
              throw new Error(response.statusText);
            }
            return response.json();
          }).then(function (data) {})["catch"](function (error) {
            console.log(error.name + ', ', error.message);
          });
        }
      } catch (err) {
        console.log(err);
      }
    }
  };
})();
/******/ })()
;