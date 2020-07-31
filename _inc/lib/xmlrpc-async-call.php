<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

class Jetpack_Xmlrpc_Async_Call {

	static $clients = array();

	public static function add_call( $method, $user_id = 0, ...$args ) {
		global $blog_id;

		$client_blog_id = is_multisite() ? $blog_id : 0;

		if ( ! isset( self::$clients[ $client_blog_id ] ) ) {
			self::$clients[ $client_blog_id ] = array();
		}

		if ( ! isset( self::$clients[ $client_blog_id ][ $user_id ] ) ) {
			self::$clients[ $client_blog_id ][ $user_id ] = new Jetpack_IXR_ClientMulticall( array( 'user_id' => $user_id ) );
		}

		if ( function_exists( 'ignore_user_abort' ) ) {
			ignore_user_abort( true );
		}

		array_unshift( $args, $method );

		// TODO -> parse args removing user id.
		call_user_func_array( array( self::$clients[ $client_blog_id ][ $user_id ], 'addCall' ), $args );

		if ( false === has_action( 'shutdown', array( 'Jetpack_Xmlrpc_Async_Call', 'do_calls' ) ) ) {
			add_action( 'shutdown', array( 'Jetpack_Xmlrpc_Async_Call', 'do_calls' ) );
		}
	}

	public static function do_calls() {
		if ( is_multisite() ) {
			self::do_calls_multisite();
		} else {
			self::do_calls_single_site();
		}
	}

	private static function do_calls_multisite() {
		foreach ( self::$clients as $client_blog_id => $blog_clients ) {
			if ( 0 === $client_blog_id ) {
				continue;
			}

			foreach ( $blog_clients as $client ) {
				if ( empty( $client->calls ) ) {
					continue;
				}

				$switch_success = switch_to_blog( $client_blog_id, true );

				if ( ! $switch_success ) {
					continue;
				}

				flush();
				$client->query();

				restore_current_blog();
			}
		}
	}

	private static function do_calls_single_site() {
		if ( ! isset( self::$clients[0] ) ) {
			return;
		}

		foreach ( self::$clients[0] as $client ) {
			if ( empty( $client->calls ) ) {
				continue;
			}

			flush();
			$client->query();

		}
	}
}
