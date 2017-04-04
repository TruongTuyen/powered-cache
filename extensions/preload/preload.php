<?php
/**
 * Plugin Name: Preloader
 * Plugin URI: https://poweredcache.com/extensions/preload
 * Description: Preload extension for Powered Cache
 * Author: Powered Cache Team
 * Version: 1.0
 * Author URI: https://poweredcache.com
 * Plugin Image: extension-image.png
 * License: GPLv2 (or later)
*/

require_once 'inc/class-pc-preload-process.php';


PC_Preload_Process::factory();

if ( is_admin() ) {
	require_once 'inc/class-pc-preload-admin.php';
	PC_Preload_Admin::factory();
}


// make description translatable
__( 'Preload extension for Powered Cache', 'powered-cache' );