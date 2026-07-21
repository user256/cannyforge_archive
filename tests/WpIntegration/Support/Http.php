<?php
/**
 * A minimal HTTP client for the real-WordPress integration suite.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\WpIntegration\Support;

/**
 * Wraps `file_get_contents()` with a stream context so the integration suite
 * can issue plain HTTP requests against the disposable wp-env instance
 * without depending on the cURL extension, mirroring the existing phpcs
 * exclusion that already allows `file_get_contents` in tests/*.
 */
final class Http {
	/**
	 * Issue a GET request.
	 *
	 * @param string $url              The target URL.
	 * @param bool   $follow_redirects Whether to follow HTTP redirects.
	 * @return array{status: int, body: string, headers: string[]}
	 */
	public static function get( string $url, bool $follow_redirects = true ): array {
		return self::request( 'GET', $url, array(), $follow_redirects );
	}

	/**
	 * Issue a POST request with form-encoded fields.
	 *
	 * @param string               $url    The target URL.
	 * @param array<string, mixed> $fields The form fields.
	 * @return array{status: int, body: string, headers: string[]}
	 */
	public static function post( string $url, array $fields ): array {
		return self::request( 'POST', $url, $fields, true );
	}

	/**
	 * Issue the request and capture its status, body, and raw headers.
	 *
	 * @param string               $method           The HTTP method.
	 * @param string               $url              The target URL.
	 * @param array<string, mixed> $fields           Form fields (POST body), if any.
	 * @param bool                 $follow_redirects Whether to follow HTTP redirects.
	 * @return array{status: int, body: string, headers: string[]}
	 */
	private static function request( string $method, string $url, array $fields, bool $follow_redirects ): array {
		$body    = array() !== $fields ? http_build_query( $fields ) : '';
		$headers = array() !== $fields ? "Content-Type: application/x-www-form-urlencoded\r\n" : '';

		$context = stream_context_create(
			array(
				'http' => array(
					'method'          => $method,
					'header'          => $headers,
					'content'         => $body,
					'ignore_errors'   => true,
					'timeout'         => 15,
					'follow_location' => $follow_redirects ? 1 : 0,
					'max_redirects'   => $follow_redirects ? 5 : 0,
				),
			)
		);

		$result = file_get_contents( $url, false, $context );

		return array(
			'status'  => self::status_from_headers( $http_response_header ?? array() ),
			'body'    => false === $result ? '' : $result,
			'headers' => $http_response_header ?? array(),
		);
	}

	/**
	 * Extract the numeric HTTP status from the raw response headers.
	 *
	 * With redirects followed, `$http_response_header` contains one status
	 * line per hop; the last one is the final response's.
	 *
	 * @param string[] $headers Raw response headers.
	 * @return int
	 */
	private static function status_from_headers( array $headers ): int {
		$status = 0;

		foreach ( $headers as $line ) {
			if ( 1 === preg_match( '#^HTTP/\S+\s+(\d+)#', $line, $matches ) ) {
				$status = (int) $matches[1];
			}
		}

		return $status;
	}
}
