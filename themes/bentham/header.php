<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head>

	<meta http-equiv="content-type" content="<?php bloginfo( 'html_type' ); ?>;charset=<?php bloginfo( 'charset' ); ?>" />

	<title>
		<?php

			// Show the basic blog-name title
			wp_title( '|', true, 'right' );

			bloginfo( 'name' );

			// Add the blog description for the home/front page.
			$site_description = get_bloginfo( 'description', 'display' );
			if ( $site_description && ( is_home() || is_front_page() ) ) {
				echo " | $site_description";
			}

			// Display a possible page number
			global $page, $paged;
			if ( $paged >= 2 || $page >= 2 ) {
				echo ' | ' . sprintf( __( 'Page %d', 'bentham' ), max( $paged, $page ) );
			}
		?>
	</title>

	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="stylesheet" href="<?php bloginfo( 'stylesheet_url' ); ?>" type="text/css" media="screen" />

	<?php
		if ( is_singular() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
		wp_head();
	?>

</head>

<body <?php body_class(); ?>>

	<div id="header">

		<?php /* The optional user-specified image header */ ?>
		<div id="logo">
			<img src="<?php header_image(); ?>" width="<?php echo HEADER_IMAGE_WIDTH; ?>" height="<?php echo HEADER_IMAGE_HEIGHT; ?>" alt="<?php _e( 'Logo', 'bentham' ); ?>" />
		</div>

		<?php /* Blog branding section using the name and description */ ?>
		<div id="branding">
			<h2 id="blog-name"><a href="<?php echo home_url(); ?>"><?php bloginfo( 'name' ); ?></a></h2>
			<h3 id="blog-subtitle"><?php bloginfo( 'description' ); ?></h3>
		</div>

		<?php /* The list of all pages on the site */ ?>
		<?php wp_nav_menu( array( 'menu_class' => 'page-navigation', 'theme_location' => 'primary_nav' ) ); ?>

	</div>

	<div id="body">
