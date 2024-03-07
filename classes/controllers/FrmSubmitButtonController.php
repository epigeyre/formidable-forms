<?php
/**
 * Submit button controller
 *
 * @since x.x
 * @package Formidable
 */

class FrmSubmitButtonController {

	const FIELD_TYPE = 'submit';

	public static function get_submit_field( $form_id ) {
		return FrmField::get_all_types_in_form( $form_id, self::FIELD_TYPE, 1 );
	}

	/**
	 * Checks if there is submit button field on the current page.
	 *
	 * @param array $values Prepared form values.
	 * @return bool
	 */
	public static function has_submit_field_on_current_page( $values ) {
		if ( empty( $values['fields'] ) ) {
			return false;
		}

		foreach ( $values['fields'] as $field ) {
			if ( self::FIELD_TYPE === $field['type'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets current action (create or update) from the global variable.
	 *
	 * @param int $form_id Form ID.
	 * @return string
	 */
	public static function get_current_action_from_global_var( $form_id ) {
		global $frm_vars;

		if ( isset( $frm_vars['form_params'][ $form_id ]['action'] ) ) {
			return $frm_vars['form_params'][ $form_id ]['action'];
		}

		return 'create';
	}

	private static function get_submit_settings_from_form( $form ) {
		return array(
			'edit_text' => FrmForm::get_option(
				array(
					'form' => $form,
					'option' => 'edit_value',
				)
			),
			'align'     => FrmForm::get_option(
				array(
					'form' => $form,
					'option' => 'submit_align',
				)
			),
			'start_over' => FrmForm::get_option(
				array(
					'form' => $form,
					'option' => 'start_over',
				)
			),
			'start_over_label' => FrmForm::get_option(
				array(
					'form' => $form,
					'option' => 'start_over_label',
				)
			),
		);
	}

	public static function copy_submit_field_settings_to_form( $form ) {
		$submit_field = self::get_submit_field( $form->id );
		if ( ! $submit_field ) {
			return $form;
		}

		$form->options['submit_value'] = $submit_field->name;

		return $form;
	}

	public static function maybe_create_submit_field( $form, $fields, &$reset_fields ) {
		$has_submit_field = false;

		foreach ( $fields as $field ) {
			if ( self::FIELD_TYPE === $field->type ) {
				$has_submit_field = true;
				break;
			}
		}

		if ( $has_submit_field ) {
			return;
		}

		$field_data = FrmFieldsHelper::setup_new_vars( self::FIELD_TYPE, $form->id );

		$submit_settings = self::get_submit_settings_from_form( $form );
		$field_data['field_options'] = $submit_settings + $field_data['field_options'];
		$field_data['name']          = FrmForm::get_option(
			array(
				'form'    => $form,
				'option'  => 'submit_value',
				'default' => __( 'Submit', 'formidable' )
			)
		);

		if ( FrmField::create( $field_data ) ) {
			$reset_fields = true;
		}
	}
}
