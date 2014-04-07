<?php
/**
 * Plugin Name: Scheduled Change
 * Description: Allows you to plan changes on any post type
 * Author: TAO Software
 * Author URI: http://software.tao.at
 * License: GPL3
 */

class Scheduled_Change {

	protected static $TAO_PUBLISH_LABEL      = 'Scheduled Change';
	protected static $TAO_PUBLISH_METABOX    = 'Scheduled Change';
	protected static $TAO_PUBLISH_STATUS     = 'tao_sc_publish';
	protected static $TAO_PUBLISH_TEXTDOMAIN = 'tao_sc_td';

	public static function init() {
		self::$TAO_PUBLISH_LABEL   = __( 'Scheduled Change', self::$TAO_PUBLISH_TEXTDOMAIN );
		self::$TAO_PUBLISH_METABOX = __( 'Scheduled Change', self::$TAO_PUBLISH_TEXTDOMAIN );
		self::register_post_status();
	}

	public static function load_pubdate() {
		$stamp = get_post_meta( $_REQUEST['postid'], self::$TAO_PUBLISH_STATUS . '_pubdate', true );
		if ( $stamp ) {
			$str  = '<div style="margin-left:20px">';
			$str .= TaoPublish::getPubdate( $stamp );
			$str .= '</div>';
			die($str);
		}
	}
	
	public static function register_post_status() {
		$args = array(
			'label'                     => _x( self::$TAO_PUBLISH_LABEL, 'Status General Name', 'default' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( self::$TAO_PUBLISH_LABEL . ' <span class="count">(%s)</span>', self::$TAO_PUBLISH_LABEL . ' <span class="count">(%s)</span>', self::$TAO_PUBLISH_TEXTDOMAIN ),
		);
		register_post_status( self::$TAO_PUBLISH_STATUS, $args );
	}

	public static function display_post_states( $states ) {
		global $post;
		$arg = get_query_var('post_status');
		if ( $arg != self::$TAO_PUBLISH_LABEL && $post->post_status == self::$TAO_PUBLISH_STATUS ) {
			return array( self::$TAO_PUBLISH_LABEL );
		}
		return $states;
	}
	
	
	public static function page_row_actions( $actions, $post ) {
		if ( $post->post_status == self::$TAO_PUBLISH_STATUS ) {
			$action = '?action=workflow_publish_now&post=' . $post->ID;
			$actions['publish_now'] = '<a href="' . admin_url('admin.php' . $action) .'">' . __('Publish Now', self::$TAO_PUBLISH_TEXTDOMAIN) . '</a>';
		} else {
			$action = '?action=workflow_copy_to_publish&post=' . $post->ID;
			$actions['copy_to_publish'] = '<a href="' . admin_url('admin.php' . $action) . '">' . self::$TAO_PUBLISH_LABEL . '</a>';
		}

		return $actions;
	}
	
	public static function manage_pages_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $val ) {
			if ( 'author' == $key ) {
				$new['tao_publish'] = __( 'Veröffentlichungsdatum' );
			}
			$new[$key] = $val;
		}
		return $new;
	}

	public static function manage_pages_custom_column( $column, $post_id) {
		if ( $column == 'tao_publish' ) {
			$stamp = get_post_meta($post_id, self::$TAO_PUBLISH_STATUS . '_pubdate', true);

			if($stamp)
				echo self::getPubdate($stamp);
		}
	}

	public static function admin_action_workflow_copy_to_publish() {
		$post = get_post( $_REQUEST['post'] );
		self::create_publishing_post( $post );
		wp_redirect( admin_url( 'edit.php?post_type='.$post->post_type ) );
	}

	public static function admin_action_workflow_publish_now() {
		$post = get_post( $_REQUEST['post'] );
		self::publish_post( $post->ID );
		wp_redirect( admin_url( 'edit.php?post_type='.$post->post_type ) );
	}


	public static function add_meta_boxes_page( $post ) {
		if($post->post_status != self::$TAO_PUBLISH_STATUS) return;

		//hides everything except the 'veroeffentlichen' button in the 'veroeffentlichen'-metabox
		echo '<style> #duplicate-action, #delete-action, #minor-publishing-actions, #misc-publishing-actions, #preview-action{display:none;}  { display:none;}</style>';

		wp_enqueue_script( 'jquery-ui-datepicker' );
		$url = "http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/blitzer/jquery-ui.min.css";
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
		wp_localize_script( self::$TAO_PUBLISH_STATUS . '-datepicker.js', 'tao_sc_dp_daynames', $days );
		wp_localize_script( self::$TAO_PUBLISH_STATUS . '-datepicker.js', 'tao_sc_dp_monthnames', $months );
		wp_localize_script( self::$TAO_PUBLISH_STATUS . '-datepicker.js', 'tao_sc_dp_id', self::$TAO_PUBLISH_STATUS . '_pubdate' );

		add_meta_box( 'meta_' . self::$TAO_PUBLISH_STATUS, self::$TAO_PUBLISH_METABOX, create_function( '$post', 'Scheduled_Change::create_meta_box( $post );' ), 'page', 'side' );
	}
	
	public static function create_meta_box( $post ) {
		wp_nonce_field( basename( __FILE__ ), self::$TAO_PUBLISH_STATUS . '_nonce' );
		$metaname = self::$TAO_PUBLISH_STATUS . '_pubdate';
		$stamp = get_post_meta( $post->ID, $metaname, true );
		$date = $time = '';
		if ( $stamp ) {
			$date = date_i18n( 'd.m.Y', $stamp );
			$time = date_i18n( 'H', $stamp );
		}
		?>
			<p>
				<strong><?php _e( 'Publishing Date', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></strong>
			</p>
			<label class="screen-reader-text" for="<?php echo $metaname; ?>"><?php _e( 'Publishing Date', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></label>
			<input type="text" class="widefat" name="<?php echo $metaname; ?>" id="<?php echo $metaname; ?>" value="<?php echo $date; ?>"/>
			<p>
				<strong><?php _e( 'Time', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></strong>
			</p>
			<label class="screen-reader-text" for="<?php echo $metaname; ?>_time"><?php _e( 'Time', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></label>
			<select class="widefat" name="<?php echo $metaname; ?>_time" id="<?=$metaname?>_time">
				<option value="06:00" <?php echo 06 == $time ? 'selected' : ''; ?>><?php _e( 'Morning', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></option>
				<option value="12:00" <?php echo 12 == $time ? 'selected' : ''; ?>><?php _e( 'Midday', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></option>
				<option value="18:00" <?php echo 18 == $time ? 'selected' : ''; ?>><?php _e( 'Evening', self::$TAO_PUBLISH_TEXTDOMAIN ); ?></option>
			</select>
		<?php
	}

	public static function prevent_status_change ( $new_status, $old_status, $post ) {
		if ( $old_status == self::$TAO_PUBLISH_STATUS && 'trash' != $new_status ) {
			remove_action( 'save_post', array( 'TaoPublish', 'save_meta' ) );

			$post->post_status = self::$TAO_PUBLISH_STATUS;
			$u = wp_update_post( $post, true );

			add_action( 'save_post', array( 'TaoPublish', 'save_meta' ), 10, 2 );
		} elseif ( 'trash' == $new_status ) {
			wp_clear_scheduled_hook( 'tao_publish_post', array( 'ID' => $post->ID ) );
		} elseif ( 'trash' == $old_status && $new_status == self::$TAO_PUBLISH_STATUS ) {
			wp_schedule_single_event( get_post_meta( $post->ID, self::$TAO_PUBLISH_STATUS.'_pubdate', true ), 'tao_publish_post', array( 'ID' => $post->ID ) );
		}
	}


	public static function create_publishing_post( $post, $parent_id = '' ) {
		if ($post->post_type != 'page') return;

		$new_author = wp_get_current_user();

		$new_post = array( //create the new post
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
			'post_type'      => $post->post_type
		);

		$new_post_id = wp_insert_post( $new_post ); //insert the new post

		$meta_keys = get_post_custom_keys( $post->ID ); //now for copying the metadata to the new post

		foreach ( $meta_keys as $key ) {
			$meta_values = get_post_custom_values( $key, $post->ID );
			foreach ( $meta_values as $value ) {
				$value = maybe_unserialize( $value );
				add_post_meta( $new_post_id, $key, $value );
			}
		}

		add_post_meta( $new_post_id, self::$TAO_PUBLISH_STATUS . '_original', $post->ID );//and finally referencing the original post

	}

	public static function save_meta( $post_id, $post )
	{
		if ( $post->post_status == self::$TAO_PUBLISH_STATUS || get_post_meta($post_id, self::$TAO_PUBLISH_STATUS . '_original', true) ) {
			$nonce = Scheduled_Change::$TAO_PUBLISH_STATUS . '_nonce';
			$pub = Scheduled_Change::$TAO_PUBLISH_STATUS . '_pubdate';

			if (isset( $_POST[$nonce] ) && wp_verify_nonce( $_POST[$nonce], basename( __FILE__ ) !== 1 ) ) return $post_id;
			if ( ! current_user_can( get_post_type_object( $post->post_type )->cap->edit_post, $post_id ) ) return $post_id;

			if ( isset( $_POST[$pub] ) && isset( $_POST[$pub.'_time'] ) && ! empty( $_POST[$pub] ) && $stamp = strtotime( $_POST[$pub] . ' ' . $_POST[$pub.'_time'] ) ) {
				if( $stamp > time() ) {
					wp_clear_scheduled_hook( 'tao_publish_post', array( 'ID' => $post_id ) );
					update_post_meta( $post_id, $pub, $stamp );
					wp_schedule_single_event( $stamp, 'tao_publish_post', array('ID' => $post_id) );
				}
			}
		}
	}

	public static function publish_post( $post_id ) {
		$orig = get_post( get_post_meta( $post_id, self::$TAO_PUBLISH_STATUS . '_original', true ) );
 
		$post = get_post( $post_id );

		$meta_keys = get_post_custom_keys( $post->ID );

		foreach ( $meta_keys as $key ) {
			$meta_values = get_post_custom_values( $key, $post->ID );
			foreach ( $meta_values as $value ) {
				$value = maybe_unserialize( $value );
				update_post_meta( $orig->ID, $key, $value );
			}
		}

		$post->ID = $orig->ID;
		$post->post_name = $orig->post_name;
		$post->guid = $orig->guid;
		$post->post_parent = $orig->post_parent;
		$post->post_status = 'publish';

		delete_post_meta( $orig->ID, self::$TAO_PUBLISH_STATUS . '_original' );
		delete_post_meta( $orig->ID, self::$TAO_PUBLISH_STATUS . '_pubdate' );

		wp_update_post( $post );
		wp_delete_post( $post_id, true );

		return $orig->ID;
	}

	public static function getPubdate( $stamp ) {
		$str = date_i18n( 'j. F Y', $stamp ) . ' - ';
		switch( date_i18n( 'H', $stamp ) ) {
			case 18: $str .= __( 'Evening', self::$TAO_PUBLISH_TEXTDOMAIN ); break;
			case 12: $str .= __( 'Midday', self::$TAO_PUBLISH_TEXTDOMAIN ); break;
			default: $str .= __( 'Morning', self::$TAO_PUBLISH_TEXTDOMAIN );
		}
		return $str;
	}
}

add_action( 'save_post', create_function( '$post_id, $post', 'return Scheduled_Change::save_meta( $post_id, $post );' ), 10, 2 );
add_action( 'tao_publish_post', create_function( '', 'return Scheduled_change::publish_post();' ) );

add_action( 'wp_ajax_load_pubdate', create_function( '', 'return Scheduled_Change::load_pubdate();' ) );
add_action( 'init', create_function( '', 'return Scheduled_Change::init();' ) );
add_action( 'admin_action_workflow_copy_to_publish', create_function( '', 'return Scheduled_Change::admin_action_workflow_copy_to_publish();' ) );
add_action( 'admin_action_workflow_publish_now', create_function( '', 'return Scheduled_Change::admin_action_workflow_publish_now();' ) );
add_action( 'add_meta_boxes_page', create_function( '$post', 'return Scheduled_Change::add_meta_boxes_page( $post );' ) );
add_action( 'transition_post_status', create_function( '$new_status, $old_status, $post', 'return Scheduled_Change::prevent_status_change( $new_status, $old_status, $post );' ), 10, 3 );

add_filter( 'display_post_states', create_function( '$states', 'return Scheduled_Change::display_post_states( $states );' ) );
add_filter( 'page_row_actions', create_function( '$actions, $post', 'return Scheduled_Change::page_row_actions( $actions, $post );' ), 10, 2 );
add_filter( 'manage_pages_columns', create_function( '$columns', 'return Scheduled_Change::manage_pages_columns( $columns );' ) );
add_filter( 'manage_pages_custom_column', create_function( '$column, $post_id', 'return Scheduled_Change::manage_pages_custom_column( $column, $post_id );' ), 10, 2 );
