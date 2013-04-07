<?php
/*
Widget Name: Thumbnail
Description: Display a thumbnail of a comic, either newest or first or random
Author: Philip M. Hofer (Frumph)
Author URI: http://frumph.net/
Version: 1.3
*/
	
class ceo_thumbnail_widget extends WP_Widget {

	function ceo_thumbnail_widget() {
		$widget_ops = array('classname' => __CLASS__, 'description' => __('Display a thumbnail of a comic, by chapter, newest, first or random, clickable to go to the comic.', 'comiceasel') );
		$this->WP_Widget(__CLASS__, __('Comic Easel - Thumbnail', 'comiceasel'), $widget_ops);
	}
	
	function widget($args, $instance) {
		global $post, $wp_query;		
		extract($args, EXTR_SKIP);
		$current_permalink = get_permalink($post->ID);
		$current_post_id = '';
		$chaptinfo = ';';
		if ($instance['thumbchapt'] !== 'All') $chaptinfo = '&chapters='.$instance['thumbchapt'];
		if ($instance['first']) { $order = 'ASC'; } else { $order = 'DESC'; }
		$comic_query = 'showposts='.$instance['thumbcount'].'&order='.$order.'&post_type=comic'.$chaptinfo;
		if ($instance['random']) $comic_query .= '&orderby=rand';
		if (!empty($post) && $instance['random']) {
			$current_post_id = $post->ID;
			$comic_query .= '&exclude='.$current_post_id;
		}
		ceo_Protect();
		$thumbnail_query = new WP_Query($comic_query);
		$archive_image = null;
		if ($thumbnail_query->have_posts()) {
			while ($thumbnail_query->have_posts()) : $thumbnail_query->the_post();
				$the_permalink = get_permalink($post->ID);
				if ($the_permalink != $current_permalink) {
					echo $before_widget;
					$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']); 
					if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
					echo '<div class="comic-thumb comic-thumb-'.$post->ID.'">';
					if ($instance['centering']) echo "\r\n<center>\r\n";
					if ($instance['secondary'] && class_exists('MultiPostThumbnails')) {
						echo "<a href=\"".$the_permalink."\" rel=\"bookmark\" title=\"Permanent Link to ".get_the_title()."\">".MultiPostThumbnails::get_post_thumbnail_url(get_post_type(), 'secondary-image', $post->ID)."</a>\r\n";
					} else {
						if ( has_post_thumbnail($post->ID) ) {
							echo "<a href=\"".$the_permalink."\" rel=\"bookmark\" title=\"Permanent Link to ".get_the_title()."\">".get_the_post_thumbnail($post->ID, 'thumbnail')."</a>\r\n";
						} else {
							echo "No Thumbnail Found.";	
						}
					}
					if ($instance['linktitle']) { echo '<div class="comic-thumb-title"><a href="'.$the_permalink.'" rel="bookmark" title="Permanent Link to '.get_the_title().'">'.get_the_title().'</a></div><div class="clear"></div>'; }
					echo '</div>';
					if ($instance['centering']) echo "\r\n</center>\r\n";
					echo $after_widget;
				}
			endwhile;
		}
		ceo_unProtect();
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['thumbchapt'] = strip_tags($new_instance['thumbchapt']);
		$instance['thumbcount'] = (int)strip_tags($new_instance['thumbcount']);
		$instance['first'] =  (bool)( $new_instance['first'] == 1 ? true : false );
		$instance['random'] =  (bool)( $new_instance['random'] == 1 ? true : false );
		$instance['linktitle'] = (bool)($new_instance['linktitle'] == 1 ? true : false );
		$instance['centering'] = (bool)($new_instance['centering'] == 1 ? true : false );
		$instance['secondary'] = (bool)($new_instance['secondary'] == 1 ? true : false );
		return $instance;
	}
	
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'thumbchapt' => '', 'first' => false, 'random' => false, 'thumbcount' => 1, 'linktitle' => false, 'centering' => false, 'secondary' => false ) );
		$title = strip_tags($instance['title']);
		$thumbchapt = $instance['thumbchapt'];
		$first = $instance['first'];
		$random = $instance['random'];
		$thumbcount = $instance['thumbcount'];
		$linktitle = $instance['linktitle'];
		$centering = $instance['centering'];
		$secondary = $instance['secondary'];
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'comiceasel'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<p><?php _e('Which Chapter?', 'comiceasel'); ?><br />	
		<?php 
		
		$allterms = &get_terms('chapters');
		if (!empty($allterms)) {
			$chaptselected = '';
			if ($thumbchapt == '') $chaptselected = 'selected';
			$chapter_options = '<option name="" '.$chaptselected.'>All</option>';
			foreach ($allterms as $term) {
				$chaptselected = '';
				if ($thumbchapt == $term->slug) $chaptselected = 'selected';
				$chapter_options .= '<option name="'.$term->slug.'" value="'.$term->slug.'" '.$chaptselected.'> '.$term->name.' </option>';
			}
			?>
			<select name=<?php echo $this->get_field_name('thumbchapt'); ?>" id="<?php echo $this->get_field_id('thumbchapt'); ?>">
			<?php echo $chapter_options; ?>
			</select>
		<?php } ?>
		</p>
		<p><label for="<?php echo $this->get_field_id('first'); ?>"><?php _e('Get first in chapter instead?','comiceasel'); ?> <input id="<?php echo $this->get_field_id('first'); ?>" name="<?php echo $this->get_field_name('first'); ?>" type="checkbox" value="1" <?php checked(true, $first); ?> /></label></p>		
		<p><label for="<?php echo $this->get_field_id('random'); ?>"><?php _e('Display a random Thumbnail?','comiceasel'); ?> <input id="<?php echo $this->get_field_id('random'); ?>" name="<?php echo $this->get_field_name('random'); ?>" type="checkbox" value="1" <?php checked(true, $random); ?> /></label></p>
		<p><em><?php _e('*note: Random thumbnail overrides the get first in chapter option.','comiceasel'); ?></em></p>
		<p><?php _e('Display how many thumbnails?', 'comiceasel'); ?><input style="width:40px;" id="<?php echo $this->get_field_id('thumbcount'); ?>" name="<?php echo $this->get_field_name('thumbcount'); ?>" type="text" value="<?php echo stripcslashes($instance['thumbcount']); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id('linktitle'); ?>"><?php _e('Include comic title?','comiceasel'); ?> <input id="<?php echo $this->get_field_id('linktitle'); ?>" name="<?php echo $this->get_field_name('linktitle'); ?>" type="checkbox" value="1" <?php checked(true, $linktitle); ?> /></label></p>
		<p><label for="<?php echo $this->get_field_id('centering'); ?>"><?php _e('Add centering html?','comiceasel'); ?> <input id="<?php echo $this->get_field_id('centering'); ?>" name="<?php echo $this->get_field_name('centering'); ?>" type="checkbox" value="1" <?php checked(true, $centering); ?> /></label></p>
		<p><label for="<?php echo $this->get_field_id('secondary'); ?>"><?php _e('Use Secondary Image if plugin active?','comiceasel'); ?> <input id="<?php echo $this->get_field_id('secondary'); ?>" name="<?php echo $this->get_field_name('secondary'); ?>" type="checkbox" value="0" <?php checked(true, $secondary); ?> /></label></p>		
		<br />
	<?php
	}
}

add_action( 'widgets_init', create_function('', 'return register_widget("ceo_thumbnail_widget");') );


?>
