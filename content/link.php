<article <?php cherry_attr( 'post' ); ?>>
	<?php
		/**
		 * Default page structure hooks
		 */

		do_action( 'cherry_entry_thumbnail' );

		do_action( 'cherry_entry_header' );

		do_action( 'cherry_entry_meta' );

		if ( is_singular() ) {

			do_action( 'cherry_entry_excerpt' );

		} else {

			do_action( 'cherry_entry_content' );

		}

		do_action( 'cherry_entry_footer' );
	?>
</article>