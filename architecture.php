<?php

// potential future enhancement - abstract to user setting?
function cfthr_enabled_post_types() {
// 	$enabled = array();
// 	$types = get_post_types();
// 	foreach ($types as $type) {
// d($type);
// 	}
// 	return $enabled;
	return array('post');
}

function cfth_register_taxonomy() {
	$types = cfthr_enabled_post_types();
	register_taxonomy(
		'timelines',
		$types,
		array(
			'hierarchical' => true,
			'labels' => array(
				'name' => __('Timelines', 'timelines'),
				'singular_name' => __('Timeline', 'timelines'),
				'search_items' => __('Search Timelines', 'timelines'),
				'popular_items' => __('Popular Timelines', 'timelines'),
				'all_items' => __('All Timelines', 'timelines'),
				'parent_item' => __('Parent Timeline', 'timelines'),
				'parent_item_colon' => __('Parent Timeline:', 'timelines'),
				'edit_item' => __('Edit Timeline', 'timelines'),
				'update_item' => __('Update Timeline', 'timelines'),
				'add_new_item' => __('Add New Timeline', 'timelines'),
				'new_item_name' => __('New Timeline Name', 'timelines'),
			),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
		)
	);
}
add_action('init', 'cfth_register_taxonomy', 9999);

// Create Timeline post type (bound to Timelines taxonomy) to save meta
function cfth_tax_bindings($configs) {
	$configs[] = array(
		'taxonomy' => 'timelines',
		'post_type' => array(
			'timeline',
			array(
				'public' => true,
				'show_ui' => true,
				'label' => __('Timelines', 'cfth'),
				'rewrite' => array(
					'slug' => 'timeline',
					'with_front' => true,
					'feeds' => false,
					'pages' => false
				),
				'supports' => array(
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'revisions'
				)
			)
		),
		'slave_title_editable' => false,
		'slave_slug_editable' => false,
	);
	return $configs;
}
add_filter('cftpb_configs', 'cfth_tax_bindings');

// hide to avoid FOUC
function cfth_hide_tax_nav_css() {
?>
<style>
#newtimelines_parent, #menu-posts-timeline {
	display: none;
}
</style>
<?php
}
add_action('admin_head', 'cfth_hide_tax_nav_css');

function cfth_hide_tax_nav_js() {
?>
<script>
jQuery(function($) {
	$('#newtimelines_parent, #menu-posts-timeline').remove();
	$('body.edit-tags-php #addtag, body.edit-tags-php #edittag').each(function() {
		var tax = $(this).find('input[name="taxonomy"]').val();
		if (tax == 'timelines') {
			$('#parent').closest('.form-field').remove();
		}
	});
});
</script>
<?php
}
add_action('admin_footer', 'cfth_hide_tax_nav_js');

// hide View link for timelines taxonomy terms
function cfth_tag_row_actions($actions, $tag) {
	global $taxonomy, $tax;
	$post = cf_taxonomy_post_type_binding::get_term_post($tag->term_id, 'timelines');
	if (empty($post) || is_wp_error($post)) {
		return $actions;
	}
	unset($actions['view']);
	return $actions;
}
add_filter('tag_row_actions', 'cfth_tag_row_actions', 10, 2);

/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'timelines_icon_post_meta_box_setup' );
add_action( 'load-post-new.php', 'timelines_icon_post_meta_box_setup' );

/* Create one or more meta boxes to be displayed on the post editor screen. */
function timelines_icon_post_meta_box() {

  add_meta_box(
    'timelines-icon-meta-box',      							// Unique ID
    esc_html__( 'Timelines Icon', 'timelines' ),	// Title
    'timelines_icon_meta_box',   									// Callback function
    'post',         															// Admin page (or post type)
    'side',         															// Context
    'default'         														// Priority
  );
}

/* Display the post meta box. */
function timelines_icon_meta_box( $object, $box ) {

	wp_enqueue_style( 'timelines-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
	wp_enqueue_style( 'timelines-style', plugins_url( '/css/admin.css', __FILE__ ) );

	wp_enqueue_script( 'timelines-yaml', 'https://cdnjs.cloudflare.com/ajax/libs/js-yaml/3.6.0/js-yaml.min.js', true );
	wp_enqueue_script( 'timelines-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', true );

  wp_nonce_field( basename( __FILE__ ), 'timelines_icon_nonce' );
	?>

  <div>
		<style>

		</style>
		<div id="view-icon"></div>

		<select id="timelines-icon" name="timelines-icon">
			<option value="">Select an icon</option>
		</select>
		
		<script>
			jQuery(document).ready(function(){
		  	
		  	//console.log('<?php echo plugins_url( '/js/icons.json', __FILE__ ); ?>');
		  	
		  	jQuery.get('<?php echo plugins_url( '/js/icons.json', __FILE__ ); ?>', function(data) {
		  		//console.log(data);
		  		
		  		jQuery.each(data.icons, function(index, icon) {
		  			//console.log(JSON.stringify(icon.content));
		  			jQuery('#timelines-icon').append('<option data-name="' + icon.name + '" value="' + encodeURIComponent(icon.content) + '">' + icon.name + '</option>');
		  		});
		  		
		  		// Set default icon
		  		jQuery("#timelines-icon option[data-name='pencil']").attr('selected', 'selected');
		  		
		  		// TODO If the icon is already saved, select it here
		  		<?php if ( esc_attr( get_post_meta( $object->ID, 'timelines_icon', true ) ) != '' ) : ?>
					jQuery("#timelines-icon option[value='<?php echo esc_attr( get_post_meta( $object->ID, 'timelines_icon', true ) ); ?>']").attr('selected', 'selected');
					<?php endif; ?>
		  		
		  		// Initialize Select2 plugin
		  		jQuery('#timelines-icon').select2();
		  		
		  		jQuery("#view-icon").html(decodeURIComponent(jQuery('#timelines-icon').val()));
		  	});

				// Display changed icon
		  	jQuery("#timelines-icon").change(function(){
		  		var icono = jQuery(this).val();
		  		
		  		if (!!icono) {
		  			jQuery("#view-icon").html(decodeURIComponent(icono));
		  		} else {
		  			jQuery("#view-icon").empty();
		  		}
		  		
		  	});

		  });
		</script>
  </div>
<?php }

/* Save post meta on the 'save_post' hook. */
add_action( 'save_post', 'timelines_icon_save_post_meta', 10, 2 );

/* Meta box setup function. */
function timelines_icon_post_meta_box_setup() {

  /* Add meta boxes on the 'add_meta_boxes' hook. */
  add_action( 'add_meta_boxes', 'timelines_icon_post_meta_box' );

  /* Save post meta on the 'save_post' hook. */
  add_action( 'save_post', 'timelines_icon_save_post_meta', 10, 2 );
}

/* Save the meta box's post metadata. */
function timelines_icon_save_post_meta( $post_id, $post ) {

  /* Verify the nonce before proceeding. */
  if ( !isset( $_POST['timelines_icon_nonce'] ) || !wp_verify_nonce( $_POST['timelines_icon_nonce'], basename( __FILE__ ) ) ) :
    return $post_id;
  endif;

  /* Get the post type object. */
  $post_type = get_post_type_object( $post->post_type );

  /* Check if the current user has permission to edit the post. */
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) :
    return $post_id;
  endif;

  /* Get the posted data and sanitize it for use as an HTML class. */
  $new_meta_value = ( isset( $_POST['timelines-icon'] ) ? $_POST['timelines-icon'] : '' );

  /* Get the meta key. */
  $meta_key = 'timelines_icon';

  /* Get the meta value of the custom field key. */
  $meta_value = get_post_meta( $post_id, $meta_key, true );

  /* If a new meta value was added and there was no previous value, add it. */
  if ( $new_meta_value && '' == $meta_value ) :
    add_post_meta( $post_id, $meta_key, $new_meta_value, true );

  /* If the new meta value does not match the old value, update it. */
  elseif ( $new_meta_value && $new_meta_value != $meta_value ) :
    update_post_meta( $post_id, $meta_key, $new_meta_value );

  /* If there is no new meta value but an old value exists, delete it. */
  elseif ( '' == $new_meta_value && $meta_value ) :
    delete_post_meta( $post_id, $meta_key, $meta_value );
  
  endif;
}
