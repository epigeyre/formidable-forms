<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 3.0
 */
class FrmStrpLiteLinkController {

	/**
	 * Process the form input and call handle_one_time_stripe_link_return_url if all of the required data is being submitted.
	 *
	 * @since 3.0
	 *
	 * @return void
	 */
	public static function handle_return_url() {
		$intent_id     = FrmAppHelper::simple_get( 'payment_intent' );
		$client_secret = FrmAppHelper::simple_get( 'payment_intent_client_secret' );
		$status        = FrmAppHelper::simple_get( 'redirect_status' );

		if ( $intent_id && $client_secret && 'succeeded' === $status ) {
			self::handle_one_time_stripe_link_return_url( $intent_id, $client_secret );
			die();
		}

		$setup_id      = FrmAppHelper::simple_get( 'setup_intent' );
		$client_secret = FrmAppHelper::simple_get( 'setup_intent_client_secret' );

		if ( $setup_id && $client_secret && 'succeeded' === $status ) {
			self::handle_recurring_stripe_link_return_url( $setup_id, $client_secret );
			die();
		}

		wp_die();
	}

	/**
	 * Redirect the user after they return to the return URL based on the status of the payment intent information passed with the request.
	 * This will redirect and get handled by FrmStrpLiteAuth::maybe_show_message or possibly by redirected if there is a success URL set.
	 * If the setup intent is completed, the payment will be changed from pending as well.
	 *
	 * @since 3.0
	 *
	 * @param string $intent_id
	 * @param string $client_secret
	 * @return void
	 */
	private static function handle_one_time_stripe_link_return_url( $intent_id, $client_secret ) {
		$redirect_helper = new FrmStrpLiteLinkRedirectHelper( $intent_id, $client_secret );
		$frm_payment     = new FrmTransLitePayment();

		$payment = $frm_payment->get_one_by( $intent_id, 'receipt_id' );
		if ( ! $payment ) {
			$redirect_helper->handle_error( 'no_payment_record' );
			die();
		}

		$intent = FrmStrpLiteAppHelper::call_stripe_helper_class( 'get_intent', $intent_id );
		if ( ! is_object( $intent ) ) {
			$redirect_helper->handle_error( 'intent_does_not_exist' );
			die();
		}

		if ( $client_secret !== $intent->client_secret ) {
			// Do an extra check against the client secret so the request isn't as easy to spoof.
			$redirect_helper->handle_error( 'unable_to_verify' );
			die();
		}

		if ( ! in_array( $intent->status, array( 'requires_capture', 'succeeded' ), true ) ) {
			$redirect_helper->handle_error( 'did_not_complete' );
			die();
		}

		$status             = 'succeeded' === $intent->status ? 'complete' : 'authorized';
		$new_payment_values = compact( 'status' );

		if ( 'complete' === $status ) {
			$charge                           = reset( $intent->charges->data );
			$new_payment_values['receipt_id'] = $charge->id;
		}

		$entry_id = $payment->item_id;
		$entry    = FrmEntry::getOne( $entry_id );

		if ( ! $entry ) {
			$redirect_helper->handle_error( 'no_entry_found' );
			die();
		}

		$action = FrmStrpLiteActionsController::get_stripe_link_action( $entry->form_id );
		if ( ! $action ) {
			$redirect_helper->handle_error( 'no_stripe_link_action' );
			die();
		}

		$frm_payment->update( $payment->id, $new_payment_values );
		FrmTransLiteActionsController::trigger_payment_status_change( compact( 'status', 'payment' ) );

		$redirect_helper->handle_success( $entry, isset( $charge ) ? $charge->id : '' );
		die();
	}

	/**
	 * Handle return URL for a stripe link recurring payment which uses setup intents.
	 * This will redirect and get handled by FrmStrpLiteAuth::maybe_show_message or possibly by redirected if there is a success URL set.
	 * If the setup intent is completed, the subscription will be created as well.
	 *
	 * @since 3.0
	 *
	 * @param string $setup_id
	 * @param string $client_secret
	 * @return void
	 */
	private static function handle_recurring_stripe_link_return_url( $setup_id, $client_secret ) {
		$redirect_helper = new FrmStrpLiteLinkRedirectHelper( $setup_id, $client_secret );
		$frm_payment     = new FrmTransLitePayment();
		$payment         = $frm_payment->get_one_by( $setup_id, 'receipt_id' );

		if ( ! is_object( $payment ) ) {
			$redirect_helper->handle_error( 'no_payment_record' );
			die();
		}

		// Verify the setup intent.
		$setup_intent = FrmStrpLiteAppHelper::call_stripe_helper_class( 'get_setup_intent', $setup_id );
		if ( ! is_object( $setup_intent ) ) {
			$redirect_helper->handle_error( 'intent_does_not_exist' );
			die();
		}

		// Verify the client secret.
		if ( $setup_intent->client_secret !== $client_secret ) {
			$redirect_helper->handle_error( 'unable_to_verify' );
			die();
		}

		// Verify the customer.
		$customer          = FrmStrpLiteAppHelper::call_stripe_helper_class(
			'get_customer',
			array(
				'user_id' => FrmTransLiteAppHelper::get_user_id_for_current_payment(),
			)
		);
		$payment_method_id = self::get_link_payment_method( $setup_intent, $customer->id );
		if ( ! $payment_method_id ) {
			$redirect_helper->handle_error( 'did_not_complete' );
			die();
		}

		// Verify the entry.
		$entry = FrmEntry::getOne( $payment->item_id );
		if ( ! is_object( $entry ) ) {
			$redirect_helper->handle_error( 'no_entry_found' );
			die();
		}

		// Verify it's an action with Stripe link enabled.
		$action = FrmStrpLiteActionsController::get_stripe_link_action( $entry->form_id );
		if ( ! is_object( $action ) ) {
			$redirect_helper->handle_error( 'no_stripe_link_action' );
			die();
		}

		$amount     = $payment->amount * 100;
		$new_charge = array(
			'customer'               => $customer->id,
			'default_payment_method' => $payment_method_id,
			'payment_settings'       => array(
				'payment_method_types'   => array( 'card', 'link' ),
			),
			'plan' => FrmStrpLiteSubscriptionHelper::get_plan_from_atts(
				array(
					'action' => $action,
					'amount' => $amount,
				)
			),
			'expand'           => array( 'latest_invoice.charge' ),
		);

		$atts = array(
			'action' => $action,
			'entry'  => $entry,
		);

		$trial_end = FrmStrpLiteActionsController::get_trial_end_time( $atts );
		if ( $trial_end ) {
			$new_charge['trial_end'] = $trial_end;
		}

		$subscription = FrmStrpLiteAppHelper::call_stripe_helper_class( 'create_subscription', $new_charge );
		if ( ! is_object( $subscription ) ) {
			$redirect_helper->handle_error( 'create_subscription_failed' );
			die();
		}

		if ( 'succeeded' !== $setup_intent->status ) {
			$redirect_helper->handle_error( 'payment_failed' );
			die();
		}

		$customer_has_been_charged = ! empty( $subscription->latest_invoice->charge );
		$atts['charge']            = FrmStrpLiteSubscriptionHelper::prepare_charge_object_for_subscription( $subscription, $amount );
		$new_payment_values        = array();

		if ( $customer_has_been_charged ) {
			$charge                           = $subscription->latest_invoice->charge;
			$new_payment_values['receipt_id'] = $charge->id;
			$new_payment_values['status']     = 'complete';
		} elseif ( $trial_end ) {
			$new_payment_values['amount']      = 0;
			$new_payment_values['begin_date']  = gmdate( 'Y-m-d', time() );
			$new_payment_values['expire_date'] = gmdate( 'Y-m-d', $trial_end );
		}

		$new_payment_values['sub_id'] = FrmStrpLiteSubscriptionHelper::create_new_subscription( $atts );

		$frm_payment->update( $payment->id, $new_payment_values );

		if ( $customer_has_been_charged ) {
			$status = 'complete';
			FrmTransLiteActionsController::trigger_payment_status_change( compact( 'status', 'payment' ) );
		}

		$redirect_helper->handle_success( $entry, isset( $charge ) ? $charge->id : '' );
		die();
	}

	/**
	 * Check for a link payment method associated with a customer for a Stripe link recurring payment/subscription.
	 * This gets created on Stripe's end after confirmSetup is called client-side in the Stripe add on.
	 * This is required in order to associate a payment method with the subscription that gets created.
	 *
	 * @since 3.0
	 *
	 * @param object $setup_intent
	 * @param string $customer_id This takes a stripe customer ID (a string prefixed with cus_) as input to confirm identity with the setup intent.
	 * @return string|false
	 */
	private static function get_link_payment_method( $setup_intent, $customer_id ) {
		if ( ! empty( $setup_intent->payment_method ) && $customer_id === $setup_intent->customer ) {
			return $setup_intent->payment_method;
		}
		return false;
	}

	/**
	 * Create a pending Stripe link payment on entry creation.
	 * Stripe link uses confirmPayment with a return URL which gets called after this.
	 * The payment is then updated from pending status later in another request, either when the return URL is loaded or with a webhook.
	 *
	 * @since 3.0
	 *
	 * @param array $atts {
	 *     @type stdClass $form
	 *     @type stdClass $entry
	 *     @type WP_Post  $action
	 *     @type string   $amount
	 *     @type object   $customer
	 * }
	 * @return void
	 */
	public static function create_pending_stripe_link_payment( $atts ) {
		if ( empty( $atts['form'] ) || empty( $atts['entry'] ) || empty( $atts['action'] ) || ! isset( $atts['amount'] ) || empty( $atts['customer'] ) ) {
			return;
		}

		$form      = $atts['form'];
		$intent_id = self::verify_intent( $form->id );

		if ( ! $intent_id ) {
			return;
		}

		$is_setup_intent = 0 === strpos( $intent_id, 'seti_' );
		$entry           = $atts['entry'];
		$action          = $atts['action'];
		$amount          = $atts['amount'];
		$customer        = $atts['customer'];

		// No need to add customer to a setup intent because setup intents require a customer on creation.
		if ( ! $is_setup_intent ) {
			FrmStrpLiteAppHelper::call_stripe_helper_class( 'update_intent', $intent_id, array( 'customer' => $customer->id ) );
		}

		$frm_payment = new FrmTransLitePayment();
		$payment     = $frm_payment->create(
			array(
				'paysys'     => 'stripe',
				'amount'     => number_format( ( $amount / 100 ), 2, '.', '' ),
				'status'     => 'pending',
				'item_id'    => $entry->id,
				'action_id'  => $action->ID,
				'receipt_id' => $intent_id,
				'sub_id'     => '',
			)
		);
	}

	/**
	 * Verify a payment intent or setup intent client secret is in the POST data and is valid.
	 *
	 * @since 3.0
	 *
	 * @param string|int $form_id
	 * @return string|false String intent id on success, False if intent is missing or cannot be verified.
	 */
	private static function verify_intent( $form_id ) {
		$client_secrets = FrmAppHelper::get_post_param( 'frmintent' . $form_id, array(), 'sanitize_text_field' );
		if ( ! $client_secrets ) {
			return false;
		}

		$client_secret              = reset( $client_secrets );
		list( $prefix, $intent_id ) = explode( '_', $client_secret );
		$intent_id                  = $prefix . '_' . $intent_id;

		$is_setup_intent = 0 === strpos( $intent_id, 'seti_' );

		$function_name = $is_setup_intent ? 'get_setup_intent' : 'get_intent';
		$intent        = FrmStrpLiteAppHelper::call_stripe_helper_class( $function_name, $intent_id );

		if ( ! $intent || $intent->client_secret !== $client_secret ) {
			return false;
		}

		return $intent_id;
	}

	/**
	 * Flag a form with the frm_stripe_link_form class so it is identifiable when initializing in JavaScript.
	 *
	 * @since 3.0
	 *
	 * @param stdClass $form
	 * @return void
	 */
	public static function add_form_classes( $form ) {
		if ( false === FrmStrpLiteActionsController::get_stripe_link_action( $form->id ) ) {
			return;
		}

		echo ' frm_stripe_link_form ';
	}

	/**
	 * We need to force AJAX submit with Stripe link to avoid the page reloading before confirmPayment is called after entry creation.
	 *
	 * @since 3.0
	 *
	 * @param mixed $form
	 * @return mixed
	 */
	public static function force_ajax_submit_for_stripe_link( $form ) {
		if ( ! is_object( $form ) ) {
			return $form;
		}

		if ( ! empty( $form->options['ajax_submit'] ) ) {
			// AJAX is already on so we can exit early.
			return $form;
		}

		if ( false !== FrmStrpLiteActionsController::get_stripe_link_action( $form->id ) ) {
			$form->options['ajax_submit'] = '1';
		}

		return $form;
	}
}