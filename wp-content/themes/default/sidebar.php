<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */
?>
	<div id="sidebar" role="complementary">
		<ul>
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>
			<li>
				<?php get_search_form(); ?>
			</li>

			<!-- Author information is disabled per default. Uncomment and fill in your details if you want to use it.
			<li><h2><?php _e('Author'); ?></h2>
			<p><?php _e('A little something about you, the author. Nothing lengthy, just an overview.'); ?></p>
			</li>
			-->

			<?php if ( is_404() || is_category() || is_day() || is_month() ||
						is_year() || is_search() || is_paged() ) {
			?> <li>

			<?php /* If this is a 404 page */ if (is_404()) { ?>
			<?php /* If this is a category archive */ } elseif (is_category()) { ?>
			<p><?php printf(__('You are currently browsing the archives for the %s category.'), single_cat_title('')); ?></p>

			<?php /* If this is a daily archive */ } elseif (is_day()) { ?>
			<p><?php printf (__('You are currently browsing the %1$s blog archives for the day %2$s.'), 
			                 '<a href="'.bloginfo('url').'/">'.bloginfo('name').'</a>', 
			                 the_time('l, F jS, Y')); ?></p>

			<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
			<p><?php printf (__('You are currently browsing the %1$s blog archives for %2$s.'),
			                 '<a href="'.bloginfo('url').'/">'.bloginfo('name').'</a>',
			                 the_time('F, Y')); ?></p>

			<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
			<p><?php printf (__('You are currently browsing the %1$s blog archives for the year %2$s.'),
			                 '<a href="'.bloginfo('url').'/">'.bloginfo('name').'</a>',
			                 the_time('Y')); ?></p>

			<?php /* If this is a search result */ } elseif (is_search()) { ?>
			<p><?php printf (__('You have searched the %1$s blog archives for <strong>\'%2$s\'</strong>. If you are unable to find anything in these search results, you can try one of these links.'),
			                 '<a href="'.bloginfo('url').'/">'.bloginfo('name').'</a>',
			                 the_search_query()); ?></p>

			<?php /* If this set is paginated */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
			<p><?php printf (__('You are currently browsing the %s blog archives.'),
			                 '<a href="'.bloginfo('url').'/">'.bloginfo('name').'</a>'); ?></p>

			<?php } ?>

			</li>
		<?php }?>
		</ul>
		<ul role="navigation">
			<?php wp_list_pages('title_li=<h2>'.__('Pages').'</h2>' ); ?>

			<li><h2><?php _e('Archives'); ?></h2>
				<ul>
				<?php wp_get_archives('type=monthly'); ?>
				</ul>
			</li>

			<?php wp_list_categories('show_count=1&title_li=<h2>'.__('Categories').'</h2>'); ?>
		</ul>
		<ul>
			<?php /* If this is the frontpage */ if ( is_home() || is_page() ) { ?>
				<?php wp_list_bookmarks(); ?>

				<li><h2><?php _e('Meta'); ?></h2>
				<ul>
					<?php wp_register(); ?>
					<li><?php wp_loginout(); ?></li>
					<li><a href="http://validator.w3.org/check/referer" title="<?php _e('This page validates as XHTML 1.0 Transitional'); ?>"><?php _e('Valid'); ?> <abbr title="eXtensible HyperText Markup Language">XHTML</abbr></a></li>
					<li><a href="http://gmpg.org/xfn/"><abbr title="<?php _e('XHTML Friends Network'); ?>">XFN</abbr></a></li>
					<li><a href="http://wordpress.org/" title="<?php _e('Powered by WordPress, state-of-the-art semantic personal publishing platform.'); ?>">WordPress</a></li>
					<?php wp_meta(); ?>
				</ul>
				</li>
			<?php } ?>

			<?php endif; ?>
		</ul>
	</div>

