<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since x.x
 */
class FrmStylesCardHelper {

	/**
	 * @var string
	 */
	private $view_file_path;

	/**
	 * @var WP_Post
	 */
	private $active_style;

	/**
	 * @var WP_Post
	 */
	private $default_style;

	/**
	 * @var int
	 */
	private $form_id;

	/**
	 * @param WP_Post    $active_style
	 * @param WP_Post    $default_style
	 * @param string|int $form_id
	 */
	public function __construct( $active_style, $default_style, $form_id ) {
		$this->view_file_path = FrmAppHelper::plugin_path() . '/classes/views/styles/_custom-style-card.php';

		$this->active_style  = $active_style;
		$this->default_style = $default_style;
		$this->form_id       = (int) $form_id;
	}


	/**
	 * Echo a style card for a specific target Style Post object.
	 *
	 * @since x.x
	 *
	 * @param WP_Post    $style
	 * @return void
	 */
	public function echo_style_card( $style ) {
		$is_default_style     = $style->ID === $this->default_style->ID;
		$is_active_style      = $style->ID === $this->active_style->ID;
		$submit_button_params = $this->get_submit_button_params();
		$params               = $this->get_params_for_style_card( $style );

		if ( $is_default_style ) {
			$params['class'] .= ' frm-default-style-card';
		}
		if ( $is_active_style ) {
			$params['class'] .= ' frm-active-style-card';
		}

		include $this->view_file_path;
	}

	/**
	 * @since x.x
	 *
	 * @return array
	 */
	private function get_submit_button_params() {
		$frm_style            = new FrmStyle();
		$defaults             = $frm_style->get_defaults();
		$submit_button_styles = array(
			'font-size: ' . esc_attr( $defaults['submit_font_size'] ) . ' !important',
			'padding: ' . esc_attr( $defaults['submit_padding'] ) . ' !important',
		);
		return array(
			'type'     => 'submit',
			'disabled' => 'disabled',
			'class'    => 'frm_full_opacity',
			'value'    => esc_attr__( 'Submit', 'formidable' ),
			'style'    => implode( ';', $submit_button_styles ),
		);
	}

	/**
	 * Get params to use in the style card HTML element used in the style list.
	 *
	 * @since x.x
	 *
	 * @param WP_Post $style
	 * @return array
	 */
	private function get_params_for_style_card( $style ) {
		$class_name = 'frm_style_' . $style->post_name;
		$params     = array(
			'class'               => 'with_frm_style frm-style-card ' . $class_name,
			'style'               => self::get_style_param_for_card( $style ),
			'data-classname'      => $class_name,
			'data-style-id'       => $style->ID,
			'data-edit-url'       => esc_url( FrmStylesHelper::get_edit_url( $style, $this->form_id ) ),
			'data-label-position' => $style->post_content['position'],
		);

		/**
		 * Filter params so Pro can add additional params, like data-delete-url.
		 *
		 * @since x.x
		 *
		 * @param array $params
		 * @param array $args {
		 *     @type WP_Post $style
		 * }
		 */
		return apply_filters( 'frm_style_card_params', $params, compact( 'style' ) );
	}

	/**
	 * @since x.x
	 *
	 * @param array $style API style data.
	 * @return void
	 */
	public function echo_card_template( $style ) {
		?>
		<div class="frm-style-card">
			<div class="frm-style-card-preview">
				<img style="max-width: 100%; border-radius: 6px;" src="<?php echo esc_url( $style['icon'][0] ); ?>" />
				<div>
					<span class="frm-style-card-title"><?php echo esc_html( $style['name'] ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the string to populate the style card's style attribute with.
	 * This is used to reset some style variables like font size, label padding, and field height, so the cards all look more similar in comparison.
	 * It's kept static as it only requires a $style as input and also gets called when resetting a style.
	 *
	 * @since x.x
	 *
	 * @param WP_Post|stdClass $style A new style is not a WP_Post object.
	 * @return string
	 */
	public static function get_style_param_for_card( $style ) {
		$styles = array();

		// Add the background color setting for fieldsets to the card.
		if ( ! $style->post_content['fieldset_bg_color'] ) {
			$background_color = '#fff';
		} else {
			$background_color = ( 0 === strpos( $style->post_content['fieldset_bg_color'], 'rgb' ) ? $style->post_content['fieldset_bg_color'] : '#' . $style->post_content['fieldset_bg_color'] );
		}
		$styles[] = '--preview-background-color: ' . $background_color;

		$frm_style = new FrmStyle();
		$defaults  = $frm_style->get_defaults();

		// Overwrite some styles. We want to make sure the sizes are normalized for the cards.
		$styles[] = '--font-size: ' . $defaults['field_font_size'];
		$styles[] = '--field-font-size: ' . $defaults['field_font_size'];
		$styles[] = '--label-padding: ' . $defaults['label_padding'];
		$styles[] = '--field-height: ' . $defaults['field_height'];

		return implode( ';', $styles );
	}
}
