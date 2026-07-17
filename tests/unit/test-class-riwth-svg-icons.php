<?php
/**
 * Tests for RIWTH_SVG_Icons.
 *
 * @package RIWTH
 */

class Test_RIWTH_SVG_Icons extends RIWTH_Test_Case {

	public function test_positive_icons_have_expected_keys() {
		$icons = RIWTH_SVG_Icons::get_svg_positive_icons();

		$this->assertArrayHasKey( 'thumbs-up', $icons );
		$this->assertArrayHasKey( 'smiley', $icons );
	}

	public function test_positive_icons_are_svg_markup() {
		foreach ( RIWTH_SVG_Icons::get_svg_positive_icons() as $key => $icon ) {
			$this->assertStringContainsString( '<svg', $icon, "Icon '{$key}' is not SVG markup" );
		}
	}

	public function test_negative_icons_have_expected_keys() {
		$icons = RIWTH_SVG_Icons::get_svg_negative_icons();

		$this->assertArrayHasKey( 'thumbs-down', $icons );
		$this->assertArrayHasKey( 'frown', $icons );
	}

	public function test_negative_icons_are_svg_markup() {
		foreach ( RIWTH_SVG_Icons::get_svg_negative_icons() as $key => $icon ) {
			$this->assertStringContainsString( '<svg', $icon, "Icon '{$key}' is not SVG markup" );
		}
	}
}
