<?php

/**
 * Handles main plugin functionality; activation, deactivation, IP lookup
 * 
 * @author  Rich Conaway
 * 
 */

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

namespace WP_GreyNoise;

use \GreyNoise\GreyNoise;

require_once(WP_GREYNOISE_PLUGIN_DIR.'class-wp-greynoise-admin.php');
require_once(WP_GREYNOISE_PLUGIN_DIR.'GreyNoise/GreyNoise.php');

class WP_GreyNoise
{
    /** @var bool */
	private static $initiated = false;

	/**
	 * Initialization check function
	 */
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

		// ip lookup / logging
		add_action('wp_loaded', ['\WP_GreyNoise\WP_GreyNoise', 'logIpAddress']);

		// cron purge 
		// special interval for testing
		add_filter('cron_schedules', ['\WP_GreyNoise\WP_GreyNoise', 'addCronInterval']);
		add_action('wpg_cron_hook', ['\WP_GreyNoise\WP_GreyNoise', 'cronPurge']);
		if(!wp_next_scheduled('wpg_cron_hook')){
			wp_schedule_event(time(), 'daily', 'wpg_cron_hook');
		}
	}

	/**
	 * Activation actions
	 */
	public static function pluginActivation()
	{
		// create log table in db
		global $wpdb;

		// get charset collation type
		$charsetCollate = $wpdb->get_charset_collate();

		// define SQL, insert table name & correct collation
		$sql = "CREATE TABLE IF NOT EXISTS `".self::buildTableName()."` (
			`id` bigint(20) unsigned NOT NULL PRIMARY KEY,
			`ip_address` varchar(255) NOT NULL,
			`is_proxy_address` tinyint(1) DEFAULT 0 NOT NULL,
			`seen` tinyint(1) DEFAULT 0 NOT NULL,
			`classification` varchar(255) NULL,
			`cve` longtext NULL,
			`country` varchar(255) NULL,
			`org` varchar(255) NULL,
  			`hits` int(11) DEFAULT 1 NOT NULL,
			`raw_response` longtext NULL,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL
			) $charsetCollate;
		";

		// insert table
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// add index on classification
		$sql = "ALTER TABLE `wp_wpg_ip_log` ADD INDEX `classification_idx` (`classification`);";
		dbDelta($sql);
	}

	/**
	 * Deactivation actions
	 */
	public static function pluginDeactivation()
	{
		// clear settings from db
		delete_option('wpg_api_key');
		delete_option('wpg_is_enable_greynoise');
		delete_option('wpg_cron_purge_days');

		// remove log table from db
		// define SQL
		$sql = "DROP TABLE IF EXISTS ".self::buildTableName().";";

		// drop table; must use $wpdb->query() as DROP not supported
		// by dbDelta()
		global $wpdb;
		$wpdb->query($sql);

		// deregister cron
		$timestamp = wp_next_scheduled('wpg_cron_hook');
		wp_unschedule_event($timestamp, 'wpg_cron_hook');
		remove_filter('cron_schedules', 'addCronInterval');
	}

	/**
	 * Helper function to build the db table name
	 * 
	 * @return string
	 */
	public static function buildTableName(): string
	{
		// build table name
		global $wpdb;
		return $wpdb->prefix.WP_GREYNOISE_DB_TABLE_NAME;
	}

	/**
	 * Helper function to convert IP (v4) address into decimal representation
	 * 
	 * @param string $ipV4Address Dot notated IP v4 address
	 * @return int|NULL
	 */
	protected static function getIpDecimal(string $ipV4Address): ?int
	{
		// convert dotted IP address into array
		$ipArray = explode('.', $ipV4Address);

		if(is_array($ipArray) && count($ipArray) === 4){
			// calc deciml version of IP address
			$decIp = (16777216 * $ipArray[0]) + (65536 * $ipArray[1]) + (256 * $ipArray[2]) + $ipArray[3];
			return $decIp;
		}
		else{
			// invalid IP address
			return NULL;
		}
	}

	/**
	 * Looks up user IP address with GN API, logs result
	 * 
	 * @return bool
	 */
	public static function logIpAddress(): bool
	{
		// check plugin status
		if(WP_GreyNoise_Admin::isGreyNoiseRunning()){
			global $wpdb;
			
			// lookup ip address
			$ipAddress = NULL;
			$isProxy = TRUE;

			if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
				$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			elseif(isset($_SERVER['HTTP_X_FORWARDED']) && !empty($_SERVER['HTTP_X_FORWARDED'])){
				$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			elseif(isset($_SERVER['HTTP_FORWARDED_FOR']) && !empty($_SERVER['HTTP_FORWARDED_FOR'])){
				$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			elseif(isset($_SERVER['HTTP_FORWARDED']) && !empty($_SERVER['HTTP_FORWARDED'])){
				$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else{
				$ipAddress = $_SERVER['REMOTE_ADDR'];
				$isProxy = FALSE;
			}

			// allow testing IP from URL
			if(isset($_GET['wpg_ip'])){
				$ipAddress = $_GET['wpg_ip'];
			}

			// exit if not IP or local IP
			if(!$ipAddress || $ipAddress === '127.0.0.1'){
				return false;
			}

			// decimal ip address
			$decIpAddress = self::getIpDecimal($ipAddress);

			// see if ip already in log
			if(self::ipInLog($decIpAddress)){
				// update log count and return
				$wpdb->query(self::prepareUpdateLoggingQuery($decIpAddress));
				return true;
			}

			// IP address doesn't exist, call GN and log
			$gnResponse = self::callGreyNoise($ipAddress);

			// only log if response not null (verbose or malicious)
			if(!is_null($gnResponse)){
				// log IP data
				$wpdb->query(self::prepareInsertLoggingQuery($decIpAddress, $ipAddress, $isProxy, $gnResponse));

				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}

	/**
	 * Check if an IP address already exists in the log
	 * 
	 * @param int $decIpAddress Decimal representation of the IP address
	 * @return bool
	 */
	protected static function ipInLog(int $decIpAddress): bool
	{
		global $wpdb;

		// get table name
		$tableName = self::buildTableName();

		// build query
		$query = $wpdb->prepare(
			"SELECT COUNT(id) from {$tableName} where id = %d",
			[
				$decIpAddress,
			]
		);

		// execute query
		$result = $wpdb->get_var($query);

		if($result == 0){
			return false;
		}
		else{
			return true;
		}
	}

	/**
	 * Make call to GN using the GN class
	 * 
	 * @param string $ipAddress Dot notated Ip address
	 * @return array|NULL
	 */
	protected static function callGreyNoise(string $ipAddress): ?array
	{
		// init GN vars
		$gnVars = [
			'seen' => false,
			'classification' => NULL,
			'cve' => NULL,
			'country' => NULL,
			'org' => NULL,
			'raw_response' => NULL,
		];

		// call GN
		$gn = GreyNoise::getInstance(get_option('wpg_api_key'));

		if(!$gn){
			return NULL;
		}

		$responseArr = $gn->callIpContext($ipAddress);

		// validate the result
		if(!is_null($responseArr)){
			$gnVars = array_merge($gnVars, $responseArr);

			return $gnVars;
		}
		else{
			return NULL;
		}
	}

	/**
	 * Prepare the insert logging query
	 * 
	 * @param int $decIpAddress Decimal representation of the IP address
	 * @param string $ipAddress Dot notated Ip address
	 * @param bool $isProxy Boolean flag of proxy
	 * @param array $gnVars Array containing data from GN lookup
	 * @return string|NULL
	 */
	protected static function prepareInsertLoggingQuery(
		int $decIpAddress,
		string $ipAddress,
		bool $isProxy,
		array $gnVars
	): ?string
	{
		global $wpdb;

		// get table name
		$tableName = self::buildTableName();

		// build log query
		$query = $wpdb->prepare(
			"
				INSERT INTO {$tableName}
				(
					id,
					ip_address,
					is_proxy_address,
					seen,
					classification,
					cve,
					country,
					org,
					raw_response,
					created_at,
					updated_at
				)
				VALUES (
					%d,
					%s,
					%d,
					%s,
					%s,
					%s,
					%s,
					%s,
					%s,
					NOW(),
					NOW()
				);
			",
			[
				$decIpAddress,
				$ipAddress,
				$isProxy,
				$gnVars['seen'],
				$gnVars['classification'],
				$gnVars['cve'],
				$gnVars['country'],
				$gnVars['org'],
				$gnVars['raw_response'],
			]
		);

		return $query;
	}
	
	/**
	 * Prepare the update logging query
	 * 
	 * @param int $decIpAddress Decimal representation of the IP address
	 * @return string|NULL
	 */
	protected static function prepareUpdateLoggingQuery(int $decIpAddress): ?string
	{
		global $wpdb;

		// get table name
		$tableName = self::buildTableName();

		// build log query
		$query = $wpdb->prepare(
			"
				UPDATE {$tableName}
				SET
					hits = hits + 1,
					updated_at = NOW()
				WHERE
					id = %d
				;
			",
			[
				$decIpAddress,
			]
		);

		return $query;
	}

	/**
	 * Called daily by the WP cron system, deletes all non-malicious log entries whos
	 * last updated value is longer ago than the `wpg_cron_purge_days` plugin setting.
	 */
	public static function cronPurge()
	{
		if(WP_GreyNoise_Admin::isGreyNoiseRunning()){
			global $wpdb;

			// get table name
			$tableName = self::buildTableName();

			// define query
			$query = $wpdb->prepare(
				"
					DELETE
					FROM {$tableName}
					WHERE
						updated_at <= DATE_SUB(NOW(), INTERVAL %d DAY)
						AND classification <> 'malicious'
					;
				",
				[
					get_option('wpg_cron_purge_days', 7),
				]
			);

			// execute
			$wpdb->query($query);	
		}
	}

	/**
	 * Special interval for testing cron jobs
	 * 
	 * @param array $schedules
	 */
	public function addCronInterval(array $schedules){
		$schedules['five_seconds'] = [
			'interval' => 5,
			'display'  => esc_html__('Every Five Seconds'),
		];

		return $schedules;
	}
}
