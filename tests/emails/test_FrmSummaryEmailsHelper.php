<?php

class test_FrmSummaryEmailsHelper extends FrmUnitTest {

	public function test_get_options() {
		$options = $this->run_private_method(
			array( 'FrmSummaryEmailsHelper', 'get_options' )
		);

		$this->assertTrue( isset( $options['last_monthly'] ) );
		$this->assertTrue( isset( $options['last_yearly'] ) );
		$this->assertTrue( isset( $options['last_license'] ) );
		$this->assertTrue( isset( $options['renewal_date'] ) );
	}

	public function test_get_date_obj() {
		$date = $this->run_private_method(
			array( 'FrmSummaryEmailsHelper', 'get_date_obj' ),
			array( '2023-08-13' )
		);

		$this->assertTrue( $date instanceof DateTime );
		$this->assertEquals( $date->format( 'Y-m-d' ), '2023-08-13' );

		$this->assertFalse(
			$this->run_private_method(
				array( 'FrmSummaryEmailsHelper', 'get_date_obj' ),
				array( '2023-08-' )
			)
		);
	}

	public function test_get_date_diff() {
		$this->assertEquals(
			$this->run_private_method(
				array( 'FrmSummaryEmailsHelper', 'get_date_diff' ),
				array( '2023-08-12', '2023-08-16' )
			),
			4
		);

		$this->assertFalse(
			$this->run_private_method(
				array( 'FrmSummaryEmailsHelper', 'get_date_diff' ),
				array( '2023-08-', '2023-08-16' )
			)
		);
	}

	public function test_get_earliest_form_created_date() {
		$form = FrmForm::getAll( array(), 'id ASC', 1 );
		$this->assertNotEmpty( $form );
		$this->assertEquals(
			$form->created_at,
			$this->run_private_method(
				array( 'FrmSummaryEmailsHelper', 'get_earliest_form_created_date' )
			)
		);
	}

	public function test_should_send_email() {
		// Nothing set yet.
		$options = array(
			'last_monthly' => '',
			'last_yearly'  => '',
			'last_license' => '',
			'renewal_date' => '',
		);
		$this->save_options( $options );

		$this->assertEquals( array( 'monthly' ), FrmSummaryEmailsHelper::should_send_emails() );

		// Yearly was sent less than 1 month ago.
		$options['last_yearly'] = date( 'Y-m-d', strtotime( '-29 days' ) );
		$this->save_options( $options );

		$this->assertEquals( array(), FrmSummaryEmailsHelper::should_send_emails() );

		// Yearly was sent less than 1 month ago, and monthly was sent over 1 month ago.
		$options['last_monthly'] = date( 'Y-m-d', strtotime( '-30 days' ) );
		$this->save_options( $options );

		$this->assertEquals( array(), FrmSummaryEmailsHelper::should_send_emails() );

		// Both yearly and monthly were sent over 1 month ago.
		$options['last_monthly'] = date( 'Y-m-d', strtotime( '-31 days' ) );
		$options['last_yearly'] = date( 'Y-m-d', strtotime( '-31 days' ) );
		$this->save_options( $options );

		$this->assertEquals( array( 'monthly' ), FrmSummaryEmailsHelper::should_send_emails() );

		// Monthly was sent over 1 month ago, yearly was sent over 1 year ago.
		$options['last_yearly'] = date( 'Y-m-d', strtotime( '-365 days' ) );
		$this->save_options( $options );

		$this->assertEquals( array( 'yearly' ), FrmSummaryEmailsHelper::should_send_emails() );
	}

	private function save_options( $options ) {
		$this->set_private_property( 'FrmSummaryEmailsHelper', 'options', $options );
	}
}
