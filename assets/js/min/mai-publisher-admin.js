/******/ (() => { // webpackBootstrap
/*!******************************************!*\
  !*** ./assets/js/mai-publisher-admin.js ***!
  \******************************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
(function ($) {
  if ('object' !== (typeof acf === "undefined" ? "undefined" : _typeof(acf))) {
    return;
  }
  var postKeys = ['maipub_single_taxonomy'];
  var taxoKeys = ['maipub_single_terms'];

  /**
   * Uses current post types or taxonomy for use in other field queries.
   *
   * @since 0.1.0
   *
   * @return object
   */
  acf.addFilter('select2_ajax_data', function (data, args, $input, field, instance) {
    if ($input && postKeys.includes(data.field_key)) {
      var postField = acf.getFields({
        key: 'maipub_single_types',
        parent: field.$el.parents('.acf-row').parents('.acf-row')
      });
      if (postField) {
        var first = postField.shift();
        var value = first ? first.val() : '';
        data.post_type = value;
      }
    }
    if (field && taxoKeys.includes(data.field_key)) {
      var taxoField = acf.getFields({
        key: 'maipub_single_taxonomy',
        sibling: field.$el
      });
      if (taxoField) {
        var first = taxoField.shift();
        var value = first ? first.val() : '';
        data.taxonomy = value;
      }
    }
    return data;
  });
})(jQuery);
/******/ })()
;