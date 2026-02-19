<?php
/**
 * API Source model with CRUD operations.
 *
 * @package WP_Fetch
 */

namespace WP_Fetch;

/**
 * Represents a single external API source configuration.
 */
class API_Source {

	private const OPTION_KEY = 'wp_fetch_sources';

	private string $name;
	private string $url;
	private string $method;
	private array $headers;
	private string $auth_type;
	private string $auth_value;
	private int $cache_ttl;
	private string $transform;
	private string $fallback;

	/**
	 * Constructor.
	 *
	 * @param array $data Source configuration data.
	 */
	public function __construct( array $data = array() ) {
		$this->name       = sanitize_key( $data['name'] ?? '' );
		$this->url        = esc_url_raw( $data['url'] ?? '' );
		$method           = strtoupper( $data['method'] ?? 'GET' );
		$this->method     = in_array( $method, array( 'GET', 'POST' ), true ) ? $method : 'GET';
		$this->headers    = self::sanitize_headers( $data['headers'] ?? array() );
		$auth_type        = $data['auth_type'] ?? 'none';
		$this->auth_type  = in_array( $auth_type, array( 'none', 'api_key', 'bearer' ), true )
			? $auth_type
			: 'none';
		$this->auth_value = sanitize_text_field( $data['auth_value'] ?? '' );
		$this->cache_ttl  = absint( $data['cache_ttl'] ?? 300 );
		$this->transform  = sanitize_text_field( $data['transform'] ?? '' );
		$this->fallback   = wp_kses_post( $data['fallback'] ?? '' );
	}

	/**
	 * Get source name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get source URL.
	 */
	public function get_url(): string {
		return $this->url;
	}

	/**
	 * Get HTTP method.
	 */
	public function get_method(): string {
		return $this->method;
	}

	/**
	 * Get custom headers.
	 */
	public function get_headers(): array {
		return $this->headers;
	}

	/**
	 * Get authentication type.
	 */
	public function get_auth_type(): string {
		return $this->auth_type;
	}

	/**
	 * Get authentication value (decrypted).
	 */
	public function get_auth_value(): string {
		return $this->auth_value;
	}

	/**
	 * Get cache TTL in seconds.
	 */
	public function get_cache_ttl(): int {
		return $this->cache_ttl;
	}

	/**
	 * Get transform expression.
	 */
	public function get_transform(): string {
		return $this->transform;
	}

	/**
	 * Get fallback HTML.
	 */
	public function get_fallback(): string {
		return $this->fallback;
	}

	/**
	 * Convert source to array for storage.
	 */
	public function to_array(): array {
		return array(
			'name'       => $this->name,
			'url'        => $this->url,
			'method'     => $this->method,
			'headers'    => $this->headers,
			'auth_type'  => $this->auth_type,
			'auth_value' => self::encrypt_value( $this->auth_value ),
			'cache_ttl'  => $this->cache_ttl,
			'transform'  => $this->transform,
			'fallback'   => $this->fallback,
		);
	}

	/**
	 * Create source from stored array (with encrypted auth).
	 *
	 * @param array $data Stored data.
	 */
	public static function from_stored( array $data ): self {
		if ( ! empty( $data['auth_value'] ) ) {
			$data['auth_value'] = self::decrypt_value( $data['auth_value'] );
		}
		return new self( $data );
	}

	/**
	 * Save a source (create or update).
	 */
	public function save(): bool {
		if ( empty( $this->name ) || empty( $this->url ) ) {
			return false;
		}

		$sources = get_option( self::OPTION_KEY, array() );
		$sources[ $this->name ] = $this->to_array();
		return update_option( self::OPTION_KEY, $sources );
	}

	/**
	 * Delete a source by name.
	 *
	 * @param string $name Source name slug.
	 */
	public static function delete( string $name ): bool {
		$sources = get_option( self::OPTION_KEY, array() );
		if ( ! isset( $sources[ $name ] ) ) {
			return false;
		}
		unset( $sources[ $name ] );
		return update_option( self::OPTION_KEY, $sources );
	}

	/**
	 * Get a single source by name.
	 *
	 * @param string $name Source name slug.
	 */
	public static function get( string $name ): ?self {
		$sources = get_option( self::OPTION_KEY, array() );
		if ( ! isset( $sources[ $name ] ) ) {
			return null;
		}
		return self::from_stored( $sources[ $name ] );
	}

	/**
	 * List all configured sources.
	 *
	 * @return self[]
	 */
	public static function list_all(): array {
		$sources = get_option( self::OPTION_KEY, array() );
		$result  = array();
		foreach ( $sources as $data ) {
			$result[] = self::from_stored( $data );
		}
		return $result;
	}

	/**
	 * Sanitize headers array.
	 *
	 * @param array|string $headers Raw headers.
	 */
	private static function sanitize_headers( array|string $headers ): array {
		if ( is_string( $headers ) ) {
			$decoded = json_decode( $headers, true );
			$headers = is_array( $decoded ) ? $decoded : array();
		}
		$clean = array();
		foreach ( $headers as $key => $value ) {
			$clean[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}
		return $clean;
	}

	/**
	 * Encrypt a value for storage.
	 *
	 * @param string $value Plain text value.
	 */
	private static function encrypt_value( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$key = wp_salt( 'auth' );
		return base64_encode( openssl_encrypt( $value, 'aes-256-cbc', $key, 0, substr( md5( $key ), 0, 16 ) ) );
	}

	/**
	 * Decrypt a stored value.
	 *
	 * @param string $encrypted Encrypted value.
	 */
	private static function decrypt_value( string $encrypted ): string {
		if ( empty( $encrypted ) ) {
			return '';
		}
		$key       = wp_salt( 'auth' );
		$decrypted = openssl_decrypt( base64_decode( $encrypted ), 'aes-256-cbc', $key, 0, substr( md5( $key ), 0, 16 ) );
		return ( false === $decrypted ) ? '' : $decrypted;
	}
}
