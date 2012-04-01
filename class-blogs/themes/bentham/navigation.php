
<div class="navigation">
	<span class="previous">
		<?php
			if ( is_single() ) {
				previous_post_link( '%link' );
			} else {
				next_posts_link( __( 'Previous Entries', 'bentham' ) );
			}
		?>
	</span>
	<span class="next">
		<?php
			if ( is_single() ) {
				next_post_link( '%link' );
			} else {
				previous_posts_link( __( 'Next Entries', 'bentham' ) );
			}
		?>
	</span>
</div>
