<?php

class EmailAgentClassTest extends WP_UnitTestCase {
	public function testFilter() {
		$edr_email = new Edr_EmailAgent();
		$str1 = "\na\rb%0Ac\r\n%0D<CR><LF>";
		$str2 = <<<EOT
de\n
f\r\n
EOT;
		$this->assertSame( 'abc', $edr_email->filter( $str1 ) );
		$this->assertSame( 'def', $edr_email->filter( $str2 ) );
	}
}
