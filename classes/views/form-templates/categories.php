<?php
/**
 * Form Templates - Categories.
 *
 * @package   Strategy11/FormidableForms
 * @copyright 2010 Formidable Forms
 * @license   GNU General Public License, version 2
 * @link      https://formidableforms.com/
 */

/**
 * Copyright (C) 2023 Formidable Forms
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<ul class="frm-form-templates-categories">
	<?php foreach ( $categories as $category_slug => $category_data ) { ?>
		<?php
		$classes = 'frm-form-templates-cat-item';

		if ( 'all-templates' === $category_slug ) {
			echo '<li class="frm-form-templates-divider"></li>';
			$classes .= ' frm-current';
		}
		?>

		<li class="<?php echo esc_attr( $classes ); ?>" data-category="<?php echo esc_attr( $category_slug ); ?>">
			<span class="frm-form-templates-cat-text"><?php echo esc_html( $category_data['name'] ); ?></span>
			<span class="frm-form-templates-cat-count"><?php echo esc_html( $category_data['count'] ); ?></span>
		</li><!-- .frm-form-templates-cat-item -->
	<?php } ?>
</ul><!-- .frm-form-templates-categories -->
