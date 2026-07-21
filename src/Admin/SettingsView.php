<?php
/**
 * Renders the settings page form markup.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/**
 * Renders the "HTML Sitemap Generator Settings" form from a Settings snapshot.
 *
 * Pure presentation: it echoes escaped markup and owns no persistence. The
 * mode-dependent panel and all controls follow the brief's mock-up
 * (see docs/PLAN.md). The accordion tab bodies live in SettingsSectionsView
 * and the mode panels in ModeSettingsPanelView (split out in ticket 611 to
 * keep this class under the PHPMD length budget); this class owns only the
 * app shell (header, nav, footer, preview panel, mode cards).
 */
final class SettingsView {
	/**
	 * The form field name carrying the nonce.
	 */
	public const NONCE_FIELD = 'cannyforge_archive_settings_nonce';

	/**
	 * The nonce action.
	 */
	public const NONCE_ACTION = 'cannyforge_archive_save_settings';

	/**
	 * Mode-panel renderer.
	 *
	 * @var ModeSettingsPanelView
	 */
	private ModeSettingsPanelView $mode_panel;

	/**
	 * Accordion tab-body renderer.
	 *
	 * @var SettingsSectionsView
	 */
	private SettingsSectionsView $sections;

	/**
	 * Construct the settings view.
	 *
	 * @param ModeSettingsPanelView|null $mode_panel Mode-panel renderer.
	 * @param SettingsSectionsView|null  $sections   Accordion tab-body renderer.
	 */
	public function __construct( ?ModeSettingsPanelView $mode_panel = null, ?SettingsSectionsView $sections = null ) {
		$this->mode_panel = $mode_panel ?? new ModeSettingsPanelView();
		$this->sections   = $sections ?? new SettingsSectionsView();
	}

	/**
	 * Render the whole settings page.
	 *
	 * @param Settings            $settings              Current settings to populate the form with.
	 * @param string              $action_url            The form post target.
	 * @param string              $preview_url           The live archive URL for the "Preview" link.
	 * @param GoogleSettings|null $google_settings       Current Google settings.
	 * @param string              $google_status         Current Google connection status.
	 * @param bool                $google_secret_saved   Whether a client secret is already stored.
	 * @param string              $google_connect_url    Connect action URL.
	 * @param string              $google_disconnect_url Disconnect action URL.
	 * @param string              $google_notice         One-shot Google notice text.
	 * @param string              $google_notice_type    One-shot Google notice type.
	 * @return void
	 */
	public function render(
		Settings $settings,
		string $action_url,
		string $preview_url = '',
		?GoogleSettings $google_settings = null,
		string $google_status = GoogleTokenStore::STATUS_DISCONNECTED,
		bool $google_secret_saved = false,
		string $google_connect_url = '',
		string $google_disconnect_url = '',
		string $google_notice = '',
		string $google_notice_type = GoogleConnectionController::NOTICE_ERROR
	): void {
		$google_settings = $google_settings ?? new GoogleSettings();

		echo '<div class="cf-app-container preview-hidden">';
		$this->render_brand_header( $preview_url );

		printf( '<form method="post" enctype="multipart/form-data" action="%s" class="cf-app-form">', esc_url( $action_url ) );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		echo '<div class="cf-app-body">';
		$this->render_sidebar_nav();

		echo '<main class="cf-app-main">';
		$this->render_content_tab(
			$settings,
			$google_settings,
			$google_status,
			$google_secret_saved,
			$google_connect_url,
			$google_disconnect_url,
			$google_notice,
			$google_notice_type
		);
		$this->render_display_accordion( $settings );
		$this->render_pagination_accordion( $settings );
		$this->render_filters_accordion( $settings );
		$this->render_seo_accordion( $settings );
		$this->render_advanced_accordion( $settings );
		echo '</main>';

		$this->render_preview_panel( $preview_url );
		echo '</div>';

		$this->render_footer();

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the sidebar tab navigation.
	 *
	 * @return void
	 */
	private function render_sidebar_nav(): void {
		echo '<aside class="cf-app-sidebar">';
		echo '<ul class="cf-app-nav">';
		echo '<li class="active"><a href="#tab-content">' . esc_html__( 'Content', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-display">' . esc_html__( 'Display', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-pagination">' . esc_html__( 'Pagination', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-filters">' . esc_html__( 'Filters', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-seo">' . esc_html__( 'SEO', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-advanced">' . esc_html__( 'Advanced', 'cannyforge-archive' ) . '</a></li>';
		echo '</ul>';
		echo '</aside>';
	}

	/**
	 * Render the "Content" tab: mode selection, mode panel, content selection.
	 *
	 * @param Settings       $settings              Current settings.
	 * @param GoogleSettings $google_settings       Current Google settings.
	 * @param string         $google_status         Current Google connection status.
	 * @param bool           $google_secret_saved   Whether a client secret is already stored.
	 * @param string         $google_connect_url    Connect action URL.
	 * @param string         $google_disconnect_url Disconnect action URL.
	 * @param string         $google_notice         One-shot Google notice text.
	 * @param string         $google_notice_type    One-shot Google notice type.
	 * @return void
	 */
	private function render_content_tab(
		Settings $settings,
		GoogleSettings $google_settings,
		string $google_status,
		bool $google_secret_saved,
		string $google_connect_url,
		string $google_disconnect_url,
		string $google_notice,
		string $google_notice_type
	): void {
		echo '<div id="tab-content" class="cf-tab-section active">';
		echo '<div class="cf-section-header">';
		echo '<h2>' . esc_html__( 'Content', 'cannyforge-archive' ) . '</h2>';
		echo '<p>' . esc_html__( 'Choose what content to show in your archive.', 'cannyforge-archive' ) . '</p>';
		echo '</div>';
		$this->render_mode_only( $settings );
		$this->mode_panel->render( $settings, $google_settings, $google_status, $google_secret_saved, $google_connect_url, $google_disconnect_url, $google_notice, $google_notice_type );
		echo '<div class="cf-card" style="margin-top:24px;">';
		$this->sections->render_content_selection( $settings );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the "Display" accordion (theme + link types).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_display_accordion( Settings $settings ): void {
		$this->render_accordion(
			'display',
			__( 'Display', 'cannyforge-archive' ),
			__( 'Choose layout and what information to show for each article.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->render_theme( $settings );
				echo '<hr style="margin:24px 0;border:0;border-top:1px solid var(--cf-border);">';
				$this->sections->render_link_types( $settings );
			}
		);
	}

	/**
	 * Render the "Pagination" accordion.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_pagination_accordion( Settings $settings ): void {
		$this->render_accordion(
			'pagination',
			__( 'Pagination', 'cannyforge-archive' ),
			__( 'Configure pagination and where the archive link appears.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->render_pagination_only( $settings );
			}
		);
	}

	/**
	 * Render the "Filters" accordion.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_filters_accordion( Settings $settings ): void {
		$this->render_accordion(
			'filters',
			__( 'Filters', 'cannyforge-archive' ),
			__( 'Control which archive types and user filters replace pagination.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->render_filters( $settings );
			}
		);
	}

	/**
	 * Render the "SEO" accordion.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_seo_accordion( Settings $settings ): void {
		$this->render_accordion(
			'seo',
			__( 'SEO', 'cannyforge-archive' ),
			__( 'Set archive title and meta description.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->render_seo( $settings );
			}
		);
	}

	/**
	 * Render the "Advanced" accordion (archive-type targeting).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_advanced_accordion( Settings $settings ): void {
		$this->render_accordion(
			'advanced',
			__( 'Advanced', 'cannyforge-archive' ),
			__( 'Additional options for fine control.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->render_targeting( $settings );
			}
		);
	}

	/**
	 * Render the live-preview side panel.
	 *
	 * @param string $preview_url The URL to preview the archive.
	 * @return void
	 */
	private function render_preview_panel( string $preview_url ): void {
		echo '<aside class="cf-app-preview">';
		echo '<div class="cf-preview-header">';
		echo '<h3>' . esc_html__( 'Live preview', 'cannyforge-archive' ) . '</h3>';
		echo '<p>' . esc_html__( 'This preview reflects your current settings.', 'cannyforge-archive' ) . '</p>';
		echo '</div>';
		echo '<div class="cf-preview-controls">';
		echo '<select><option>Desktop</option></select>';
		echo '<div class="cf-preview-icons"><span class="dashicons dashicons-desktop"></span><span class="dashicons dashicons-smartphone"></span></div>';
		echo '</div>';
		echo '<div class="cf-preview-frame">';
		echo '<iframe src="' . esc_url( $preview_url ) . '" title="Preview"></iframe>';
		echo '</div>';
		echo '</aside>';
	}

	/**
	 * Render the sticky footer with the save action.
	 *
	 * @return void
	 */
	private function render_footer(): void {
		echo '<footer class="cf-app-footer">';
		echo '<div class="cf-footer-status"><span class="dashicons dashicons-saved"></span> ' . esc_html__( 'All changes saved', 'cannyforge-archive' ) . '</div>';
		echo '<div class="cf-footer-actions">';
		echo '<button type="button" class="cf-btn cf-btn-text">' . esc_html__( 'Reset to defaults', 'cannyforge-archive' ) . '</button>';
		submit_button( __( 'Save changes', 'cannyforge-archive' ), 'primary cf-btn cf-btn-primary', 'submit', false, array( 'id' => 'cf-save-btn' ) );
		echo '</div>';
		echo '</footer>';
	}

	/**
	 * Render the branded page header: the CannyForge wordmark above the page title.
	 *
	 * @param string $preview_url The URL to preview the archive.
	 * @return void
	 */
	private function render_brand_header( string $preview_url ): void {
		echo '<header class="cf-app-header">';
		echo '<div class="cf-header-left">';
		echo '<button type="button" class="cf-btn cf-btn-icon" id="cf-nav-toggle" aria-label="Toggle navigation" style="border:none;box-shadow:none;padding:4px;margin-right:8px;"><span class="dashicons dashicons-menu" style="font-size:24px;width:24px;height:24px;"></span></button>';
		echo '<h1>' . esc_html__( 'CannyForge Archive', 'cannyforge-archive' ) . '</h1>';
		echo '<span class="cf-badge">' . esc_html__( 'Draft changes', 'cannyforge-archive' ) . '</span>';
		echo '</div>';
		echo '<div class="cf-header-right">';
		if ( '' !== $preview_url ) {
			echo '<button type="button" class="cf-btn cf-btn-outline" id="cf-preview-toggle">' . esc_html__( 'Live Preview', 'cannyforge-archive' ) . '</button>';
			printf(
				'<a class="cf-btn cf-btn-outline" href="%s" target="_blank" rel="noopener noreferrer">%s <span class="dashicons dashicons-external" style="font-size:16px;margin-top:2px;margin-left:4px;"></span></a>',
				esc_url( $preview_url ),
				esc_html__( 'Open', 'cannyforge-archive' )
			);
		}
		echo '<button type="button" class="cf-btn cf-btn-primary" onclick="document.getElementById(\'cf-save-btn\').click();">' . esc_html__( 'Save changes', 'cannyforge-archive' ) . '</button>';
		echo '<button type="button" class="cf-btn cf-btn-icon"><span class="dashicons dashicons-ellipsis"></span></button>';
		echo '</div>';
		echo '</header>';
	}

	/**
	 * Render a collapsible accordion section.
	 *
	 * @param string   $id        Accordion element id.
	 * @param string   $title     Accordion title.
	 * @param string   $desc      Accordion description.
	 * @param callable $render_cb Callback that renders the accordion body.
	 * @return void
	 */
	private function render_accordion( string $id, string $title, string $desc, callable $render_cb ): void {
		echo '<details class="cf-accordion" id="accordion-' . esc_attr( $id ) . '">';
		echo '<summary class="cf-accordion-summary">';
		echo '<div class="cf-accordion-title">';
		echo '<span class="dashicons dashicons-admin-generic"></span>';
		echo '<div><strong>' . esc_html( $title ) . '</strong><p>' . esc_html( $desc ) . '</p></div>';
		echo '</div>';
		echo '<div class="cf-accordion-status"><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
		echo '</summary>';
		echo '<div class="cf-accordion-body">';
		$render_cb();
		echo '</div>';
		echo '</details>';
	}

	/**
	 * Render the top mode-and-panel section.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_mode_only( Settings $settings ): void {
		$mode = $settings->mode();
		echo '<div class="cf-card" style="margin-top:24px;">';
		echo '<h3 style="margin-top:0;">' . esc_html__( 'Archive style', 'cannyforge-archive' ) . '</h3>';
		echo '<div class="cf-mode-cards">';

		$this->render_mode_card( 'news', __( 'Latest posts', 'cannyforge-archive' ), __( 'Show recently published content', 'cannyforge-archive' ), 'dashicons-rss', Mode::News === $mode );
		$this->render_mode_card( 'blog', __( 'Curated archive', 'cannyforge-archive' ), __( 'Show selected evergreen content', 'cannyforge-archive' ), 'dashicons-bookmark', Mode::Blog === $mode );
		$this->render_mode_card( 'hybrid', __( 'Latest + curated', 'cannyforge-archive' ), __( 'Combine recent and selected content', 'cannyforge-archive' ), 'dashicons-networking', Mode::Hybrid === $mode );

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render a single archive-mode selection card.
	 *
	 * @param string $value   Mode option value.
	 * @param string $title   Card title.
	 * @param string $desc    Card description.
	 * @param string $icon    Dashicon class for the card.
	 * @param bool   $checked Whether this mode is currently selected.
	 * @return void
	 */
	private function render_mode_card( string $value, string $title, string $desc, string $icon, bool $checked ): void {
		$class = $checked ? 'cf-mode-card active' : 'cf-mode-card';
		echo '<label class="' . esc_attr( $class ) . '">';
		echo '<input type="radio" name="mode" value="' . esc_attr( $value ) . '" ' . checked( $checked, true, false ) . ' style="display:none;">';
		echo '<div class="cf-mode-card-header">';
		echo '<div class="cf-radio-circle">' . ( $checked ? '<div class="cf-radio-dot"></div>' : '' ) . '</div>';
		if ( $checked ) {
			echo '<div class="cf-check-badge"><span class="dashicons dashicons-yes"></span></div>';
		}
		echo '</div>';
		echo '<div class="cf-mode-card-icon"><span class="dashicons ' . esc_attr( $icon ) . '"></span></div>';
		echo '<h4>' . esc_html( $title ) . '</h4>';
		echo '<p>' . esc_html( $desc ) . '</p>';
		echo '</label>';
	}
}
