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
use CannyForge\Archive\Contracts\Settings\PaginationStyle;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Contracts\Settings\Theme;
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
	 * Mode-panel renderer.
	 *
	 * @var ModeSettingsPanelView
	 */
	private ModeSettingsPanelView $mode_panel;

	/**
	 * Shared field renderer.
	 *
	 * @var FormFieldView
	 */
	private FormFieldView $fields;

	/**
	 * Construct the settings view.
	 *
	 * @param ModeSettingsPanelView|null $mode_panel Mode-panel renderer.
	 * @param FormFieldView|null         $fields     Shared field renderer.
	 */
	public function __construct( ?ModeSettingsPanelView $mode_panel = null, ?FormFieldView $fields = null ) {
		$this->mode_panel = $mode_panel ?? new ModeSettingsPanelView();
		$this->fields     = $fields ?? new FormFieldView();
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

		echo '<main class="cf-app-main">';

		echo '<div id="tab-content" class="cf-tab-section active">';
		echo '<div class="cf-section-header">';
		echo '<h2>' . esc_html__( 'Content', 'cannyforge-archive' ) . '</h2>';
		echo '<p>' . esc_html__( 'Choose what content to show in your archive.', 'cannyforge-archive' ) . '</p>';
		echo '</div>';
		$this->render_mode_only( $settings );
		$this->mode_panel->render( $settings, $google_settings, $google_status, $google_secret_saved, $google_connect_url, $google_disconnect_url, $google_notice, $google_notice_type );
		echo '<div class="cf-card" style="margin-top:24px;">';
		$this->render_content_selection( $settings );
		echo '</div>';
		echo '</div>';

		$this->render_accordion( 'display', __( 'Display', 'cannyforge-archive' ), __( 'Choose layout and what information to show for each article.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_theme( $settings );
			echo '<hr style="margin:24px 0;border:0;border-top:1px solid var(--cf-border);">';
			$this->render_link_types( $settings );
		} );

		$this->render_accordion( 'pagination', __( 'Pagination', 'cannyforge-archive' ), __( 'Configure pagination and where the archive link appears.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_pagination_only( $settings );
		} );

		$this->render_accordion( 'filters', __( 'Filters', 'cannyforge-archive' ), __( 'Control which archive types and user filters replace pagination.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_filters( $settings );
		} );

		$this->render_accordion( 'seo', __( 'SEO', 'cannyforge-archive' ), __( 'Set archive title and meta description.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_seo( $settings );
		} );

		$this->render_accordion( 'advanced', __( 'Advanced', 'cannyforge-archive' ), __( 'Additional options for fine control.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_targeting( $settings );
		} );

		echo '</main>';

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

		echo '</div>';

		echo '<footer class="cf-app-footer">';
		echo '<div class="cf-footer-status"><span class="dashicons dashicons-saved"></span> ' . esc_html__( 'All changes saved', 'cannyforge-archive' ) . '</div>';
		echo '<div class="cf-footer-actions">';
		echo '<button type="button" class="cf-btn cf-btn-text">' . esc_html__( 'Reset to defaults', 'cannyforge-archive' ) . '</button>';
		submit_button( __( 'Save changes', 'cannyforge-archive' ), 'primary cf-btn cf-btn-primary', 'submit', false, array( 'id' => 'cf-save-btn' ) );
		echo '</div>';
		echo '</footer>';

		echo '</form>';
		echo '</div>';
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

	private function render_mode_card( string $value, string $title, string $desc, string $icon, bool $checked ): void {
		$class = $checked ? 'cf-mode-card active' : 'cf-mode-card';
		echo '<label class="' . esc_attr( $class ) . '">';
		echo '<input type="radio" name="mode" value="' . esc_attr( $value ) . '" ' . checked( $checked, true, false ) . ' style="display:none;">';
		echo '<div class="cf-mode-card-header">';
		echo '<div class="cf-radio-circle">' . ( $checked ? '<div class="cf-radio-dot"></div>' : '' ) . '</div>';
		if ( $checked ) echo '<div class="cf-check-badge"><span class="dashicons dashicons-yes"></span></div>';
		echo '</div>';
		echo '<div class="cf-mode-card-icon"><span class="dashicons ' . esc_attr( $icon ) . '"></span></div>';
		echo '<h4>' . esc_html( $title ) . '</h4>';
		echo '<p>' . esc_html( $desc ) . '</p>';
		echo '</label>';
	}

	private function render_pagination_only( Settings $settings ): void {
		echo '<p><label><strong>' . esc_html__( 'Leading Pagination Pages', 'cannyforge-archive' ) . '</strong><br>';
		printf( '<input type="number" min="1" name="pagination_limit" value="%d"></label></p>', absint( $settings->pagination_limit() ) );

		echo '<p><label><strong>' . esc_html__( 'Pagination Pattern', 'cannyforge-archive' ) . '</strong><br>';
		echo '<select name="pagination_style">';
		printf( '<option value="%s"%s>%s</option>', esc_attr( PaginationStyle::Leading->value ), selected( $settings->pagination_style()->value, PaginationStyle::Leading->value, false ), esc_html__( '1, 2, 3, Archive', 'cannyforge-archive' ) );
		printf( '<option value="%s"%s>%s</option>', esc_attr( PaginationStyle::LeadingWithTail->value ), selected( $settings->pagination_style()->value, PaginationStyle::LeadingWithTail->value, false ), esc_html__( '1, 2, penultimate, last, Archive', 'cannyforge-archive' ) );
		echo '</select></label></p>';

		echo '<p><label><strong>' . esc_html__( '"View Archive" link URL (optional)', 'cannyforge-archive' ) . '</strong><br>';
		printf( '<input type="url" name="archive_url" value="%s" placeholder="%s"></label></p>', esc_attr( $settings->archive_url() ), esc_attr__( 'Defaults to the archive page', 'cannyforge-archive' ) );
	}

	/**
	 * Render the theme section.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	/**
	 * Render the front-end theming controls.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_theme( Settings $settings ): void {
		$theme = $settings->theme();

		echo '<h2>' . esc_html__( 'Front-end Theme', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'These styles apply to the archive page and the "View Archive" pagination block.', 'cannyforge-archive' );
		echo '</p>';

		echo '<p><label>' . esc_html__( 'Layout', 'cannyforge-archive' ) . ' ';
		echo '<select name="theme_layout">';
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( Theme::LAYOUT_DEFAULT ),
			selected( $theme->layout(), Theme::LAYOUT_DEFAULT, false ),
			esc_html__( 'Default (follows blog pagination layout)', 'cannyforge-archive' )
		);
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( Theme::LAYOUT_LIST ),
			selected( $theme->layout(), Theme::LAYOUT_LIST, false ),
			esc_html__( 'Simple list', 'cannyforge-archive' )
		);
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( Theme::LAYOUT_CARDS ),
			selected( $theme->layout(), Theme::LAYOUT_CARDS, false ),
			esc_html__( 'Cards', 'cannyforge-archive' )
		);
		echo '</select></label></p>';

		echo '<p><button type="button" class="button button-secondary" onclick="document.getElementById(\'cf-colors-modal\').showModal()">' . esc_html__( 'Edit Colours', 'cannyforge-archive' ) . '</button></p>';
		echo '<dialog id="cf-colors-modal" style="border: none; border-radius: 16px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); max-width: 400px; width: 100%;">';
		echo '<h3>' . esc_html__( 'Edit Colours', 'cannyforge-archive' ) . '</h3>';

		printf( '<p><label>%s <input type="color" name="theme_accent_color" value="%s"></label></p>', esc_html__( 'Accent Color', 'cannyforge-archive' ), esc_attr( $theme->accent_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_surface_color" value="%s"></label></p>', esc_html__( 'Surface Color', 'cannyforge-archive' ), esc_attr( $theme->surface_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_text_color" value="%s"></label></p>', esc_html__( 'Text Color', 'cannyforge-archive' ), esc_attr( $theme->text_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_border_color" value="%s"></label></p>', esc_html__( 'Border Color', 'cannyforge-archive' ), esc_attr( $theme->border_color() ) );

		echo '<div style="text-align: right; margin-top: 1.5rem;">';
		echo '<button type="button" class="button button-primary" onclick="document.getElementById(\'cf-colors-modal\').close()">' . esc_html__( 'Done', 'cannyforge-archive' ) . '</button>';
		echo '</div>';
		echo '</dialog>';
	}

	/**
	 * Render the lower settings grid.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	/**
	 * Render the archive-type targeting checkboxes (ticket 109).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_targeting( Settings $settings ): void {
		$targeting = $settings->targeting();

		echo '<h2>' . esc_html__( 'Pagination Targeting', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'Replace pagination on these archive types.', 'cannyforge-archive' );
		echo '</p>';
		$this->fields->checkbox( 'target_category', __( 'Category archives', 'cannyforge-archive' ), $targeting->category() );
		$this->fields->checkbox( 'target_tag', __( 'Tag archives', 'cannyforge-archive' ), $targeting->tag() );
		$this->fields->checkbox( 'target_author', __( 'Author archives', 'cannyforge-archive' ), $targeting->author() );
		$this->fields->checkbox( 'target_date', __( 'Date archives', 'cannyforge-archive' ), $targeting->date() );
	}

	/**
	 * Render the client-side filter checkboxes.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_filters( Settings $settings ): void {
		$filters = $settings->filters();

		echo '<h2>' . esc_html__( 'User Filters', 'cannyforge-archive' ) . '</h2>';
		$this->fields->checkbox( 'filter_search', __( 'Search Box', 'cannyforge-archive' ), $filters->search() );
		$this->fields->checkbox( 'filter_category', __( 'Category filters', 'cannyforge-archive' ), $filters->category() );
		$this->fields->checkbox( 'filter_tag', __( 'Tag filters', 'cannyforge-archive' ), $filters->tag() );
		$this->fields->checkbox( 'filter_month_year', __( 'Month + Year filters', 'cannyforge-archive' ), $filters->month_year() );
		$this->fields->checkbox( 'filter_author', __( 'Author filters', 'cannyforge-archive' ), $filters->author() );
	}

	/**
	 * Render the content-selection controls (ticket 111).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_content_selection( Settings $settings ): void {
		$selection  = $settings->content_selection();
		$categories = $this->term_name_options( 'category' );
		$tags       = $this->term_name_options( 'post_tag' );

		echo '<h2>' . esc_html__( 'Content Selection', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'Choose categories and tags from your site instead of typing term names manually.', 'cannyforge-archive' );
		echo '</p>';
		$this->fields->multiselect_field( 'select_include_categories', __( 'Include categories', 'cannyforge-archive' ), $categories, $selection->include_categories(), __( 'Only show entries that match at least one selected category.', 'cannyforge-archive' ) );
		$this->fields->multiselect_field( 'select_include_tags', __( 'Include tags', 'cannyforge-archive' ), $tags, $selection->include_tags(), __( 'Only show entries that match at least one selected tag.', 'cannyforge-archive' ) );
		$this->fields->multiselect_field( 'select_exclude_categories', __( 'Exclude categories', 'cannyforge-archive' ), $categories, $selection->exclude_categories(), __( 'Hide any entry that matches one of these categories.', 'cannyforge-archive' ) );
		$this->fields->multiselect_field( 'select_exclude_tags', __( 'Exclude tags', 'cannyforge-archive' ), $tags, $selection->exclude_tags(), __( 'Hide any entry that matches one of these tags.', 'cannyforge-archive' ) );
		$this->fields->checkbox( 'select_exclude_noindex', __( 'Exclude noindex content', 'cannyforge-archive' ), $selection->exclude_noindex() );
		$this->fields->list_field( 'select_pinned_urls', __( 'Pinned URLs (shown first)', 'cannyforge-archive' ), $selection->pinned_urls() );
	}

	/**
	 * Render the archive link-type checkboxes.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_link_types( Settings $settings ): void {
		$types = $settings->link_types();

		echo '<h2>' . esc_html__( 'Archive Link Types', 'cannyforge-archive' ) . '</h2>';
		$this->fields->checkbox( 'link_title', __( 'Title (default)', 'cannyforge-archive' ), $types->title() );
		$this->fields->checkbox( 'link_description', __( 'Description / snippet', 'cannyforge-archive' ), $types->description() );
		$this->fields->checkbox( 'link_featured_image', __( 'Featured Image', 'cannyforge-archive' ), $types->featured_image() );
		$this->fields->checkbox( 'link_categories', __( 'Categories', 'cannyforge-archive' ), $types->categories() );
		$this->fields->checkbox( 'link_tags', __( 'Tags', 'cannyforge-archive' ), $types->tags() );
		$this->fields->checkbox( 'link_author', __( 'Author', 'cannyforge-archive' ), $types->author() );
		$this->fields->checkbox( 'link_published_date', __( 'Published date', 'cannyforge-archive' ), $types->published_date() );
	}

	/**
	 * Available term names for a taxonomy, for admin multi-select menus.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array{value: string, label: string}>
	 */
	private function term_name_options( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$options = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$options[] = array(
					'value' => (string) $term->name,
					'label' => (string) $term->name,
				);
			}
		}

		return $options;
	}

	/**
	 * Render the SEO controls (ticket 110).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_seo( Settings $settings ): void {
		$seo = $settings->seo();

		echo '<h2>' . esc_html__( 'SEO', 'cannyforge-archive' ) . '</h2>';

		echo '<p><label>' . esc_html__( 'Archive title', 'cannyforge-archive' ) . ' ';
		printf( '<input type="text" name="seo_title" value="%s"></label></p>', esc_attr( $seo->title() ) );

		echo '<p><label>' . esc_html__( 'Meta description', 'cannyforge-archive' ) . ' ';
		printf(
			'<textarea name="seo_meta_description" rows="2" cols="50">%s</textarea></label></p>',
			esc_textarea( $seo->meta_description() )
		);

		$this->fields->checkbox( 'seo_index', __( 'Allow indexing (index)', 'cannyforge-archive' ), $seo->index() );
		$this->fields->checkbox( 'seo_follow', __( 'Allow following links (follow)', 'cannyforge-archive' ), $seo->follow() );

		echo '<p><label>' . esc_html__( 'Canonical URL (optional)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="url" name="seo_canonical" value="%s" placeholder="%s"></label></p>',
			esc_attr( $seo->canonical() ),
			esc_attr__( 'Defaults to the archive URL', 'cannyforge-archive' )
		);
	}
}
