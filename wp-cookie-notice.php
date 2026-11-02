<?php
/**
 * Plugin Name:       WP Cookie Notice
 * Plugin URI:        https://github.com/Lelyaler/wp-cookie-notice
 * Description:       Легковесный, настраиваемый плагин уведомления о cookie для WordPress. Настройки в админке через Settings API, чистый Vanilla JS без сторонних зависимостей.
 * Version:           1.1.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Valeria Chernyavskaya
 * Author URI:        https://lelyaler.github.io/Portfolio/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-cookie-notice
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_COOKIE_NOTICE_VERSION', '1.1.0' );
define( 'WP_COOKIE_NOTICE_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_COOKIE_NOTICE_URL', plugin_dir_url( __FILE__ ) );

require_once WP_COOKIE_NOTICE_DIR . 'includes/class-wp-cookie-notice.php';

/**
 * Запуск плагина.
 */
function wp_cookie_notice_run() {
	$plugin = new WP_Cookie_Notice();
	$plugin->init();
}
add_action( 'plugins_loaded', 'wp_cookie_notice_run' );

/**
 * Хук активации: установка начальных опций по умолчанию.
 */
register_activation_hook( __FILE__, array( 'WP_Cookie_Notice', 'activate' ) );
