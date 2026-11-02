/**
 * WP Cookie Notice Frontend Script (Vanilla JS)
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var banner = document.getElementById("wp-cookie-notice-banner");
    var acceptBtn = document.getElementById("wp-cookie-notice-accept-btn");

    if (!banner || !acceptBtn) {
      return;
    }

    var config = window.wpCookieNoticeConfig || {
      cookieName: "wp_cookie_consent_accepted",
      expiryDays: 30,
    };

    function hasConsent() {
      // Проверка localStorage
      try {
        if (localStorage.getItem(config.cookieName) === "1") {
          return true;
        }
      } catch (e) {
        // Fallback если localStorage заблокирован
      }

      // Проверка cookies
      var nameEQ = config.cookieName + "=";
      var ca = document.cookie.split(";");
      for (var i = 0; i < ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(nameEQ) === 0) {
          return true;
        }
      }
      return false;
    }

    function setConsent() {
      // Запись в localStorage
      try {
        localStorage.setItem(config.cookieName, "1");
      } catch (e) {}

      // Запись в document.cookie
      var d = new Date();
      d.setTime(d.getTime() + config.expiryDays * 24 * 60 * 60 * 1000);
      var expires = "expires=" + d.toUTCString();
      document.cookie =
        config.cookieName +
        "=1;" +
        expires +
        ";path=/;SameSite=Lax";
    }

    // Если согласия еще нет — показываем баннер
    if (!hasConsent()) {
      banner.style.display = "block";
      // Небольшая задержка для плавного срабатывания CSS-перехода
      requestAnimationFrame(function () {
        banner.classList.add("is-visible");
      });
    }

    // Обработка клика по кнопке согласия
    acceptBtn.addEventListener("click", function () {
      setConsent();
      banner.classList.remove("is-visible");

      setTimeout(function () {
        banner.style.display = "none";
      }, 300);
    });
  });
})();
