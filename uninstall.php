<?php
/**
 * Handles plugin data cleanup on uninstall.
 *
 * @package SimpleContact
 * @since 1.0.0
 * @author Codex
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-simple-contact-installer.php';

Simple_Contact_Installer::uninstall();
