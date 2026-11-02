<?php
/**
 * Вызывается при удалении плагина через панель WordPress.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wp_cookie_notice_options' );
