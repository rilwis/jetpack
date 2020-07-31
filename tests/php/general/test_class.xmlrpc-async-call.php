<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Tests for Jetpack_Xmlrpc_Async_Call class
 */
class WP_Test_Xmlrpc_Async_Call extends WP_UnitTestCase {

	public function test_xis() {
		jetpack_require_lib( 'xmlrpc-async-call' );
		Jetpack_Xmlrpc_Async_Call::add_call( 'test', 0 );
		$this->assertTrue( true );
	}

}
