<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmWelcomeScreenController {
	public static $menu_slug   = 'formidable-welcome-screen';
	public static $option_name = 'frm_welcome_screen_activation_redirect';

	public static function activation_redirect() {
		if ( get_option( self::$option_name ) != 'yes' ) {
			return;
		}
		update_option( self::$option_name, 'no' );
		wp_safe_redirect( add_query_arg( array( 'page' => self::$menu_slug ), admin_url( 'admin.php' ) ) );
	}

	public static function screen_page() {
		add_submenu_page( 'formidable', 'Formidable | ' . __( 'Welcome Screen', 'formidable' ), __( 'Welcome Screen', 'formidable' ), 'read', self::$menu_slug, __CLASS__ . '::screen_content' );
	}

	public static function screen_content() {
		include( FrmAppHelper::plugin_path() . '/classes/views/welcome/screen.php' );
	}

	public static function remove_menu() {
		remove_submenu_page( 'formidable', self::$menu_slug );
	}

}
