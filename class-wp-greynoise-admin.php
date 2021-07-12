<?php

/**
 * Handles plugin admin functionality; settings, etc.
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

require_once(WP_GREYNOISE_PLUGIN_DIR.'class-wp-greynoise-dashboard.php');
require_once(WP_GREYNOISE_PLUGIN_DIR.'GreyNoise/GreyNoise.php');

class WP_GreyNoise_Admin
{
	/** @var bool */
	private static $initiated = false;

	/**
	 * Initialization check function
	 */
	public static function init()
	{
		if (!self::$initiated) {
			self::initHooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function initHooks()
	{
		self::$initiated = true;

		// register settings
		add_action('admin_init', ['\WP_GreyNoise\WP_GreyNoise_Admin', 'registerSettings']);
		
		// settings menu
		add_action('admin_menu', ['\WP_GreyNoise\WP_GreyNoise_Admin', 'adminMenu']);

		// actions to handle log deletes before any headers sent
		add_action('wp_loaded', ['\WP_GreyNoise\WP_GreyNoise_Dashboard', 'deleteLog']);
		add_action('wp_loaded', ['\WP_GreyNoise\WP_GreyNoise_Dashboard', 'deleteLogs']);

		// custom CSS
		add_action('admin_enqueue_scripts', ['\WP_GreyNoise\WP_GreyNoise_Admin', 'enqueueScripts']);
	}

	/**
	 * Register plugin settings, CSS, and JS
	 */
	public static function registerSettings()
	{
		// apiKey
		add_option('wpg_api_key', false);
		register_setting(
			'wpg_options_group',
			'wpg_api_key',
			[
				'sanitize_callback' => ['\WP_GreyNoise\WP_GreyNoise_Admin', 'validateApiKey'],
			]
		);

		// enableGreyNoise
		add_option('wpg_is_enable_greynoise', false);
		register_setting(
			'wpg_options_group',
			'wpg_is_enable_greynoise',
			[
				'sanitize_callback' => ['\WP_GreyNoise\WP_GreyNoise_Admin', 'validateIsEnableGreyNoise'],
			]
		);

		// isVerboseLogging
		add_option('wpg_cron_purge_days', 7);
		register_setting(
			'wpg_options_group',
			'wpg_cron_purge_days',
			[
				'sanitize_callback' => ['\WP_GreyNoise\WP_GreyNoise_Admin', 'validateCronPurgeDays'],
			]
		);

		// Plugin CSS
		wp_register_style(
			'wpg_style',
			plugins_url('/css/wp_greynoise.css', __FILE__),
			[],
			'1.0.0',
			'all'
		);

		// plugin JS
		wp_register_script(
			'wpg_script',
			plugins_url('/js/wp_greynoise.js', __FILE__),
			[],
			'1.0.0',
			true
		);
	}

	/**
	 * Enqueue plugin CSS and JS
	 */
	public static function enqueueScripts()
	{
		wp_enqueue_style('wpg_style');
		wp_enqueue_script('wpg_script');
	}

	/**
	 * Defines the plugin admin settings and dashboard page
	 */
	public static function adminMenu()
	{
		add_options_page(
			'WP GreyNoise',
			'WP GreyNoise',
			'manage_options',
			'wp_greynoise_options',
			['\WP_GreyNoise\WP_GreyNoise_Admin', 'optionPageRender']
		);

		add_menu_page(
			'WP GreyNoise',
			'WP GreyNoise',
			'manage_options',
			'wp_greynoise_dash',
			['\WP_GreyNoise\WP_GreyNoise_Dashboard', 'pageRender'],
			'',
			66
		);
	}

	/**
	 * Validate the API key using GreyNoise class
	 * 
	 * @param string $apiKey GN API key string
	 * @return string
	 */
	public static function validateApiKey(string $apiKey): ?string
	{
		$gNoise = GreyNoise::getInstance($apiKey);

		if ($gNoise) {
			// api key is valid, can be returned
			return $apiKey;
		} else {
			// api key is not valid
			return NULL;
		}
	}

	/**
	 * Validate 'enable' checkbox
	 * 
	 * @param int|NULL $isEnableGreyNoise The form checkbox value
	 * @return bool
	 */
	public static function validateIsEnableGreyNoise(?int $isEnableGreyNoise): bool
	{
		return self::validateBoolean($isEnableGreyNoise);
	}

	/**
	 * Validate cron purge select box setting.
	 * 
	 * @param int $cronPurgeDays The form checkbox value
	 * @return bool
	 */
	public static function validateCronPurgeDays(int $cronPurgeDays): int
	{
		// define valid days
		$validDays = [7, 14, 21, 30];
		$cleanCronPurgeDays = 7;

		// test value, set to 7 if not valid
		if(in_array($cronPurgeDays, $validDays)){
			$cleanCronPurgeDays = $cronPurgeDays;
		}

		// return clean value
		return $cleanCronPurgeDays;
	}

	/**
	 * Helper function to sanitize form boolean input
	 * 
	 * @param int|NULL $value The value to sanitize
	 * @return bool
	 */
	protected static function validateBoolean(?int $value): bool
	{
		if ($value == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Helper function to determine plugin status
	 * 
	 * @return bool
	 */
	public static function isGreyNoiseRunning(): bool
	{
		if(!empty(get_option('wpg_api_key')) && get_option('wpg_is_enable_greynoise')){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Render the dashboard page
	 */
	public static function adminPageRender()
	{
?>
		<div>
			<h2>WP GreyNoise Dashboard</h2>
			
		</div>
	<?php
	}

	/**
	 * Render the settings page / form
	 */
	public static function optionPageRender()
	{
	?>
		<div id="wpg-form-wrap">
			<h2>WP GreyNoise</h2>

			<?php if (empty(get_option('wpg_api_key'))) : ?>
				<div class="notice notice-error inline">
					You must enter a valid API key to run WP Greynoise!
				</div>
			<?php elseif (!get_option('wpg_is_enable_greynoise')) : ?>
				<div class="notice notice-warning inline">
					WP GreyNoise is disabled!
				</div>
			<?php else : ?>
				<div class="notice notice-success inline">
					WP GreyNoise is running!
				</div>
			<?php endif ?>

			<form method="post" action="options.php">
				<?php settings_fields('wpg_options_group'); ?>
				<p>
					The plugin will not perform IP lookups unless you enter a valid API key <strong>and</strong>
					you check the 'Enable GreyNoise' checkbox.
					<br><br>
					You can start / stop logging at any time using the 'Enable GreyNoise' checkbox.
					<br><br>
					This plugin logs the IP address of <strong><i>every</i></strong> visitor, it can generate large
					amounts of data, and quickly fill your database. <strong><i>use with extreme caution!</i></strong>
				</p>

				<table class="form-table" role="presentation">
					<tr valign="top">
						<th scope="row">
							<label for="wpg_api_key">GreyNoise API Key</label>
						</th>
						<td id="wpg_api_key_cell" class="<?php if(!empty(get_option('wpg_api_key'))) echo "verified" ?>">
							<input type="text" id="wpg_api_key" name="wpg_api_key" value="<?php echo get_option('wpg_api_key') ?>"  class="regular-text code" />
							<p class="description">
								Enter the API key shown on your GreyNoise account screen (<a href="https://www.greynoise.io/viz/account/" target="_blank">https://www.greynoise.io/viz/account/</a>).
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="wpg_is_enable_greynoise">Enable GreyNoise?</label>
						</th>
						<td>
							<input type="checkbox" id="wpg_is_enable_greynoise" name="wpg_is_enable_greynoise" value="1" <?php checked(get_option('wpg_is_enable_greynoise')) ?> />
							<p class="description">
								Use this to switch IP lookup on / off.
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="wpg_cron_purge_days">Delete non-malicious IPs after how many days?</label>
						</th>
						<td>
							<select name='wpg_cron_purge_days'>
								<option value='7' <?php selected( get_option('wpg_cron_purge_days'), 7 ); ?>>7</option>
								<option value='14' <?php selected( get_option('wpg_cron_purge_days'), 14 ); ?>>14</option>
								<option value='21' <?php selected( get_option('wpg_cron_purge_days'), 21 ); ?>>21</option>
								<option value='30' <?php selected( get_option('wpg_cron_purge_days'), 30 ); ?>>30</option>
							</select>

							<p class="description">
								This plugin logs the IP address of <strong><i>every</i></strong> visitor, it can generate large
								amounts of data, and quickly fill your database.
								<br>
								Use this setting to automatically purge non-malicious IP addresses after a number of days since last activity.
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
<?php
	}
}
