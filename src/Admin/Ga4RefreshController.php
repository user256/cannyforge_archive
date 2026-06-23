<?php
/**
 * Admin-post handler for manual GA4 cache refresh.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\Ga4TopContentRefresher;

/**
 * Owns the manual GA4 cache-refresh action.
 *
 * Mirrors {@see SearchConsoleRefreshController} for the optional second Google
 * signal (ticket 406); like its sibling, all GA4 HTTP happens here on demand,
 * never during page render.
 */
final class Ga4RefreshController {
	/**
	 * Admin-post action: refresh GA4 cache.
	 */
	public const ACTION_REFRESH = 'cannyforge_archive_google_ga4_refresh';

	/**
	 * Nonce field for the refresh action.
	 */
	public const REFRESH_NONCE_FIELD = 'cannyforge_archive_google_ga4_refresh_nonce';

	/**
	 * Nonce action for the refresh action.
	 */
	public const REFRESH_NONCE_ACTION = 'cannyforge_archive_google_ga4_refresh';

	/**
	 * Capability required to run the refresh.
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * Archive settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private SettingsRepositoryInterface $settings_repository;

	/**
	 * Google settings store.
	 *
	 * @var GoogleSettingsStore
	 */
	private GoogleSettingsStore $google_settings;

	/**
	 * Google token store.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $tokens;

	/**
	 * GA4 refresher.
	 *
	 * @var Ga4TopContentRefresher
	 */
	private Ga4TopContentRefresher $refresher;

	/**
	 * Construct the controller.
	 *
	 * @param SettingsRepositoryInterface $settings_repository Archive settings repository.
	 * @param GoogleSettingsStore         $google_settings     Google settings store.
	 * @param GoogleTokenStore            $tokens              Google token store.
	 * @param Ga4TopContentRefresher      $refresher           GA4 refresher.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings_repository,
		GoogleSettingsStore $google_settings,
		GoogleTokenStore $tokens,
		Ga4TopContentRefresher $refresher
	) {
		$this->settings_repository = $settings_repository;
		$this->google_settings     = $google_settings;
		$this->tokens              = $tokens;
		$this->refresher           = $refresher;
	}

	/**
	 * Register the admin-post handler.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_REFRESH, array( $this, 'refresh' ) );
	}

	/**
	 * Refresh the cached GA4 top post IDs.
	 *
	 * @return void
	 */
	public function refresh(): void {
		$this->require_capability();
		check_admin_referer( self::REFRESH_NONCE_ACTION, self::REFRESH_NONCE_FIELD );

		if ( ! $this->can_refresh() ) {
			$this->redirect_to_settings(
				__( 'Connect Google and save a GA4 property ID before refreshing.', 'cannyforge-archive' ),
				GoogleConnectionController::NOTICE_ERROR
			);
		}

		$limit = $this->settings_repository->get()->blog_max_urls();
		$ids   = $this->refresher->refresh( $limit );

		$this->redirect_to_settings(
			sprintf(
				/* translators: %d: number of cached post IDs */
				__( 'GA4 cache refreshed: %d post IDs.', 'cannyforge-archive' ),
				count( $ids )
			),
			GoogleConnectionController::NOTICE_SUCCESS
		);
	}

	/**
	 * Whether the plugin is currently configured to refresh GA4 data.
	 *
	 * @return bool
	 */
	private function can_refresh(): bool {
		return '' !== $this->google_settings->get()->ga4_property_id()
			&& GoogleTokenStore::STATUS_CONNECTED === $this->tokens->status();
	}

	/**
	 * Redirect back to the settings page with a one-shot notice.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type.
	 * @return never
	 */
	private function redirect_to_settings( string $message, string $type ): never {
		wp_safe_redirect(
			add_query_arg(
				array(
					GoogleConnectionController::NOTICE_KEY => rawurlencode( $message ),
					GoogleConnectionController::NOTICE_TYPE_KEY => $type,
				),
				admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG )
			)
		);
		exit;
	}

	/**
	 * Enforce the required capability for the handler.
	 *
	 * @return void
	 */
	private function require_capability(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'cannyforge-archive' ) );
		}
	}
}
