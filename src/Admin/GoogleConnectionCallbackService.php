<?php
/**
 * Completes a successful Google OAuth callback and primes property caches.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\Ga4PropertyClient;
use CannyForge\Archive\Integration\Google\Ga4PropertyStore;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyClient;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyStore;

/** Owns the post-OAuth property-list side effects. */
final class GoogleConnectionCallbackService {
	/**
	 * Google token store.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $tokens;

	/**
	 * Search Console property client.
	 *
	 * @var SearchConsolePropertyClient
	 */
	private SearchConsolePropertyClient $search_client;

	/**
	 * Search Console property store.
	 *
	 * @var SearchConsolePropertyStore
	 */
	private SearchConsolePropertyStore $search_store;

	/**
	 * GA4 property client.
	 *
	 * @var Ga4PropertyClient
	 */
	private Ga4PropertyClient $ga4_client;

	/**
	 * GA4 property store.
	 *
	 * @var Ga4PropertyStore
	 */
	private Ga4PropertyStore $ga4_store;

	/**
	 * Construct the callback completion service.
	 *
	 * @param GoogleTokenStore            $tokens       Google token store.
	 * @param SearchConsolePropertyClient $search_client Search Console client.
	 * @param SearchConsolePropertyStore  $search_store  Search Console store.
	 * @param Ga4PropertyClient           $ga4_client    GA4 property client.
	 * @param Ga4PropertyStore            $ga4_store     GA4 property store.
	 */
	public function __construct(
		GoogleTokenStore $tokens,
		SearchConsolePropertyClient $search_client,
		SearchConsolePropertyStore $search_store,
		Ga4PropertyClient $ga4_client,
		Ga4PropertyStore $ga4_store
	) {
		$this->tokens        = $tokens;
		$this->search_client = $search_client;
		$this->search_store  = $search_store;
		$this->ga4_client    = $ga4_client;
		$this->ga4_store     = $ga4_store;
	}

	/**
	 * Exchange the code, persist scope metadata, and populate property caches.
	 *
	 * @param GoogleOauthClient                                          $oauth       OAuth client.
	 * @param string                                                     $code        Authorization code.
	 * @param string                                                     $redirect_uri OAuth callback URI.
	 * @param array{wizard: bool, analytics: bool, search_console: bool} $state_data OAuth state payload.
	 * @return array{success: bool, message: string, notice_type: string}
	 */
	public function complete( GoogleOauthClient $oauth, string $code, string $redirect_uri, array $state_data ): array {
		if ( ! $oauth->connect( $code, $redirect_uri ) ) {
			return array(
				'success'     => false,
				'message'     => '' !== $oauth->last_error() ? $oauth->last_error() : __( 'Could not complete Google sign-in.', 'cannyforge-archive' ),
				'notice_type' => GoogleConnectionController::NOTICE_ERROR,
			);
		}

		$this->tokens->set_analytics_scope_granted( $state_data['analytics'] );
		$search_properties = array();
		if ( $state_data['search_console'] ) {
			$this->search_store->clear();
			$search_properties = $this->search_client->list_properties();
			if ( array() !== $search_properties ) {
				$this->search_store->save( $search_properties );
			}
		}

		$ga4_error = $this->load_ga4_properties( $state_data );
		$message   = $this->success_message( $state_data, $search_properties );
		if ( '' !== $ga4_error ) {
			$message = sprintf(
				/* translators: %s: GA4 property-list failure reason. */
				__( 'Google connected, but GA4 properties could not be loaded: %s Check that the Analytics Admin API is enabled and that this account can access a GA4 property.', 'cannyforge-archive' ),
				$ga4_error
			);
		}

		return array(
			'success'     => true,
			'message'     => $message,
			'notice_type' => '' === $ga4_error ? GoogleConnectionController::NOTICE_SUCCESS : GoogleConnectionController::NOTICE_ERROR,
		);
	}

	/**
	 * Build the post-connect success message for the selected path.
	 *
	 * @param array{wizard: bool, analytics: bool, search_console: bool} $state_data OAuth state payload.
	 * @param array<int, array{site_url: string, permission: string}>    $search_properties Search Console properties.
	 * @return string
	 */
	private function success_message( array $state_data, array $search_properties ): string {
		if ( $state_data['analytics'] && ! $state_data['search_console'] ) {
			return __( 'Google connected. Choose an Analytics property below.', 'cannyforge-archive' );
		}
		if ( $state_data['analytics'] && $state_data['search_console'] ) {
			return __( 'Google connected. Choose your Search Console and Analytics properties below.', 'cannyforge-archive' );
		}

		return array() !== $search_properties
			? __( 'Google connected. Choose a Search Console property below.', 'cannyforge-archive' )
			: __( 'Google connected. Load Search Console properties below to choose a property.', 'cannyforge-archive' );
	}

	/**
	 * Load GA4 properties only for a wizard flow that requested Analytics.
	 *
	 * @param array{wizard: bool, analytics: bool, search_console: bool} $state_data OAuth state payload.
	 * @return string Failure reason, or empty when not requested/successful.
	 */
	private function load_ga4_properties( array $state_data ): string {
		if ( ! $state_data['wizard'] || ! $state_data['analytics'] ) {
			return '';
		}

		$this->ga4_store->clear();
		$properties = $this->ga4_client->list_properties();
		if ( array() !== $properties ) {
			$this->ga4_store->save( $properties );
			return '';
		}

		return $this->ga4_client->last_error();
	}
}
