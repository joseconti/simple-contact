<?php
/**
 * Handles plugin installation tasks such as database schema creation.
 *
 * @package SimpleContact
 * @since 1.0.0
 */

/**
 * Class Simple_Contact_Installer
 *
 * Creates and upgrades the custom contact table.
 *
 * @since 1.0.0
 */
class Simple_Contact_Installer {
	/**
	 * Schema version option key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const OPTION_KEY = 'simple_contact_schema_version';

	/**
	 * Current database schema version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Runs on plugin activation to install or upgrade schema.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate() {
		self::maybe_upgrade_schema();
	}

	/**
	 * Installs or upgrades the database schema when required.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return void
	 */
	public static function maybe_upgrade_schema() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'sc_contacts';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (\n" .
			"\tid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
			"\tname VARCHAR(120) NOT NULL,\n" .
			"\temail VARCHAR(190) NOT NULL,\n" .
			"\tcreated_at DATETIME NOT NULL,\n" .
			"\tconsent_ip VARBINARY(16) NULL,\n" .
			"\tuser_agent VARCHAR(255) NULL,\n" .
			"\tPRIMARY KEY  (id),\n" .
			"\tKEY email (email),\n" .
			"\tKEY created_at (created_at)\n" .
			") {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseSchema,WordPress.DB.DirectDatabaseSchema.SchemaChange -- Schema creation handled via dbDelta is expected during installation.
		dbDelta( $sql );

		update_option( self::OPTION_KEY, self::VERSION );
	}

	/**
	 * Cleans up data during uninstall.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sc_contacts';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		maybe_drop_table( $table_name, "DROP TABLE IF EXISTS {$table_name}" );

		delete_option( self::OPTION_KEY );
	}
}
