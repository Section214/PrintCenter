<?php


class Tests_PrintCenter extends WP_UnitTestCase {
	protected $object;

	public function setUp() {
		parent::setUp();
		$this->object = printcenter();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_printcenter_instance() {
		$this->assertClassHasStaticAttribute( 'instance', 'PrintCenter' );
		$this->assertClassHasAttribute( 'loader', 'PrintCenter' );
	}
}
