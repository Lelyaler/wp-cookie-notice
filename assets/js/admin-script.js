/**
 * WP Cookie Notice Admin Script
 */
(function ($) {
  "use strict";

  $(function () {
    // Инициализация стандартного пикера цветов WordPress
    if ($.fn.wpColorPicker) {
      $(".wp-color-picker-field").wpColorPicker();
    }
  });
})(jQuery);
