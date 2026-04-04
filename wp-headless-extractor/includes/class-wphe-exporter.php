<?php
namespace WP_Headless_Extractor;

class Exporter {

	private $upload_dir;
	private $base_dir;
	private $content_dir;

	public function __construct() {
		$upload_info       = wp_upload_dir();
		$this->upload_dir  = $upload_info['basedir'];
		$this->base_dir    = trailingslashit( $this->upload_dir ) . 'wp-headless-extractor';
		$this->content_dir = trailingslashit( $this->base_dir ) . 'content';
	}

	public function setup_directories() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Delete old export directory if it exists to start fresh
		if ( $wp_filesystem->is_dir( $this->base_dir ) ) {
			$wp_filesystem->delete( $this->base_dir, true );
		}

		// Create base directory
		if ( ! $wp_filesystem->mkdir( $this->base_dir ) ) {
			return false;
		}

		// Create content directory
		if ( ! $wp_filesystem->mkdir( $this->content_dir ) ) {
			return false;
		}

		// Secure the directory
		$this->secure_directory();

		return true;
	}

	private function secure_directory() {
		global $wp_filesystem;

		$htaccess_content = "Deny from all\n";
		$wp_filesystem->put_contents( $this->base_dir . '/.htaccess', $htaccess_content, FS_CHMOD_FILE );

		$index_content = "<?php\n// Silence is golden.\n";
		$wp_filesystem->put_contents( $this->base_dir . '/index.php', $index_content, FS_CHMOD_FILE );
	}

	public function save_json( $filename, $data ) {
		global $wp_filesystem;
		$file_path = trailingslashit( $this->base_dir ) . $filename;
		$json_data = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		return $wp_filesystem->put_contents( $file_path, $json_data, FS_CHMOD_FILE );
	}

	public function save_markdown( $post_data ) {
		global $wp_filesystem;

		// Create subdirectories based on post type
		$type_dir = trailingslashit( $this->content_dir ) . $post_data['type'];
		if ( ! $wp_filesystem->is_dir( $type_dir ) ) {
			$wp_filesystem->mkdir( $type_dir );
		}

		$filename  = sanitize_title( $post_data['slug'] ) . '.md';
		$file_path = trailingslashit( $type_dir ) . $filename;

		$markdown_content = $this->generate_frontmatter( $post_data['frontmatter'] );
		$markdown_content .= "\n\n" . $post_data['rendered_content'];

		return $wp_filesystem->put_contents( $file_path, $markdown_content, FS_CHMOD_FILE );
	}

	private function generate_frontmatter( $data, $indent = '' ) {
		if ( $indent === '' ) {
			$yaml = "---\n";
		} else {
			$yaml = "";
		}

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				// Handle empty arrays
				if ( empty( $value ) ) {
					$yaml .= $indent . $key . ": []\n";
				} else {
					// Check if associative or sequential
					$is_assoc = array_keys($value) !== range(0, count($value) - 1);
					$yaml .= $indent . $key . ":\n";
					if ( $is_assoc ) {
						$yaml .= $this->generate_frontmatter( $value, $indent . '  ' );
					} else {
						foreach ( $value as $item ) {
							if ( is_array( $item ) ) {
								$yaml .= $indent . "  -\n";
								$yaml .= $this->generate_frontmatter( $item, $indent . '    ' );
							} else {
								$yaml .= $indent . "  - " . $this->escape_yaml_string( $item ) . "\n";
							}
						}
					}
				}
			} else {
				$yaml .= $indent . $key . ": " . $this->escape_yaml_string( $value ) . "\n";
			}
		}

		if ( $indent === '' ) {
			$yaml .= "---";
		}

		return $yaml;
	}

	private function escape_yaml_string( $string ) {
		if ( is_bool( $string ) ) {
			return $string ? 'true' : 'false';
		}
		if ( is_numeric( $string ) ) {
			return $string;
		}
		if ( $string === null ) {
			return 'null';
		}

		// Basic escaping for strings that might break YAML
		$string = str_replace( '"', '\"', $string );
		// Replace newlines and carriage returns
		$string = str_replace( ["\r\n", "\n", "\r"], "\\n", $string );
		return '"' . $string . '"';
	}

	public function create_zip() {
		$zip_filename = 'headless-export-' . date('Y-m-d-H-i-s') . '.zip';
		$zip_filepath = trailingslashit( $this->base_dir ) . $zip_filename;

		$success = false;

		// We need to exclude the zip file itself and index/htaccess from the zip
		// To do this simply, we'll zip the content dir and the site-data.json

		// Try ZipArchive first (faster, more robust)
		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new \ZipArchive();
			if ( $zip->open( $zip_filepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) === true ) {
				// Add site-data.json if exists
				if ( file_exists( $this->base_dir . '/site-data.json' ) ) {
					$zip->addFile( $this->base_dir . '/site-data.json', 'site-data.json' );
				}
				// Add content directory
				if ( is_dir( $this->content_dir ) ) {
					$zip->addEmptyDir( 'content' );
					$this->add_folder_to_zip_filtered( $this->content_dir, $zip, strlen( $this->content_dir ) + 1, 'content/' );
				}
				$zip->close();
				$success = true;
			}
		}
		// Fallback to WordPress bundled PclZip
		else {
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
			$zip = new \PclZip( $zip_filepath );

			$files_to_zip = [];
			if ( file_exists( $this->base_dir . '/site-data.json' ) ) {
				$files_to_zip[] = $this->base_dir . '/site-data.json';
			}
			if ( is_dir( $this->content_dir ) ) {
				$files_to_zip[] = $this->content_dir;
			}

			if ( ! empty( $files_to_zip ) ) {
				$v_list = $zip->create( $files_to_zip, PCLZIP_OPT_REMOVE_PATH, $this->base_dir );
				if ( $v_list != 0 ) {
					$success = true;
				}
			}
		}

		if ( $success ) {
			// Do not delete base_dir here. Keep it protected.
			// Return a secure download URL
			$download_url = wp_nonce_url(
				admin_url( 'admin-ajax.php?action=wphe_download_zip&file=' . urlencode( $zip_filename ) ),
				'wphe_download_zip'
			);
			return $download_url;
		}

		return false;
	}

	private function add_folder_to_zip_filtered( $folder, &$zip_file, $exclusive_length, $prefix = '' ) {
		$handle = opendir( $folder );
		while ( false !== ( $f = readdir( $handle ) ) ) {
			if ( $f != '.' && $f != '..' ) {
				$file_path = "$folder/$f";
				$local_path = $prefix . substr( $file_path, $exclusive_length );
				if ( is_file( $file_path ) ) {
					$zip_file->addFile( $file_path, $local_path );
				} elseif ( is_dir( $file_path ) ) {
					$zip_file->addEmptyDir( $local_path );
					$this->add_folder_to_zip_filtered( $file_path, $zip_file, $exclusive_length, $prefix );
				}
			}
		}
		closedir( $handle );
	}

	private function add_folder_to_zip( $folder, &$zip_file, $exclusive_length ) {
		$handle = opendir( $folder );
		while ( false !== ( $f = readdir( $handle ) ) ) {
			if ( $f != '.' && $f != '..' ) {
				$file_path = "$folder/$f";
				$local_path = substr( $file_path, $exclusive_length );
				if ( is_file( $file_path ) ) {
					$zip_file->addFile( $file_path, $local_path );
				} elseif ( is_dir( $file_path ) ) {
					$zip_file->addEmptyDir( $local_path );
					$this->add_folder_to_zip( $file_path, $zip_file, $exclusive_length );
				}
			}
		}
		closedir( $handle );
	}
}
