# Role: Senior WordPress Plugin Developer & Software Architect

You are tasked with building a robust, production-ready, Object-Oriented WordPress plugin.

## 1. Project Overview

**Plugin Name:** WP Headless Content Extractor
**Objective:** Create a standard WordPress plugin that extracts all website content, cleans it of WordPress/Builder bloat, and exports it into a highly structured `.ZIP` file containing JSON and Markdown files. This payload will be used to migrate the site to a modern headless framework (Next.js, Astro, etc.).

## 2. Architecture & File Structure

The plugin must follow standard Object-Oriented Programming (OOP) practices, utilizing namespaces and the WordPress Plugin Boilerplate structure.

Generate the code for the following directory structure:

```text
wp-headless-extractor/
├── wp-headless-extractor.php       // Main plugin file (Header definition, activation/deactivation hooks, init)
├── README.md                       // Comprehensive documentation on how to install and use
├── includes/
│   ├── class-wphe-init.php         // Main bootstrapper class
│   ├── class-wphe-extractor.php    // Core extraction logic (DB queries, formatting)
│   ├── class-wphe-ajax.php         // Handles batched AJAX processing
│   ├── class-wphe-exporter.php     // Handles ZIP generation and file streaming
├── admin/
│   ├── class-wphe-admin.php        // Registers admin menu and settings pages
│   ├── views/
│   │   ├── admin-dashboard.php     // HTML for the UI (Settings, Checkboxes, Progress Bar)
│   ├── css/
│   │   └── admin-style.css         // Admin UI styling
│   ├── js/
│   │   └── admin-script.js         // AJAX logic for batch processing and UI updates
```

## 3. Core Functionalities & Scope

### 3.1. Admin Dashboard UI

- **Menu:** Register a top-level menu item in the WP Admin Dashboard named "Site Extractor".
- **Settings Form:**
  - Toggles/Checkboxes for what to extract: Pages, Posts, Custom Post Types (dynamically fetched), Media Library, Menus, Meta/SEO Data.
- **Execution UI:**
  - A prominent "Start Extraction" button.
  - A visual progress bar (0% to 100%) and a status log console (e.g., "Extracting post 10 of 50...").
  - A "Download ZIP" button that appears upon 100% completion.

### 3.2. Batch Processing Engine (Crucial)

- Do NOT extract everything in a single PHP request (to prevent 504 Timeouts / memory exhaustion).
- Implement an AJAX-driven batch process inside `class-wphe-ajax.php` and `admin-script.js`.
- The JS should request data in chunks (e.g., 10 posts per AJAX call), write to a temporary file/folder using WP_Filesystem, and recursively call the next batch until `total_items` is reached.

### 3.3. Data Extraction Scope (`class-wphe-extractor.php`)

- **Querying:** Only query published content (ignore drafts, revisions, and trashed posts).
- **Data Cleansing:** For every post/page, extract BOTH:
  - `raw_content`: The direct DB value.
  - `rendered_content`: Run `apply_filters('the_content', $post->post_content)` to execute shortcodes and page builder logic into clean frontend HTML.
- **Taxonomies & Menus:** Extract categories, tags, and exactly structured navigation menus (Header, Footer).
- **Media Assets:** Extract media URLs, file paths, and `alt` text.
- **SEO/Meta:** Query `postmeta` to grab Yoast/RankMath SEO titles and descriptions, plus any Advanced Custom Fields (ACF).

### 3.4. Output Generation (`class-wphe-exporter.php`)

The final output must be a generated `.ZIP` file containing:

1.  `site-data.json`: A single JSON file mapping the global site architecture, menus, media list, and general settings.
2.  `/content/`: A directory populated with `.md` (Markdown) files for every page and post.
    - **Formatting constraint:** Each Markdown file MUST contain YAML Frontmatter at the top (Title, Slug, Date, Featured Image URL, Author, SEO Meta) followed by the `rendered_content` (HTML/Text body) below the frontmatter.

## 4. Coding Standards & Security

- **Security First:** Use `wp_verify_nonce()` for all AJAX requests. Ensure `current_user_can('manage_options')` is checked on all admin and AJAX endpoints.
- **Data Escaping:** Use `esc_html()`, `esc_attr()`, and `wp_kses_post()` in the admin UI.
- **Namespaces:** Use `Namespace WP_Headless_Extractor;` across all PHP classes to prevent collisions.
- **WP Filesystem:** Use the native `WP_Filesystem` API to generate the temporary JSON/Markdown files and compile the ZIP, ensuring compatibility across different hosting environments.

## 5. Execution Steps for Jules

1.  Acknowledge these requirements.
2.  Generate the code file by file, utilizing the exact directory structure outlined in Section 2.
3.  Ensure the JavaScript correctly handles the AJAX loop for batch processing.
4.  Provide the complete, copy-pasteable code blocks for every required file.
