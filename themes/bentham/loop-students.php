
<?php

	// Request a list of posts grouped by users
	$posts_by_user = bentham_get_posts_by_user();
	$total_users = count( $posts_by_user ) - 1;

	foreach ( $posts_by_user as $current_user => $post_info ):
?>

		<div class="user-posts <?php if ( $current_user == $total_users ) { echo 'last'; } ?>" id="user-posts-<?php echo $post_info->user_id; ?>">

			<div class="user-info">

				<?php /* Show the user's name and gravatar, making the name a link to their blog if possible */ ?>
				<?php echo get_avatar( $post_info->user_id, 54 ); ?>
				<h3 class="user-name">
					<?php
						$name_parts = array(
							sprintf( '%s %s',
								get_user_meta( $post_info->user_id, 'first_name', true ),
								get_user_meta( $post_info->user_id, 'last_name', true ) ) );

						$user_url = bentham_get_blog_url_for_student( $post_info->user_id );
						if ( $user_url ) {
							array_unshift(
								$name_parts,
								sprintf( '<a href="%s" title="%s">',
									$user_url,
									__( 'View all posts by this user', 'bentham' ) ) );
							$name_parts[] = '</a>';
						}
						echo implode( "", $name_parts );
					?>
				</h3>

				<?php /* Show information on the total posts and comments made by the student */ ?>
				<ul class="user-meta">
					<li class="meta post-count">
						<?php
							printf( _n( '%d post', '%d posts', $post_info->total_posts, 'bentham' ), $post_info->total_posts );
						?>
					</li>
					<li class="meta comment-count">
						<?php
							$comment_count = bentham_get_total_comments_for_student( $post_info->user_id );
							printf( _n( '%d comment', '%d comments', $comment_count, 'bentham' ), $comment_count );
						?>
					</li>
				</ul>

			</div>

			<?php /* Show the list of posts made by the student */ ?>
			<ul class="posts">
				<?php
					// Apply utility 'first' and 'last' classes to the proper posts
					$total_posts = count( $post_info->posts ) - 1;
					foreach ( $post_info->posts as $post_count => $post ) {
						$post_classes = array();
						if ( ! $post_count ) {
							$post_classes[] = 'first';
						}
						if ( $post_count == $total_posts ) {
							$post_classes[] = 'last';
						}
						switch_to_blog( $post->from_blog );
				?>
					<li class="post <?php echo implode( ' ', $post_classes ); ?>">
						<h3 class="title">
							<a href="<?php echo $post->cb_sw_permalink; ?>"><?php the_title(); ?></a>
						</h3>
						<h4 class="meta"><?php the_time( 'F jS, Y' ); ?></h4>
						<div class="entry">
							<?php echo $post->cb_sw_excerpt; ?>
						</div>
					</li>
				<?php
						restore_current_blog();
					}
				?>
			</ul>

			<?php /* Used to make the fadeout at the end of the list work */ ?>
			<div class="end-posts"></div>

		</div>

<?php
	endforeach;
?>
