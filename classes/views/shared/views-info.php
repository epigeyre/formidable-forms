<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_wrap">
	<div class="frm_page_container" style="overflow-y: auto;">
		<?php
		FrmAppHelper::get_admin_header(
			array(
				'label' => __( 'Views', 'formidable' ),
				'form'  => $form,
				'close' => $form ? admin_url( 'admin.php?page=formidable&frm_action=views&form=' . $form->id ) : '',
			)
		);
		?>
		<div style="overflow-y: auto;">
			<div class="frmcenter" style="margin-top:10vh">
				<div class="frm-video-wrapper">
					<iframe width="843" height="474" src="https://www.youtube.com/embed/3T-WMIvYadg" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
				<h2><?php esc_html_e( 'Show and Edit Entries with Views', 'formidable' ); ?></h2>
				<p style="max-width:400px;margin:20px auto">
					<?php esc_html_e( 'Bring entries to the front-end of your site for full-featured applications or just to show the content.', 'formidable' ); ?>
				</p>
				<?php
				$upgrade_link_args = array(
					'medium' => 'views-info',
					'plan'   => 'view',
				);
				FrmAddonsController::conditional_action_button( 'views', $upgrade_link_args );
				?>
			</div>
		</div>
	</div>
</div>
