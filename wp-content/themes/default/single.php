<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */

get_header();
?>

	<div id="content" class="widecolumn" role="main">

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div class="navigation">
			<div class="alignleft"><?php previous_post_link(__('&laquo; %link')) ?></div>
			<div class="alignright"><?php next_post_link(__('%link &raquo;')) ?></div>
		</div>

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
			<h2><?php the_title(); ?></h2>

			<div class="entry">
				<?php the_content('<p class="serif">'.__('Read the rest of this entry &raquo;').'</p>'); ?>

				<?php wp_link_pages(array('before' => '<p><strong>'.__('Pages:').'</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
				<?php the_tags( '<p>Tags: ', ', ', '</p>'); ?>

				<p class="postmetadata alt">
					<small>
						<?php 
						    /* This is commented, because it requires a little adjusting sometimes.
							You'll need to download this plugin, and follow the instructions:
							http://binarybonsai.com/wordpress/time-since/ */
						    /* $entry_datetime = abs(strtotime($post->post_date) - (60*120)); echo time_since($entry_datetime); echo ' ago'; */
						printf(__('This entry was posted on %1$s at %2$s and is filed under %3$s.'),
						       apply_filters('the_time', get_the_time( 'l, F jS, Y' ), 'l, F jS, Y'),
						       apply_filters('the_time', get_the_time( '' ), ''),
						       get_the_category_list(', ')) ?>
						<?php printf(__('You can follow any responses to this entry through the %s feed.'), 
						             apply_filters( 'post_comments_feed_link_html', "<a href='". get_post_comments_feed_link('RSS 2.0')."'>RSS 2.0</a>")); ?>

						<?php if ( comments_open() && pings_open() ) {
							// Both Comments and Pings are open ?>
							<?php printf(__('You can <a href="#respond">leave a response</a>, or <a href="%s" rel="trackback">trackback</a> from your own site.'), trackback_url(false)); ?>

						<?php } elseif ( !comments_open() && pings_open() ) {
							// Only Pings are Open ?>
							<?php printf(__('Responses are currently closed, but you can <a href="%s" rel="trackback">trackback</a> from your own site.'), trackback_url(false)); ?>

						<?php } elseif ( comments_open() && !pings_open() ) {
							// Comments are open, Pings are not ?>
							<?php _e('You can skip to the end and leave a response. Pinging is currently not allowed.'); ?>

						<?php } elseif ( !comments_open() && !pings_open() ) {
							// Neither Comments, nor Pings are open ?>
							<?php _e('Both comments and pings are currently closed.'); ?>

						<?php } edit_post_link(__('Edit this entry'),'','.'); ?>

					</small>
				</p>

			</div>
		</div>

	<?php comments_template(); ?>

	<?php endwhile; else: ?>

		<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>

<?php endif; ?>

	</div>

<?php get_footer(); ?>
