<?php
use WordPressdotorg\MU_Plugins\Encryption\HiddenString;
/**
 * This file contains globally-exported function names for the Encryption plugin.
 *
 * It provides a wrapper around the libsodium's Authenticated Encryption with
 * Additional Data ciphers (AEAD with XChaCha20-Poly1305)
 */

/**
 * Encrypt a value.
 *
 * Unlike the Encryption plugin, this function simply returns false for any errors.
 *
 * @param string $value The plaintext value.
 * @param string $key   The key to use for encryption. Optional.
 * @return string|false The encrypted value, or false on error.
 */
function wporg_encrypt( string $value, string $key = '' ) {
	try {
		return \WordPressdotorg\MU_Plugins\Encryption\encrypt( $value, '', $key );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Encrypt a value, with authentication.
 *
 * Unlike the Encryption plugin, this function simply returns false for any errors.
 *
 * @param string $value The plaintext value.
 * @param string $additional_data Additional, authenticated data. This is used in the verification of the authentication tag appended to the ciphertext, but it is not encrypted or stored in the ciphertext. Optional.
 * @param string $key   The key to use for encryption. Optional.
 * @return string|false The encrypted value, or false on error.
 */
function wporg_authenticated_encrypt( string $value, string $additional_data = '', string $key = '' ) {
	try {
		return \WordPressdotorg\MU_Plugins\Encryption\encrypt( $value, $additional_data, $key );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Decrypt a value.
 *
 * Unlike the Encryption plugin, this function simply returns false for any errors, and
 * HiddenStrings that can be cast to string as needed.
 *
 * @param string $value The encrypted value.
 * @param string $key   The key to use for decryption. Optional.
 * @return string|false The decrypted value, or false on error.
 */
function wporg_decrypt( string $value, string $key = '' ) {
	try {
		$value = \WordPressdotorg\MU_Plugins\Encryption\decrypt( $value, '', $key );

		return new HiddenString( $value->getString(), false );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Decrypt a value, with authentication.
 *
 * Unlike the Encryption plugin, this function simply returns false for any errors, and
 * HiddenStrings that can be cast to string as needed.
 *
 * @param string $value The encrypted value.
 * @param string $additional_data Additional, authenticated data. This is used in the verification of the authentication tag appended to the ciphertext, but it is not encrypted or stored in the ciphertext. Optional.
 * @param string $key   The key to use for decryption. Optional.
 * @return string|false The decrypted value, or false on error.
 */
function wporg_authenticated_decrypt( string $value, string $additional_data = '', string $key = '' ) {
	try {
		$value = \WordPressdotorg\MU_Plugins\Encryption\decrypt( $value, $additional_data, $key );

		return new HiddenString( $value->getString(), false );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Determine if a value is encrypted.
 *
 * @param string $value The value to check.
 * @return bool True if the value is encrypted, false otherwise.
 */
function wporg_is_encrypted( string $value ) : bool {
	return \WordPressdotorg\MU_Plugins\Encryption\is_encrypted( $value );
}