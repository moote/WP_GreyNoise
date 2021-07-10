<?php

/**
 * Handles plugin admin functionality; settings, dashboard etc
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

use GreyNoise\GreyNoise;

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

		add_action('admin_init', ['WP_GreyNoise_Admin', 'registerSettings']);
		add_action('admin_menu', ['WP_GreyNoise_Admin', 'adminMenu']);
		add_action('admin_enqueue_scripts', ['WP_GreyNoise_Admin', 'enqueueScripts']);
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
				'sanitize_callback' => ['WP_GreyNoise_Admin', 'validateApiKey'],
			]
		);

		// enableGreyNoise
		add_option('wpg_is_enable_greynoise', false);
		register_setting(
			'wpg_options_group',
			'wpg_is_enable_greynoise',
			[
				'sanitize_callback' => ['WP_GreyNoise_Admin', 'validateIsEnableGreyNoise'],
			]
		);

		// isVerboseLogging
		add_option('wpg_is_verbose_logging', false);
		register_setting(
			'wpg_options_group',
			'wpg_is_verbose_logging',
			[
				'sanitize_callback' => ['WP_GreyNoise_Admin', 'validateIsVerboseLogging'],
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
	}

	/**
	 * Enqueue plugin CSS and JS
	 */
	public static function enqueueScripts()
	{
		wp_enqueue_style('wpg_style');
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
			['WP_GreyNoise_Admin', 'optionPageRender']
		);

		add_menu_page(
			'WP GreyNoise',
			'WP GreyNoise',
			'manage_options',
			'wp_greynoise_dash',
			['WP_GreyNoise_Admin', 'adminPageRender'],
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
	 * Validate 'verbose logging' checkbox
	 * 
	 * @param int|NULL $isVerboseLoging The form checkbox value
	 * @return bool
	 */
	public static function validateIsVerboseLogging(?int $isVerboseLoging): bool
	{
		return self::validateBoolean($isVerboseLoging);
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
			<h2>WP GreyNoise</h2>
			<p>Sup?</p>
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
					Verbose logging logs the IP address of <strong><i>every</i></strong> visitor! This can generate large
					amounts of data, and quickly fill your database; <strong><i>use with extreme caution!</i></strong>
				</p>

				<table class="form-table" role="presentation">
					<tr valign="top">
						<th scope="row">
							<label for="wpg_api_key">GreyNoise API Key</label>
						</th>
						<td id="wpg_api_key_cell" class="<?php if(!empty(get_option('wpg_api_key'))) echo "verified" ?>">
							<input type="text" id="wpg_api_key" name="wpg_api_key" value="<?php echo get_option('wpg_api_key') ?>"  class="regular-text code" />
							<?php /*if(!empty(get_option('wpg_api_key'))): ?>
								<img id="wpg-api-key-verified" src="<?php echo WP_GREYNOISE_PLUGIN_DIR_URL."images/thumbs-up.svg" ?>" alt="API Key Verified!" title="API Key Verified!" />
							<?php endif*/ ?>
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
							<label for="wpg_is_verbose_logging">Enable Verbose Logging?</label>
						</th>
						<td>
							<input type="checkbox" id="wpg_is_verbose_logging" name="wpg_is_verbose_logging" value="1" <?php checked(get_option('wpg_is_verbose_logging')) ?>" />
							<p class="description">
								Selecting this will log the IP address of <strong><i>every</i></strong> visitor! This can generate large
								amounts of data, and quickly fill your database; <strong><i>use with extreme caution!</i></strong>
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
