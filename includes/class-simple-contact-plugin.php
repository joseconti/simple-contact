<?php
/**
 * Main plugin bootstrap file.
 *
 * @package SimpleContact
 * @since 1.0.0
 * @author Codex
 */

/**
 * Class Simple_Contact_Plugin
 *
 * Boots the plugin and registers core hooks.
 *
 * @since 1.0.0
 */
class Simple_Contact_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Simple_Contact_Plugin
	 */
	private static $instance;

	/**
	 * Retrieves the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Simple_Contact_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Registers hooks required for the plugin to operate.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Loads the PHP dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once SIMPLE_CONTACT_PATH . 'includes/simple-contact-helpers.php';
		require_once SIMPLE_CONTACT_PATH . 'includes/class-simple-contact-installer.php';
		require_once SIMPLE_CONTACT_PATH . 'includes/class-simple-contact-form.php';
		require_once SIMPLE_CONTACT_PATH . 'includes/class-simple-contact-notification.php';
		require_once SIMPLE_CONTACT_PATH . 'includes/class-simple-contact-form-handler.php';
		require_once SIMPLE_CONTACT_PATH . 'includes/class-simple-contact-shortcode.php';
		require_once SIMPLE_CONTACT_PATH . 'includes/class-simple-contact-block.php';
	}

	/**
	 * Loads the plugin text domain for translations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'simple-contact', false, dirname( plugin_basename( SIMPLE_CONTACT_PLUGIN_FILE ) ) . '/languages' );
	}
}
