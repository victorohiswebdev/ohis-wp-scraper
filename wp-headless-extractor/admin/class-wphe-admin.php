<?php
namespace WP_Headless_Extractor;

class Admin {

	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'add_plugin_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles_scripts' ] );
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'Site Extractor',
			'Site Extractor',
			'manage_options',
			'wp-headless-extractor',
			[ $this, 'display_plugin_admin_page' ],
			'dashicons-download',
			80
		);
	}

	public function enqueue_styles_scripts( $hook ) {
		// Only load scripts and styles on our plugin page
		if ( 'toplevel_page_wp-headless-extractor' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wphe-admin-style',
			WPHE_PLUGIN_URL . 'admin/css/admin-style.css',
			[],
			WPHE_VERSION,
			'all'
		);

		wp_enqueue_script(
			'wphe-admin-script',
			WPHE_PLUGIN_URL . 'admin/js/admin-script.js',
			[ 'jquery' ],
			WPHE_VERSION,
			true
		);

		// Localize script to pass nonce and ajax url
		wp_localize_script(
			'wphe-admin-script',
			'wphe_ajax_obj',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wphe_nonce' ),
			]
		);
	}

	public function display_plugin_admin_page() {
		// Get all public post types
		$post_types = get_post_types( [ 'public' => true, '_builtin' => false ], 'objects' );

		// Include the view
		require_once WPHE_PLUGIN_DIR . 'admin/views/admin-dashboard.php';
	}
}
