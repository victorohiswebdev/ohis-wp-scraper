<?php
namespace WP_Headless_Extractor;

class Ajax {

	public function register_hooks() {
		add_action( 'wp_ajax_wphe_init_extraction', [ $this, 'init_extraction' ] );
		add_action( 'wp_ajax_wphe_process_batch', [ $this, 'process_batch' ] );
		add_action( 'wp_ajax_wphe_finalize_extraction', [ $this, 'finalize_extraction' ] );
	}

	private function verify_request() {
		check_ajax_referer( 'wphe_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
	}

	public function init_extraction() {
		$this->verify_request();

		// Clean up old exports and set up directories
		$exporter = new Exporter();
		$success = $exporter->setup_directories();

		if ( ! $success ) {
			wp_send_json_error( [ 'message' => 'Failed to create export directories. Check permissions.' ] );
		}

		$options = isset( $_POST['options'] ) ? (array) $_POST['options'] : [];

		$extractor = new Extractor();

		// Extract global data (site info, menus, media, etc.)
		$site_data = $extractor->get_site_data( $options );
		$exporter->save_json( 'site-data.json', $site_data );

		// Prepare list of items to process
		$items_to_process = $extractor->get_items_to_process( $options );

		wp_send_json_success( [
			'message'     => 'Initialization complete.',
			'total_items' => count( $items_to_process ),
			'items'       => $items_to_process
		] );
	}

	public function process_batch() {
		$this->verify_request();

		// Ensure WP_Filesystem is available
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$items = isset( $_POST['items'] ) ? (array) $_POST['items'] : [];
		if ( empty( $items ) ) {
			wp_send_json_success( [ 'message' => 'No items in batch.' ] );
		}

		$extractor = new Extractor();
		$exporter  = new Exporter();

		foreach ( $items as $item ) {
			// Extract all post types that are passed in the batch
			if ( ! empty( $item['id'] ) ) {
				$post_data = $extractor->extract_post( $item['id'] );
				if ( $post_data ) {
					$exporter->save_markdown( $post_data );
				}
			}
		}

		wp_send_json_success( [ 'message' => 'Batch processed successfully.' ] );
	}

	public function finalize_extraction() {
		$this->verify_request();

		// Ensure WP_Filesystem is available
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$exporter = new Exporter();
		$zip_url  = $exporter->create_zip();

		if ( $zip_url ) {
			wp_send_json_success( [
				'message'  => 'Extraction finalized. ZIP created successfully.',
				'zip_url'  => $zip_url
			] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to create ZIP file.' ] );
		}
	}
}
