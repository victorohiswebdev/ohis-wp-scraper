<?php
namespace WP_Headless_Extractor;

class Extractor {

	public function get_site_data( $options = [] ) {
		$data = [
			'site_info' => [
				'name'        => get_bloginfo( 'name' ),
				'description' => get_bloginfo( 'description' ),
				'url'         => get_bloginfo( 'url' ),
			],
			'menus'    => [],
			'media'    => [],
			'settings' => [], // Additional global settings can be added here
		];

		if ( ! empty( $options['extract_menus'] ) && $options['extract_menus'] === 'true' ) {
			$data['menus'] = $this->extract_menus();
		}

		if ( ! empty( $options['extract_media'] ) && $options['extract_media'] === 'true' ) {
			$data['media'] = $this->extract_media();
		}

		return $data;
	}

	public function get_items_to_process( $options = [] ) {
		$items = [];
		$post_types_to_fetch = [];

		if ( ! empty( $options['extract_pages'] ) && $options['extract_pages'] === 'true' ) {
			$post_types_to_fetch[] = 'page';
		}
		if ( ! empty( $options['extract_posts'] ) && $options['extract_posts'] === 'true' ) {
			$post_types_to_fetch[] = 'post';
		}

		// Handle custom post types dynamically
		$all_post_types = get_post_types( [ 'public' => true, '_builtin' => false ], 'names' );
		foreach ( $all_post_types as $cpt ) {
			if ( ! empty( $options[ 'extract_cpt_' . $cpt ] ) && $options[ 'extract_cpt_' . $cpt ] === 'true' ) {
				$post_types_to_fetch[] = $cpt;
			}
		}

		if ( empty( $post_types_to_fetch ) ) {
			return $items;
		}

		$query_args = [
			'post_type'      => $post_types_to_fetch,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		$query = new \WP_Query( $query_args );

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$items[] = [
					'type' => get_post_type( $post_id ),
					'id'   => $post_id,
				];
			}
		}

		return $items;
	}

	public function extract_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return false;
		}

		// Extract raw and rendered content
		$raw_content      = $post->post_content;
		$rendered_content = apply_filters( 'the_content', $raw_content );

		// Extract categories and tags
		$categories = wp_get_post_terms( $post_id, 'category', [ 'fields' => 'names' ] );
		$tags       = wp_get_post_terms( $post_id, 'post_tag', [ 'fields' => 'names' ] );

		// Featured image
		$featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' );

		// Author
		$author_id   = $post->post_author;
		$author_name = get_the_author_meta( 'display_name', $author_id );

		// SEO & Meta (Basic Yoast/RankMath extraction example)
		$seo_title       = get_post_meta( $post_id, '_yoast_wpseo_title', true ) ?: get_post_meta( $post_id, 'rank_math_title', true ) ?: get_the_title( $post_id );
		$seo_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ?: get_post_meta( $post_id, 'rank_math_description', true ) ?: wp_trim_words( $post->post_excerpt ?: $raw_content, 20 );

		// ACF Data (if ACF is active)
		$acf_data = [];
		if ( function_exists( 'get_fields' ) ) {
			$acf_data = get_fields( $post_id ) ?: [];
		}

		// Prepare Frontmatter data
		$frontmatter = [
			'title'           => get_the_title( $post_id ),
			'slug'            => $post->post_name,
			'date'            => $post->post_date,
			'type'            => $post->post_type,
			'author'          => $author_name,
			'featured_image'  => $featured_image_url ?: '',
			'categories'      => $categories,
			'tags'            => $tags,
			'seo_title'       => $seo_title,
			'seo_description' => $seo_description,
			'acf'             => $acf_data,
		];

		return [
			'frontmatter'      => $frontmatter,
			'raw_content'      => $raw_content,
			'rendered_content' => $rendered_content,
			'slug'             => $post->post_name,
			'type'             => $post->post_type,
		];
	}

	private function extract_menus() {
		$menus_data = [];
		$menus      = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			$menu_items = wp_get_nav_menu_items( $menu->term_id );
			$items_data = [];

			if ( $menu_items ) {
				foreach ( $menu_items as $item ) {
					$items_data[] = [
						'id'        => $item->ID,
						'title'     => $item->title,
						'url'       => $item->url,
						'parent_id' => $item->menu_item_parent,
						'order'     => $item->menu_order,
					];
				}
			}

			$menus_data[ $menu->slug ] = [
				'name'  => $menu->name,
				'items' => $this->build_menu_tree( $items_data ),
			];
		}

		return $menus_data;
	}

	private function build_menu_tree( array $elements, $parentId = 0 ) {
		$branch = [];
		foreach ( $elements as $element ) {
			if ( $element['parent_id'] == $parentId ) {
				$children = $this->build_menu_tree( $elements, $element['id'] );
				if ( $children ) {
					$element['children'] = $children;
				} else {
					$element['children'] = [];
				}
				$branch[] = $element;
			}
		}
		return $branch;
	}

	private function extract_media() {
		$media_data = [];
		$query_args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
		];

		$query = new \WP_Query( $query_args );

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$media_data[] = [
					'id'        => $post->ID,
					'url'       => wp_get_attachment_url( $post->ID ),
					'alt'       => get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
					'title'     => $post->post_title,
					'mime_type' => $post->post_mime_type,
				];
			}
		}

		return $media_data;
	}
}
