<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header();
?>

	<div id="content" class="narrowcolumn" role="main">

		<?php if (have_posts()) : ?>

 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="pagetitle"><?php printf(__("Archive for the &#8216;%s&#8217; Category"), single_cat_title('',false)); ?></h2>
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="pagetitle"><?php printf(__("Posts Tagged &#8216;%s&#8217;"), single_tag_title('',false)); ?></h2>
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="pagetitle"><?php printf(__("Archive for %s"), get_the_time(__('F jS, Y'))); ?></h2>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="pagetitle"><?php printf(__("Archive for %s"), get_the_time(__('F, Y'))); ?></h2>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="pagetitle"><?php printf(__("Archive for %s"), get_the_time(__('Y'))); ?></h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2 class="pagetitle"><?php _e("Author Archive"); ?></h2>
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="pagetitle"><?php _e("Blog Archives"); ?></h2>
 	  <?php } ?>


		<div class="navigation">
			<div class="alignleft"><?php next_posts_link(__('&laquo; Older Entries')) ?></div>
			<div class="alignright"><?php previous_posts_link(__('Newer Entries &raquo;')) ?></div>
		</div>

		<?php while (have_posts()) : the_post(); ?>
		<div <?php post_class() ?>>
				<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php printf( __("Permanent Link to %s"), the_title_attribute(array("echo"=>false))); ?>"><?php the_title(); ?></a></h3>
				<small><?php the_time(__('l, F jS, Y')) ?></small>

				<div class="entry">
					<?php the_content() ?>
				</div>

				<p class="postmetadata"><?php the_tags(null, ', ', '<br />'); _e(" Posted in "); the_category(', ') ?> | <?php edit_post_link(__('Edit'), '', ' | '); ?>  <?php comments_popup_link(__('No Comments &#187;'), __('1 Comment &#187;'), __('% Comments &#187;')); ?></p>

			</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link(__('&laquo; Older Entries')) ?></div>
			<div class="alignright"><?php previous_posts_link(__('Newer Entries &raquo;')) ?></div>
		</div>
	<?php else :

		if ( is_category() ) { // If this is a category archive
			printf("<h2 class='center'>".__("Sorry, but there aren't any posts in the %s category yet.")."</h2>", single_cat_title('',false));
		} else if ( is_date() ) { // If this is a date archive
			echo("<h2>" . __("Sorry, but there aren't any posts with this date.") . "</h2>");
		} else if ( is_author() ) { // If this is a category archive
			$userdata = get_userdatabylogin(get_query_var('author_name'));
			printf("<h2 class='center'>". __("Sorry, but there aren't any posts by %s yet.")."</h2>", $userdata->display_name);
		} else {
			echo("<h2 class='center'>".__("No posts found.")."</h2>");
		}
		get_search_form();

	endif;
?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
