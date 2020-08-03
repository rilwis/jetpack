<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Tests for Jetpack_Xmlrpc_Async_Call class
 */
class WP_Test_Xmlrpc_Async_Call extends WP_UnitTestCase {

	/**
	 * Test add call
	 */
	public function test_add_call() {
		jetpack_require_lib( 'xmlrpc-async-call' );

		Jetpack_Xmlrpc_Async_Call::add_call( 'test', 0, 'test_arg', 'test_arg2' );
		Jetpack_Xmlrpc_Async_Call::add_call( 'test', 1, 'test_arg', 'test_arg2' );

		$this->assertArrayHasKey( 0, Jetpack_Xmlrpc_Async_Call::$clients );
		$this->assertInstanceOf( 'Jetpack_IXR_ClientMulticall', Jetpack_Xmlrpc_Async_Call::$clients[0][0] );
		$this->assertInstanceOf( 'Jetpack_IXR_ClientMulticall', Jetpack_Xmlrpc_Async_Call::$clients[0][1] );

		$this->assertNotEmpty( Jetpack_Xmlrpc_Async_Call::$clients[0][0]->calls );
		$this->assertEquals( 'test', Jetpack_Xmlrpc_Async_Call::$clients[0][0]->calls[0]['methodName'] );
		$this->assertEquals( array( 'test_arg', 'test_arg2' ), Jetpack_Xmlrpc_Async_Call::$clients[0][0]->calls[0]['params'] );

		$this->assertEquals( 10, has_action( 'shutdown', array( 'Jetpack_Xmlrpc_Async_Call', 'do_calls' ) ) );

	}


}
