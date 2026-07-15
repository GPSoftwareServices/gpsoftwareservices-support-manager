<?php
/**
 * Database schema updater.
 *
 * The installer contains the canonical schema. dbDelta() safely creates
 * missing tables, columns and indexes without deleting existing data.
 *
 * @package GPSoftwareServicesSupportManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GPSUMA_Updater {
	/** Current database schema version. */
	const DB_VERSION = '5.2.1';

	/**
	 * Synchronize the database schema when the installed version is older.
	 *
	 * @return void
	 */
	public static function update() {
		$installed_version = (string) get_option( 'gpsuma_db_version', '0.0.0' );

		if ( version_compare( $installed_version, self::DB_VERSION, '>=' ) ) {
			return;
		}

		GPSUMA_Installer::install();
	}
}
