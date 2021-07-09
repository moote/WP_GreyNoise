<?php

require_once(WP_GREYNOISE_PLUGIN_DIR . 'GreyNoise/GreyNoise.php');

class WP_GreyNoise_Admin
{
	private static $initiated = false;

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
	}

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

	public static function registerSettings()
	{
		add_option('wpg_api_key', false);
		register_setting(
			'wpg_options_group',
			'wpg_api_key',
			[
				'sanitize_callback' => ['WP_GreyNoise_Admin', 'validateApiKey'],
			]
		);
	}

	/**
	 * Validate the API key using GreyNoise class
	 */
	public static function validateApiKey($apiKey)
	{
		$gNoise = \GreyNoise\GreyNoise::getInstance($apiKey);

		if($gNoise){
			// api key is valid, can be returned
			return $apiKey;
		}
		else{
			// api key is not valid
			return false;
		}
	}

	/**
	 * Render the settings page / form
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
		<div>
			<h2>WP GreyNoise</h2>
			<form method="post" action="options.php">
				<?php settings_fields('wpg_options_group'); ?>
				<h3>This is my option</h3>
				<p>Some text here.</p>
				<table>
					<tr valign="top">
						<th scope="row"><label for="wpg_api_key">GreyNoise API Key</label></th>
						<td><input type="text" id="wpg_api_key" name="wpg_api_key" value="<?php echo get_option('wpg_api_key'); ?>" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
<?php
	}
}
