/******/ (() => { // webpackBootstrap
/*!***************************************!*\
  !*** ./src/js/mai-publisher-admin.js ***!
  \***************************************/
(function ($) {
  if ('object' !== typeof acf) {
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
      var $field = $input.parents('.acf-row').find('.acf-field[data-key="maipub_single_taxonomy"]');
      data.taxonomy = $field ? acf.getField($field).val() : '';
    }
    return data;
  });
})(jQuery);
/******/ })()
;
//# sourceMappingURL=mai-publisher-admin.js.map