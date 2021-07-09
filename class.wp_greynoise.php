<?php

class WP_GreyNoise {

    private static $initiated = false;

    public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

    /**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
	}

	public static function plugin_activation()
	{
		// TODO: create log table in db

	}

	public static function plugin_deactivation()
	{
		// clear settings from db
		delete_option('wpg_api_key');
		delete_option('wpg_is_enable_greynoise');
		delete_option('wpg_is_verbose_logging');

		// TODO: remove log table from db

	}
}