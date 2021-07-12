<?php
/**
 * @package WP_GreyNoise
 */
/*
Plugin Name: WP GreyNoise
Plugin URI: https://idunnit.com/
Description: GreyNoise IP lookup automation for WordPress
Version: 0.9.1
Author: Rich Conaway
Author URI: https://idunnit.com
License: GPLv2 or later
Text Domain: wp-greynoise
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

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Do not access directly.';
	exit;
}

define('WP_GREYNOISE_VERSION', '0.9.1');
define('WP_GREYNOISE_MINIMUM_WP_VERSION', '4.0');
define('WP_GREYNOISE_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('WP_GREYNOISE_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ));
define('WP_GREYNOISE_DB_TABLE_NAME', 'wpg_ip_log');

register_activation_hook( __FILE__, ['\WP_GreyNoise\WP_GreyNoise', 'pluginActivation']);
register_deactivation_hook( __FILE__, ['\WP_GreyNoise\WP_GreyNoise', 'pluginDeactivation']);

require_once(WP_GREYNOISE_PLUGIN_DIR . 'class-wp-greynoise.php');
add_action( 'init', ['\WP_GreyNoise\WP_GreyNoise', 'init']);

if(is_admin()){
	require_once(WP_GREYNOISE_PLUGIN_DIR . 'class-wp-greynoise-admin.php');
	add_action('init', ['\WP_GreyNoise\WP_GreyNoise_Admin', 'init']);
}
