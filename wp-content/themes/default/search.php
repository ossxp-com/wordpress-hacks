<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header(); ?>

	<div id="content" class="narrowcolumn" role="main">

	<?php if (have_posts()) : ?>

		<h2 class="pagetitle">Search Results</h2>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link(__('&laquo; Older Entries')) ?></div>
			<div class="alignright"><?php previous_posts_link(__('Newer Entries &raquo;')) ?></div>
		</div>


		<?php while (have_posts()) : the_post(); ?>

			<div <?php post_class() ?>>
				<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent Link to'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
				<small><?php the_time(__('l, F jS, Y')) ?></small>

				<p class="postmetadata"><?php the_tags(__('Tags: '), ', ', '<br />'); ?> <?php _e('Posted in'); ?> <?php the_category(', ') ?> | <?php edit_post_link(__('Edit'), '', ' | '); ?>  <?php comments_popup_link(__('No Comments &#187;'), __('1 Comment &#187;'), __('% Comments &#187;')); ?></p>
			</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link(__('&laquo; Older Entries')) ?></div>
			<div class="alignright"><?php previous_posts_link(__('Newer Entries &raquo;')) ?></div>
		</div>

	<?php else : ?>

		<h2 class="center"><?php _e('No posts found. Try a different search?'); ?></h2>
		<?php get_search_form(); ?>

	<?php endif; ?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
