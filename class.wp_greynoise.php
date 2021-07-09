<?php

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

class WP_GreyNoise
{

	private static $initiated = false;

	public static function init()
	{
		if (!self::$initiated) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks()
	{
		self::$initiated = true;
	}

	/**
	 * Activation actions
	 */
	public static function plugin_activation()
	{
		// create log table in db
		global $wpdb;

		// get charset collation type
		$charsetCollate = $wpdb->get_charset_collate();

		// define SQL, insert table name & correct collation
		$sql = "CREATE TABLE IF NOT EXISTS `".self::buildTableName()."` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`ip_address` varchar(255) NOT NULL,
			`seen` tinyint(1) NOT NULL,
			`classification` varchar(255) NULL,
			`cve` longtext NULL,
			`country` varchar(255) NULL,
			`org` varchar(255) NULL,
			`raw_response` longtext NULL
			) $charsetCollate;
		";

		// insert table
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Deactivation actions
	 */
	public static function plugin_deactivation()
	{
		// clear settings from db
		delete_option('wpg_api_key');
		delete_option('wpg_is_enable_greynoise');
		delete_option('wpg_is_verbose_logging');

		// remove log table from db
		// define SQL
		$sql = "DROP TABLE IF EXISTS ".self::buildTableName().";";

		// drop table; must use $wpdb->query() as DROP not supported
		// by dbDelta()
		global $wpdb;
		$wpdb->query($sql);
	}

	/**
	 * Helper function to build the db table name
	 */
	protected static function buildTableName()
	{
		// build table name
		global $wpdb;
		return $wpdb->prefix.WP_GREYNOISE_DB_TABLE_NAME;
	}
}
