<?php
/** Content types and post meta registration. */
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'wv_register_content_types' );

function wv_register_content_types(): void {
	$common = array(
		'public'              => false, // no WordPress front end; Astro renders everything
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'map_meta_cap'        => true,
	);

	register_post_type(
		'wv_case_study',
		$common + array(
			'labels'        => wv_labels( 'Case study', 'Case studies' ),
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 21,
			'rest_base'     => 'case-studies',
			'supports'      => array( 'title', 'editor', 'custom-fields', 'revisions' ),
		)
	);

	register_post_type(
		'wv_talent',
		$common + array(
			'labels'        => wv_labels( 'Talent profile', 'Talent board' ),
			'menu_icon'     => 'dashicons-groups',
			'menu_position' => 22,
			'rest_base'     => 'talent',
			'supports'      => array( 'title', 'custom-fields', 'revisions' ),
		)
	);

	register_post_type(
		'wv_role',
		$common + array(
			'labels'        => wv_labels( 'Open role', 'Open roles' ),
			'menu_icon'     => 'dashicons-id-alt',
			'menu_position' => 23,
			'rest_base'     => 'roles',
			'supports'      => array( 'title', 'custom-fields', 'revisions' ),
		)
	);

	register_post_type(
		'wv_log',
		$common + array(
			'labels'        => wv_labels( 'Log entry', 'Delivery log' ),
			'menu_icon'     => 'dashicons-list-view',
			'menu_position' => 24,
			'rest_base'     => 'delivery-log',
			'supports'      => array( 'title', 'custom-fields' ),
		)
	);

	// Enquiries from the contact form: private, never exposed through the REST API.
	register_post_type(
		'wv_enquiry',
		array(
			'labels'          => wv_labels( 'Enquiry', 'Enquiries' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 25,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);

	foreach ( wv_fields() as $post_type => $fields ) {
		foreach ( $fields as $name => $def ) {
			$schema = wv_field_schema( $def );
			register_post_meta(
				$post_type,
				wv_meta_key( $name ),
				array(
					'type'              => $schema['type'],
					'single'            => true,
					'default'           => $def['default'] ?? ( 'array' === $schema['type'] ? array() : ( 'boolean' === $schema['type'] ? false : ( 'integer' === $schema['type'] ? 0 : '' ) ) ),
					'show_in_rest'      => array( 'schema' => $schema ),
					'sanitize_callback' => fn( $value ) => wv_sanitize_field( $def, $value ),
					'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
				)
			);
		}
	}
}

function wv_labels( string $singular, string $plural ): array {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'add_new'            => 'Add new',
		'add_new_item'       => "Add new {$singular}",
		'edit_item'          => "Edit {$singular}",
		'new_item'           => "New {$singular}",
		'view_item'          => "View {$singular}",
		'search_items'       => "Search {$plural}",
		'not_found'          => 'Nothing found',
		'not_found_in_trash' => 'Nothing found in Trash',
		'all_items'          => $plural,
		'menu_name'          => $plural,
	);
}

/** Post types whose changes should rebuild the public site. */
function wv_content_post_types(): array {
	return array( 'post', 'wv_case_study', 'wv_talent', 'wv_role', 'wv_log' );
}

/** Read all fields of a post into a plain array. */
function wv_get_fields( int $post_id ): array {
	$post_type = get_post_type( $post_id );
	$out       = array();
	foreach ( wv_fields()[ $post_type ] ?? array() as $name => $def ) {
		$value = get_post_meta( $post_id, wv_meta_key( $name ), true );
		switch ( $def['type'] ) {
			case 'number':
				$value = (int) $value;
				break;
			case 'bool':
				$value = (bool) $value;
				break;
			case 'list':
			case 'results':
				$value = is_array( $value ) ? $value : array();
				break;
		}
		$out[ $name ] = $value;
	}
	return $out;
}

/**
 * Block the guard terms in titles and body content at save time, for every
 * editor (classic, block editor, REST). Publishing is downgraded to draft.
 */
add_filter(
	'wp_insert_post_data',
	function ( array $data ) {
		if ( ! in_array( $data['post_type'], wv_content_post_types(), true ) || 'publish' !== $data['post_status'] ) {
			return $data;
		}
		$term = wv_guard_find( $data['post_title'], $data['post_content'], $data['post_excerpt'] );
		if ( $term ) {
			$data['post_status'] = 'draft';
			set_transient( 'wv_guard_notice_' . get_current_user_id(), $term, 60 );
		}
		return $data;
	},
	10,
	1
);
