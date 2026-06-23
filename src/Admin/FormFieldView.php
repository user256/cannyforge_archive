<?php
/**
 * Shared rendering helpers for simple admin form fields.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

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
