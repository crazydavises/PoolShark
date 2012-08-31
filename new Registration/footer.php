<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */
?>

	</div><!-- #main -->

	<footer id="colophon" role="contentinfo">

			<?php
				/* A sidebar in the footer? Yep. You can can customize
				 * your footer with three columns of widgets.
				 */
				if ( ! is_404() )
					get_sidebar( 'footer' );
			?>

<!-- Custom Login/Register/Password Code @ http://digwp.com/2010/12/login-register-password-code/ -->
<!-- jQuery -->

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
<script type="text/javascript" charset="utf-8">
	jQuery(document).ready(function() {
		jQuery(".tab_content_login").hide();
		jQuery("ul.tabs_login li:first").addClass("active_login").show();
		jQuery(".tab_content_login:first").show();
		jQuery("ul.tabs_login li").click(function() {
			jQuery("ul.tabs_login li").removeClass("active_login");
			jQuery(this).addClass("active_login");
			jQuery(".tab_content_login").hide();
			var activeTab = jQuery(this).find("a").attr("href");
			if (jQuery.browser.msie) {jQuery(activeTab).show();}
			else {jQuery(activeTab).show();}
			return false;
		});
	});
</script>

<!-- Custom Login/Register/Password Code @ http://digwp.com/2010/12/login-register-password-code/ -->

			<div id="site-generator">
				<?php do_action( 'twentyeleven_credits' ); ?>
				<a href="<?php echo esc_url( __( 'http://wordpress.org/', 'twentyeleven' ) ); ?>" title="<?php esc_attr_e( 'Semantic Personal Publishing Platform', 'twentyeleven' ); ?>" rel="generator"><?php printf( __( 'Proudly powered by %s', 'twentyeleven' ), 'WordPress' ); ?></a>
			</div>
			
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
