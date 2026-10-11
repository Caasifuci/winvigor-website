<?php
/**
 * Public-content guard.
 *
 * Company rule: the public site never mentions security clearance or
 * defence-sector work. This guard blocks publishing content that does,
 * whether it is entered in the admin or through Claude (MCP abilities).
 */
defined( 'ABSPATH' ) || exit;

/** Terms that must not appear in public content. Extend via the `wv_guard_terms` filter. */
function wv_guard_terms(): array {
	return apply_filters(
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
		)
	);
}

/**
 * Return the first blocked term found in any of the given strings or arrays, or null.
 *
 * @param mixed ...$values Strings or (nested) arrays of strings.
 */
function wv_guard_find( ...$values ): ?string {
	$text = '';
	array_walk_recursive(
		$values,
		function ( $v ) use ( &$text ) {
			if ( is_scalar( $v ) ) {
				$text .= ' ' . wp_strip_all_tags( (string) $v );
			}
		}
	);
	foreach ( wv_guard_terms() as $term ) {
		if ( preg_match( '/\b' . preg_quote( $term, '/' ) . '\b/iu', $text ) ) {
			return $term;
		}
	}
	return null;
}
