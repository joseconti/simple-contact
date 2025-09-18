<?php
/**
 * Plugin Name: Simple Contact Shortcode & Block
 * Plugin URI: https://example.com/simple-contact
 * Description: Provides a simple contact form via shortcode and Gutenberg block, stores submissions, and notifies the admin.
 * Version: 1.0.0
 * Author: Codex
 * Author URI: https://example.com
 * Text Domain: simple-contact
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package SimpleContact
 */

define( 'SIMPLE_CONTACT_VERSION', '1.0.0' );
define( 'SIMPLE_CONTACT_PLUGIN_FILE', __FILE__ );
define( 'SIMPLE_CONTACT_PATH', plugin_dir_path( SIMPLE_CONTACT_PLUGIN_FILE ) );
define( 'SIMPLE_CONTACT_URL', plugin_dir_url( SIMPLE_CONTACT_PLUGIN_FILE ) );

require_once SIMPLE_CONTACT_PATH . 'includes/class-simple-contact-plugin.php';

// Bootstrap the plugin.
Simple_Contact_Plugin::get_instance();
