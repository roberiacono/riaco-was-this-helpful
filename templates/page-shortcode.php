<div class="wrap">
<h1><?php esc_html_e( get_admin_page_title() ); ?></h1>
<p>
	<?php echo wp_kses_post( sprintf( __( 'You can display the Helpful Box using the shortcode %s inside posts/pages content.', 'ri-was-this-helpful' ), '<code>[helpful_box]</code>' ) ); ?>
</p>
</div>
