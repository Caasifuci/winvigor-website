<?php
/** Admin UI: field meta boxes, save handling, notices, enquiry columns. */
defined( 'ABSPATH' ) || exit;

add_action(
	'add_meta_boxes',
	function () {
		$titles = array(
			'post'          => 'Insight details',
			'wv_case_study' => 'Case study details',
			'wv_talent'     => 'Profile details',
			'wv_role'       => 'Role details',
			'wv_log'        => 'Log entry details',
		);
		foreach ( $titles as $post_type => $title ) {
			add_meta_box( 'wv_fields', $title, 'wv_render_meta_box', $post_type, 'normal', 'high' );
		}
	}
);

/** Clearer title placeholders for each type. */
add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return array(
			'wv_talent' => 'Role, e.g. "Senior Java engineer" (no names)',
			'wv_role'   => 'Job title',
			'wv_log'    => 'What happened, in one sentence',
		)[ $post->post_type ] ?? $text;
	},
	10,
	2
);

function wv_render_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'wv_save_fields', 'wv_fields_nonce' );
	$fields = wv_fields()[ $post->post_type ] ?? array();
	$values = wv_get_fields( $post->ID );
	echo '<style>.wv-f{margin:0 0 14px}.wv-f label{display:block;font-weight:600;margin-bottom:4px}.wv-f .description{margin-top:4px}.wv-f textarea,.wv-f input[type=text]{width:100%}</style>';
	if ( 'wv_talent' === $post->post_type ) {
		echo '<p><strong>Public profile.</strong> Never enter names, photos, employers, security clearance or defence-sector details. Keep those in internal systems.</p>';
	}
	if ( 'wv_role' === $post->post_type ) {
		echo '<p><strong>Clearance:</strong> state any required level in the Security clearance field below, not in free text. Name the client agency only if the contract allows publicity. Defence/military wording is still blocked.</p>';
	}
	foreach ( $fields as $name => $def ) {
		$id    = 'wv_' . $name;
		$value = $values[ $name ];
		if ( ( '' === $value || null === $value ) && isset( $def['default'] ) && 'auto-draft' === $post->post_status ) {
			$value = $def['default'];
		}
		$req = ! empty( $def['required'] ) ? ' <span style="color:#b32d2e">*</span>' : '';
		echo '<div class="wv-f">';
		switch ( $def['type'] ) {
			case 'bool':
				printf( '<label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>', esc_attr( $id ), checked( (bool) $value, true, false ), esc_html( $def['label'] ) );
				break;
			case 'select':
				printf( '<label for="%1$s">%2$s%3$s</label><select id="%1$s" name="%1$s"><option value="">Choose…</option>', esc_attr( $id ), esc_html( $def['label'] ), $req ); // phpcs:ignore
				foreach ( $def['options'] as $opt ) {
					printf( '<option %1$s>%2$s</option>', selected( $value, $opt, false ), esc_html( $opt ) );
				}
				echo '</select>';
				break;
			case 'textarea':
				printf( '<label for="%1$s">%2$s%3$s</label><textarea id="%1$s" name="%1$s" rows="3" %4$s>%5$s</textarea>', esc_attr( $id ), esc_html( $def['label'] ), $req, isset( $def['max'] ) ? 'maxlength="' . (int) $def['max'] . '"' : '', esc_textarea( (string) $value ) ); // phpcs:ignore
				break;
			case 'list':
				$text = is_array( $value ) ? implode( "\n", $value ) : '';
				printf( '<label for="%1$s">%2$s%3$s</label><textarea id="%1$s" name="%1$s" rows="4">%4$s</textarea>', esc_attr( $id ), esc_html( $def['label'] ), $req, esc_textarea( $text ) ); // phpcs:ignore
				if ( isset( $def['options'] ) ) {
					echo '<p class="description">Allowed: ' . esc_html( implode( ', ', $def['options'] ) ) . '</p>';
				}
				break;
			case 'results':
				$lines = array();
				foreach ( (array) $value as $r ) {
					$lines[] = trim( implode( ' | ', array_filter( array( $r['value'] ?? '', $r['label'] ?? '', $r['detail'] ?? '' ), 'strlen' ) ) );
				}
				printf( '<label for="%1$s">%2$s</label><textarea id="%1$s" name="%1$s" rows="4">%3$s</textarea>', esc_attr( $id ), esc_html( $def['label'] ), esc_textarea( implode( "\n", $lines ) ) );
				break;
			default:
				$type = array( 'number' => 'number', 'date' => 'date' )[ $def['type'] ] ?? 'text';
				printf( '<label for="%1$s">%2$s%3$s</label><input type="%4$s" id="%1$s" name="%1$s" value="%5$s" %6$s>', esc_attr( $id ), esc_html( $def['label'] ), $req, esc_attr( $type ), esc_attr( (string) $value ), isset( $def['pattern'] ) ? 'pattern="' . esc_attr( $def['pattern'] ) . '"' : '' ); // phpcs:ignore
		}
		if ( ! empty( $def['help'] ) ) {
			echo '<p class="description">' . esc_html( $def['help'] ) . '</p>';
		}
		echo '</div>';
	}
}

add_action(
	'save_post',
	function ( int $post_id, WP_Post $post ) {
		if ( ! isset( $_POST['wv_fields_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wv_fields_nonce'] ), 'wv_save_fields' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$fields = wv_fields()[ $post->post_type ] ?? array();
		$values = array();
		foreach ( $fields as $name => $def ) {
			$raw             = wp_unslash( $_POST[ 'wv_' . $name ] ?? ( 'bool' === $def['type'] ? '' : '' ) ); // phpcs:ignore
			$values[ $name ] = wv_sanitize_field( $def, $raw );
			update_post_meta( $post_id, wv_meta_key( $name ), $values[ $name ] );
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}
		// Published content must be complete and pass the public-content guard.
		$problems = wv_validate_fields( $post->post_type, $values );
		$term     = wv_guard_find( $post->post_type, $post->post_title, $post->post_content, $values );
		if ( $term ) {
			$problems[] = sprintf( 'Contains "%s", which must not appear on the public site.', $term );
		}
		if ( $problems ) {
			remove_action( 'save_post', __FUNCTION__ );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
			set_transient( 'wv_save_problems_' . get_current_user_id(), $problems, 120 );
		}
	},
	10,
	2
);

add_action(
	'admin_notices',
	function () {
		$uid      = get_current_user_id();
		$problems = get_transient( 'wv_save_problems_' . $uid );
		$term     = get_transient( 'wv_guard_notice_' . $uid );
		if ( $term ) {
			$problems   = (array) $problems;
			$problems[] = sprintf( 'Contains "%s", which must not appear on the public site.', $term );
			delete_transient( 'wv_guard_notice_' . $uid );
		}
		if ( $problems ) {
			delete_transient( 'wv_save_problems_' . $uid );
			echo '<div class="notice notice-error"><p><strong>Saved as draft, not published.</strong></p><ul style="list-style:disc;padding-left:20px">';
			foreach ( (array) $problems as $p ) {
				echo '<li>' . esc_html( $p ) . '</li>';
			}
			echo '</ul></div>';
		}
	}
);

/* Useful list columns. */
add_filter( 'manage_wv_talent_posts_columns', fn( $c ) => array_slice( $c, 0, 2, true ) + array( 'wv_ref' => 'Ref', 'wv_disc' => 'Discipline', 'wv_avail' => 'Available' ) + $c );
add_action(
	'manage_wv_talent_posts_custom_column',
	function ( $col, $id ) {
		if ( 'wv_ref' === $col ) {
			echo esc_html( get_post_meta( $id, 'wv_ref', true ) );
		} elseif ( 'wv_disc' === $col ) {
			echo esc_html( get_post_meta( $id, 'wv_discipline', true ) );
		} elseif ( 'wv_avail' === $col ) {
			$d = get_post_meta( $id, 'wv_available_from', true );
			echo esc_html( $d && $d > wp_date( 'Y-m-d' ) ? $d : 'Now' );
		}
	},
	10,
	2
);
add_filter( 'manage_wv_enquiry_posts_columns', fn( $c ) => array_slice( $c, 0, 2, true ) + array( 'wv_from' => 'From', 'wv_profile' => 'Profile' ) + $c );
add_action(
	'manage_wv_enquiry_posts_custom_column',
	function ( $col, $id ) {
		if ( 'wv_from' === $col ) {
			echo esc_html( get_post_meta( $id, 'wv_email', true ) );
		} elseif ( 'wv_profile' === $col ) {
			echo esc_html( get_post_meta( $id, 'wv_profile', true ) );
		}
	},
	10,
	2
);
