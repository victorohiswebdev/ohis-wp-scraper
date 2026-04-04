<?php
namespace WP_Headless_Extractor;

class Init {

	public function run() {
		// Initialize AJAX handlers
		$ajax = new Ajax();
		$ajax->register_hooks();

		// Register download endpoint
		add_action( 'wp_ajax_wphe_download_zip', [ $this, 'download_zip' ] );

		// Initialize Admin if we are in the admin area
		if ( is_admin() ) {
			$admin = new Admin();
			$admin->register_hooks();
		}
	}

	public function download_zip() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( 'wphe_download_zip' );

		if ( empty( $_GET['file'] ) ) {
			wp_die( 'No file specified.' );
		}

		$filename = sanitize_file_name( $_GET['file'] );
		$upload_info = wp_upload_dir();
		$file_path = trailingslashit( $upload_info['basedir'] ) . 'wp-headless-extractor/' . $filename;

		if ( ! file_exists( $file_path ) ) {
			wp_die( 'File not found.' );
		}

		// Serve the file
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		readfile( $file_path );
		exit;
	}

}
