=== Plugin Name ===
Contributors: peiche, alexkingorg, crowdfavorite
Tags: content, timeline, display, presentation, story, storyline, context
Requires at least: 3.5
Tested up to: 4.9.5
Stable tag: 1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Timelines displays a timeline of related posts.

== Description ==

If you have ongoing themes you write about on your site, you can use Timelines to show those posts in a timeline, with a link to the timeline from each of the posts. This helps you avoid feeling like you have to rehash too much history about the topic in each post.

Another good usage of Timelines is on a news site to track posts related to a single ongoing story. For example, a tech blog might create a timeline to group stories about a product launch event. Several months later, stories about sales figures for the product might be added to the thread. By placing all of these posts in a thread, there is a useful visual way of browsing all of the posts.

The timeline display is both responsive and retina (HiDPI) friendly. See an <a href="http://eichefam.net/timeline/namines-wish">example here</a>.

Developers, please contribute on <a href="https://github.com/peiche/timelines">GitHub</a>.

== Installation ==

1. Upload the `timelines` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Optional: add the Recent Timelines widget to your sidebar
1. Optional: use the shortcode to display a timeline timeline in a post or page

Shortcode syntax:

`[timeline term="timeline-slug"]`

== Frequently Asked Questions ==

= What themes is Timelines compatible with? =

Timelines has been tested with <a href="http://eichefam.net/projects/recover">ReCover</a> and Twenty Seventeen. It looks best when used with a wide theme. Timelines does not look great in themes with a Narrow content column, such as Twenty Fourteen.

= How can I list my posts from newest to oldest instead of vice versa? =

You can override the timeline view to create any presentation you like. You can also adjust how the timeline posts are retrieved using the `timelines_timeline_posts_query` filter.

= How can I show posts from a timeline in a different way? =

If you want to output different CSS, that's easy. Example:

	function my_custom_css($my_css) {
		$my_css = '<style>
			.threads-timeline {
				/* your CSS rules here */
			}
			/* ... */
		</style>';
		return $css;
	}
	add_filter('timelines_timeline_css', 'my_custom_css');

This utilizes the <a href="http://codex.wordpress.org/Plugin_API">WordPress Plugin API</a> - it's how to customize something without having to hack the code (and lose your changes on upgrade).

If you want to do a completely different presentation of your timeline, that's straightforward too. The timeline is connected by a custom taxonomy term - you can query on this term to get the posts and present them any way you like.

If you don't want to write your own code, you may find that a plugin like <a href="http://wordpress.org/extend/plugins/query-posts/">Query Posts</a> is a good solution for you.

= Why isn't my question listed here? =

Ask them in the support forums and we'll add them here as they are answered.

== Changelog ==

= 1.1 =
* Updated to use the <a href="https://select2.github.io/">Select2</a> plugin instead of Chosen.
* Improved the loading of scripts and styles on the New/Edit Post page. 

= 1.0 =
* Forked from Threads by Crowd Favorite.
* Rewrite to display timeline based on Codyhouse's <a href="https://codyhouse.co/gem/vertical-timeline/">Vertical Timeline</a>.
* Added <a href="http://fontawesome.io/">Font Awesome</a> icons for posts on a timeline.

== Upgrade Notice ==

= 1.0 =
Initial public release.

== Developers ==

The architecture of Timelines is a custom taxonomy coupled with a "dependent" custom post type where content (description, featured image, etc.) for the taxonomy term can be stored. The <a href="https://github.com/crowdfavorite/wp-tax-post-binding">CF Taxonomy Post Type Binding</a> plugin provides the functionality to keep the post type and taxonomy term in sync with each other. The timeline display is the display of the custom post type, while the taxonomy is not public.

Timelines separates presentation files into views, with appropriate <a href="http://codex.wordpress.org/Plugin_API">filters</a> on each. You can override the templates, CSS, etc. used to display a timeline by using these filters.

Developement for Timelines occurs in the <a href="https://github.com/peiche/timelines">public GitHub repository</a>.
