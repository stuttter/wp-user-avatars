<?php
/**
 * Front-end avatar editor test doubles.
 *
 * @package WP_User_Avatars
 */

/**
 * Provide the register_block_type test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function register_block_type( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}

/**
 * Provide the get_block_wrapper_attributes test double.
 *
 * @param array<string, string> $attributes Extra wrapper attributes.
 *
 * @return string
 */
function get_block_wrapper_attributes( $attributes = array() ) {
	wpua_test_call( __FUNCTION__, array( $attributes ) );
	return 'class="wp-block-wp-user-avatars-avatar-editor ' . esc_attr( $attributes['class'] ?? '' ) . '"';
}

/**
 * Provide the has_block test double.
 *
 * @param string  $block_name Full block name.
 * @param WP_Post $post       Post to inspect.
 *
 * @return bool
 */
function has_block( $block_name, $post ) {
	$override = $GLOBALS['wpua_test']['returns']['has_block'] ?? null;

	if ( null !== $override ) {
		wpua_test_call( __FUNCTION__, array( $block_name, $post ) );
		return (bool) $override;
	}

	return false !== strpos( $post->post_content, '<!-- wp:' . $block_name );
}

/**
 * Provide the wp_strip_all_tags test double.
 *
 * @param string $value         Text to sanitize.
 * @param bool   $remove_breaks Whether to remove line breaks.
 *
 * @return string
 */
function wp_strip_all_tags( $value, $remove_breaks = false ) {
	$value = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $value );
	$value = preg_replace( '@<[^>]*?>@si', '', (string) $value );

	if ( $remove_breaks ) {
		$value = preg_replace( '/[\r\n\t ]+/', ' ', $value );
	}

	return trim( (string) $value );
}

/**
 * Provide the wp_get_raw_referer test double.
 *
 * @return false|string
 */
function wp_get_raw_referer() {
	return $GLOBALS['wpua_test']['returns']['wp_get_raw_referer'] ?? false;
}

/**
 * Provide the wp_validate_redirect test double.
 *
 * @param string $location Redirect candidate.
 * @param string $fallback Safe fallback URL.
 *
 * @return string
 */
function wp_validate_redirect( $location, $fallback = '' ) {
	wpua_test_call( __FUNCTION__, array( $location, $fallback ) );
	return '' !== $location ? $location : $fallback;
}
