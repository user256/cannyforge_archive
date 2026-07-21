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
 * (see docs/PLAN.md).
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
	 * The `id` attribute on the settings `<form>`, referenced by the
	 * header/footer controls that live outside it via the `form=""` attribute.
	 */
	public const FORM_ID = 'cf-settings-form';

	/**
	 * Mode-panel renderer.
	 *
	 * @var ModeSettingsPanelView
	 */
	private ModeSettingsPanelView $mode_panel;

	/**
	 * Accordion section-body renderer.
	 *
	 * @var SettingsSectionsView
	 */
	private SettingsSectionsView $sections;

	/**
	 * Construct the settings view.
	 *
	 * @param ModeSettingsPanelView|null $mode_panel Mode-panel renderer.
	 * @param SettingsSectionsView|null  $sections   Accordion section-body renderer.
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

		printf(
			'<form method="post" enctype="multipart/form-data" action="%s" class="cf-app-form" id="%s">',
			esc_url( $action_url ),
			esc_attr( self::FORM_ID )
		);
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		echo '<div class="cf-app-body">';

		$this->render_sidebar_nav();

		echo '<main class="cf-app-main">';
		$this->render_content_tab( $settings, $google_settings, $google_status, $google_secret_saved, $google_connect_url, $google_disconnect_url, $google_notice, $google_notice_type );
		$this->render_settings_accordions( $settings );
		echo '</main>';

		$this->render_preview_panel( $preview_url );

		echo '</div>';

		$this->render_footer();

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the tab-navigation sidebar.
	 *
	 * @return void
	 */
	private function render_sidebar_nav(): void {
		echo '<aside class="cf-app-sidebar" id="cf-app-sidebar">';
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
	 * Render the always-visible "Content" tab: mode cards, the mode-dependent
	 * panel, and content-selection.
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
		$this->sections->content_selection( $settings );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the Display/Pagination/Filters/SEO/Advanced accordions.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_settings_accordions( Settings $settings ): void {
		$this->render_accordion(
			'display',
			__( 'Display', 'cannyforge-archive' ),
			__( 'Choose layout and what information to show for each article.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->theme( $settings );
				echo '<hr style="margin:24px 0;border:0;border-top:1px solid var(--cf-border);">';
				$this->sections->link_types( $settings );
			}
		);

		$this->render_accordion(
			'pagination',
			__( 'Pagination', 'cannyforge-archive' ),
			__( 'Configure pagination and where the archive link appears.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->pagination( $settings );
			}
		);

		$this->render_accordion(
			'filters',
			__( 'Filters', 'cannyforge-archive' ),
			__( 'Control which archive types and user filters replace pagination.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->filters( $settings );
			}
		);

		$this->render_accordion(
			'seo',
			__( 'SEO', 'cannyforge-archive' ),
			__( 'Set archive title and meta description.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->seo( $settings );
			}
		);

		$this->render_accordion(
			'advanced',
			__( 'Advanced', 'cannyforge-archive' ),
			__( 'Additional options for fine control.', 'cannyforge-archive' ),
			function () use ( $settings ) {
				$this->sections->targeting( $settings );
			}
		);
	}

	/**
	 * Render the live-preview sidebar panel.
	 *
	 * @param string $preview_url The live archive URL shown in the iframe.
	 * @return void
	 */
	private function render_preview_panel( string $preview_url ): void {
		echo '<aside class="cf-app-preview" id="cf-preview-panel">';
		echo '<div class="cf-preview-header">';
		echo '<h3>' . esc_html__( 'Live preview', 'cannyforge-archive' ) . '</h3>';
		echo '<p>' . esc_html__( 'This shows the last saved version of your archive. Save changes to update it.', 'cannyforge-archive' ) . '</p>';
		echo '<p class="cf-preview-stale" id="cf-preview-stale" hidden>' . esc_html__( 'You have unsaved changes that are not shown below yet.', 'cannyforge-archive' ) . '</p>';
		echo '</div>';
		$this->render_preview_device_controls();
		echo '<div class="cf-preview-frame" id="cf-preview-frame" data-device="desktop">';
		echo '<iframe src="' . esc_url( $preview_url ) . '" title="' . esc_attr__( 'Archive preview', 'cannyforge-archive' ) . '"></iframe>';
		echo '</div>';
		echo '</aside>';
	}

	/**
	 * Render the sticky footer: dirty/saved status and the Reset/Save actions.
	 *
	 * @return void
	 */
	private function render_footer(): void {
		echo '<footer class="cf-app-footer">';
		echo '<div class="cf-footer-status" id="cf-form-status" data-state="saved" aria-live="polite">';
		echo '<span class="dashicons dashicons-saved" aria-hidden="true"></span> ';
		echo '<span class="cf-footer-status-text">' . esc_html__( 'All changes saved', 'cannyforge-archive' ) . '</span>';
		echo '</div>';
		echo '<div class="cf-footer-actions">';
		echo '<button type="reset" class="cf-btn cf-btn-text" id="cf-reset-btn">' . esc_html__( 'Reset to saved values', 'cannyforge-archive' ) . '</button>';
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
		echo '<button type="button" class="cf-btn cf-btn-icon" id="cf-nav-toggle" aria-label="' . esc_attr__( 'Toggle navigation', 'cannyforge-archive' ) . '" aria-expanded="true" aria-controls="cf-app-sidebar"><span class="dashicons dashicons-menu" aria-hidden="true"></span></button>';
		echo '<h1>' . esc_html__( 'CannyForge Archive', 'cannyforge-archive' ) . '</h1>';
		echo '</div>';
		echo '<div class="cf-header-right">';
		if ( '' !== $preview_url ) {
			echo '<button type="button" class="cf-btn cf-btn-outline" id="cf-preview-toggle" aria-expanded="false" aria-controls="cf-preview-panel">' . esc_html__( 'Live Preview', 'cannyforge-archive' ) . '</button>';
			printf(
				'<a class="cf-btn cf-btn-outline" href="%s" target="_blank" rel="noopener noreferrer">%s <span class="dashicons dashicons-external" aria-hidden="true"></span></a>',
				esc_url( $preview_url ),
				esc_html__( 'Preview Archive', 'cannyforge-archive' )
			);
		}
		printf(
			'<button type="submit" class="cf-btn cf-btn-primary" form="%s">%s</button>',
			esc_attr( self::FORM_ID ),
			esc_html__( 'Save changes', 'cannyforge-archive' )
		);
		echo '</div>';
		echo '</header>';
	}

	/**
	 * Render the preview device-size toggle (Desktop / Tablet / Mobile).
	 *
	 * The buttons carry both an icon and a visually-hidden text label so the
	 * accessible name matches what is visually obvious to sighted users; the
	 * JS in admin.js applies the actual size simulation to the preview frame.
	 *
	 * @return void
	 */
	private function render_preview_device_controls(): void {
		echo '<div class="cf-preview-controls" role="group" aria-label="' . esc_attr__( 'Preview device size', 'cannyforge-archive' ) . '">';
		$this->render_preview_device_button( 'desktop', __( 'Desktop', 'cannyforge-archive' ), 'dashicons-desktop', true );
		$this->render_preview_device_button( 'tablet', __( 'Tablet', 'cannyforge-archive' ), 'dashicons-tablet', false );
		$this->render_preview_device_button( 'mobile', __( 'Mobile', 'cannyforge-archive' ), 'dashicons-smartphone', false );
		echo '</div>';
	}

	/**
	 * Render one preview device-size toggle button.
	 *
	 * @param string $device  Device key (`desktop`, `tablet`, `mobile`).
	 * @param string $label   Accessible/visible label.
	 * @param string $icon    Dashicon class.
	 * @param bool   $pressed Whether this is the initially-active device.
	 * @return void
	 */
	private function render_preview_device_button( string $device, string $label, string $icon, bool $pressed ): void {
		printf(
			'<button type="button" class="cf-preview-device" data-cf-preview-device="%1$s" aria-pressed="%2$s"><span class="dashicons %3$s" aria-hidden="true"></span><span class="cf-visually-hidden">%4$s</span></button>',
			esc_attr( $device ),
			$pressed ? 'true' : 'false',
			esc_attr( $icon ),
			esc_html( $label )
		);
	}

	/**
	 * Render one collapsible settings section.
	 *
	 * @param string   $id        Accordion id suffix (`accordion-{$id}`).
	 * @param string   $title     Section title.
	 * @param string   $desc      Section description.
	 * @param callable $render_cb Callback that echoes the section's fields.
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
	 * Render the top mode-selection cards.
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
	 * Render one mode-selection card.
	 *
	 * The radio is a real, focusable form control (visually hidden with the
	 * `.cf-visually-hidden` clip technique, not `display:none`), so keyboard
	 * and screen-reader users can reach and operate it. All active-state
	 * visuals (border, dot, check badge) are driven purely by CSS via
	 * `:has(input:checked)` — they update instantly on any input method,
	 * including a native form reset, with no JS required.
	 *
	 * @param string $value   Radio value.
	 * @param string $title   Card title.
	 * @param string $desc    Card description.
	 * @param string $icon    Dashicon class.
	 * @param bool   $checked Whether this card is the currently-selected mode.
	 * @return void
	 */
	private function render_mode_card( string $value, string $title, string $desc, string $icon, bool $checked ): void {
		echo '<label class="cf-mode-card">';
		echo '<input type="radio" name="mode" value="' . esc_attr( $value ) . '" class="cf-visually-hidden" ' . checked( $checked, true, false ) . '>';
		echo '<div class="cf-mode-card-header">';
		echo '<div class="cf-radio-circle"><div class="cf-radio-dot"></div></div>';
		echo '<div class="cf-check-badge"><span class="dashicons dashicons-yes" aria-hidden="true"></span></div>';
		echo '</div>';
		echo '<div class="cf-mode-card-icon"><span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span></div>';
		echo '<h4>' . esc_html( $title ) . '</h4>';
		echo '<p>' . esc_html( $desc ) . '</p>';
		echo '</label>';
	}
}
