<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<p>
	<?php /* translators: %s: shortcode code. */ ?>
	<?php echo wp_kses_post( sprintf( __( 'You can display the Helpful Box using the shortcode %s inside posts/pages content.', 'riaco-was-this-helpful' ), '<code>[riwth_helpful_box]</code>' ) ); ?>
</p>
</div>
