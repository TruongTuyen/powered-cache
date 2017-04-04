<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PC_Object_Cache {

	/**
	 * Return an instance of the current class
	 *
	 * @since 1.0
	 * @return PC_Object_Cache
	 */
	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}


	/**
	 * Setup hooks
	 *
	 * @since 1.0
	 */
	public function setup() {
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );
		add_action( 'admin_post_pc_purge_object_cache', array( $this, 'purge_object_cache' ) );
	}


	/**
	 * Add purge button on admin bar
	 *
	 * @since 1.0
	 *
	 * @param $wp_admin_bar
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
		$wp_admin_bar->add_menu( array(
			'id'     => 'object-cache-purge',
			'title'  => __( 'Purge Object Cache', 'powered-cache' ),
			'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=pc_purge_object_cache' ), 'pc_purge_object_cache' ),
			'parent' => 'powered-cache',
		) );
	}


	/**
	 * Purge object cache
	 *
	 * @since 1.0
	 */
	public function purge_object_cache() {
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'pc_purge_object_cache' ) ) {
			wp_nonce_ays( '' );
		}

		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		PC_Admin_Helper::set_flash_message( __( 'Object cache deleted successfully!', 'powered-cache' ) );

		wp_safe_redirect( wp_get_referer() );
		die();
	}


}