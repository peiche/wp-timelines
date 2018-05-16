<?php
/*
Plugin Name: Timelines
Plugin URI: https://github.com/peiche/timelines/
Description: Provide context for an ongoing story by showing a timeline of related posts (with a link to that timeline from each post). Forked from Threads by Crowd Favorite.
Version: 1.2
Author: Paul Eiche
Author URI: http://eichefam.net
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

define('CFTH_PATH', trailingslashit(plugin_dir_path(__FILE__)));

// utility library for binding custom taxonomies and post types together
require(CFTH_PATH.'lib/cf-tax-post-binding/cf-tax-post-binding.php');

// set up custom post types and taxonomies
require(CFTH_PATH.'architecture.php');

// sidebar widget
require(CFTH_PATH.'recent-timelines-widget.php');

// check for /timeline support in permalink patterns
function cfth_permalink_check() {
	$rewrite_rules = get_option('rewrite_rules');
	if ($rewrite_rules == '') {
		return;
	}
	global $wp_rewrite;
	$pattern = $wp_rewrite->front.'timeline/';
	if (substr($pattern, 0, 1) == '/') {
		$pattern = substr($pattern, 1);
	}
	// check for 'timeline' in rewrite rules
	foreach ($rewrite_rules as $rule => $params) {
		if (substr($rule, 0, strlen($pattern)) == $pattern) {
			return;
		}
	}
	// flush rules if not found above
	flush_rewrite_rules();
}
add_action('admin_init', 'cfth_permalink_check');

// show views as appropriate
function cfth_template_redirect() {
	if (is_singular('timeline')) {
		add_filter('the_content', 'cfth_timeline_single', 999999);
		add_action('wp_head', 'cfth_timeline_css');
		return;
	}
// TODO
// 	if (is_post_type_archive('timeline')) {
// 		add_filter('the_content', 'cfth_timeline_archive', 999999);
// 		return;
// 	}
}
add_action('template_redirect', 'cfth_template_redirect');

function cfth_timeline_single($content) {
	global $post;
	if ($post->post_type == 'timeline') {
		$term_id = cftpb_get_term_id('timelines', $post->ID);
		$view = apply_filters('timelines_single_view', CFTH_PATH.'views/content/type-timeline.php');
		ob_start();
		include($view);
		$content = ob_get_clean();
	}
	return $content;
}

// TODO
function cfth_timeline_archive($content) {
	$view = apply_filters('timelines_archive_view', CFTH_PATH.'views/loop/type-timeline.php');
	ob_start();
	include($view);
	return ob_get_clean();
}

function cfth_timeline_timeline($term_id) {
	$posts = cfth_timeline_content($term_id);
	$view = apply_filters('timelines_timeline_view', CFTH_PATH.'views/content/timeline.php');
	ob_start();
	include($view);
	return ob_get_clean();
}

function cfth_timeline_shortcode($atts) {
	extract(shortcode_atts(array(
		'term' => null,
	), $atts));
	$_term = get_term_by('slug', $term, 'timelines');
	if (empty($_term) || is_wp_error($_term)) {
		return '<p>'.sprintf(__('Sorry, could not find a timeline: <i>%s</i>', 'timelines'), esc_html($term)).'</p>';
	}
	ob_start();
	cfth_timeline_css();
	$css = ob_get_clean();
	return $css.cfth_timeline_timeline($_term->term_id);
}
add_shortcode('timeline', 'cfth_timeline_shortcode');

function cfth_timeline_links($timelines) {
	$links = array();
	foreach ($timelines as $timeline) {
		$post = cftpb_get_post($timeline->term_id, $timeline->taxonomy);
		$links[] = '<a href="'.get_permalink($post->ID).'">'.$timeline->name.'</a>';
	}
	return $links;
}

function cfth_timeline_notice($posts, $query) {
	foreach ($posts as $post) {
// check for one or more timelines
		$timelines = wp_get_post_terms($post->ID, 'timelines');
		if (count($timelines) > 0) {
			$timeline_links = cfth_timeline_links($timelines);
			$timeline_links = implode(', ', $timeline_links);
			if (count($timelines) == 1) {
				$notice_single = sprintf(__('This post is part of the timeline: %s - an ongoing story on this site. View the timeline for more context on this post.', 'timelines'), $timeline_links);
				$notice = apply_filters('cfth_timeline_notice_single', $notice_single);
			}
			else {
				$notice_mult = sprintf(__('This post is part of the following timelines: %s - ongoing stories on this site. View the timelines for more context on this post.', 'timelines'), $timeline_links);
				$notice = apply_filters('cfth_timeline_notice_mult', $notice_mult);
			}
			$post->post_content .= "\n\n".'<p class="timelines-post-notice">'.$notice.'</p>';
			$post = apply_filters('cfth_timeline_notice', $post, $timelines);
		}
	}
	return $posts;
}
add_filter('the_posts', 'cfth_timeline_notice', 10, 2);

function cfth_timeline_posts($term_id) {
	$term = get_term_by('id', $term_id, 'timelines');
	if ($term) {
		$query_params = apply_filters('timelines_timeline_posts_query', array(
			'posts_per_page' => -1,
			'taxonomy' => 'timelines',
			'term' => $term->slug,
			'order' => 'ASC',
		));
		$query = new WP_Query($query_params);
		return $query->posts;
	}
	return array();
}

function cfth_timeline_content($term_id) {
	$posts = cfth_timeline_posts($term_id);
	if (!count($posts)) {
		return array();
	}

	if (count($posts) > 1) {
		$first = $posts[0]->post_date_gmt;
		$last = $posts[count($posts) - 1]->post_date_gmt;
	}

// max reasonable height is 200-250px, need to take max duration, set to 200-250px, check against min height,
// set all others accordingly

	$prev = null;
	foreach ($posts as $_post) {
		$_post->timelines_data = array(
			'time_offset' => 0,
		);
		if ($prev) {
			$prev_timestamp = strtotime($prev->post_date_gmt);
			$this_timestamp = strtotime($_post->post_date_gmt);
			$prev->timelines_data['time_offset'] = $this_timestamp - $prev_timestamp;
		}
		$prev = $_post;
	}

	foreach ($posts as $_post) {
		$_post->timelines_data['lat'] = false;
		$_post->timelines_data['lat_text'] = '';
		$time_offset = $_post->timelines_data['time_offset'];
		$margin = ceil($time_offset / 15000);
		if ($time_offset > (DAY_IN_SECONDS * 90)) {
			$_post->timelines_data['lat'] = true;
			// calc semi-meaningful duration here
			$margin = 0;
			$_offset = $time_offset;
			$y = $m = $d = 0;
			if ($_offset > (DAY_IN_SECONDS * 365)) {
				$y = floor($_offset / (DAY_IN_SECONDS * 365));
				$_offset -= ($y * DAY_IN_SECONDS * 365);
			}
			if ($_offset > (DAY_IN_SECONDS * 60)) {
				$m = floor($_offset / (DAY_IN_SECONDS * 30));
				$_offset -= ($m * DAY_IN_SECONDS * 30);
			}
			if ($_offset > DAY_IN_SECONDS) {
				$d = floor($_offset / DAY_IN_SECONDS);
				$_offset -= ($d * DAY_IN_SECONDS);
			}

			if ($y > 1 && $m > 0) {
				$lat = sprintf(__('%s Years, %s Months', 'timelines'), $y, $m);
			}
			else if ($y > 1) {
				$lat = sprintf(__('%s Years', 'timelines'), $y);
			}
			else if ($y == 1 && $m > 0) {
				$lat = sprintf(__('1 Year, %s Months', 'timelines'), $m);
			}
			else if ($y == 1 || ($y == 0 && $m == 12)) {
				$lat = sprintf(__('1 Year', 'timelines'));
			}
			else if ($m >= 6) {
				$lat = sprintf(__('%s Months', 'timelines'), $m, $d);
			}
			else if ($m > 0 && $d > 0) {
				$lat = sprintf(__('%s Months, %s Days', 'timelines'), $m, $d);
			}
			else if ($m > 0) {
				$lat = sprintf(__('%s Months', 'timelines'), $m);
			}
			else {
				$lat = sprintf(__('%s Days', 'timelines'), $d);
			}
			$_post->timelines_data['lat_text'] = $lat;
		}
		else if ($margin > 200) {
			$margin = 200;
		}
		$_post->timelines_data['margin'] = $margin;

		$_post->timelines_data['intersects'] = array();
		$timelines = wp_get_post_terms($_post->ID, 'timelines');
		foreach ($timelines as $timeline) {
			if ($timeline->term_id != $term_id) {
				$_post->timelines_data['intersects'][] = $timeline;
			}
		}
	}

	return $posts;
}

function cfth_update_timeline_date($post_id, $post) {
	if ($post->post_type == 'timeline') {
// don't infinite loop
		remove_action('save_post', 'cfth_update_timeline_date', 10, 2);
// get term
		$term_id = cftpb_get_term_id('timelines', $post_id);
		$term = get_term($term_id, 'timelines');
// get most recent post
		$query = new WP_Query(array(
			'posts_per_page' => 1,
			'taxonomy' => 'timelines',
			'term' => $term->slug,
			'post_status' => 'publish',
			'order' => 'DESC'
		));
		if (count($query->posts == 1)) {
			$timeline_post = $query->posts[0];
// get term post, update with date
			wp_update_post(array(
				'ID' => $post_id,
				'post_date' => $timeline_post->post_date,
				'post_date_gmt' => $timeline_post->post_date_gmt,
			));
		}
	}
	else if ($post->post_status == 'publish') {
// get timelines
		$timelines = wp_get_post_terms($post->ID, 'timelines');
// update each timeline date with current date
		foreach ($timelines as $timeline) {
			if (is_object($timeline)) {
				$_post = cftpb_get_post($timeline->term_id, 'timelines');
				if ($_post) {
					$now = current_time('mysql');
					if ($now > $_post->post_date) {
						$data = array(
							'ID' => $_post->ID,
							'post_date' => $now,
							'post_date_gmt' => current_time('mysql', 1),
						);
						wp_update_post($data);
					}
				}
			}
		}
	}
}
add_action('save_post', 'cfth_update_timeline_date', 10, 2);

function cfth_asset_url($path) {
	$url = plugins_url($path, __FILE__);
	return apply_filters('cfth_asset_url', $url, $path, __FILE__);
}

function cfth_timeline_css() {
	$css = apply_filters('timelines_timeline_css', '');
	if (!empty($css)) {
		echo $css;
		return;
	}

	wp_register_style( 'timelines-style', cfth_asset_url( '/css/style.css' ) );
	wp_enqueue_style( 'timelines-style' );

}
