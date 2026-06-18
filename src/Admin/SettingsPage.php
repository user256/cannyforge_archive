<?php
/**
 * The settings admin page.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Settings\CsvUrlExtractor;

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
	 * Default archive endpoint slug for the preview link. Mirrors the Frontend
	 * ArchivePage endpoint slug; kept local so Admin needn't depend on Frontend.
	 */
	private const DEFAULT_ARCHIVE_SLUG = 'archive';

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
	 * Construct the page.
	 *
	 * @param SettingsRepositoryInterface $repository Settings persistence.
	 * @param SettingsFormParser          $parser     Form mapper.
	 * @param SettingsView                $view       Form renderer.
	 * @param CsvUrlExtractor|null        $csv        CSV URL extractor.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		SettingsFormParser $parser,
		SettingsView $view,
		?CsvUrlExtractor $csv = null
	) {
		$this->repository = $repository;
		$this->parser     = $parser;
		$this->view       = $view;
		$this->csv        = $csv ?? new CsvUrlExtractor();
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
			__( 'Archive Generator', 'cannyforge-archive' ),
			__( 'Archive Generator', 'cannyforge-archive' ),
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

		$saved = $this->maybe_save();
		if ( $saved ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Settings saved.', 'cannyforge-archive' );
			echo '</p></div>';
		}

		$action_url = esc_url_raw( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		$this->view->render( $this->repository->get(), $action_url, $this->preview_url() );
	}

	/**
	 * The live archive URL for the "Preview" link: the configured destination
	 * override, or the archive endpoint under the site root.
	 *
	 * @return string
	 */
	private function preview_url(): string {
		$override = $this->repository->get()->archive_url();

		return '' !== $override
			? $override
			: home_url( '/' . self::DEFAULT_ARCHIVE_SLUG . '/' );
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
		$csv_urls = $this->uploaded_csv_urls();
		$this->repository->save(
			$this->parser->parse( is_array( $input ) ? $input : array(), $csv_urls )
		);

		return true;
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
