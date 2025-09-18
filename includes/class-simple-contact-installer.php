<?php
/**
 * Handles plugin installation tasks such as database schema creation.
 *
 * @package SimpleContact
 * @since 1.0.0
 * @author Codex
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

		$table_name       = $wpdb->prefix . 'sc_contacts';
		$installed        = get_option( self::OPTION_KEY, '' );
		$table_already_on = self::table_exists( $table_name );

		if ( $table_already_on && is_string( $installed ) && version_compare( $installed, self::VERSION, '>=' ) ) {
			return;
		}

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

		if ( self::table_exists( $table_name ) ) {
			update_option( self::OPTION_KEY, self::VERSION );
		}
	}

	/**
	 * Determines if the custom contact table already exists.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $table_name Fully qualified table name.
	 *
	 * @return bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		if ( '' === $table_name ) {
			return false;
		}

		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );

		if ( false === $query ) {
			return false;
		}

		$result = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Checking table existence during installation is safe without caching and the statement is prepared above.

		return $table_name === $result;
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
