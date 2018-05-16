<?php

if (!count($posts)) {
	return;
}

?>

<?php echo term_description( $term_id ); ?>

<div class="timelines-container">

	<div class="timelines-timeline">
	<?php

	$interescts = array();

	foreach ($posts as $_post) {
	?>
		<?php
		$post_format = 'standard';
		if ( get_post_format() != '' ) {
			$post_format = get_post_format();
		}
		?>
		<div class="timelines-block timelines-format-<?php echo $post_format; ?>">

			<div class="timelines-icon">
				<?php echo urldecode( get_post_meta( $_post->ID, 'timelines_icon', true ) ); ?>
			</div>

			<div class="timelines-content">
				<a class="timelines-link timelines-link-cover" href="<?php echo get_permalink( $_post->ID ); ?>"></a>

				<h2 class="timelines-title"><?php echo get_the_title( $_post->ID ); ?></h2>

				<?php
					if ( $_post->post_excerpt ) {
						echo '<p>' . $_post->post_excerpt . '</p>';
					} else if ( strpos( $_post->post_content, '<!--more-->' ) ) {
						echo '<p>' . substr($_post->post_content, 0, strpos( $_post->post_content, '<!--more-->' ) ) . '</p>';
					} else if ( 300 === strlen( substr( $_post->post_content, 0, 300 ) ) ){
						echo '<p>' .  substr($_post->post_content, 0, 300) . '...</p>';
					} else {
						echo '<p>' .  substr($_post->post_content, 0, 300) . '</p>';
					}
				?>

				<span class="timelines-date"><?php echo date( 'M j, Y', strtotime( $_post->post_date ) ); ?></span>
			</div>

	<?php
		if (!empty($_post->timelines_data['intersects'])) {
	?>
			<div class="timelines-intersects">
	<?php
			$links = cfth_thread_links($_post->timelines_data['intersects']);
			$links = implode(', ', $links);
			if (count($_post->timelines_data['intersects']) == 1) {
				printf(__('Also in thread: %s', 'timelines'), $links);
			}
			else {
				printf(__('Also in timelines: %s', 'timelines'), $links);
			}
	?>
			</div>
	<?php
		}
	?>
		</div>
	<?php
	}

	?>
	</div>
</div>
