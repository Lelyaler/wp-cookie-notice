<?php
/**
 * Основной класс функционала плагина WP Cookie Notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Cookie_Notice {

	/**
	 * Опции плагина.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Инициализация хуков.
	 */
	public function init() {
		$this->options = $this->get_options();

		// Админка
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( WP_COOKIE_NOTICE_DIR . 'wp-cookie-notice.php' ), array( $this, 'add_plugin_action_links' ) );
		}

		// Фронтенд
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
			add_action( 'wp_footer', array( $this, 'render_cookie_notice' ) );
		}
	}

	/**
	 * Начальные настройки при активации плагина.
	 */
	public static function activate() {
		if ( false === get_option( 'wp_cookie_notice_options' ) ) {
			$defaults = self::get_default_options();
			update_option( 'wp_cookie_notice_options', $defaults );
		}
	}

	/**
	 * Значения по умолчанию.
	 *
	 * @return array
	 */
	public static function get_default_options() {
		global $wp_rewrite;
		$privacy_url = ( function_exists( 'get_privacy_policy_url' ) && ! empty( $wp_rewrite ) ) ? get_privacy_policy_url() : '';

		return array(
			'message'            => __( 'Мы используем файлы cookie для улучшения работы сайта и аналитики. Продолжая просмотр сайта, вы соглашаетесь с нашей политикой конфиденциальности.', 'wp-cookie-notice' ),
			'button_text'        => __( 'Принять', 'wp-cookie-notice' ),
			'privacy_text'       => __( 'Политика конфиденциальности', 'wp-cookie-notice' ),
			'privacy_url'        => $privacy_url,
			'position'           => 'bottom',
			'bg_color'           => '#181b20',
			'text_color'         => '#f3f4f6',
			'btn_bg_color'       => '#f14f4f',
			'btn_text_color'     => '#ffffff',
			'cookie_expiry_days' => 30,
		);
	}

	/**
	 * Получение текущих опций с fallback на дефолтные.
	 *
	 * @return array
	 */
	public function get_options() {
		$saved    = get_option( 'wp_cookie_notice_options', array() );
		$defaults = self::get_default_options();

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Регистрация страницы настроек в админке.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Настройки Cookie Notice', 'wp-cookie-notice' ),
			__( 'Cookie Notice', 'wp-cookie-notice' ),
			'manage_options',
			'wp-cookie-notice',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Ссылка на страницу настроек в списке плагинов.
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=wp-cookie-notice' ) ),
			__( 'Настройки', 'wp-cookie-notice' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Регистрация полей через Settings API.
	 */
	public function register_settings() {
		register_setting(
			'wp_cookie_notice_group',
			'wp_cookie_notice_options',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'wp_cookie_notice_main_section',
			__( 'Основные параметры баннера', 'wp-cookie-notice' ),
			null,
			'wp-cookie-notice'
		);

		add_settings_field(
			'message',
			__( 'Текст уведомления', 'wp-cookie-notice' ),
			array( $this, 'render_message_field' ),
			'wp-cookie-notice',
			'wp_cookie_notice_main_section'
		);

		add_settings_field(
			'button_text',
			__( 'Текст кнопки подтверждения', 'wp-cookie-notice' ),
			array( $this, 'render_button_text_field' ),
			'wp-cookie-notice',
			'wp_cookie_notice_main_section'
		);

		add_settings_field(
			'privacy_url',
			__( 'Ссылка на Политику конфиденциальности', 'wp-cookie-notice' ),
			array( $this, 'render_privacy_url_field' ),
			'wp-cookie-notice',
			'wp_cookie_notice_main_section'
		);

		add_settings_field(
			'privacy_text',
			__( 'Текст ссылки политики', 'wp-cookie-notice' ),
			array( $this, 'render_privacy_text_field' ),
			'wp-cookie-notice',
			'wp_cookie_notice_main_section'
		);

		add_settings_section(
			'wp_cookie_notice_design_section',
			__( 'Внешний вид и поведение', 'wp-cookie-notice' ),
			null,
			'wp-cookie-notice'
		);

		add_settings_field(
			'position',
			__( 'Расположение на экране', 'wp-cookie-notice' ),
			array( $this, 'render_position_field' ),
			'wp-cookie-notice',
			'wp_cookie_notice_design_section'
		);

		add_settings_field(
			'colors',
			__( 'Цветовая схема', 'wp-cookie-notice' ),
			array( $this, 'render_colors_field' ),
			'wp-cookie-notice',
			'wp_cookie_notice_design_section'
		);

		add_settings_field(
			'cookie_expiry_days',
			__( 'Срок хранения согласия (дни)', 'wp-cookie-notice' ),
			array( $this, 'render_expiry_field' ),
			'wp-cookie-notice',
			'wp_cookie_notice_design_section'
		);
	}

	/**
	 * Санитизация данных при сохранении.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();

		$sanitized['message']            = isset( $input['message'] ) ? sanitize_textarea_field( $input['message'] ) : '';
		$sanitized['button_text']        = isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : '';
		$sanitized['privacy_text']       = isset( $input['privacy_text'] ) ? sanitize_text_field( $input['privacy_text'] ) : '';
		$sanitized['privacy_url']        = isset( $input['privacy_url'] ) ? esc_url_raw( $input['privacy_url'] ) : '';
		$sanitized['position']           = ( isset( $input['position'] ) && 'top' === $input['position'] ) ? 'top' : 'bottom';
		$sanitized['bg_color']           = isset( $input['bg_color'] ) ? sanitize_hex_color( $input['bg_color'] ) : '#181b20';
		$sanitized['text_color']         = isset( $input['text_color'] ) ? sanitize_hex_color( $input['text_color'] ) : '#f3f4f6';
		$sanitized['btn_bg_color']       = isset( $input['btn_bg_color'] ) ? sanitize_hex_color( $input['btn_bg_color'] ) : '#f14f4f';
		$sanitized['btn_text_color']     = isset( $input['btn_text_color'] ) ? sanitize_hex_color( $input['btn_text_color'] ) : '#ffffff';
		$sanitized['cookie_expiry_days'] = isset( $input['cookie_expiry_days'] ) ? max( 1, absint( $input['cookie_expiry_days'] ) ) : 30;

		return $sanitized;
	}

	/* Рендеринг полей админки */

	public function render_message_field() {
		echo '<textarea name="wp_cookie_notice_options[message]" rows="3" class="large-text code">' . esc_textarea( $this->options['message'] ) . '</textarea>';
	}

	public function render_button_text_field() {
		echo '<input type="text" name="wp_cookie_notice_options[button_text]" value="' . esc_attr( $this->options['button_text'] ) . '" class="regular-text" />';
	}

	public function render_privacy_url_field() {
		echo '<input type="url" name="wp_cookie_notice_options[privacy_url]" value="' . esc_attr( $this->options['privacy_url'] ) . '" class="large-text" placeholder="https://example.com/privacy-policy" />';
		echo '<p class="description">' . esc_html__( 'Оставьте пустым, если ссылка не требуется.', 'wp-cookie-notice' ) . '</p>';
	}

	public function render_privacy_text_field() {
		echo '<input type="text" name="wp_cookie_notice_options[privacy_text]" value="' . esc_attr( $this->options['privacy_text'] ) . '" class="regular-text" />';
	}

	public function render_position_field() {
		$position = $this->options['position'];
		?>
		<select name="wp_cookie_notice_options[position]">
			<option value="bottom" <?php selected( $position, 'bottom' ); ?>><?php esc_html_e( 'Внизу экрана (Bottom fixed)', 'wp-cookie-notice' ); ?></option>
			<option value="top" <?php selected( $position, 'top' ); ?>><?php esc_html_e( 'Вверху экрана (Top fixed)', 'wp-cookie-notice' ); ?></option>
		</select>
		<?php
	}

	public function render_colors_field() {
		?>
		<div class="wp-cookie-colors-grid">
			<label>
				<span><?php esc_html_e( 'Фон баннера:', 'wp-cookie-notice' ); ?></span><br>
				<input type="text" name="wp_cookie_notice_options[bg_color]" value="<?php echo esc_attr( $this->options['bg_color'] ); ?>" class="wp-color-picker-field" data-default-color="#181b20">
			</label>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<label>
				<span><?php esc_html_e( 'Цвет текста:', 'wp-cookie-notice' ); ?></span><br>
				<input type="text" name="wp_cookie_notice_options[text_color]" value="<?php echo esc_attr( $this->options['text_color'] ); ?>" class="wp-color-picker-field" data-default-color="#f3f4f6">
			</label>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<label>
				<span><?php esc_html_e( 'Фон кнопки:', 'wp-cookie-notice' ); ?></span><br>
				<input type="text" name="wp_cookie_notice_options[btn_bg_color]" value="<?php echo esc_attr( $this->options['btn_bg_color'] ); ?>" class="wp-color-picker-field" data-default-color="#f14f4f">
			</label>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<label>
				<span><?php esc_html_e( 'Текст кнопки:', 'wp-cookie-notice' ); ?></span><br>
				<input type="text" name="wp_cookie_notice_options[btn_text_color]" value="<?php echo esc_attr( $this->options['btn_text_color'] ); ?>" class="wp-color-picker-field" data-default-color="#ffffff">
			</label>
		</div>
		<?php
	}

	public function render_expiry_field() {
		echo '<input type="number" min="1" max="365" name="wp_cookie_notice_options[cookie_expiry_days]" value="' . esc_attr( $this->options['cookie_expiry_days'] ) . '" class="small-text" /> ' . esc_html__( 'дней', 'wp-cookie-notice' );
	}

	/**
	 * Рендеринг страницы настроек в админке.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wp_cookie_notice_group' );
				do_settings_sections( 'wp-cookie-notice' );
				submit_button( __( 'Сохранить настройки', 'wp-cookie-notice' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Подключение скриптов и стилей в админке (Color Picker).
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_wp-cookie-notice' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'wp-cookie-notice-admin',
			WP_COOKIE_NOTICE_URL . 'assets/js/admin-script.js',
			array( 'wp-color-picker', 'jquery' ),
			WP_COOKIE_NOTICE_VERSION,
			true
		);
	}

	/**
	 * Подключение ассетов на фронтенде.
	 */
	public function enqueue_public_assets() {
		wp_enqueue_style(
			'wp-cookie-notice',
			WP_COOKIE_NOTICE_URL . 'assets/css/cookie-notice.css',
			array(),
			WP_COOKIE_NOTICE_VERSION
		);

		// Инлайн-стили цветов из настроек
		$custom_css = sprintf(
			'#wp-cookie-notice-banner { background-color: %s; color: %s; }
			 #wp-cookie-notice-banner .wp-cookie-notice-btn { background-color: %s; color: %s; }',
			esc_attr( $this->options['bg_color'] ),
			esc_attr( $this->options['text_color'] ),
			esc_attr( $this->options['btn_bg_color'] ),
			esc_attr( $this->options['btn_text_color'] )
		);
		wp_add_inline_style( 'wp-cookie-notice', $custom_css );

		wp_enqueue_script(
			'wp-cookie-notice',
			WP_COOKIE_NOTICE_URL . 'assets/js/cookie-notice.js',
			array(),
			WP_COOKIE_NOTICE_VERSION,
			true
		);

		wp_localize_script(
			'wp-cookie-notice',
			'wpCookieNoticeConfig',
			array(
				'expiryDays' => (int) $this->options['cookie_expiry_days'],
				'cookieName' => 'wp_cookie_consent_accepted',
			)
		);
	}

	/**
	 * Вывод разметки баннера в подвале сайта.
	 */
	public function render_cookie_notice() {
		$position_class = ( 'top' === $this->options['position'] ) ? 'position-top' : 'position-bottom';

		// Предоставляем фильтр для расширения другими разработчиками
		$should_render = apply_filters( 'wp_cookie_notice_should_render', true );
		if ( ! $should_render ) {
			return;
		}
		?>
		<div id="wp-cookie-notice-banner" class="wp-cookie-notice-banner <?php echo esc_attr( $position_class ); ?>" role="region" aria-label="<?php esc_attr_e( 'Уведомление об использовании cookie', 'wp-cookie-notice' ); ?>" style="display: none;">
			<div class="wp-cookie-notice-container">
				<div class="wp-cookie-notice-text">
					<?php echo esc_html( $this->options['message'] ); ?>
					<?php if ( ! empty( $this->options['privacy_url'] ) && ! empty( $this->options['privacy_text'] ) ) : ?>
						<a href="<?php echo esc_url( $this->options['privacy_url'] ); ?>" class="wp-cookie-notice-privacy-link" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $this->options['privacy_text'] ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="wp-cookie-notice-actions">
					<button type="button" id="wp-cookie-notice-accept-btn" class="wp-cookie-notice-btn">
						<?php echo esc_html( $this->options['button_text'] ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
		do_action( 'wp_cookie_notice_after_banner' );
	}
}
