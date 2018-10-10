<?php
/*
 * Plugin Name: Fix WP Engine Multisite Admin Bar
 * Plugin URI: https://github.com/mistercorey
 * Description: WP Engine's admin bar menu includes an Empty Caches link that errors for all users except Super Admins. This is a fix.
 * Version: 1.0.0
 * Author: Corey Salzano
 * Author URI: https://profiles.wordpress.org/salzano
 * Text Domain: fix-wpe-admin-bar
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class Fix_WP_Engine_Admin_Bar{

	function current_user_is_super_admin() {
		return is_super_admin( get_current_user_id() );
	}

	function empty_caches_callback() {

		if( ! class_exists( 'WpeCommon' ) ) {
			echo 'Empty Caches failed, WP Engine System must-use plugin may not be running.';
			wp_die();
		}

		WpeCommon::purge_varnish_cache();
		WpeCommon::purge_memcached();

		echo 'Empty Caches succeeded, purged Varnish cache and memcached.';
		wp_die();
	}

	function fix_empty_caches_link_for_admins( $wp_admin_bar ) {

		if( $this->current_user_is_super_admin() ) { return; }

		$wpe_quick_links = $wp_admin_bar->get_node( 'wpengine_adminbar_cache' );

		if( $wpe_quick_links && isset( $wpe_quick_links->href ) ) {
			$wp_admin_bar->remove_node( $wpe_quick_links->id );
			$wpe_quick_links->href = '#';
			$wp_admin_bar->add_node( $wpe_quick_links );
		}
	}

	function hooks() {
		add_action( 'admin_bar_menu', array( $this, 'fix_empty_caches_link_for_admins' ), 999 );
		add_action( 'admin_footer', array( $this, 'output_footer_javascript' ) );
		add_action( 'wp_ajax_wpe_empty_caches', array( $this, 'empty_caches_callback' ) );
	}

	function output_footer_javascript() {

		if( ! is_admin_bar_showing() || $this->current_user_is_super_admin() ) { return; }

?><script type="text/javascript">
	jQuery('li#wp-admin-bar-wpengine_adminbar_cache .ab-item').on( 'click', function() {
		var data = {
			'action': 'wpe_empty_caches',
		};

		jQuery.post( ajaxurl, data, function(response) {
			alert( response );
		});
	});
</script><?php
	}
}
$salzano_fix_empty_caches_link = new Fix_WP_Engine_Admin_Bar();
$salzano_fix_empty_caches_link->hooks();
