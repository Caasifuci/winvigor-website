<?php
/**
 * Abilities exposed to Claude through the WordPress MCP Adapter.
 *
 * Requires WordPress 6.9+ (Abilities API in core) and the MCP Adapter
 * plugin (github.com/WordPress/mcp-adapter). Each ability checks the
 * connected user's capabilities, validates fields and applies the
 * public-content guard, exactly like the admin screens do.
 */
defined( 'ABSPATH' ) || exit;

/** Friendly content type names used by abilities → WordPress post types. */
function wv_ability_types(): array {
	return array(
		'insight'    => 'post',
		'case-study' => 'wv_case_study',
		'talent'     => 'wv_talent',
		'role'       => 'wv_role',
		'log'        => 'wv_log',
	);
}

add_action(
	'wp_abilities_api_categories_init',
	function () {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			'winvigor',
			array(
				'label'       => 'Winvigor website',
				'description' => 'Manage content for www.winvigor.com: insights, case studies, talent board, open roles and delivery log.',
			)
		);
	}
);

add_action( 'wp_abilities_api_init', 'wv_register_abilities' );

function wv_register_abilities(): void {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	$expose = fn( bool $readonly, bool $destructive = false ) => array(
		'show_in_rest' => true,
		'mcp'          => array( 'public' => true ),
		'annotations'  => array(
			'readonly'    => $readonly,
			'destructive' => $destructive,
			'idempotent'  => $readonly,
		),
	);
	$can_edit = fn() => current_user_can( 'edit_posts' );
	$type_enum = array( 'type' => 'string', 'enum' => array_keys( wv_ability_types() ) );

	wp_register_ability(
		'winvigor/site-status',
		array(
			'label'               => 'Website status',
			'description'         => 'Counts of published and draft content by type, the public site URL, and the status of the last site rebuild. Call this first to orient.',
			'category'            => 'winvigor',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'wv_ability_site_status',
			'permission_callback' => $can_edit,
			'meta'                => $expose( true ),
		)
	);

	wp_register_ability(
		'winvigor/list-content',
		array(
			'label'               => 'List website content',
			'description'         => 'List items of one content type with their fields. Types: insight (articles), case-study, talent (talent board profiles), role (open roles), log (delivery log).',
			'category'            => 'winvigor',
			'input_schema'        => array(
				'type'       => 'object',
				'required'   => array( 'type' ),
				'properties' => array(
					'type'   => $type_enum,
					'status' => array( 'type' => 'string', 'enum' => array( 'publish', 'draft', 'any' ), 'default' => 'any' ),
					'search' => array( 'type' => 'string' ),
				),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'wv_ability_list',
			'permission_callback' => $can_edit,
			'meta'                => $expose( true ),
		)
	);

	wp_register_ability(
		'winvigor/get-content',
		array(
			'label'               => 'Get one content item',
			'description'         => 'Fetch one item by ID, including its body HTML and all fields.',
			'category'            => 'winvigor',
			'input_schema'        => array(
				'type'       => 'object',
				'required'   => array( 'id' ),
				'properties' => array( 'id' => array( 'type' => 'integer' ) ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'wv_ability_get',
			'permission_callback' => $can_edit,
			'meta'                => $expose( true ),
		)
	);

	$save_descriptions = array(
		'insight'    => 'Create or update an Insights article. "title" is the headline, "content" is the article body as HTML (<p>, <h2>, <h3>, <ul>, <blockquote>). "fields.description" is a one or two sentence summary.',
		'case-study' => 'Create or update a case study. "title" is the headline, "content" is the narrative as HTML (sections Context, Approach, Outcome as <h2>). Name a client in fields.client only if they have approved it; otherwise describe them anonymously.',
		'talent'     => 'Create or update a talent board profile. "title" is the role (e.g. "Senior Java engineer"). Profiles are public and anonymous: never include names, employers, photos, security clearance or defence work. If fields.ref matches an existing profile, that profile is updated.',
		'role'       => 'Create or update an open role on the Careers page. "title" is the job title. If the client requires a security clearance, set fields.clearance (NATO CONFIDENTIAL, NATO SECRET, COSMIC TOP SECRET or EU SECRET) and fields.clearance_status (Must hold / Must be eligible) instead of writing it in the summary. Name the client agency only if the contract allows publicity.',
		'log'        => 'Add or update a delivery log entry for the home page. "title" is one factual sentence about what happened; fields.log_date and fields.sector are required.',
	);
	foreach ( wv_ability_types() as $type => $post_type ) {
		wp_register_ability(
			'winvigor/save-' . $type,
			array(
				'label'               => 'Save ' . str_replace( '-', ' ', $type ),
				'description'         => $save_descriptions[ $type ] . ' Publishing triggers a site rebuild (live in about two minutes). Use status "draft" to save without publishing. Content with missing required fields or blocked terms is saved as a draft and the problems are returned.',
				'category'            => 'winvigor',
				'input_schema'        => wv_ability_save_schema( $post_type ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => fn( $input ) => wv_ability_save( $post_type, (array) $input ),
				'permission_callback' => $can_edit,
				'meta'                => $expose( false ),
			)
		);
	}

	wp_register_ability(
		'winvigor/unpublish-content',
		array(
			'label'               => 'Unpublish content',
			'description'         => 'Move a published item back to draft so it disappears from the public site after the next rebuild. Nothing is deleted.',
			'category'            => 'winvigor',
			'input_schema'        => array(
				'type'       => 'object',
				'required'   => array( 'id' ),
				'properties' => array( 'id' => array( 'type' => 'integer' ) ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'wv_ability_unpublish',
			'permission_callback' => $can_edit,
			'meta'                => $expose( false, true ),
		)
	);

	wp_register_ability(
		'winvigor/rebuild-site',
		array(
			'label'               => 'Rebuild the public site now',
			'description'         => 'Trigger an immediate rebuild of www.winvigor.com instead of waiting for the automatic batched rebuild.',
			'category'            => 'winvigor',
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => function () {
				wp_clear_scheduled_hook( 'wv_run_rebuild' );
				update_option( 'wv_rebuild_reason', 'requested via MCP', false );
				$ok = wv_trigger_rebuild();
				return array( 'triggered' => $ok, 'detail' => $ok ? 'Build started. The site updates in about two minutes.' : ( get_option( 'wv_last_rebuild' )['error'] ?? 'WV_DEPLOY_HOOK is not configured.' ) );
			},
			'permission_callback' => fn() => current_user_can( 'publish_posts' ),
			'meta'                => $expose( false ),
		)
	);
}

function wv_ability_save_schema( string $post_type ): array {
	$field_props = array();
	foreach ( wv_fields()[ $post_type ] as $name => $def ) {
		$field_props[ $name ] = wv_field_schema( $def ) + array( 'description' => trim( $def['label'] . '. ' . ( $def['help'] ?? '' ) ) );
	}
	$props = array(
		'id'     => array( 'type' => 'integer', 'description' => 'Existing item ID to update. Omit to create.' ),
		'title'  => array( 'type' => 'string' ),
		'status' => array( 'type' => 'string', 'enum' => array( 'publish', 'draft' ), 'default' => 'draft' ),
		'fields' => array( 'type' => 'object', 'properties' => $field_props ),
	);
	if ( post_type_supports( $post_type, 'editor' ) || 'post' === $post_type ) {
		$props['content'] = array( 'type' => 'string', 'description' => 'Body as HTML.' );
	}
	if ( 'post' === $post_type ) {
		$props['topics'] = array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Topic names (WordPress categories).' );
	}
	return array( 'type' => 'object', 'properties' => $props );
}

function wv_ability_item( WP_Post $post, bool $with_content = false ): array {
	$type = array_search( $post->post_type, wv_ability_types(), true );
	$item = array(
		'id'       => $post->ID,
		'type'     => $type,
		'title'    => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
		'status'   => $post->post_status,
		'slug'     => $post->post_name,
		'modified' => $post->post_modified_gmt,
		'url'      => 'publish' === $post->post_status ? get_permalink( $post ) : null,
		'fields'   => wv_get_fields( $post->ID ),
	);
	if ( 'post' === $post->post_type ) {
		$item['topics'] = wp_list_pluck( get_the_category( $post->ID ), 'name' );
	}
	if ( $with_content ) {
		$item['content'] = $post->post_content;
	}
	return $item;
}

function wv_ability_site_status(): array {
	$counts = array();
	foreach ( wv_ability_types() as $type => $post_type ) {
		$c               = wp_count_posts( $post_type );
		$counts[ $type ] = array( 'published' => (int) ( $c->publish ?? 0 ), 'draft' => (int) ( $c->draft ?? 0 ) );
	}
	return array(
		'public_site'        => wv_site_url( '/' ),
		'content'            => $counts,
		'rebuild_configured' => (bool) wv_config( 'WV_DEPLOY_HOOK' ),
		'rebuild_queued'     => (bool) wp_next_scheduled( 'wv_run_rebuild' ),
		'last_rebuild'       => get_option( 'wv_last_rebuild' ) ?: null,
		'rules'              => array(
			'No security clearance or defence-sector references in brand content (pages, case studies, insights, log, talent).',
			'Talent profiles are anonymous: role, skills and availability only. Never state an individual\'s clearance.',
			'Open roles may state a required clearance, using the Security clearance field.',
			'Name clients in case studies only with their approval.',
			'Show, do not tell: figures, dates and outcomes over adjectives.',
		),
	);
}

function wv_ability_list( $input ): array {
	$input     = (array) $input;
	$post_type = wv_ability_types()[ $input['type'] ?? '' ] ?? null;
	if ( ! $post_type ) {
		return array( 'error' => 'Unknown type.' );
	}
	$status = $input['status'] ?? 'any';
	$posts  = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any' === $status ? array( 'publish', 'draft', 'pending', 'future' ) : $status,
			'posts_per_page' => 200,
			's'              => $input['search'] ?? '',
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
	return array( 'items' => array_map( 'wv_ability_item', $posts ) );
}

function wv_ability_get( $input ) {
	$post = get_post( (int) ( (array) $input )['id'] );
	if ( ! $post || ! in_array( $post->post_type, wv_ability_types(), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return new WP_Error( 'not_found', 'No such item.' );
	}
	return wv_ability_item( $post, true );
}

function wv_ability_save( string $post_type, array $input ) {
	$fields_in = (array) ( $input['fields'] ?? array() );
	$id        = (int) ( $input['id'] ?? 0 );

	// Talent: match on profile reference so repeated saves update rather than duplicate.
	if ( ! $id && 'wv_talent' === $post_type && ! empty( $fields_in['ref'] ) ) {
		$existing = get_posts( array( 'post_type' => 'wv_talent', 'post_status' => 'any', 'meta_key' => 'wv_ref', 'meta_value' => sanitize_text_field( $fields_in['ref'] ), 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore
		$id       = $existing[0] ?? 0;
	}
	if ( $id ) {
		$current = get_post( $id );
		if ( ! $current || $current->post_type !== $post_type || ! current_user_can( 'edit_post', $id ) ) {
			return new WP_Error( 'not_found', 'No item of this type with that ID.' );
		}
	}

	$want_publish = 'publish' === ( $input['status'] ?? 'draft' );
	if ( $want_publish && ! current_user_can( 'publish_posts' ) ) {
		return new WP_Error( 'forbidden', 'This user cannot publish.' );
	}

	// Merge with existing values, sanitise.
	$values = $id ? wv_get_fields( $id ) : array();
	foreach ( wv_fields()[ $post_type ] as $name => $def ) {
		if ( array_key_exists( $name, $fields_in ) ) {
			$values[ $name ] = wv_sanitize_field( $def, $fields_in[ $name ] );
		} elseif ( ! $id && isset( $def['default'] ) ) {
			$values[ $name ] = $def['default'];
		}
	}

	$postarr = array( 'post_type' => $post_type );
	if ( $id ) {
		$postarr['ID'] = $id;
	}
	if ( isset( $input['title'] ) ) {
		$postarr['post_title'] = sanitize_text_field( (string) $input['title'] );
	}
	if ( isset( $input['content'] ) ) {
		$postarr['post_content'] = wp_kses_post( (string) $input['content'] );
	}
	$title   = $postarr['post_title'] ?? ( $id ? get_post_field( 'post_title', $id ) : '' );
	$content = $postarr['post_content'] ?? ( $id ? get_post_field( 'post_content', $id ) : '' );

	$problems = '' === trim( (string) $title ) ? array( 'A title is required.' ) : array();
	if ( $want_publish ) {
		$problems = array_merge( $problems, wv_validate_fields( $post_type, $values ) );
	}
	$term = wv_guard_find( $post_type, $title, $content, $values );
	if ( $term ) {
		$problems[] = sprintf( 'Contains "%s", which must not appear on the public site. Remove it.', $term );
	}
	$postarr['post_status'] = ( $want_publish && ! $problems ) ? 'publish' : 'draft';

	$saved = $id ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $saved ) ) {
		return $saved;
	}
	foreach ( $values as $name => $value ) {
		update_post_meta( $saved, wv_meta_key( $name ), $value );
	}
	if ( 'post' === $post_type && isset( $input['topics'] ) ) {
		$cat_ids = array();
		foreach ( (array) $input['topics'] as $topic ) {
			$topic = sanitize_text_field( (string) $topic );
			if ( '' === $topic ) {
				continue;
			}
			$term_obj  = get_term_by( 'name', $topic, 'category' );
			if ( ! $term_obj ) {
				$created = wp_insert_term( $topic, 'category' );
				if ( is_wp_error( $created ) ) {
					continue;
				}
				$cat_ids[] = (int) $created['term_id'];
			} else {
				$cat_ids[] = (int) $term_obj->term_id;
			}
		}
		wp_set_post_categories( $saved, $cat_ids );
	}

	$item             = wv_ability_item( get_post( $saved ) );
	$item['problems'] = $problems;
	$item['note']     = 'publish' === $item['status'] ? 'Published. The public site rebuilds automatically within a few minutes.' : ( $want_publish ? 'Saved as draft because of the problems listed.' : 'Saved as draft.' );
	return $item;
}

function wv_ability_unpublish( $input ) {
	$id   = (int) ( (array) $input )['id'];
	$post = get_post( $id );
	if ( ! $post || ! in_array( $post->post_type, wv_ability_types(), true ) || ! current_user_can( 'edit_post', $id ) ) {
		return new WP_Error( 'not_found', 'No such item.' );
	}
	wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
	return array( 'id' => $id, 'status' => 'draft', 'note' => 'Unpublished. It disappears from the public site after the next rebuild.' );
}
