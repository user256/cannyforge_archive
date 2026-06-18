<?php
/**
 * The settings admin page.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;

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
	 * Construct the page.
	 *
	 * @param SettingsRepositoryInterface $repository Settings persistence.
	 * @param SettingsFormParser          $parser     Form mapper.
	 * @param SettingsView                $view       Form renderer.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		SettingsFormParser $parser,
		SettingsView $view
	) {
		$this->repository = $repository;
		$this->parser     = $parser;
		$this->view       = $view;
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
			__( 'CannyForge Archive', 'cannyforge-archive' ),
			__( 'CannyForge Archive', 'cannyforge-archive' ),
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
		$this->view->render( $this->repository->get(), $action_url );
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
		$input = wp_unslash( $_POST );
		$this->repository->save( $this->parser->parse( is_array( $input ) ? $input : array() ) );

		return true;
	}
}
