<?php
/**
 * BbPress_Dash.
 *
 * @since   1.0.0
 * @package BbPress_Dash
 */
class BbPress_Dash_Test extends WP_UnitTestCase {

	/**
	 * Test if our class exists.
	 *
	 * @since  1.0.0
	 */
	function test_class_exists() {
		$this->assertTrue( class_exists( 'BbPress_Dash') );
	}

	/**
	 * Test that our main helper function is an instance of our class.
	 *
	 * @since  1.0.0
	 */
	function test_get_instance() {
		$this->assertInstanceOf(  'BbPress_Dash', bbpress_dash() );
	}

	/**
	 * Replace this with some actual testing code.
	 *
	 * @since  1.0.0
	 */
	function test_sample() {
		$this->assertTrue( true );
	}
}
