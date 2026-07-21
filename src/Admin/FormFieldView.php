<?php
/**
 * Shared rendering helpers for simple admin form fields.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders small reusable field fragments.
 */
final class FormFieldView {
	/**
	 * Render a labelled textarea holding one value per line.
	 *
	 * @param string   $name   Field name.
	 * @param string   $label  Human label.
	 * @param string[] $values Current values.
	 * @return void
	 */
	public function list_field( string $name, string $label, array $values ): void {
		printf( '<p><label>%s<br>', esc_html( $label ) );
		printf(
			'<textarea name="%s" rows="3" cols="40">%s</textarea></label></p>',
			esc_attr( $name ),
			esc_textarea( implode( "\n", $values ) )
		);
	}

	/**
	 * Render a labelled multi-select field.
	 *
	 * @param string                                          $name        Field name, without [] suffix.
	 * @param string                                          $label       Human label.
	 * @param array<int, array{value: string, label: string}> $options  Available options.
	 * @param string[]                                        $selected    Selected values.
	 * @param string                                          $description Optional helper text.
	 * @return void
	 */
	public function multiselect_field( string $name, string $label, array $options, array $selected, string $description = '' ): void {
		printf( '<p><label>%s<br>', esc_html( $label ) );
		printf(
			'<select name="%s[]" multiple size="6" style="min-width:100%%;">',
			esc_attr( $name )
		);

		foreach ( $options as $option ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $option['value'] ),
				selected( in_array( $option['value'], $selected, true ), true, false ),
				esc_html( $option['label'] )
			);
		}

		echo '</select></label>';

		if ( '' !== $description ) {
			printf( '<span class="description">%s</span>', esc_html( $description ) );
		}

		echo '</p>';
	}

	/**
	 * Render a single labelled checkbox.
	 *
	 * @param string $name    Field name.
	 * @param string $label   Human label.
	 * @param bool   $checked Whether it is checked.
	 * @return void
	 */
	public function checkbox( string $name, string $label, bool $checked ): void {
		printf(
			'<p><label><input type="checkbox" name="%s" value="1" %s> %s</label></p>',
			esc_attr( $name ),
			checked( $checked, true, false ),
			esc_html( $label )
		);
	}
}
