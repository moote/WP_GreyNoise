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
		// todo
		
	}

	public static function plugin_deactivation()
	{
		// todo
		
	}
}