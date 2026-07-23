<?php
/**
 * The settings admin page.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Archive\ArchiveUrlResolver;
use CannyForge\Archive\Core\Settings\CsvUrlExtractor;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\Ga4PropertyStore;
use CannyForge\Archive\Integration\Google\GoogleClientConfigImporter;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyStore;
use CannyForge\Archive\Integration\Google\SecretCipher;

/**
 * Registers and renders the CannyForge Archive settings page.
 *
 * Thin controller: it owns the WordPress menu/capability/nonce ceremony and
 * delegates form parsing to {@see SettingsFormParser}, persistence to the
 * repository, and rendering to {@see SettingsView}.
 */
final class SettingsPage {
	/**
	 * The admin page / menu slug.
	 */
	public const PAGE_SLUG = 'cannyforge-archive';

	/**
	 * Capability required to view and save settings.
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * Settings persistence.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private SettingsRepositoryInterface $repository;

	/**
	 * Form-to-value-object mapper.
	 *
	 * @var SettingsFormParser
	 */
	private SettingsFormParser $parser;

	/**
	 * Form renderer.
	 *
	 * @var SettingsView
	 */
	private SettingsView $view;

	/**
	 * CSV → URL extractor for the Blog-mode import.
	 *
	 * @var CsvUrlExtractor
	 */
	private CsvUrlExtractor $csv;

	/**
	 * Dedicated Google settings store.
	 *
	 * @var GoogleSettingsStore
	 */
	private GoogleSettingsStore $google_settings;

	/**
	 * Dedicated Google token store.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $google_tokens;

	/**
	 * Cached Search Console top-content IDs.
	 *
	 * @var SearchConsoleCacheStore
	 */
	private SearchConsoleCacheStore $search_cache;

	/**
	 * Cached GA4 top-content IDs.
	 *
	 * @var Ga4CacheStore
	 */
	private Ga4CacheStore $ga4_cache;

	/**
	 * Google OAuth client JSON upload reader (shared with the wizard).
	 *
	 * @var GoogleClientJsonUploadReader
	 */
	private GoogleClientJsonUploadReader $json_upload;

	/**
	 * The stepped Google setup wizard sub-view of this page.
	 *
	 * @var GoogleWizardPage
	 */
	private GoogleWizardPage $wizard;

	/**
	 * Short-lived Search Console property list.
	 *
	 * @var SearchConsolePropertyStore
	 */
	private SearchConsolePropertyStore $property_store;

	/**
	 * Resolves the "View Archive" preview link: the `archive_url` override, or
	 * the archive endpoint URL. Shared with the front-end pagination link and
	 * the endpoint's own tail redirect (ticket 612) so all three destinations
	 * agree.
	 *
	 * @var ArchiveUrlResolver
	 */
	private ArchiveUrlResolver $url_resolver;

	/**
	 * Construct the page.
	 *
	 * @param SettingsRepositoryInterface     $repository Settings persistence.
	 * @param SettingsFormParser              $parser     Form mapper.
	 * @param SettingsView                    $view       Form renderer.
	 * @param CsvUrlExtractor|null            $csv        CSV URL extractor.
	 * @param GoogleSettingsStore|null        $google_settings        Google settings store.
	 * @param GoogleTokenStore|null           $google_tokens          Google token store.
	 * @param SearchConsoleCacheStore|null    $search_cache           Search Console cache store.
	 * @param Ga4CacheStore|null              $ga4_cache              GA4 cache store.
	 * @param GoogleClientConfigImporter|null $google_client_importer Google client JSON importer.
	 * @param ArchiveUrlResolver|null         $url_resolver           Archive URL resolver.
	 * @param SearchConsolePropertyStore|null $property_store         Property cache.
	 * @param GoogleWizardPage|null           $wizard                 Google setup wizard sub-view.
	 * @param Ga4PropertyStore|null           $ga4_property_store     GA4 property cache.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		SettingsFormParser $parser,
		SettingsView $view,
		?CsvUrlExtractor $csv = null,
		?GoogleSettingsStore $google_settings = null,
		?GoogleTokenStore $google_tokens = null,
		?SearchConsoleCacheStore $search_cache = null,
		?Ga4CacheStore $ga4_cache = null,
		?GoogleClientConfigImporter $google_client_importer = null,
		?ArchiveUrlResolver $url_resolver = null,
		?SearchConsolePropertyStore $property_store = null,
		?GoogleWizardPage $wizard = null,
		?Ga4PropertyStore $ga4_property_store = null
	) {
		$this->repository      = $repository;
		$this->parser          = $parser;
		$this->view            = $view;
		$this->csv             = $csv ?? new CsvUrlExtractor();
		$this->google_settings = $google_settings ?? new GoogleSettingsStore();
		$this->google_tokens   = $google_tokens ?? new GoogleTokenStore();
		$this->search_cache    = $search_cache ?? new SearchConsoleCacheStore();
		$this->ga4_cache       = $ga4_cache ?? new Ga4CacheStore();
		$this->json_upload     = new GoogleClientJsonUploadReader( $google_client_importer );
		$this->url_resolver    = $url_resolver ?? new ArchiveUrlResolver();
		$this->property_store  = $property_store ?? new SearchConsolePropertyStore();
		$this->wizard          = $wizard ?? new GoogleWizardPage(
			$this->google_settings,
			$this->google_tokens,
			$this->property_store,
			$this->search_cache,
			$this->ga4_cache,
			$this->json_upload,
			null,
			$ga4_property_store
		);
	}

	/**
	 * Register the admin menu hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'CannyForge Archive Generator', 'cannyforge-archive' ),
			__( 'CannyForge Archive Generator', 'cannyforge-archive' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-networking'
		);
	}

	/**
	 * Render the page, handling a save submission first.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these settings.', 'cannyforge-archive' ) );
		}

		if ( GoogleWizardPage::is_requested() ) {
			$this->wizard->render_page( $this->google_notice(), $this->google_notice_type() );
			return;
		}

		$saved = $this->maybe_save();
		if ( $saved ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Settings saved.', 'cannyforge-archive' );
			echo '</p></div>';
		}

		if ( '' !== $this->json_upload->error() ) {
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo esc_html__( 'Google OAuth client JSON was not imported: ', 'cannyforge-archive' );
			echo esc_html( $this->json_upload->error() );
			echo '</p></div>';
		}

		if ( ! SecretCipher::backend_available() ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'No authenticated-encryption backend is available on this server (the PHP sodium extension, or OpenSSL with AES-256-GCM). Google secrets cannot be encrypted at rest, so new Client Secrets and connection tokens will not be saved until this is fixed — ask your host to enable the sodium PHP extension.', 'cannyforge-archive' );
			echo '</p></div>';
		}

		$action_url = esc_url_raw( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		$this->view->render(
			$this->repository->get(),
			$action_url,
			$this->preview_url(),
			$this->google_settings->get(),
			$this->google_connection_status(),
			$this->google_notice(),
			$this->google_notice_type()
		);
	}

	/**
	 * The live archive URL for the "Preview" link: the configured destination
	 * override, or the archive endpoint under the site root.
	 *
	 * @return string
	 */
	private function preview_url(): string {
		return $this->url_resolver->destination_url( $this->repository->get() );
	}

	/**
	 * The Google connection status to render, substituting the distinct
	 * "needs re-authorising" pseudo-status (ticket 605) whenever the stored
	 * refresh token fails to authenticate/decrypt — so a salt rotation (or
	 * other key change) surfaces as an actionable state on the settings
	 * panel instead of silently reading as blank/disconnected.
	 *
	 * @return string
	 */
	private function google_connection_status(): string {
		return $this->google_tokens->connection_needs_reauthorising()
			? GoogleTokenStore::STATUS_NEEDS_REAUTH
			: $this->google_tokens->status();
	}

	/**
	 * Persist the submission when a valid one is present.
	 *
	 * @return bool True when settings were saved.
	 */
	private function maybe_save(): bool {
		if ( ! isset( $_POST[ SettingsView::NONCE_FIELD ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ SettingsView::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, SettingsView::NONCE_ACTION ) ) {
			return false;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$input    = wp_unslash( $_POST );
		$input    = is_array( $input ) ? $input : array();
		$input    = array_merge( $input, $this->json_upload->fields() );
		$csv_urls = $this->uploaded_csv_urls();
		$this->repository->save(
			$this->parser->parse( $input, $csv_urls )
		);
		$this->google_settings->save_overlay( $input );
		$this->search_cache->clear();
		$this->ga4_cache->clear();

		return true;
	}

	/**
	 * One-shot Google notice passed back from the connect/disconnect handlers.
	 *
	 * @return string
	 */
	private function google_notice(): string {
		$raw = filter_input( INPUT_GET, GoogleConnectionController::NOTICE_KEY, FILTER_UNSAFE_RAW );

		return is_scalar( $raw ) ? sanitize_text_field( rawurldecode( (string) $raw ) ) : '';
	}

	/**
	 * One-shot Google notice type passed back from the connect/disconnect handlers.
	 *
	 * @return string
	 */
	private function google_notice_type(): string {
		$raw  = filter_input( INPUT_GET, GoogleConnectionController::NOTICE_TYPE_KEY, FILTER_UNSAFE_RAW );
		$type = is_scalar( $raw ) ? sanitize_text_field( (string) $raw ) : '';

		return GoogleConnectionController::NOTICE_SUCCESS === $type
			? GoogleConnectionController::NOTICE_SUCCESS
			: GoogleConnectionController::NOTICE_ERROR;
	}

	/**
	 * Read the uploaded Blog-URL CSV and extract its URLs.
	 *
	 * Returns an empty list when no file was uploaded or the upload failed. Only a
	 * genuinely uploaded file is read (guards against path injection).
	 *
	 * @return string[]
	 */
	private function uploaded_csv_urls(): array {
		// Nonce + capability are verified by the caller, maybe_save(), before this runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES['blog_urls_csv']['tmp_name'] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- path validated via is_uploaded_file below.
		$tmp = (string) $_FILES['blog_urls_csv']['tmp_name'];
		if ( ! is_uploaded_file( $tmp ) ) {
			return array();
		}

		$contents = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a small uploaded temp file.

		return false === $contents ? array() : $this->csv->extract( $contents );
	}
}
