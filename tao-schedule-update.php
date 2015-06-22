<?php
/**
 * Plugin Name: TAO Schedule Update
 * Description: Allows you to plan changes on any post type
 * Author: TAO Software
 * Author URI: http://software.tao.at
 * Version: 1.04.01
 * License: MIT
 */

class TAO_ScheduleUpdate {

	protected static $TAO_PUBLISH_LABEL      = 'Scheduled Update';
	protected static $TAO_PUBLISH_METABOX    = 'Scheduled Update';
	protected static $TAO_PUBLISH_STATUS     = 'tao_sc_publish';
	protected static $TAO_PUBLISH_TEXTDOMAIN = 'tao-scheduleupdate-td';


	/**
	 * Initializes TAO_PUBLISH_LABEL and TAO_PUBLISH_METABOX with their localized strings.
	 *
	 * This method initializes TAO_PUBLISH_LABEL and TAO_PUBLISH_METABOX with their localized
	 * strings and registers the tao_sc_publish post status.
	 *
	 * @return void
	 */
	public static function init() {
		self::load_plugin_textdomain();
		self::$TAO_PUBLISH_LABEL   = __( 'Scheduled Update', self::$TAO_PUBLISH_TEXTDOMAIN );
		self::$TAO_PUBLISH_METABOX = __( 'Scheduled Update', self::$TAO_PUBLISH_TEXTDOMAIN );
		self::register_post_status();

		$pt = TAO_ScheduleUpdate::get_post_types();
		foreach ( $pt as $type ) {
			add_action( 'manage_edit-'.$type->name.'_columns', create_function( '$columns', 'return TAO_ScheduleUpdate::manage_pages_columns( $columns );' ) );
			add_filter( 'manage_'.$type->name.'_posts_custom_column', create_function( '$column, $post_id', 'return TAO_ScheduleUpdate::manage_pages_custom_column( $column, $post_id );' ), 10, 2 );
			add_action( 'add_meta_boxes', create_function( '$post_type, $post', 'return TAO_ScheduleUpdate::add_meta_boxes_page( $post_type, $post );' ), 10, 2 );
		}
	}

	/**
	 * Wrapper for wp's own load_plugin_textdomain.
	 *
	 * @access private
	 * @return void
	 */
	private static function load_plugin_textdomain(){
		load_plugin_textdomain( 'tao-scheduleupdate-td', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
	}

	/**
	 * Retreives all currently registered posttypes.
	 *
	 * @access private
	 * @return array Array of all registered post type as objects
	 */
	private static function get_post_types() {
		return get_post_types( array( 'public' => true ), 'objects' );
	}


	/**
	 * Displays a post's publishing date.
	 *
	 * @see get_post_meta
	 * @return void
	 */
	public static function load_pubdate() {
		$stamp = get_post_meta( $_REQUEST['postid'], self::$TAO_PUBLISH_STATUS . '_pubdate', true );
		if ( $stamp ) {
			$str  = '<div style="margin-left:20px">';
			$str .= TaoPublish::getPubdate( $stamp );
			$str .= '</div>';
			die($str);
		}
	}

	/**
	 * Registers the post status tao_sc_publish.
	 *
	 * @see register_post_status
	 * @return void
	 */
	public static function register_post_status() {
		$args = array(
			'label'                     => _x( self::$TAO_PUBLISH_LABEL, 'Status General Name', 'default' ),
			'public'                    => false,
			'internal'                  => true,
			'publicly_queryable'        => false,
			'protected'                 => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( self::$TAO_PUBLISH_LABEL . ' <span class="count">(%s)</span>', self::$TAO_PUBLISH_LABEL . ' <span class="count">(%s)</span>', self::$TAO_PUBLISH_TEXTDOMAIN ),
		);
		register_post_status( self::$TAO_PUBLISH_STATUS, $args );
	}


	/**
	 * Adds post's state to 'scheduled updates'-posts.
	 *
	 * @param array $states Array of post states
	 * @global $post
	 */
	public static function display_post_states( $states ) {
		global $post;
		$arg = get_query_var('post_status');
		$the_post_types = self::get_post_types();
		// default states for non public posts
		if( !isset($the_post_types[$post->post_type]) ) return $states;
		$type = $the_post_types[$post->post_type];

		if ( $arg != self::$TAO_PUBLISH_LABEL && $post->post_status == self::$TAO_PUBLISH_STATUS ) {
			$states = array( self::$TAO_PUBLISH_LABEL );
			if ( ! $type->hierarchical ) {
				$orig = get_post(get_post_meta($post->ID, self::$TAO_PUBLISH_STATUS . '_original', true));
				array_push($states, __( 'Original', self::$TAO_PUBLISH_TEXTDOMAIN ).': ' . $orig->post_title);
			}
		}

		return $states;
	}


	/**
	 * Adds links for scheduled updates.
	 *
	 * Adds a link for immediate publishing to all sheduled posts. Adds a link to schedule a change
	 * to all non-scheduled posts.
	 *
	 * @param array $actions Array of available actions added by previous hooks
	 * @oaram post $post the post for which to add actions
	 * @return array Array of available actions for the given post
	 */
	public static function page_row_actions( $actions, $post ) {
		if ( $post->post_status == self::$TAO_PUBLISH_STATUS ) {
			$action = '?action=workflow_publish_now&post=' . $post->ID;
			$actions['publish_now'] = '<a href="' . admin_url('admin.php' . $action) .'">' . __('Publish Now', self::$TAO_PUBLISH_TEXTDOMAIN) . '</a>';
		} elseif( $post->post_status != 'trash') {
			$action = '?action=workflow_copy_to_publish&post=' . $post->ID;
			$actions['copy_to_publish'] = '<a href="' . admin_url('admin.php' . $action) . '">' . self::$TAO_PUBLISH_LABEL . '</a>';
		}

		return $actions;
	}


	/**
	 * Adds a column to the pages overview.
	 *
	 * @param array $columns Array of available columns added by previous hooks
	 * @return array Array of available columns
	 */
	public static function manage_pages_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $val ) {
			if ( 'author' == $key ) {
				$new['tao_publish'] = __( 'Releasedate', self::$TAO_PUBLISH_TEXTDOMAIN );
			}
			$new[$key] = $val;
		}
		return $new;
	}


	/**
	 * Manages the content of previously added custom columns.
	 *
	 * @see TAO_ScheduleUpdate::manage_pages_columns()
	 * @param string $column Name of the column
	 * @param int $post_id id of the current post
	 */
	public static function manage_pages_custom_column( $column, $post_id) {
		if ( 'tao_publish' == $column ) {
			$stamp = get_post_meta($post_id, self::$TAO_PUBLISH_STATUS . '_pubdate', true);

			if( $stamp ) {
				echo self::getPubdate($stamp);
			}
		}
	}


	/**
	 * Handles the admin action workflow_copy_to_publish.
	 * redirects to post edit screen if successful
	 *
	 * @return void
	 */
	public static function admin_action_workflow_copy_to_publish() {
		$post = get_post( $_REQUEST['post'] );
		$publishing_id = self::create_publishing_post( $post );
		if ( $publishing_id ) {
			wp_redirect( admin_url( 'post.php?action=edit&post='.$publishing_id ) );
		} else {
			$html  = sprintf( __('Could not schedule %s %s', self::$TAO_PUBLISH_TEXTDOMAIN ), $post->post_type, '<i>'.htmlspecialchars( $post->post_title ).'</i>' );
			$html .= '<br><br>';
			$html .= '<a href="' . esc_attr( admin_url( 'edit.php?post_type='.$post->post_type ) ) . '">' . __('Back') . '</a>';
			wp_die( $html );
		}
	}

	/**
	 * Handles the admin action workflow_publish_now
	 *
	 * @return void
	 */
	public static function admin_action_workflow_publish_now() {
		$post = get_post( $_REQUEST['post'] );
		self::publish_post( $post->ID );
		wp_redirect( admin_url( 'edit.php?post_type='.$post->post_type ) );
	}


	/**
	 * Adds the 'scheduled update'-metabox to the edit-page screen.
	 *
	 * @param post $post The post being currently edited
	 * @see add_meta_box
	 * @return void
	 */
	public static function add_meta_boxes_page( $post_type, $post ) {
		if($post->post_status != self::$TAO_PUBLISH_STATUS) return;

		//hides everything except the 'publish' button in the 'publish'-metabox
		echo '<style> #duplicate-action, #delete-action, #minor-publishing-actions, #misc-publishing-actions, #preview-action {display:none;} </style>';

		wp_enqueue_script( 'jquery-ui-datepicker' );
		$url = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/blitzer/jquery-ui.min.css';
		wp_enqueue_style( 'jquery-ui-blitzer', $url );
		wp_enqueue_script( self::$TAO_PUBLISH_STATUS . '-datepicker.js', plugins_url( 'js/publish-datepicker.js', __FILE__ ), array( 'jquery-ui-datepicker' ) );

		$months = array();
		for ( $i=1; $i<=12; $i++ ) {
			$months[] = date_i18n( 'F', strtotime( '2014-'.$i.'-01 00:00:00' ) );
		}
		$days = array();
		for ( $i=23;$i<=29;$i++ ) {
			$days[] = date_i18n( 'D', strtotime( '2014-03-'.$i.' 00:00:00' ) );
		}
		$js_data = array(
			'datepicker' => array(
				'daynames'   => $days,
				'monthnames' => $months,
				'elementid' => self::$TAO_PUBLISH_STATUS . '_pubdate',
				),
			'text' => array(
				'save' => __( 'Save' ),
			),
		);

		wp_localize_script( self::$TAO_PUBLISH_STATUS . '-datepicker.js', 'TAO_ScheduleUpdate', $js_data );

		add_meta_box( 'meta_' . self::$TAO_PUBLISH_STATUS, self::$TAO_PUBLISH_METABOX, create_function( '$post', 'TAO_ScheduleUpdate::create_meta_box( $post );' ), $post_type, 'side' );
	}

	/**
	 * Creates the HTML-Code for the 'scheduled update'-metabox
	 *
	 * @param post $post The post being currently edited
	 * @return void
	 */
	public static function create_meta_box( $post ) {
		wp_nonce_field( basename( __FILE__ ), self::$TAO_PUBLISH_STATUS . '_nonce' );
		$metaname = self::$TAO_PUBLISH_STATUS . '_pubdate';
		$stamp = get_post_meta( $post->ID, $metaname, true );
		$date = $time = '';
		$dateo = new DateTime('now', self::get_timezone_object() );
		if ( $stamp ) {
			$dateo->setTimestamp( $stamp );
		}
		$time = $dateo->format( 'H:i' );
		$date = $dateo->format( 'd.m.Y' );
		?>
			<p>
				<strong><?php _e( 'Releasedate', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></strong>
			</p>
			<label class="screen-reader-text" for="<?php echo $metaname; ?>"><?php _e( 'Releasedate', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></label>
			<input type="text" class="widefat" name="<?php echo $metaname; ?>" id="<?php echo $metaname; ?>" value="<?php echo $date; ?>"/>
			<p>
				<strong><?php _e( 'Time', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></strong>
			</p>
			<label class="screen-reader-text" for="<?php echo $metaname; ?>_time"><?php _e( 'Time', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></label>
			<select name="<?php echo $metaname; ?>_time_hrs" id="<?php echo $metaname; ?>_time">
				<?php for ($i = 0; $i < 24; $i++ ) : ?>
				<option value="<?php echo sprintf( '%02d', $i ); ?>" <?php echo $i == $dateo->format( 'H' ) ? 'selected' : ''; ?>><?php echo sprintf( '%02d', $i ); ?></option>
				<?php endfor; ?>
			</select>:
			<select name="<?php echo $metaname; ?>_time_mins">
				<?php for ($i = 0; $i < 60; $i+=5 ) : ?>
				<option value="<?php echo sprintf( '%02d', $i ); ?>" <?php echo $i == ceil( $dateo->format( 'i' ) / 10 ) * 10 ? 'selected' : ''; ?>><?php echo sprintf( '%02d', $i ); ?></option>
				<?php endfor; ?>
			</select>
			<p>
				<?php echo sprintf( __( 'Please enter <i>Time</i> as %s', self::$TAO_PUBLISH_TEXTDOMAIN ), self::get_timezone_string() ); ?>
			</p>
			<p>
				<div id="pastmsg" style="color:red; display:none;"><?php echo __( 'The Releasedate is in the past, this post will be published 5 Minutes from now.', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></div>
			</p>
		<?php
	}


	/**
	 * Gets the currently set timezone..
	 *
	 * Retreives either the timezone_string or the gmt_offset.
	 *
	 * @see get_option
	 * @access private
	 * @return string The set timezone
	 */
	private static function get_timezone_string() {
		$current_offset = get_option( 'gmt_offset' );
		$tzstring = get_option( 'timezone_string' );

		$check_zone_info = true;

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
			$tzstring = '';
		}

		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
			$check_zone_info = false;
			if ( 0 == $current_offset )
				$tzstring = 'UTC+0';
			elseif ( $current_offset < 0 )
				$tzstring = 'UTC' . $current_offset;
			else
				$tzstring = 'UTC+' . $current_offset;
		}

		return $tzstring;
	}

	/**
	 * Creates a timezone object based on the option gmt_offset
	 *
	 * @see DateTimeZone
	 * @return DateTimeZone timezone specified by the gmt_offset option
	 */
	private static function get_timezone_object() {
		$offset = get_option( 'gmt_offset' ) * 3600;
		$ids = DateTimeZone::listIdentifiers();
		foreach ( $ids as $timezone ) {
			$tzo = new DateTimeZone( $timezone );
			$dt = new DateTime( 'now', $tzo );
			if ( $tzo->getOffset( $dt ) == $offset ) {
				return $tzo;
			}
		}
	}

	/**
	 * Prevents scheduled updates to switch to other post states.
	 *
	 * Prevents post with the state 'scheduled update' to switch to published after being saved
	 * clears cron hook if post is trashed
	 * restores cron hook if post us un-trashed
	 *
	 * @param string $new_status the post's new status
	 * @param string $old_status the post's old status
	 * @param post $post the post changing status
	 * @return void
	 */
	public static function prevent_status_change ( $new_status, $old_status, $post ) {
		if ( $old_status == self::$TAO_PUBLISH_STATUS && 'trash' != $new_status ) {
			remove_action( 'save_post', create_function( '$post_id, $post', 'return TAO_ScheduleUpdate::save_meta( $post_id, $post );' ), 10 );

			$post->post_status = self::$TAO_PUBLISH_STATUS;
			$u = wp_update_post( $post, true );

			add_action( 'save_post', create_function( '$post_id, $post', 'return TAO_ScheduleUpdate::save_meta( $post_id, $post );' ), 10, 2 );
		} elseif ( 'trash' == $new_status ) {
			wp_clear_scheduled_hook( 'tao_publish_post', array( 'ID' => $post->ID ) );
		} elseif ( 'trash' == $old_status && $new_status == self::$TAO_PUBLISH_STATUS ) {
			wp_schedule_single_event( get_post_meta( $post->ID, self::$TAO_PUBLISH_STATUS.'_pubdate', true ), 'tao_publish_post', array( 'ID' => $post->ID ) );
		}
	}

	/**
	 * Copies an entire post and sets it's status to 'scheduled update'
	 *
	 * @param post $post the post to be copied
	 * @return int - ID of the newly created post
	 */
	public static function create_publishing_post( $post ) {

		$new_author = wp_get_current_user();

		//create the new post
		$new_post = array(
			'menu_order'     => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_author->ID,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_mime_type' => $post->mime_type,
			'post_parent'    => $post->ID,
			'post_password'  => $post->post_password,
			'post_status'    => self::$TAO_PUBLISH_STATUS,
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
		);

		//insert the new post
		$new_post_id = wp_insert_post( $new_post );

		//copy meta and terms over to the new post
		self::copy_meta_and_terms( $post->ID, $new_post_id );

		//and finally referencing the original post
		add_post_meta( $new_post_id, self::$TAO_PUBLISH_STATUS . '_original', $post->ID );

		return $new_post_id;
	}

	/**
	 * copies meta and terms from one post to another
	 * @param int $source_post_id the post from which to copy
	 * @param int $destination_post_id the post which will get the meta and terms
	 * @return void
	 */
	public static function copy_meta_and_terms( $source_post_id, $destination_post_id ) {

		$source_post = get_post( $source_post_id );
		$destination_post = get_post( $destination_post_id );

		//abort if any of the ids is not a post
		if( !$source_post || !$destination_post ) return;

		//remove all meta from the destination, 
		$dest_keys = get_post_custom_keys( $destination_post->ID );
		foreach( $dest_keys as $key ) {
			delete_post_meta( $destination_post->ID, $key );
		}

		//now for copying the metadata to the new post
		$meta_keys = get_post_custom_keys( $source_post->ID ) ?: array(); 
		foreach ( $meta_keys as $key ) {
			$meta_values = get_post_custom_values( $key, $source_post->ID );
			foreach ( $meta_values as $value ) {
				$value = maybe_unserialize( $value );
				add_post_meta( $destination_post->ID, $key, $value );
			}
		}
		

		//and now for copying the terms
		$taxonomies = get_object_taxonomies( $source_post->post_type );
		foreach( $taxonomies as $taxonomy ) {
			$post_terms = wp_get_object_terms( $source_post->ID, $taxonomy, array( 'orderby' => 'term_order' ) );
			$terms = array();
			foreach( $post_terms as $term ) {
				$terms[] = $term->slug;
			}
			//reset taxonomy to empty
			wp_set_object_terms( $destination_post->ID, NULL, $taxonomy );
			//then add new terms
			$what = wp_set_object_terms( $destination_post->ID, $terms, $taxonomy );
		}

	}


	/**
	 * Saves a post's publishing date.
	 *
	 * @param int $post_id the post's id
	 * @param post $post the post being saved
	 * @return void
	 */
	public static function save_meta( $post_id, $post )
	{
		if ( $post->post_status == self::$TAO_PUBLISH_STATUS || get_post_meta($post_id, self::$TAO_PUBLISH_STATUS . '_original', true) ) {
			$nonce = TAO_ScheduleUpdate::$TAO_PUBLISH_STATUS . '_nonce';
			$pub = TAO_ScheduleUpdate::$TAO_PUBLISH_STATUS . '_pubdate';

			if (isset( $_POST[$nonce] ) && wp_verify_nonce( $_POST[$nonce], basename( __FILE__ ) !== 1 ) ) return $post_id;
			if ( ! current_user_can( get_post_type_object( $post->post_type )->cap->edit_post, $post_id ) ) return $post_id;

			if ( isset( $_POST[$pub] ) && isset( $_POST[$pub.'_time_hrs'] ) && isset( $_POST[$pub.'_time_mins'] ) && ! empty( $_POST[$pub] ) ) {
				$tz = self::get_timezone_object();
				$stamp = DateTime::createFromFormat('d.m.Y H:i', $_POST[$pub] . ' ' . $_POST[$pub.'_time_hrs'] . ':' . $_POST[$pub.'_time_mins'], $tz )->getTimestamp();
				if ( ! $stamp || $stamp <= time())
					$stamp = strtotime('+5 minutes');

				wp_clear_scheduled_hook( 'tao_publish_post', array( 'ID' => $post_id ) );
				update_post_meta( $post_id, $pub, $stamp );
				wp_schedule_single_event( $stamp, 'tao_publish_post', array('ID' => $post_id) );
			}
		}
	}

	/**
	 * Publishes a scheduled update
	 *
	 * Copies the original post's contents and meta into it's "scheduled update" and then deletes
	 * the original post. This function is either called by wp_cron or if the user hits the
	 * 'publish now' action
	 *
	 * @param int $post_id the post's id
	 * @return int the original post's id
	 */
	public static function publish_post( $post_id ) {

		$orig_id = get_post_meta( $post_id, self::$TAO_PUBLISH_STATUS . '_original', true );
		//break early if given post is not an actual scheduled post created by this plugin
		if( !$orig_id ) {
			return $post_id;
		}

		$orig = get_post( $orig_id );

		$post = get_post( $post_id );

		self::copy_meta_and_terms( $post->ID, $orig->ID );

		$post->ID = $orig->ID;
		$post->post_name = $orig->post_name;
		$post->guid = $orig->guid;
		$post->post_parent = $orig->post_parent;
		$post->post_status = $orig->post_status;
		$post->post_date = date_i18n( 'Y-m-d H:i:s' ); //we need this to get wp to recognize this as a newly updated post

		delete_post_meta( $orig->ID, self::$TAO_PUBLISH_STATUS . '_original' );
		delete_post_meta( $orig->ID, self::$TAO_PUBLISH_STATUS . '_pubdate' );

		wp_update_post( $post );
		wp_delete_post( $post_id, true );

		return $orig->ID;
	}


	/**
	 * Reformats a timestamp into human readable publishing date and time
	 *
	 * @param int $stamp unix timestamp to be formatted
	 * @see date_i18n, DateTime, TAO_ScheduleUpdate::get_timezone_object
	 * @return string the formatted timestamp
	 */
	public static function getPubdate( $stamp ) {
		$date = new DateTime( 'now', self::get_timezone_object() );
		$date->setTimestamp( $stamp );
		$str = $date->format( 'd.' ) . date_i18n( ' F Y', mktime( 0, 0, 0, $date->format( 'm' ) ) ) . ' - ' . $date->format( 'H:i \U\T\CO' );
		return $str;
	}

}

add_action( 'save_post', create_function( '$post_id, $post', 'return TAO_ScheduleUpdate::save_meta( $post_id, $post );' ), 10, 2 );
add_action( 'tao_publish_post', create_function( '$post_id', 'return TAO_ScheduleUpdate::publish_post( $post_id );' ) );

add_action( 'wp_ajax_load_pubdate', create_function( '', 'return TAO_ScheduleUpdate::load_pubdate();' ) );
add_action( 'init', create_function( '', 'return TAO_ScheduleUpdate::init();' ) );
add_action( 'admin_action_workflow_copy_to_publish', create_function( '', 'return TAO_ScheduleUpdate::admin_action_workflow_copy_to_publish();' ) );
add_action( 'admin_action_workflow_publish_now', create_function( '', 'return TAO_ScheduleUpdate::admin_action_workflow_publish_now();' ) );
add_action( 'transition_post_status', create_function( '$new_status, $old_status, $post', 'return TAO_ScheduleUpdate::prevent_status_change( $new_status, $old_status, $post );' ), 10, 3 );

add_filter( 'display_post_states', create_function( '$states', 'return TAO_ScheduleUpdate::display_post_states( $states );' ) );
add_filter( 'page_row_actions', create_function( '$actions, $post', 'return TAO_ScheduleUpdate::page_row_actions( $actions, $post );' ), 10, 2 );
add_filter( 'post_row_actions', create_function( '$actions, $post', 'return TAO_ScheduleUpdate::page_row_actions( $actions, $post );' ), 10, 2 );
add_filter( 'manage_pages_columns', create_function( '$columns', 'return TAO_ScheduleUpdate::manage_pages_columns( $columns );' ) );
