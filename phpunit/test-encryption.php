<?php
use WordPressdotorg\MU_Plugins\Encryption\HiddenString;
use const WordPressdotorg\MU_Plugins\Encryption\{ PREFIX, NONCE_LENGTH, KEY_LENGTH };
use function WordPressdotorg\MU_Plugins\Encryption\{encrypt, decrypt, is_encrypted, get_encryption_key, generate_encryption_key };

class Test_WPORG_Encryption extends WP_UnitTestCase {

	public function wpSetUpBeforeClass() {
		if ( ! defined( 'WPORG_ENCRYPTION_KEY' ) ) {
			define( 'WPORG_ENCRYPTION_KEY', generate_encryption_key() );
		}
		if ( ! defined( 'WPORG_SECONDARY_ENCRYPTION_KEY' ) ) {
			define( 'WPORG_SECONDARY_ENCRYPTION_KEY', generate_encryption_key() );
		}
	}

	public function test_encrypt_decrypt() {
		$input = 'This is a plaintext string. It contains no sensitive data.';

		$encrypted = encrypt( $input );

		$this->assertNotEquals( $input, $encrypted );

		$decrypted = decrypt( $encrypted );

		$this->assertTrue( $decrypted instanceOf HiddenString );

		$this->assertNotEquals( $input, $decrypted );
		$this->assertEquals( $input, $decrypted->getString() );
		$this->assertNotEquals( $input, (string) $decrypted );
	}

	public function test_authenticated_encrypt_decrypt() {
		$input = 'This is a plaintext string. It contains no sensitive data.';
		$additional_data = 'USER1';

		$encrypted = encrypt( $input, $additional_data );

		$this->assertNotEquals( $input, $encrypted );
		$this->assertStringNotContainsString( $additional_data, $encrypted );

		$this->expectException( Exception::class );
		$decrypt_without_data = decrypt( $encrypted );

		$this->expectException( Exception::class );
		$decrypt_with_wrong_data = decrypt( $encrypted, 'USER2' );

		$this->expectException( Exception::class );
		$decrypt_with_wrong_key = decrypt( $encrypted, $additional_data, 'secondary' );

		$decrypted = decrypt( $encrypted, $additional_data );

		$this->assertTrue( $decrypted instanceOf HiddenString );

		$this->assertNotEquals( $input, $decrypted );
		$this->assertEquals( $input, $decrypted->getString() );
	}

	public function test_is_encrypted() {
		$this->assertFalse( is_encrypted( 'TEST STRING' ) );
		$this->assertFalse( is_encrypted( PREFIX ) );
		$this->assertFalse( is_encrypted( PREFIX . 'TEST STRING' ) );

		$string_prefix_length = str_repeat( '.', mb_strlen( PREFIX, '8bit' ) );
		$string_nonce_length  = str_repeat( '.', NONCE_LENGTH );

		$this->assertFalse( is_encrypted( $string_prefix_length . $string_nonce_length ) );
		$this->assertFalse( is_encrypted( $string_prefix_length . $string_nonce_length . 'TEST STRING' ) );

		$this->assertTrue( is_encrypted( PREFIX . $string_nonce_length ) );
		$this->assertTrue( is_encrypted( PREFIX . $string_nonce_length . 'TEST STRING' ) );

		$test_string = 'This is a plaintext string. It contains no sensitive data.';
		$this->assertTrue( is_encrypted( encrypt( $test_string ) ) );
	}

	public function test_generate_key_different() {
		$one_key = generate_encryption_key();

		$length = mb_strlen( sodium_hex2bin( $one_key ), '8bit' );
		$this->assertEquals( KEY_LENGTH, $length );

		$two_key = generate_encryption_key();
		$this->assertNotEquals( $one_key, $two_key );
	}

	public function test_get_encryption_key() {
		$this->assertSame( sodium_hex2bin( WPORG_ENCRYPTION_KEY ), get_encryption_key()->getString() );
		$this->assertSame( sodium_hex2bin( WPORG_ENCRYPTION_KEY ), get_encryption_key( '' )->getString() );
		$this->assertSame( sodium_hex2bin( WPORG_ENCRYPTION_KEY ), get_encryption_key( false )->getString() );

		$this->assertSame( sodium_hex2bin( WPORG_SECONDARY_ENCRYPTION_KEY ), get_encryption_key( 'secondary' )->getString() );

		$this->expectException( Exception::class );
		get_encryption_key( '404-key' );
	}

	public function test_can_encrypt_hiddenstring() {
		$hidden_string = new HiddenString( "TEST STRING" );

		$encrypted = encrypt( $hidden_string );

		$this->assertTrue( is_encrypted( $encrypted ) );

		$this->assertSame( $hidden_string->getString(), decrypt( $encrypted )->getString() );
	}

	public function test_encrypt_decrypt_invalid_inputs() {
		$this->expectException( Exception::class );
		encrypt( "TEST STRING", '', '404-key' );

		$this->expectException( Exception::class );
		decrypt( "TEST STRING", '', '404-key' );

		$this->expectException( Exception::class );
		decrypt( "TEST STRING" );
	}

	public function test_exported_functions() {
		// This only tests the behavioural functions, not the encryption/decryption.

		$input = 'This is a plaintext string. It contains no sensitive data.';

		$encrypted = wporg_encrypt( $input );

		$this->assertNotEquals( $input, $encrypted );

		$decrypted = wporg_decrypt( $encrypted );

		$this->assertTrue( $decrypted instanceOf HiddenString );

		$this->assertNotSame( $input, $decrypted );
		$this->assertEquals( $input, $decrypted->getString() );
		$this->assertEquals( $input, (string) $decrypted );

		$this->assertFalse( wporg_encrypt( '', '404-key' ) );
		$this->assertFalse( wporg_decrypt( '', '404-key' ) );
		$this->assertFalse( wporg_decrypt( 'TEST STRING' ) );
	}

	public function test_exported_authenticated_functions() {
		// This only tests the behavioural functions, not the encryption/decryption.

		$input           = 'This is a plaintext string. It contains no sensitive data.';
		$additional_data = 'USER1';

		$encrypted = wporg_authenticated_encrypt( $input, $additional_data );

		$this->assertNotEquals( $input, $encrypted );

		$decrypted = wporg_authenticated_decrypt( $encrypted, $additional_data );

		$this->assertTrue( $decrypted instanceOf HiddenString );

		$this->assertNotSame( $input, $decrypted );
		$this->assertEquals( $input, $decrypted->getString() );
		$this->assertEquals( $input, (string) $decrypted );

		$this->assertFalse( wporg_authenticated_encrypt( '', '', '404-key' ) );
		$this->assertFalse( wporg_authenticated_decrypt( '', '', '404-key' ) );
		$this->assertFalse( wporg_authenticated_decrypt( 'TEST STRING' ) );
	}
}
