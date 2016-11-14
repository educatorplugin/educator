<?php

class FormattingTest extends WP_UnitTestCase {
	public function prepare_price_settings() {
		$settings = get_option( 'edr_settings' );
		$settings['currency'] = 'USD';
		$settings['decimal_point'] = '.';
		$settings['thousands_sep'] = '\'';
		$settings['currency_position'] = 'after';

		update_option( 'edr_settings', $settings );
	}

	public function test_format_price() {
		$this->prepare_price_settings();

		$this->assertSame( '1&#039;234.12 &#36;', edr_format_price( 1234.1200 ) );
		$this->assertSame( '1&#039;234 &#36;', edr_format_price( 1234.0 ) );
		$this->assertSame( '10.90 &#36;', edr_format_price( 10.9 ) );
		$this->assertSame( '10.90 &#36;', edr_format_price( '10.9' ) );
	}

	public function test_format_membership_price() {
		$this->prepare_price_settings();

		$expected_price_daily = '1&#039;234.12 &#36; per day';
		$expected_price_monthly = '1&#039;234.12 &#36; per 6 months';
		$expected_price_yearly = '1&#039;234.12 &#36; per year';

		$actual_price_daily = edr_format_membership_price( 1234.12, 1, 'days' );
		$actual_price_monthly = edr_format_membership_price( 1234.12, 6, 'months' );
		$actual_price_yearly = edr_format_membership_price( 1234.12, 1, 'years' );

		$this->assertSame( $expected_price_daily, $actual_price_daily );
		$this->assertSame( $expected_price_monthly, $actual_price_monthly );
		$this->assertSame( $expected_price_yearly, $actual_price_yearly );
	}

	public function test_format_grade() {
		$this->prepare_price_settings();

		$this->assertSame( '90%', edr_format_grade( 90 ) );
		$this->assertSame( '67.89%', edr_format_grade( 67.89 ) );
		$this->assertSame( '65%', edr_format_grade( 65.00 ) );
		$this->assertSame( '89.9%', edr_format_grade( 89.90 ) );
	}

	public function test_kses_allowed_tags() {
		$expected = array(
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'pre'        => array(),
			'code'       => array(),
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'rel'    => array(),
				'target' => array(),
			),
			'strong'     => array(),
			'em'         => array(),
			'img'        => array(
				'src'    => true,
				'alt'    => true,
				'height' => true,
				'width'  => true,
			),
		);

		$this->assertSame( $expected, edr_kses_allowed_tags() );
	}

	public function test_kses_data() {
		$html_input = $expected = <<<TEXT
<a href="" title="" target="" rel=""></a><ul><li></li></ul><ol></ol><pre></pre><code></code>
<strong></strong><em></em><img src="" alt="" width="" height="">
TEXT;
		$html_input .= '<script>alert(1);</script>';
		$html_input .= '<div class="some-class">abc</div>';
		$expected .= 'alert(1);abc';

		$this->assertSame( $expected, edr_kses_data( $html_input ) );
	}
}
