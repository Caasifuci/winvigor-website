<?php
/**
 * Field definitions: the single source of truth for every custom field.
 * Used to register post meta (REST schema), render admin meta boxes,
 * sanitise input, and describe MCP ability inputs.
 *
 * These mirror src/content.config.ts in the Astro project. Change both together.
 */
defined( 'ABSPATH' ) || exit;

function wv_disciplines(): array {
	return array( 'Software engineering', 'Cloud & platform', 'Data & analytics', 'Quality & test', 'Analysis & delivery' );
}

/** Clearance levels an open role may require. */
function wv_clearance_levels(): array {
	return array( 'NATO CONFIDENTIAL', 'NATO SECRET', 'COSMIC TOP SECRET', 'EU SECRET' );
}

function wv_service_titles(): array {
	return array( 'Strategic IT consulting', 'Custom software development', 'Resource augmentation', 'Operational risk management' );
}

/**
 * Field types:
 *   text, textarea, number, date (Y-m-d), bool, select (options),
 *   list (array of strings, entered one per line), results (array of {value,label,detail}).
 */
function wv_fields(): array {
	$sample = array(
		'type'  => 'bool',
		'label' => 'Sample content',
		'help'  => 'Illustrative content. Shown with a "Sample" tag and hidden when the site is built with PUBLIC_SHOW_SAMPLES=false.',
	);

	return array(
		'post'          => array(
			'description' => array( 'type' => 'textarea', 'label' => 'Summary', 'max' => 220, 'help' => 'One or two sentences shown in listings and search results. Falls back to the excerpt.' ),
			'sample'      => $sample,
		),
		'wv_case_study' => array(
			'summary'  => array( 'type' => 'textarea', 'label' => 'Summary', 'max' => 260, 'required' => true ),
			'client'   => array( 'type' => 'text', 'label' => 'Client (as shown publicly)', 'required' => true, 'help' => 'Use an anonymised description, e.g. "A European public institution", unless the client has approved being named.' ),
			'sector'   => array( 'type' => 'text', 'label' => 'Sector', 'required' => true ),
			'services' => array( 'type' => 'list', 'label' => 'Services', 'options' => wv_service_titles(), 'required' => true, 'help' => 'One per line. Must match a service title exactly.' ),
			'duration' => array( 'type' => 'text', 'label' => 'Duration' ),
			'results'  => array( 'type' => 'results', 'label' => 'Results (max 4)', 'help' => 'One per line: value | label | detail (detail optional). Example: 38% | Lower annual run cost | Year one after cutover.' ),
			'order'    => array( 'type' => 'number', 'label' => 'Sort order', 'default' => 100, 'help' => 'Lower numbers appear first.' ),
			'featured' => array( 'type' => 'bool', 'label' => 'Feature on home page' ),
			'sample'   => $sample,
		),
		'wv_talent'     => array(
			'ref'            => array( 'type' => 'text', 'label' => 'Profile reference', 'required' => true, 'pattern' => '^WV-\d{4}$', 'help' => 'Format WV-1234. Shown publicly instead of a name.' ),
			'discipline'     => array( 'type' => 'select', 'label' => 'Discipline', 'options' => wv_disciplines(), 'required' => true ),
			'seniority'      => array( 'type' => 'select', 'label' => 'Seniority', 'options' => array( 'Mid-level', 'Senior', 'Lead', 'Principal' ), 'required' => true ),
			'years'          => array( 'type' => 'number', 'label' => 'Years of experience', 'required' => true ),
			'skills'         => array( 'type' => 'list', 'label' => 'Key skills (2–6)', 'required' => true, 'help' => 'One per line.' ),
			'languages'      => array( 'type' => 'list', 'label' => 'Languages', 'required' => true, 'help' => 'One per line.' ),
			'location'       => array( 'type' => 'text', 'label' => 'Location', 'required' => true, 'help' => 'e.g. "Brussels area" or "Remote (EU)".' ),
			'available_from' => array( 'type' => 'date', 'label' => 'Available from', 'help' => 'Leave empty if available now.' ),
			'engagement'     => array( 'type' => 'select', 'label' => 'Engagement', 'options' => array( 'Contract', 'Permanent', 'Contract or permanent' ), 'required' => true ),
			'summary'        => array( 'type' => 'textarea', 'label' => 'Public summary', 'max' => 200, 'required' => true ),
			'listed'         => array( 'type' => 'bool', 'label' => 'Show on talent board', 'default' => true ),
			'sample'         => $sample,
		),
		'wv_role'       => array(
			'discipline' => array( 'type' => 'select', 'label' => 'Discipline', 'options' => wv_disciplines(), 'required' => true ),
			'location'   => array( 'type' => 'text', 'label' => 'Location', 'required' => true ),
			'role_type'  => array( 'type' => 'select', 'label' => 'Type', 'options' => array( 'Permanent', 'Contract', 'Permanent or contract' ), 'required' => true ),
			'summary'    => array( 'type' => 'textarea', 'label' => 'Summary', 'max' => 220, 'required' => true ),
			'clearance'  => array( 'type' => 'select', 'label' => 'Security clearance', 'options' => wv_clearance_levels(), 'help' => 'Only if the client requires it. Leave empty otherwise.' ),
			'clearance_status' => array( 'type' => 'select', 'label' => 'Clearance status', 'options' => array( 'Must hold', 'Must be eligible' ), 'help' => 'Required when a clearance level is set.' ),
			'open'       => array( 'type' => 'bool', 'label' => 'Accepting applications', 'default' => true ),
			'sample'     => $sample,
		),
		'wv_log'        => array(
			'log_date' => array( 'type' => 'date', 'label' => 'Date', 'required' => true ),
			'sector'   => array( 'type' => 'text', 'label' => 'Sector', 'required' => true ),
			'sample'   => $sample,
		),
	);
}

/** Meta key for a field. */
function wv_meta_key( string $field ): string {
	return 'wv_' . $field;
}

/** JSON schema fragment for REST registration and ability inputs. */
function wv_field_schema( array $def ): array {
	switch ( $def['type'] ) {
		case 'number':
			return array( 'type' => 'integer' );
		case 'bool':
			return array( 'type' => 'boolean' );
		case 'date':
			return array( 'type' => 'string', 'format' => 'date' );
		case 'select':
			return array( 'type' => 'string', 'enum' => array_merge( array( '' ), $def['options'] ) );
		case 'list':
			return array( 'type' => 'array', 'items' => array( 'type' => 'string' ) );
		case 'results':
			return array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'value'  => array( 'type' => 'string' ),
						'label'  => array( 'type' => 'string' ),
						'detail' => array( 'type' => 'string' ),
					),
				),
			);
		default:
			return array( 'type' => 'string' );
	}
}

/** Sanitise one value according to its field definition. Accepts arrays or newline text for list/results. */
function wv_sanitize_field( array $def, $value ) {
	switch ( $def['type'] ) {
		case 'number':
			return ( '' === $value || null === $value ) ? (int) ( $def['default'] ?? 0 ) : (int) $value;
		case 'bool':
			return in_array( $value, array( true, 1, '1', 'true', 'on', 'yes' ), true );
		case 'date':
			$value = sanitize_text_field( (string) $value );
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
		case 'select':
			$value = sanitize_text_field( (string) $value );
			return in_array( $value, $def['options'], true ) ? $value : '';
		case 'list':
			$items = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n|,/', (string) $value );
			$items = array_map( fn( $i ) => sanitize_text_field( (string) $i ), $items );
			return array_values( array_filter( $items, 'strlen' ) );
		case 'results':
			$rows = array();
			if ( is_array( $value ) ) {
				foreach ( $value as $row ) {
					$rows[] = array( $row['value'] ?? '', $row['label'] ?? '', $row['detail'] ?? '' );
				}
			} else {
				foreach ( preg_split( '/\r\n|\r|\n/', (string) $value ) as $line ) {
					if ( '' !== trim( $line ) ) {
						$rows[] = array_map( 'trim', explode( '|', $line ) ) + array( '', '', '' );
					}
				}
			}
			$out = array();
			foreach ( array_slice( $rows, 0, 4 ) as $r ) {
				$v = sanitize_text_field( (string) $r[0] );
				$l = sanitize_text_field( (string) $r[1] );
				if ( '' === $v || '' === $l ) {
					continue;
				}
				$item = array( 'value' => $v, 'label' => $l );
				$d    = sanitize_text_field( (string) ( $r[2] ?? '' ) );
				if ( '' !== $d ) {
					$item['detail'] = $d;
				}
				$out[] = $item;
			}
			return $out;
		case 'textarea':
			$value = sanitize_textarea_field( (string) $value );
			return isset( $def['max'] ) ? mb_substr( $value, 0, (int) $def['max'] ) : $value;
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Validate a full set of values for a post type.
 * Returns an array of human-readable problems (empty when valid).
 */
function wv_validate_fields( string $post_type, array $values ): array {
	$problems = array();
	foreach ( wv_fields()[ $post_type ] ?? array() as $name => $def ) {
		$v = $values[ $name ] ?? null;
		$empty = ( null === $v || '' === $v || array() === $v );
		if ( ! empty( $def['required'] ) && $empty ) {
			$problems[] = sprintf( '%s is required.', $def['label'] );
		}
		if ( ! $empty && isset( $def['pattern'] ) && ! preg_match( '/' . $def['pattern'] . '/', (string) $v ) ) {
			$problems[] = sprintf( '%s has the wrong format.', $def['label'] );
		}
		if ( 'list' === $def['type'] && isset( $def['options'] ) && is_array( $v ) ) {
			foreach ( $v as $item ) {
				if ( ! in_array( $item, $def['options'], true ) ) {
					$problems[] = sprintf( '"%s" is not a valid value for %s.', $item, $def['label'] );
				}
			}
		}
	}
	if ( 'wv_role' === $post_type && ! empty( $values['clearance'] ) && empty( $values['clearance_status'] ) ) {
		$problems[] = 'Choose whether candidates must hold the clearance or only be eligible for it.';
	}
	if ( 'wv_role' === $post_type && empty( $values['clearance'] ) && ! empty( $values['clearance_status'] ) ) {
		$problems[] = 'Clearance status is set but no clearance level is chosen.';
	}
	if ( 'wv_talent' === $post_type && isset( $values['skills'] ) && is_array( $values['skills'] ) && ( count( $values['skills'] ) < 2 || count( $values['skills'] ) > 6 ) ) {
		$problems[] = 'List between 2 and 6 key skills.';
	}
	return $problems;
}
