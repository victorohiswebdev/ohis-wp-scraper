# WP Headless Content Extractor

A robust, Object-Oriented WordPress plugin to extract website content, clean it of WordPress bloat, and export it into a structured `.ZIP` containing JSON and Markdown files. Ideal for migrating sites to modern headless frameworks like Next.js, Astro, or Gatsby.

## Features

- **AJAX Batch Processing:** Extracts content in batches to prevent PHP memory exhaustion and timeouts.
- **Dynamic Post Type Support:** Automatically detects and allows extraction of custom post types.
- **Clean Markdown Export:** Extracts raw database content and rendered content (shortcodes executed), formatted securely into Markdown files with YAML Frontmatter.
- **Global Data Export:** Generates a `site-data.json` file mapping global site architecture, structured menus, and media.
- **Secure Processing:** Stores temporary files in a secured directory within `wp-content/uploads/wp-headless-extractor` using auto-generated `.htaccess` rules.
- **No Dependencies:** Built with 100% native WordPress and PHP functions, requiring no external packages like Composer. Gracefully falls back to WordPress's bundled `PclZip` if PHP's `ZipArchive` isn't available.

## Installation

1. Clone or copy the `wp-headless-extractor` directory into your `wp-content/plugins/` folder.
2. Go to your WordPress Admin dashboard.
3. Navigate to **Plugins** > **Installed Plugins**.
4. Find **WP Headless Content Extractor** and click **Activate**.

## Usage

1. After activation, look for the **Site Extractor** menu item in your WordPress admin sidebar.
2. Select the content you want to export (Pages, Posts, Custom Post Types, Menus, Media).
3. Click the **Start Extraction** button.
4. Watch the progress bar as the plugin processes your content in batches.
5. Once complete (100%), click the **Download ZIP File** button to retrieve your structured site data.

## Output Structure

The downloaded ZIP file contains:

- `site-data.json`: Global data including site info, hierarchical navigation menus, and media library details.
- `/content/`: A folder organized by post type (e.g., `post`, `page`, `custom_post_type`).
  - Inside each folder are `.md` files for every published item.
  - Each `.md` file contains YAML frontmatter (Title, Slug, SEO Data, etc.) at the top, followed by the rendered HTML/text content below.

## Security

The plugin strictly verifies permissions (`manage_options`) and nonces for all endpoints. Extraction artifacts are securely protected from public internet access using `.htaccess` directives during the generation process.

## Requirements

- WordPress 5.0+
- PHP 7.4+
