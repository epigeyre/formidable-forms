<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmTransLiteAction extends FrmFormAction {

	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_stripe_icon frm_credit_card_alt_icon frm_icon_font',
			'limit'    => 99,
			'active'   => true,
			'priority' => 45, // after user registration
			'event'    => array( 'create' ),
			'color'    => 'var(--green)',
		);

		$this->FrmFormAction( 'payment', __( 'Collect a Payment', 'formidable' ), $action_ops );
	}

	/**
	 * @param WP_Post $instance
	 * @param array   $args
	 * @return void
	 */
	public function form( $instance, $args = array() ) {
		$form_action = $instance;

		global $wpdb;

		$list_fields    = self::get_defaults();
		$action_control = $this;
		$options        = $form_action->post_content;

		// TODO Call get_classes_for_fields in the Payments submodule.
		$classes = array(
			'credit_card'        => '',
			'billing_address'    => '',
			'billing_first_name' => '',
			'billing_last_name'  => '',
		);

		$form_fields         = $this->get_field_options( $args['form']->id );
		$field_dropdown_atts = compact( 'form_fields', 'form_action' );
		$currencies          = FrmCurrencyHelper::get_currencies();
		$repeat_times        = FrmTransLiteAppHelper::get_repeat_times();

		include FrmTransLiteAppHelper::plugin_path() . '/views/action-settings/payments-options.php';
	}

	/**
	 * @return array
	 */
	public function get_defaults() {
		$defaults = array(
			'description'          => '',
			'email'                => '',
			'amount'               => '',
			'type'                 => '',
			'interval_count'       => 1,
			'interval'             => 'month',
			'payment_count'        => 9999,
			'trial_interval_count' => 0,
			'currency'             => $this->default_currency(),
			'gateway'              => array(),
			'credit_card'          => '',
			'billing_first_name'   => '',
			'billing_last_name'    => '',
			'billing_company'      => '',
			'billing_address'      => '',
			'use_shipping'         => 0,
			'shipping_first_name'  => '',
			'shipping_last_name'   => '',
			'shipping_company'     => '',
			'shipping_address'     => '',
		);
		return (array) apply_filters( 'frm_pay_action_defaults', $defaults );
	}

	/**
	 * @since 2.01
	 *
	 * @return string
	 */
	private function default_currency() {
		if ( ! is_callable( 'FrmAppHelper::get_settings' ) ) {
			return 'usd';
		}

		$frm_settings = FrmAppHelper::get_settings();
		$currency     = trim( $frm_settings->currency );
		if ( ! $currency ) {
			$currency = 'USD';
		}

		return strtolower( $currency );
	}

	/**
	 * @param mixed $form_id
	 * @return array
	 */
	public function get_field_options( $form_id ) {
		$form_fields = FrmField::getAll(
			array(
				'fi.form_id'  => absint( $form_id ),
				'fi.type not' => array( 'divider', 'end_divider', 'html', 'break', 'captcha', 'rte', 'form' ),
			),
			'field_order'
		);
		return $form_fields;
	}

	/**
	 * @param array $form_atts
	 * @param array $field_atts
	 * @return array
	 */
	public function maybe_show_fields_dropdown( $form_atts, $field_atts ) {
		$field_count = 0;
		$field_id    = 0;

		foreach ( $form_atts['form_fields'] as $field ) {
			$type_is_allowed = empty( $field_atts['allowed_fields'] ) || in_array( $field->type, (array) $field_atts['allowed_fields'], true );

			if ( ! $type_is_allowed ) {
				continue;
			}

			$field_count++;
			$field_id = $field->id;
		}

		return compact( 'field_count', 'field_id' );
	}

	/**
	 * Show the dropdown fields for custom form fields
	 *
	 * @param  array $form_atts
	 * @param  array $field_atts
	 * @return void
	 */
	public function show_fields_dropdown( $form_atts, $field_atts ) {
		if ( ! isset( $field_atts['allowed_fields'] ) ) {
			$field_atts['allowed_fields'] = array();
		}
		$has_field = false;
		?>
		<select class="frm_with_left_label" name="<?php echo esc_attr( $this->get_field_name( $field_atts['name'] ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( $field_atts['name'] ) ); ?>">
			<option value=""><?php esc_html_e( '&mdash; Select &mdash;', 'formidable' ); ?></option>
			<?php
			foreach ( $form_atts['form_fields'] as $field ) {
				$type_is_allowed = empty( $field_atts['allowed_fields'] ) || in_array( $field->type, (array) $field_atts['allowed_fields'], true );

				if ( ! $type_is_allowed ) {
					continue;
				}

				$has_field  = true;
				$key_exists = array_key_exists( $field_atts['name'], $form_atts['form_action']->post_content );
				?>
				<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $key_exists ? $form_atts['form_action']->post_content[ $field_atts['name'] ] : 0, $field->id ); ?>>
					<?php echo esc_attr( FrmAppHelper::truncate( $field->name, 50, 1 ) ); ?>
				</option>
				<?php
				unset( $field );
			}

			if ( ! $has_field && ! empty( $field_atts['allowed_fields'] ) ) {
				$readable_fields = str_replace( '_', ' ', implode( ', ', (array) $field_atts['allowed_fields'] ) );
				?>
				<option value="">
					<?php
					// translators: %s: The comma separated field types expected in the form.
					echo esc_html( sprintf( __( 'Oops! You need a %s field in your form.', 'formidable' ), $readable_fields ) );
					?>
				</option>
				<?php
			}
			?>
		</select>
		<?php
	}

	public static function get_single_action_type( $action_id, $type = '' ) {
		$action_control = FrmFormActionsController::get_form_actions( 'payment' );
		return $action_control->get_single_action( $action_id );
	}
}
