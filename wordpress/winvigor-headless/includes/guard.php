<?php
/**
 * Public-content guard.
 *
 * Company rule: brand content (pages, case studies, insights, delivery log,
 * talent profiles) never mentions security clearance or defence-sector work.
 * This guard blocks publishing content that does, whether it is entered in
 * the admin or through Claude (MCP abilities).
 *
 * Exception: open roles. Contractor roles (e.g. for NATO agencies) must state
 * the clearance required, so roles may mention clearance and NATO/EU
 * classification levels. The level itself belongs in the structured
 * "Security clearance" field. Defence/military wording stays blocked.
 * Talent profiles are never exempt: publishing that an individual holds a
 * clearance advertises a cleared person.
 */
defined( 'ABSPATH' ) || exit;

/** Terms that open roles may use (clearance requirements). */
function wv_guard_role_exempt_terms(): array {
	return array( 'security clearance', 'clearance', 'habilitation de sécurité', 'veiligheidsmachtiging', 'NATO', 'top secret', 'EU SECRET', 'classified' );
}

/**
 * Terms that must not appear in public content of the given post type.
 * Extend via the `wv_guard_terms` filter (receives the post type).
 */
function wv_guard_terms( string $post_type = '' ): array {
	$terms = apply_filters(
		'wv_guard_terms',
		array(
			'security clearance',
			'clearance',
			'habilitation de sécurité',
			'veiligheidsmachtiging',
			'defence',
			'defense',
			'military',
			'NATO',
			'top secret',
			'EU SECRET',
			'classified',
		),
		$post_type
	);
	if ( 'wv_role' === $post_type ) {
		$exempt = array_map( 'strtolower', wv_guard_role_exempt_terms() );
		$terms  = array_values( array_filter( $terms, fn( $t ) => ! in_array( strtolower( $t ), $exempt, true ) ) );
	}
	return $terms;
}

/**
 * Return the first term blocked for this post type found in any of the given
 * strings or arrays, or null.
 *
 * @param string $post_type Post type being checked (exemptions differ per type).
 * @param mixed  ...$values Strings or (nested) arrays of strings.
 */
function wv_guard_find( string $post_type, ...$values ): ?string {
	$text = '';
	array_walk_recursive(
		$values,
		function ( $v ) use ( &$text ) {
			if ( is_scalar( $v ) ) {
				$text .= ' ' . wp_strip_all_tags( (string) $v );
			}
		}
	);
	foreach ( wv_guard_terms( $post_type ) as $term ) {
		if ( preg_match( '/\b' . preg_quote( $term, '/' ) . '\b/iu', $text ) ) {
			return $term;
		}
	}
	return null;
}
