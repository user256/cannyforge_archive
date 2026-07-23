<?php
/**
 * Renders the settings-page accordion section bodies.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\PaginationStyle;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Contracts\Settings\Theme;

/**
 * Presentation-only renderer for the Display/Pagination/Filters/SEO/Advanced
 * accordion bodies. Split out of {@see SettingsView} to keep that class's
 * top-level page structure readable; every method here is pure output, owns
 * no persistence, and mirrors the field-name contract {@see SettingsFormParser}
 * expects back on submit.
 */
final class SettingsSectionsView {
	/**
	 * Shared field renderer.
	 *
	 * @var FormFieldView
	 */
	private FormFieldView $fields;

	/**
	 * Construct the sections renderer.
	 *
	 * @param FormFieldView|null $fields Shared field renderer.
	 */
	public function __construct( ?FormFieldView $fields = null ) {
		$this->fields = $fields ?? new FormFieldView();
	}

	/**
	 * Render the pagination fields (limit, style, archive-link URL).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function pagination( Settings $settings ): void {
		echo '<p><label><strong>' . esc_html__( 'Leading Pagination Pages', 'cannyforge-archive' ) . '</strong><br>';
		printf( '<input type="number" min="1" name="pagination_limit" value="%d"></label></p>', absint( $settings->pagination_limit() ) );

		echo '<p><label><strong>' . esc_html__( 'Pagination Pattern', 'cannyforge-archive' ) . '</strong><br>';
		echo '<select name="pagination_style">';
		printf( '<option value="%s"%s>%s</option>', esc_attr( PaginationStyle::Leading->value ), selected( $settings->pagination_style()->value, PaginationStyle::Leading->value, false ), esc_html__( '1, 2, 3, Archive', 'cannyforge-archive' ) );
		printf( '<option value="%s"%s>%s</option>', esc_attr( PaginationStyle::LeadingWithTail->value ), selected( $settings->pagination_style()->value, PaginationStyle::LeadingWithTail->value, false ), esc_html__( '1, 2, penultimate, last, Archive', 'cannyforge-archive' ) );
		echo '</select></label></p>';

		echo '<p><label><strong>' . esc_html__( '"View Archive" link URL (optional)', 'cannyforge-archive' ) . '</strong><br>';
		printf( '<input type="url" name="archive_url" value="%s" placeholder="%s"></label></p>', esc_attr( $settings->archive_url() ), esc_attr__( 'Defaults to the archive page', 'cannyforge-archive' ) );

		$this->fields->checkbox(
			'full_archive_pagination',
			__( 'Enable full archive pages after the optimised first page', 'cannyforge-archive' ),
			$settings->full_archive_pagination()
		);
		echo '<p class="description">' . esc_html__( 'When enabled, /archive/ remains the selected first page and /archive/page/2/ onward lists every remaining eligible post.', 'cannyforge-archive' ) . '</p>';
	}

	/**
	 * Render the front-end theming controls, including the colour-editor dialog.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function theme( Settings $settings ): void {
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

		$this->render_colour_dialog( $theme );
	}

	/**
	 * Render the "Edit Colours" opener button and its dialog.
	 *
	 * @param Theme $theme Current theme settings.
	 * @return void
	 */
	private function render_colour_dialog( Theme $theme ): void {
		printf(
			'<p><button type="button" class="button button-secondary" data-cf-dialog-open="colors">%s</button></p>',
			esc_html__( 'Edit Colours', 'cannyforge-archive' )
		);
		echo '<dialog class="cannyforge-modal cannyforge-modal--narrow" id="cf-colors-modal" data-cf-dialog="colors" aria-labelledby="cf-colors-modal-title">';
		printf(
			'<button type="button" class="cannyforge-modal__close" aria-label="%s" data-cf-dialog-close>&times;</button>',
			esc_attr__( 'Close', 'cannyforge-archive' )
		);
		echo '<h3 id="cf-colors-modal-title">' . esc_html__( 'Edit Colours', 'cannyforge-archive' ) . '</h3>';

		printf( '<p><label>%s <input type="color" name="theme_accent_color" value="%s"></label></p>', esc_html__( 'Accent Color', 'cannyforge-archive' ), esc_attr( $theme->accent_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_surface_color" value="%s"></label></p>', esc_html__( 'Surface Color', 'cannyforge-archive' ), esc_attr( $theme->surface_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_text_color" value="%s"></label></p>', esc_html__( 'Text Color', 'cannyforge-archive' ), esc_attr( $theme->text_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_border_color" value="%s"></label></p>', esc_html__( 'Border Color', 'cannyforge-archive' ), esc_attr( $theme->border_color() ) );

		// Live WCAG AA (4.5:1) contrast check for the chosen text/accent vs.
		// surface colour pairs — computed client-side in admin.js as the
		// colour pickers change, since the ratio depends on values the site
		// owner is actively editing. Empty/hidden until a pair actually fails
		// (ticket 609).
		echo '<p class="cf-contrast-warning" data-cf-contrast-warning role="status" hidden></p>';

		echo '<p class="submit">';
		printf(
			'<button type="button" class="button button-primary" data-cf-dialog-close>%s</button>',
			esc_html__( 'Done', 'cannyforge-archive' )
		);
		echo '</p>';
		echo '</dialog>';
	}

	/**
	 * Render the archive-type targeting checkboxes (ticket 109).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function targeting( Settings $settings ): void {
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
	public function filters( Settings $settings ): void {
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
	public function content_selection( Settings $settings ): void {
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
	public function link_types( Settings $settings ): void {
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
	public function seo( Settings $settings ): void {
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
