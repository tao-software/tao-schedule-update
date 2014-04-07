<?php

define('TAO_PUBLISH_LABEL', 'Geplante Änderung');
define('TAO_PUBLISH_METABOX', 'Geplante Änderung');
define('TAO_PUBLISH_STATUS', 'tao_publish');

add_action('wp_ajax_load_pubdate', function(){

	$stamp = get_post_meta($_REQUEST['postid'], TAO_PUBLISH_STATUS . '_pubdate', true);
	if($stamp)
	{
		$str = '<div style="margin-left:20px">';
		$str .= TaoPublish::getPubdate($stamp);
		$str .= '</div>';
		die($str);
	}

});

//Register post_status
add_action('init', function(){
		register_post_status(TAO_PUBLISH_STATUS, array(
												'label' => _x(TAO_PUBLISH_LABEL, 'Status General Name', 'default'),
												'public' => true,
												'exclude_from_search' => false,
												'show_in_admin_all_list' => true,
												'show_in_admin_status_list' => true,
												'label_count' => _n_noop(TAO_PUBLISH_LABEL . ' <span class="count">(%s)</span>', TAO_PUBLISH_LABEL . ' <span class="count">(%s)</span>', TAO_TD),
												));
	});

add_filter('display_post_states', function($states){
		global $post;
		$arg = get_query_var('post_status');
		if($arg != TAO_PUBLISH_LABEL && $post->post_status == TAO_PUBLISH_STATUS)
		{
			return array(TAO_PUBLISH_LABEL);
		}
		return $states;
	});

//add link to page edit view
add_filter('page_row_actions', function($actions, $post) {
		if($post->post_status == TAO_PUBLISH_STATUS)
		{
			$action = '?action=workflow_publish_now&post=' . $post->ID;
			$actions['publish_now'] = '<a href="' . admin_url('admin.php' . $action) .'">Jetzt Veröffentlichen</a>';
			return $actions;
		}
		else
		{
			$action = '?action=workflow_copy_to_publish&post=' . $post->ID;
			$actions['copy_to_publish'] = '<a href="' . admin_url('admin.php' . $action) . '">' . TAO_PUBLISH_LABEL . '</a>';
		}

		return $actions;
	}, 10, 2);

add_filter('manage_pages_columns', function($columns){
	$new = array();
	unset($columns['comments']);

	foreach($columns as $key => $val)
	{
		if($key == 'author')
		{
			$new['tao_publish'] = __('Veröffentlichungsdatum');
		}
		$new[$key] = $val;
	}
	return $new;
});

add_filter('manage_pages_custom_column', function($column, $post_id){
	if($column == 'tao_publish')
	{
		$stamp = get_post_meta($post_id, TAO_PUBLISH_STATUS . '_pubdate', true);

		if($stamp)
			echo TaoPublish::getPubdate($stamp);
	}
}, 10, 2);


//do stuff if said link is clicked
add_action('admin_action_workflow_copy_to_publish', function(){

		$post = get_post($_REQUEST['post']);

		TaoPublish::create_publishing_post($post);

		wp_redirect( admin_url( 'edit.php?post_type='.$post->post_type) );

	});

add_action('admin_action_workflow_publish_now', function(){
		$post = get_post($_REQUEST['post']);
		TaoPublish::publish_post($post->ID);
		wp_redirect(admin_url('edit.php?post_type='.$post->post_type));
	});

//add metabox to edit view of tao-publish posts
add_action('add_meta_boxes_page', function($post){

		if($post->post_status != TAO_PUBLISH_STATUS) return;

		//hides everything except the 'veroeffentlichen' button in the 'veroeffentlichen'-metabox
		echo '<style> #duplicate-action, #delete-action, #minor-publishing-actions, #misc-publishing-actions, #preview-action{display:none;}  { display:none;}</style>';

		wp_enqueue_script('jquery-ui-datepicker');
		$url = "http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/blitzer/jquery-ui.min.css";
		wp_enqueue_style('jquery-ui-blitzer', $url);
		wp_enqueue_script(TAO_PUBLISH_STATUS . '-datepicker.js', TaoKoeGiga::plugins_url('js/publish-datepicker.js'), array('jquery-ui-datepicker'));

		add_meta_box('meta_' . TAO_PUBLISH_STATUS,
								TAO_PUBLISH_METABOX,
								function($post) {
									wp_nonce_field(basename(__FILE__), TAO_PUBLISH_STATUS . '_nonce');
									$metaname = TAO_PUBLISH_STATUS . '_pubdate';
									$stamp = get_post_meta($post->ID, $metaname, true);
									$date = $time = '';
									if($stamp)
									{
										$date = date_i18n('d.m.Y', $stamp);
										$time = date_i18n('H', $stamp);
									}
									?>

										<p>
											<strong>Veröffentlichungsdatum</strong>
										</p>
										<label class="screen-reader-text" for="<?=$metaname?>">Veröffentlichungsdatum</label>
										<input type="text" class="widefat" name="<?=$metaname?>" id="<?=$metaname?>" value="<?=$date?>"/>
										<p>
											<strong>Uhrzeit</strong>
										</p>
										<label class="screen-reader-text" for="<?=$metaname?>_time">Uhrzeit</label>
										<select class="widefat" name="<?=$metaname?>_time" id="<?=$metaname?>_time">
											<option value="06:00" <?=$time==06?'selected':'';?>>Morgens</option>
											<option value="12:00" <?=$time==12?'selected':''?>>Mittags</option>
											<option value="18:00" <?=$time==18?'selected':''?>>Abends</option>
										</select>
									<?php
									},
									'page',
									'side');

	}, 10, 2);

//prevent tao-publish posts from changing status (except if getting trashed)
add_action('transition_post_status', function($new_status, $old_status, $post) {

		if($old_status == TAO_PUBLISH_STATUS && $new_status != 'trash')
		{
			remove_action('save_post', array('TaoPublish', 'save_meta'));

			$post->post_status = TAO_PUBLISH_STATUS;
			$u = wp_update_post($post, true);

			add_action('save_post', array('TaoPublish', 'save_meta'), 10, 2);
		}
		elseif($new_status == 'trash')
		{
			wp_clear_scheduled_hook('tao_publish_post', array('ID' => $post->ID));
		}
		elseif($old_status == 'trash' && $new_status == TAO_PUBLISH_STATUS)
		{
			wp_schedule_single_event(get_post_meta($post->ID, TAO_PUBLISH_STATUS.'_pubdate', true), 'tao_publish_post', array('ID' => $post->ID));
		}
	}, 10, 3);



class TaoPublish
{

	public static function create_publishing_post($post, $parent_id = '')
	{
		if($post->post_type != 'page') return;

		$new_author = wp_get_current_user();

		$new_post = array( //create the new post
			'menu_order' => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status' => $post->ping_status,
			'post_author' => $new_author->ID,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_mime_type' => $post->mime_type,
			'post_parent' => $post->ID,
			'post_password' => $post->post_password,
			'post_status' => TAO_PUBLISH_STATUS,
			'post_title' => $post->post_title,
			'post_type' => $post->post_type
			);

		$new_post_id = wp_insert_post($new_post); //insert the new post

		$meta_keys = get_post_custom_keys($post->ID); //now for copying the metadata to the new post

		foreach($meta_keys as $key)
		{
			$meta_values = get_post_custom_values($key, $post->ID);
			foreach($meta_values as $value)
			{
				$value = maybe_unserialize($value);
				add_post_meta($new_post_id, $key, $value);
			}
		}

		add_post_meta($new_post_id, TAO_PUBLISH_STATUS . '_original', $post->ID);//and finally referencing the original post

	}

	public static function save_meta($post_id, $post)
	{
		if($post->post_status == TAO_PUBLISH_STATUS || get_post_meta($post_id, TAO_PUBLISH_STATUS . '_original', true))
		{
			$nonce = TAO_PUBLISH_STATUS . '_nonce';
			$pub = TAO_PUBLISH_STATUS . '_pubdate';

			if(isset($_POST[$nonce]) && wp_verify_nonce($_POST[$nonce], basename(__FILE__) !== 1)) return $post_id;
			if(!current_user_can(get_post_type_object($post->post_type)->cap->edit_post, $post_id)) return $post_id;

			if(isset($_POST[$pub]) && isset($_POST[$pub.'_time']) && !empty($_POST[$pub]) && $stamp = strtotime($_POST[$pub] . ' ' . $_POST[$pub.'_time']))
			{
				if($stamp > time())
				{
					wp_clear_scheduled_hook('tao_publish_post', array('ID' => $post_id));
					update_post_meta($post_id, $pub, $stamp);
					wp_schedule_single_event($stamp, 'tao_publish_post', array('ID' => $post_id));
				}
			}
		}
	}

	public static function publish_post($post_id)
	{
		$orig = get_post(get_post_meta($post_id, TAO_PUBLISH_STATUS . '_original', true));

		$post = get_post($post_id);

		$meta_keys = get_post_custom_keys($post->ID);

		foreach($meta_keys as $key)
		{
			$meta_values = get_post_custom_values($key, $post->ID);
			foreach($meta_values as $value)
			{
				$value = maybe_unserialize($value);
				update_post_meta($orig->ID, $key, $value);
			}
		}

		$post->ID = $orig->ID;
		$post->post_name = $orig->post_name;
		$post->guid = $orig->guid;
		$post->post_parent = $orig->post_parent;
		$post->post_status = 'publish';

		delete_post_meta($orig->ID, TAO_PUBLISH_STATUS . '_original');
		delete_post_meta($orig->ID, TAO_PUBLISH_STATUS . '_pubdate');

		wp_update_post($post);
		wp_delete_post($post_id, true);

		return $orig->ID;
	}

	public static function getPubdate($stamp)
	{
		$str = date_i18n('j. F Y', $stamp) . ' - ';
		switch(date_i18n('H', $stamp))
		{
			case 18: $str .= 'Abends'; break;
			case 12: $str .= 'Mittags'; break;
			default: $str .= 'Morgens';
		}
		return $str;
	}
}

add_action('save_post', array('TaoPublish', 'save_meta'), 10, 2);
add_action('tao_publish_post', array('TaoPublish', 'publish_post'));
?>
