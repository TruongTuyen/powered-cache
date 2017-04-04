<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PC_Config {


	/**
	 * Return an instance of the current class
	 *
	 * @since 1.0
	 * @return PC_Config
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			global $wp_filesystem;

			if ( ! $wp_filesystem ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				WP_Filesystem();
			}
		}

		return $instance;
	}

	/**
	 * placeholder
	 *
	 * @since 1.0
	 */
	public function __construct() { }


	/**
	 * Default options
	 *
	 * @since 1.0
	 * @return mixed|void
	 */
	public function default_settings() {
		$settings = array(
			// basic options
			'enable_page_caching'        => false,
			'object_cache'               => 'off',
			'cache_mobile'               => true,
			'cache_mobile_separate_file' => false,
			'loggedin_user_cache'        => false,
			'ssl_cache'                  => false,
			'gzip_compression'           => false,
			'cache_timeout'              => 1440,
			'cache_location'             => pc_get_cache_dir(),
			// advanced options
			'rejected_user_agents'       => '',
			'rejected_cookies'           => '',
			'rejected_uri'               => '',
			'accepted_query_strings'     => '',
			// cdn
			'cdn_status'                 => false,
			'cdn_hostname'               => array(),
			'cdn_zone'                   => array(),
			'cdn_rejected_files'         => '',
			// skip extensions, we don't need default for them
			// misc
			'show_cache_message'         => true,
		);


		return apply_filters( 'pc_default_settings', $settings );
	}

	/**
	 * Generates advanced-cache.php
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function generate_advanced_cache_file() {
		global $wp_filesystem;

		$file = untrailingslashit( WP_CONTENT_DIR )  . '/advanced-cache.php';


		$file_string = '';

		if ( true === pc_get_option( 'enable_page_caching' ) ) {
			$file_string = $this->advanced_cache_file_content();
		}

		if ( ! $wp_filesystem->put_contents( $file, $file_string, FS_CHMOD_FILE ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Setup object-cache.php
	 *
	 * @since 1.0
	 * @param string $backend
	 *
	 * @return bool
	 */
	public function setup_object_cache( $backend = 'off' ) {
		global $wp_filesystem;

		$file = untrailingslashit( WP_CONTENT_DIR )  . '/object-cache.php';

		if ( 'off' === $backend ) {
			if ( $wp_filesystem->exists( $file ) ) {
				$wp_filesystem->delete( $file ); // remove object cache file
			}

			return true;
		}

		$file_string = $this->object_cache_file_content( $backend );

		if ( ! $wp_filesystem->put_contents( $file, $file_string, FS_CHMOD_FILE ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Generate advanced-cache.php and define WP_CACHE
	 *
	 * @since 1.0
	 *
	 * @param $status
	 *
	 * @return bool
	 */
	public function setup_page_cache( $status ) {

		/**
		 * Forcing multisite settings always true
		 */
		if ( is_multisite() ) {
			$status = true;
		}

		PC_Config::factory()->generate_advanced_cache_file();
		PC_Config::factory()->define_wp_cache( $status );
		PC_Config::factory()->configure_htaccess( $status );

		return true;
	}


	/**
	 * object-cache.php contents
	 *
	 * @since 1.0
	 * @param $backend
	 * @see PC_Admin_Helper::object_cache_dropins
	 *
	 * @return mixed|void
	 */
	public function object_cache_file_content( $backend ) {
		$string = '<?php ' . "\n";
		$string .= "defined( 'ABSPATH' ) || exit;" . "\n";
		$string .= "define( 'POWERED_OBJECT_CACHE', true );" . "\n";
		$string .= "if ( ! @file_exists( WP_CONTENT_DIR . '/pc-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' ) ) { return; }" . "\n";

		$object_caches = PC_Admin_Helper::object_cache_dropins();

		$string .= "\$GLOBALS['powered_cache_options'] = include( WP_CONTENT_DIR . '/pc-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' );" . "\n\n";

		$string .= 'if ( @file_exists( \'' . $object_caches[ $backend ] . '\' ) ) {' . "\n";
		$string .= "\t" . 'include( \'' . $object_caches[ $backend ] . '\' );' . "\n";
		$string .= '} else {' . "\n";
		$string .= "\t" . 'define( \'POWERED_OBJECT_CACHE_HAS_PROBLEM\', true );' . "\n";
		$string .= '}';

		return apply_filters( 'pc_object_cache_file_content', $string );
	}


	/**
	 * Prepare advanced-cache.php contents
	 *
	 * @since 1.0
	 * @return mixed|void
	 */
	public function advanced_cache_file_content(){

		$string = '<?php ' . PHP_EOL;
		$string .= "defined( 'ABSPATH' ) || exit;" . PHP_EOL;
		$string .= "define( 'POWERED_PAGE_CACHE', true );" . PHP_EOL;

		$string .= "\$config_file = WP_CONTENT_DIR . '/pc-config/config-' . \$_SERVER['HTTP_HOST'];" . PHP_EOL . PHP_EOL;

		$string .= "if ( is_multisite() && ( defined( 'SUBDOMAIN_INSTALL' ) && false === SUBDOMAIN_INSTALL ) ) {" . PHP_EOL;
		$string .= "\t" . "\$request_uri   = explode( '/', ltrim( \$_SERVER['REQUEST_URI'], '/' ) );" . PHP_EOL;
		$string .= "\t" . "\$sub_site_name = \$request_uri[0];" . PHP_EOL;
		$string .= "\t" . "\$config_file .= '-'.\$sub_site_name;" . PHP_EOL;
		$string .= "}" . PHP_EOL;
		$string .= "\$config_file .= '.php';" . PHP_EOL;


		$string .= "if ( ! @file_exists( \$config_file ) ) { return; }" . PHP_EOL;
		// get config file
		$string .= "\$GLOBALS['powered_cache_options'] = include( \$config_file );" . PHP_EOL . PHP_EOL;
		// mobile cache varibales
		$string .= '$powered_cache_mobile_browsers = ' . var_export( pc_mobile_browsers(), true ) . ";" . PHP_EOL;
		$string .= '$powered_cache_mobile_prefixes = ' . var_export( pc_mobile_prefixes(), true ) . ";" . PHP_EOL;

		$string .= 'if ( @file_exists( \'' . PC_DROPIN_DIR . 'page-cache.php' . '\' ) ) {' . PHP_EOL;
		$string .= "\t" . 'include( \'' . PC_DROPIN_DIR . 'page-cache.php' . '\' );' . PHP_EOL;
		$string .= '} else {' . PHP_EOL;
		$string .= "\t" . 'define( \'POWERED_PAGE_CACHE_HAS_PROBLEM\', true );' . PHP_EOL;
		$string .= '}';


		return apply_filters( 'pc_advanced_cache_file_content', $string );
	}

	/**
	 * Define WP_CACHE constant
	 *
	 * @since 1.0
	 * @param $status
	 *
	 * @return bool
	 */
	public function define_wp_cache( $status ) {
		global $wp_filesystem;
		$config_path = $this->find_wp_config_file();

		if ( ! $config_path ) {
			return false;
		}


		if ( defined( 'WP_CACHE' ) && WP_CACHE === $status ) {
			return true;
		}

		$config_file_string = $wp_filesystem->get_contents( $config_path );

		// Config file is empty. Maybe couldn't read it?
		if ( empty( $config_file_string ) ) {
			return false;
		}

		$config_file = preg_split( "#(\n|\r)#", $config_file_string );
		$line_key = false;

		foreach ( $config_file as $key => $line ) {
			if ( ! preg_match( '/^\s*define\(\s*(\'|")([A-Z_]+)(\'|")(.*)/', $line, $match ) ) {
				continue;
			}

			if ( $match[2] == 'WP_CACHE' ) {
				$line_key = $key;
			}
		}

		if ( $line_key !== false ) {
			unset( $config_file[ $line_key ] );
		}

		$status_string = ( $status ) ? 'true' : 'false';

		array_shift( $config_file );
		array_unshift( $config_file, '<?php', "define( 'WP_CACHE', $status_string ); // Powered Cache" );

		foreach ( $config_file as $key => $line ) {
			if ( '' === $line ) {
				unset( $config_file[$key] );
			}
		}

		if ( ! $wp_filesystem->put_contents( $config_path, implode( "\n\r", $config_file ), FS_CHMOD_FILE ) ) {
			return false;
		}

		return true;
	}


	/**
	 * seeking wp-config file
	 *
	 * @since 1.0
	 *
	 * @return bool|string
	 */
	public function find_wp_config_file() {
		global $wp_filesystem;

		$file = '/wp-config.php';

		for ( $i = 1; $i <= 3; $i ++ ) {
			if ( $i > 1 ) {
				$file = '/..' . $file;
			}

			if ( $wp_filesystem->exists( untrailingslashit( ABSPATH ) . $file ) ) {
				$config_path = untrailingslashit( ABSPATH ) . $file;
				break;
			}
		}

		if ( ! isset( $config_path ) ) {
			return false;
		}

		return $config_path;
	}

	/**
	 * Save settings to file
	 *
	 * @since 1.0
	 * @param $configuration
	 *
	 * @return bool
	 */
	public function save_to_file( $configuration ) {
		global $wp_filesystem;

		$config_dir = WP_CONTENT_DIR  . '/pc-config';

		$site_url_parts   = parse_url( site_url() );
		$config_file_name = $site_url_parts['host'];

		if ( is_multisite() && ( defined( 'SUBDOMAIN_INSTALL' ) && false === SUBDOMAIN_INSTALL ) ) {
			if ( is_main_site( get_current_blog_id() ) ) {
				$config_file_name .= '-blog';
			} else {
				$subdir_name = ltrim( parse_url( get_site_url( get_current_blog_id() ), PHP_URL_PATH ), '/' );
				$config_file_name .= '-' . $subdir_name;
			}
		}


		$config_file = $config_dir . '/config-' . $config_file_name . '.php';

		$configuration['cache_location'] = pc_get_cache_dir();

		$wp_filesystem->mkdir( $config_dir );

		$config_file_string = '<?php ' . "\n\r" . "defined( 'ABSPATH' ) || exit;" . "\n\r" . 'return ' . var_export( $configuration, true ) . '; ' . "\n\r";

		if ( ! $wp_filesystem->put_contents( $config_file, $config_file_string, FS_CHMOD_FILE ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Create .htaccess file based on current setting preferences
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function configure_htaccess( $enable = true ) {
		global $wp_filesystem;
		$rules         = '';
		$htaccess_file = get_home_path() . '.htaccess';

		if ( $wp_filesystem->is_writable( $htaccess_file ) ) {
			$contents = $wp_filesystem->get_contents( $htaccess_file );

			$rules .= apply_filters( 'pc_pre_htaccess', '', $contents );

			//clean up
			$contents = preg_replace( '/# BEGIN POWERED CACHE(.*)# END POWERED CACHE/is', '', $contents );

			if ( false === $enable ) {
				return $wp_filesystem->put_contents( $htaccess_file, $contents, FS_CHMOD_FILE );
			}

			$rules .= '# BEGIN POWERED CACHE' . PHP_EOL;

			// todo set option here.
			if ( apply_filters( 'pc_browser_cache', true ) ) {
				$wp_mime_types = wp_get_mime_types();
				$mime_types    = array_flip( $wp_mime_types );
				//mimes
				$rules .= '<IfModule mod_mime.c>' . PHP_EOL;
					foreach ( $mime_types as $mime_type => $ext ) {
						$ext_str = '.' . str_replace( '|', ' .', $ext );
						$rules .= "    AddType " . $mime_type . " " . $ext_str . PHP_EOL;
					}
				$rules .= "</IfModule>" . PHP_EOL;

				// set expire time

				$rules .= '<IfModule mod_expires.c>' . PHP_EOL;
				$rules .= "    ExpiresActive On".PHP_EOL;
				$rules .= '    ExpiresByType  text/html            "access plus 0 seconds"'. PHP_EOL;
				$rules .= '    ExpiresByType  text/richtext        "access plus 0 seconds"'. PHP_EOL;
				$rules .= '    ExpiresByType  image/svg+xml        "access plus 0 seconds"'. PHP_EOL;
				$rules .= '    ExpiresByType  text/plain           "access plus 0 seconds"'. PHP_EOL;
				$rules .= '    ExpiresByType  text/xsd             "access plus 0 seconds"'. PHP_EOL;
				$rules .= '    ExpiresByType  text/xsl             "access plus 0 seconds"'. PHP_EOL;
				$rules .= '    ExpiresByType  text/xml             "access plus 0 seconds"'. PHP_EOL;
				$rules .= '    ExpiresByType  text/cache-manifest  "access plus 0 seconds"'. PHP_EOL;


				foreach ( $mime_types as $mime_type => $ext ) {


					if ( in_array( $mime_type, array( 'text/html', 'text/richtext', 'image/svg+xml', 'text/plain', 'text/xsd', 'text/xsl', 'text/xml', 'text/cache-manifest' ) ) ) {
						continue;
					}

					/**
					 * Apache allow both format like A2592000 => "access plus 1 month"
					 * A => access, M => Modified
					 *
					 * @see http://httpd.apache.org/docs/current/mod/mod_expires.html
					 */

					if ( in_array( $mime_type, array( 'text/css', 'application/javascript' ) ) ) {
						$expiry_time = apply_filters( 'pc_browser_cache_assets_lifespan', 'access plus 1 year' );
					} else {
						$expiry_time = apply_filters( 'pc_browser_cache_default_lifespan', 'access plus 1 month' );
					}
					$rules .= '    ExpiresByType '.$mime_type.'                 "'.$expiry_time.'"' . PHP_EOL;
				}
				$rules .= "</IfModule>" . PHP_EOL;

			}


			// gzip
			$rules .= '<IfModule mod_deflate.c>' . PHP_EOL;
			$rules .= '  <IfModule mod_headers.c>' . PHP_EOL;
			$rules .= '    Header append Vary User-Agent env=!dont-vary' . PHP_EOL;
			$rules .= '  </IfModule>' . PHP_EOL;
			$rules .= '    AddOutputFilterByType DEFLATE text/css text/x-component application/x-javascript application/javascript text/javascript text/x-js text/html text/richtext image/svg+xml text/plain text/xsd text/xsl text/xml image/bmp application/java application/msword application/vnd.ms-fontobject application/x-msdownload image/x-icon application/json application/vnd.ms-access application/vnd.ms-project application/x-font-otf application/vnd.ms-opentype application/vnd.oasis.opendocument.database application/vnd.oasis.opendocument.chart application/vnd.oasis.opendocument.formula application/vnd.oasis.opendocument.graphics application/vnd.oasis.opendocument.presentation application/vnd.oasis.opendocument.spreadsheet application/vnd.oasis.opendocument.text audio/ogg application/pdf application/vnd.ms-powerpoint application/x-shockwave-flash image/tiff application/x-font-ttf application/vnd.ms-opentype audio/wav application/vnd.ms-write application/font-woff application/font-woff2 application/vnd.ms-excel' . PHP_EOL;
			$rules .= '  <IfModule mod_mime.c>' . PHP_EOL;
			$rules .= '    AddOutputFilter DEFLATE js css htm html xml' . PHP_EOL;
			$rules .= '  </IfModule>' . PHP_EOL;
			$rules .= '</IfModule>' . PHP_EOL;




			// remove etag
			$rules .= '<IfModule mod_headers.c>' . PHP_EOL;
			$rules .= 'Header unset ETag' . PHP_EOL;
			$rules .= '</IfModule>' . PHP_EOL . PHP_EOL;


			// rewrite

			$env_pc_ua = '';
			$env_pc_ssl = '';
			$env_pc_enc = '';


			$rewrite_base = network_home_url( '', 'relative' );
			if ( empty( $rewrite_base ) ) {
				$rewrite_base = '/';
			}

			$cache_dir = pc_get_cache_dir();

			$rules .= '<IfModule mod_rewrite.c>' . PHP_EOL;
			$rules .= '    RewriteEngine On' . PHP_EOL;
			$rules .= '    RewriteBase ' . $rewrite_base . PHP_EOL;


			if ( true === pc_get_option( 'cache_mobile' ) && true === pc_get_option( 'cache_mobile_separate_file' ) ) {
				$mobile_browsers = addcslashes( implode( '|', preg_split( '/[\s*,\s*]*,+[\s*,\s*]*/', pc_mobile_browsers() ) ), ' ' );
				$mobile_prefixes = addcslashes( implode( '|', preg_split( '/[\s*,\s*]*,+[\s*,\s*]*/', pc_mobile_prefixes() ) ), ' ' );
				// mobile env set
				$rules .= "    RewriteCond %{HTTP_USER_AGENT} (" . $mobile_browsers . ") [NC]" . PHP_EOL;
				$rules .= "    RewriteRule .* - [E=PC_UA:-mobile]" . PHP_EOL;
				$rules .= "    RewriteCond %{HTTP_USER_AGENT} ^(" . $mobile_prefixes . ") [NC]" . PHP_EOL;
				$rules .= "    RewriteRule .* - [E=PC_UA:-mobile]" . PHP_EOL;
				$env_pc_ua = '%{ENV:PC_UA}';
			}

			if ( true === pc_get_option( 'ssl_cache' ) ) {
				$rules .= "    RewriteCond %{HTTPS} =on" . PHP_EOL;
				$rules .= "    RewriteRule .* - [E=W3TC_SSL:-https]" . PHP_EOL;
				$rules .= "    RewriteCond %{SERVER_PORT} =443" . PHP_EOL;
				$rules .= "    RewriteRule .* - [E=PC_SSL:-https]" . PHP_EOL;
				$env_pc_ssl = '%{ENV:PC_SSL}';
			}

			if ( true === pc_get_option( 'gzip_compression' ) ) {
				$rules .= "    RewriteCond %{HTTP:Accept-Encoding} gzip" . PHP_EOL;
				$rules .= "    RewriteRule .* - [E=PC_ENC:_gzip]" . PHP_EOL;
				$env_pc_enc = '%{ENV:PC_ENC}';
			}

			$rules .= "    RewriteCond %{REQUEST_METHOD} !=POST" . PHP_EOL;
			$rules .= '    RewriteCond %{QUERY_STRING} =""' . PHP_EOL;

			if ( substr( get_option( 'permalink_structure' ), - 1 ) == '/' ) {
				$rules .= '    RewriteCond %{REQUEST_URI} \/$' . PHP_EOL;
			}

				// Get root base
			$home_root = parse_url( home_url() );
			$home_root = isset( $home_root['path'] ) ? trailingslashit($home_root['path']) : '/';

			$site_root = parse_url( site_url() );
			$site_root = isset( $site_root['path'] ) ? trailingslashit($site_root['path']) : '';


			// reject user agent
			if ( false !== pc_get_option( 'rejected_user_agents' ) ) {
				$rejected_user_agents = preg_split( '#(\r\n|\n|\r)#', pc_get_option( 'rejected_user_agents' ) );
				if ( ! empty( $rejected_user_agents ) ) {
					$rules .= '    RewriteCond %{HTTP_USER_AGENT} !^(' . implode( '|', $rejected_user_agents ) . ').* [NC]' . PHP_EOL;
				}
			}


			// ignore cookies
			if ( false !== pc_get_option( 'rejected_uri' ) ) {
				$cookies = preg_split( '#(\n|\r)#', pc_get_option( 'rejected_uri' ) );
			}
			$wp_cookies = array( 'wordpressuser_', 'wordpresspass_', 'wordpress_sec_', 'wordpress_logged_in_' );
			if ( ! empty( $cookies ) ) {
				$wp_cookies = array_merge( $cookies, $wp_cookies );
			}
			$rules .= '    RewriteCond %{HTTP:Cookie} !(' . implode( '|', $wp_cookies ) . ') [NC]' . PHP_EOL;

			// dont cache fbexternal
			$rules .= '    RewriteCond %{HTTP_USER_AGENT} !^(facebookexternalhit).* [NC]'.PHP_EOL;


			$cache_location = pc_get_cache_dir();
			$cache_location = untrailingslashit( $cache_location ) . '/powered-cache/';
			if ( strpos( ABSPATH, $cache_location ) === false ) {
				// clean doc root
				$cache_path = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $cache_location );
			} else {
				$cache_path = $site_root . str_replace( ABSPATH, '', $cache_location );
			}


			if ( apply_filters( 'pc_maybe_1and1_hosting', ( 0 === strpos( $_SERVER['DOCUMENT_ROOT'], '/kunden/homepage/' ) ) ) ) {
				$rules .= '    RewriteCond "' . str_replace( '/kunden/homepage/', '/', $cache_location ) . '%{HTTP_HOST}' . '%{REQUEST_URI}/index' . $env_pc_ssl . $env_pc_ua . '.html' . $env_pc_enc . '" -f' . PHP_EOL;
			} else {
				$rules .= '    RewriteCond "%{DOCUMENT_ROOT}/' . ltrim( $cache_path, '/' ) . '%{HTTP_HOST}' . '%{REQUEST_URI}/index' . $env_pc_ssl . $env_pc_ua . '.html' . $env_pc_enc . '" -f' . PHP_EOL;
			}
			$rules .= '    RewriteRule .* "' . $cache_path . '%{HTTP_HOST}' . '%{REQUEST_URI}/index' . $env_pc_ssl . $env_pc_ua . '.html' . $env_pc_enc . '" [L]' . PHP_EOL;
			$rules .= '</IfModule>' . PHP_EOL;
			$rules .= '# END POWERED CACHE' . PHP_EOL;

			$contents = $rules . $contents;

			// Update the .htacces file
			if ( ! $wp_filesystem->put_contents( $htaccess_file, $contents, FS_CHMOD_FILE ) ) {
				return false;
			}

			return true;
		}

		return false;
	}


}