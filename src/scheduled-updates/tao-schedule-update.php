<?php
/**
 * TAO Schedule Update
 *
 * Plugin Name: Post Scheduled Updates
 * Description: Allows you to plan changes on any post type
 * Author: TAO Digital
 * Author URI: http://tao-digital.at/
 * Version: 1.15
 * License: MIT
 * Text Domain: tao-scheduleupdate-td
 *
 * @package PostScheduler
 */

namespace PostScheduler;
/**
 * TAO Scheudle Update main class
 */
class ScheduledUpdate {


	/**
	 * Label to be displayed to the user
	 *
	 * @access public
	 * @var string
	 */
	public static $tao_publish_label         = 'Scheduled Update';

	/**
	 * Title for the Publish Metabox
	 *
	 * @access protected
	 * @var string
	 */
	protected static $_tao_publish_metabox    = 'Scheduled Update';

	/**
	 * Status for wordpress posts
	 *
	 * @access protected
	 * @var string
	 */
	protected static $_tao_publish_status     = 'tao_sc_publish';


	/**
	 * Initializes tao_publish_label and _tao_publish_metabox with their localized strings.
	 *
	 * This method initializes tao_publish_label and _tao_publish_metabox with their localized
	 * strings and registers the tao_sc_publish post status.
	 *
	 * @return void
	 */
	public static function init() {
		require_once dirname( __FILE__ ) . '/options.php';

		self::load_plugin_textdomain();
		self::$tao_publish_label   = __('Scheduled Update', 'tao-scheduleupdate-td');
		self::$_tao_publish_metabox = __('Scheduled Update', 'tao-scheduleupdate-td');
		self::register_post_status();

		$pt = ScheduledUpdate::get_post_types();
		foreach ( $pt as $type ) {
			add_action('manage_edit-' . $type->name . '_columns', array('ScheduledUpdate', 'manage_pages_columns'));
			add_filter('manage_' . $type->name . '_posts_custom_column', array('ScheduledUpdate', 'manage_pages_custom_column'), 10, 2);
			add_action('add_meta_boxes', array('ScheduledUpdate', 'add_meta_boxes_page'), 10, 2);
		}
	}

	/**
	 * Wrapper for wp's own load_plugin_textdomain.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private static function load_plugin_textdomain() {
		load_plugin_textdomain('tao-scheduleupdate-td', false, dirname(plugin_basename(__FILE__)) . '/language/');
	}

	/**
	 * Retreives all currently registered posttypes.
	 *
	 * @access private
	 *
	 * @return array Array of all registered post type as objects
	 */
	private static function get_post_types() {
		return get_post_types(array(
            'public' => true,
        ), 'objects');
	}


	/**
	 * Displays a post's publishing date.
	 *
	 * @see get_post_meta
	 *
	 * @return void
	 */
	public static function load_pubdate() {
		if ( isset( $_REQUEST['postid'] ) ) { // WPCS: CSRF okay.
			$stamp = get_post_meta(absint(wp_unslash($_REQUEST['postid'])), self::$_tao_publish_status . '_pubdate', true); // WPCS: CSRF okay.
			if ( $stamp ) {
				$str  = '<div style="margin-left:20px">';
				$str .= TaoPublish::get_pubdate( $stamp );
				$str .= '</div>';
				die( $str ); // WPCS: XSS okay.
			}
		}
	}

	/**
	 * Registers the post status tao_sc_publish.
	 *
	 * @see register_post_status
	 *
	 * @return void
	 */
	public static function register_post_status() {
		$public = false;
		if ( ScheduledUpdate_Options::get( 'tsu_visible' ) ) {
			// we only want to register as public if we're not on the search page.
			$public = ! is_search();
		}

		// compatibility with CMS Tree Page View.
		$exclude_from_search = ! is_admin();

		$args = array(
			'label'                     => _x('Scheduled Update', 'Status General Name', 'default'),
			'public'                    => $public,
			'internal'                  => true,
			'publicly_queryable'        => true,
			'protected'                 => true,
			'exclude_from_search'       => $exclude_from_search,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators: number of posts.
			'label_count'               => _n_noop('Scheduled Update <span class="count">(%s)</span>', 'Scheduled Update <span class="count">(%s)</span>', 'tao-scheduleupdate-td'),
		);

		register_post_status(self::$_tao_publish_status, $args);
	}

	/**
	 * Adds the tao-schedule-update post status to the list of displayable stati in the parent dropdown
	 *
	 * @param array $args arguments passed by the filter.
	 *
	 * @return array Array of parameters
	 */
	public static function parent_dropdown_status( $args ) {
		if ( ! isset( $args['post_status'] ) || ! is_array( $args['post_status'] ) ) {
			$args['post_status'] = array( 'publish' );
		}

		$args['post_status'][] = 'tao_sc_publish';

		return $args;
	}

	/**
	 * Adds post's state to 'scheduled updates'-posts.
	 *
	 * @param array $states Array of post states.
	 *
	 * @global $post
	 */
	public static function display_post_states( $states ) {
		global $post;
		$arg = get_query_var('post_status');
		$the_post_types = self::get_post_types();
		// default states for non public posts.
		if ( ! isset( $the_post_types[ $post->post_type ] ) ) {
			return $states;
		}
		$type = $the_post_types[ $post->post_type ];

		if ( $arg !== self::$tao_publish_label && $post->post_status === self::$_tao_publish_status ) {
			$states = array( self::$tao_publish_label );
			if ( ! $type->hierarchical ) {
				$orig = get_post(get_post_meta($post->ID, self::$_tao_publish_status . '_original', true));
				array_push( $states, __( 'Original', 'tao-scheduleupdate-td' ) . ': ' . $orig->post_title );
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
	 * @param array $actions Array of available actions added by previous hooks.
	 * @param post $post    the post for which to add actions.
	 *
	 * @return array Array of available actions for the given post
	 */
	public static function page_row_actions( $actions, $post ) {
		$copy = '?action=workflow_copy_to_publish&post=' . $post->ID . '&n=' . wp_create_nonce('workflow_copy_to_publish' . $post->ID);
		if ( $post->post_status === self::$_tao_publish_status ) {
			$action = '?action=workflow_publish_now&post=' . $post->ID . '&n=' . wp_create_nonce('workflow_publish_now' . $post->ID);
			$actions['publish_now'] = '<a href="' . admin_url('admin.php' . $action) . '">' . __('Publish Now', 'tao-scheduleupdate-td') . '</a>';
			if ( ScheduledUpdate_Options::get( 'tsu_recursive' ) ) {
				$actions['copy_to_publish'] = '<a href="' . admin_url('admin.php' . $copy) . '">' . __('Schedule recursive', 'tao-scheduleupdate-td') . '</a>';
			}
		} elseif ( 'trash' !== $post->post_status ) {
			$actions['copy_to_publish'] = '<a href="' . admin_url('admin.php' . $copy) . '">' . self::$tao_publish_label . '</a>';
		}

		return $actions;
	}


	/**
	 * Adds a column to the pages overview.
	 *
	 * @param array $columns Array of available columns added by previous hooks.
	 *
	 * @return array Array of available columns
	 */
	public static function manage_pages_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $val ) {
			$new[ $key ] = $val;
			if ( 'title' === $key ) {
				$new['tao_publish'] = esc_html__('Releasedate', 'tao-scheduleupdate-td');
			}
		}
		return $new;
	}


	/**
	 * Manages the content of previously added custom columns.
	 *
	 * @see ScheduledUpdate::manage_pages_columns()
	 *
	 * @param string $column  Name of the column.
	 * @param int    $post_id id of the current post.
	 *
	 * @return void
	 */
	public static function manage_pages_custom_column( $column, $post_id ) {
		if ( 'tao_publish' === $column ) {
			$stamp = get_post_meta($post_id, self::$_tao_publish_status . '_pubdate', true);

			if ( $stamp ) {
				echo esc_html(self::get_pubdate($stamp));
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
		if ( isset( $_REQUEST['n'], $_REQUEST['post'] ) && wp_verify_nonce(sanitize_key($_REQUEST['n']), 'workflow_copy_to_publish' . absint($_REQUEST['post']))) {
			$post = get_post(absint(wp_unslash($_REQUEST['post'])));
			$publishing_id = self::create_publishing_post( $post );
			if ( $publishing_id ) {
				wp_redirect(admin_url('post.php?action=edit&post=' . $publishing_id));
			} else {
				// translators: %1$s: post type, %2$s: post title.
				$html  = sprintf( __( 'Could not schedule %1$s %2$s', 'tao-scheduleupdate-td' ), $post->post_type, '<i>' . htmlspecialchars( $post->post_title ) . '</i>' );
				$html .= '<br><br>';
				$html .= '<a href="' . esc_attr(admin_url('edit.php?post_type=' . $post->post_type)) . '">' . __('Back') . '</a>';
				wp_die($html); // WPCS: XSS okay.
			}
		}
	}

	/**
	 * Handles the admin action workflow_publish_now
	 *
	 * @return void
	 */
	public static function admin_action_workflow_publish_now() {
		if ( isset( $_REQUEST['n'], $_REQUEST['post'] ) && wp_verify_nonce(sanitize_key($_REQUEST['n']), 'workflow_publish_now' . absint($_REQUEST['post']))) {
			$post = get_post(absint(wp_unslash($_REQUEST['post'])));
			self::publish_post( $post->ID );
			wp_redirect(admin_url('edit.php?post_type=' . $post->post_type));
		}
	}


	/**
	 * Adds the 'scheduled update'-metabox to the edit-page screen.
	 *
	 * @param string $post_type The post type of the post being edited.
	 * @param post   $post The post being currently edited.
	 *
	 * @return void
	 *@see add_meta_box
	 *
	 */
	public static function add_meta_boxes_page( $post_type, $post ) {
		if ( $post->post_status !== self::$_tao_publish_status ) {
			return;
		}

		// hides everything except the 'publish' button in the 'publish'-metabox
		echo '<style> #duplicate-action, #delete-action, #minor-publishing-actions, #misc-publishing-actions, #preview-action {display:none;} </style>'; // WPCS: XSS okay.

		wp_enqueue_script('jquery-ui-datepicker');
        $url = '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/blitzer/jquery-ui.min.css';
		wp_enqueue_style('jquery-ui-blitzer', $url);
        wp_enqueue_script(self::$_tao_publish_status . '-datepicker.js', plugins_url('js/publish-datepicker.js', __FILE__), array('jquery-ui-datepicker'));

        $months = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$months[] = date_i18n('F', strtotime('2014-' . $i . '-01 00:00:00'));
        }
		$days = array();
		for ( $i = 23;$i <= 29;$i++ ) {
			$days[] = date_i18n('D', strtotime('2014-03-' . $i . ' 00:00:00'));
        }

		// Get WP date format and make it usable in the datepicker.
		$df = get_option('date_format');
        $df = str_replace(
			array( 'd',  'j', 'S', 'l',  'D', 'm',  'n', 'F',  'M', 'Y',  'y', 'c',        'r',         'U' ),
			array( 'dd', 'd', '',  'DD', 'D', 'mm', 'm', 'MM', 'M', 'yy', 'y', 'yy-mm-dd', 'D, d M yy', '@' ),
			$df
		);

		$js_data = array(
			'datepicker' => array(
				'daynames'   => $days,
				'monthnames' => $months,
				'elementid'  => self::$_tao_publish_status . '_pubdate',
				'displayid'  => self::$_tao_publish_status . '_pubdate_display',
				'dateformat' => $df,
			),
			'text' => array(
				'save' => __('Save'),
            ),
		);

		wp_localize_script(self::$_tao_publish_status . '-datepicker.js', 'TAOScheduleUpdate', $js_data);

        add_meta_box('meta_' . self::$_tao_publish_status, self::$_tao_publish_metabox, array('ScheduledUpdate', 'create_meta_box'), $post_type, 'side');
    }

	/**
	 * Creates the HTML-Code for the 'scheduled update'-metabox
	 *
	 * @param post $post The post being currently edited.
	 *
	 * @return void
	 */
	public static function create_meta_box( $post ) {
		wp_nonce_field(basename(__FILE__), self::$_tao_publish_status . '_nonce');
        $metaname = self::$_tao_publish_status . '_pubdate';
		$stamp = get_post_meta($post->ID, $metaname, true);
        $date = '';
		$time = '';
		$offset = get_option('gmt_offset') * 3600;
		$dateo = new DateTime('now', self::get_timezone_object() );
		if ( $stamp ) {
			$dateo->setTimestamp( $stamp );
		}
		$time = $dateo->format( 'H:i' );
		$date = date_i18n(get_option('date_format'), $dateo->getTimestamp() + $offset);
        $date2 = $dateo->format( 'd.m.Y' );

		if ( ! $stamp && ScheduledUpdate_Options::get( 'tsu_nodate' ) === 'nothing' ) {
			$date = '';
		}
		$dec_time = floatval( get_option( 'gmt_offset' ) );
		$gmt_hour = floor( $dec_time );
		$gmt_min = round( 60 * ($dec_time -$gmt_hour) );
?>
			<p>
				<strong><?php esc_html_e('Releasedate', 'tao-scheduleupdate-td'); ?></strong>
			</p>
			<label class="screen-reader-text" for="<?php echo esc_attr($metaname); ?>"><?php esc_html_e('Releasedate', 'tao-scheduleupdate-td'); ?></label>
			<input type="hidden" name="<?php echo esc_attr($metaname); ?>" id="<?php echo esc_attr($metaname); ?>" value="<?php echo esc_attr($date2); ?>"/>
			<input type="text" class="widefat" name="<?php echo esc_attr($metaname); ?>_display" id="<?php echo esc_attr($metaname); ?>_display" value="<?php echo esc_attr($date); ?>"/>
			<p>
				<strong><?php esc_html_e('Time', 'tao-scheduleupdate-td'); ?></strong>
			</p>
			<label class="screen-reader-text" for="<?php echo esc_attr($metaname); ?>_time"><?php esc_html_e('Time', 'tao-scheduleupdate-td'); ?></label>
			<select name="<?php echo esc_attr($metaname); ?>_time_hrs" id="<?php echo esc_attr($metaname); ?>_time">
				<?php for ( $i = 0; $i < 24; $i++ ) : ?>
				<option value="<?php echo esc_attr(sprintf('%02d', $i)); ?>" <?php echo intval( $dateo->format( 'H' ) ) === $i ? 'selected' : ''; ?>><?php echo esc_html(sprintf('%02d', $i)); ?></option>
				<?php endfor; ?>
			</select>:
			<select name="<?php echo esc_attr($metaname); ?>_time_mins">
				<?php for ( $i = 0; $i < 60; $i += 5 ) : ?>
				<option value="<?php echo esc_attr(sprintf('%02d', $i)); ?>" <?php echo intval( ceil( $dateo->format( 'i' ) / 10 ) * 10 ) === $i ? 'selected' : ''; ?>><?php echo esc_html(sprintf('%02d', $i)); ?></option>
				<?php endfor; ?>
			</select>
			<input type="hidden" name="tao_added_minutes" id="tao_used_gmt" value="<?php echo esc_attr($gmt_hour >= 0 ? '+' : '-');
            echo esc_attr(sprintf('%02d', $gmt_hour) . ':' . sprintf('%02d', $gmt_min)) ?>">
			<p>
				<?php
				// translators: timezone placeholder
				echo sprintf( __( 'Please enter <i>Time</i> as %s', 'tao-scheduleupdate-td' ), self::get_timezone_string() ); // WPCS: XSS okay.
				?>
			</p>
			<p>
				<div id="pastmsg" style="color:red; display:none;">
					<?php
					echo esc_html__('The releasedate is in the past.', 'tao-scheduleupdate-td');
                    if ( ScheduledUpdate_Options::get( 'tsu_nodate' ) === 'nothing' ) {
						echo esc_html__('This post will not be published.', 'tao-scheduleupdate-td');
                    } else {
						echo esc_html__('This post will be published 5 minutes from now.', 'tao-scheduleupdate-td');
                    }
?>
				</div>
			</p>
		<?php
	}


	/**
	 * Gets the currently set timezone..
	 *
	 * Retreives either the timezone_string or the gmt_offset.
	 *
	 * @see get_option
	 *
	 * @access private
	 *
	 * @return string The set timezone
	 */
	private static function get_timezone_string() {
		$current_offset = get_option('gmt_offset');
        $tzstring = get_option('timezone_string');

        $check_zone_info = true;

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
			$tzstring = '';
		}

		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
			$check_zone_info = false;
			if ( 0 === $current_offset ) {
				$tzstring = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$tzstring = 'UTC' . $current_offset;
			} else {
				$tzstring = 'UTC+' . $current_offset;
			}
		}

		return $tzstring;
	}

	/**
	 * Creates a timezone object based on the option gmt_offset
	 *
	 * @return DateTimeZone timezone specified by the gmt_offset option
	 *@see DateTimeZone
	 *
	 */
	private static function get_timezone_object() {
		$offset = intval( get_option( 'gmt_offset' ) * 3600 );
		$ids = DateTimeZone::listIdentifiers();
		foreach ( $ids as $timezone ) {
			$tzo = new DateTimeZone($timezone );
			$dt = new DateTime('now', $tzo );
			if ( $tzo->getOffset( $dt ) === $offset ) {
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
	 * @param string $new_status the post's new status.
	 * @param string $old_status the post's old status.
	 * @param post $post       the post changing status.
	 *
	 * @return void
	 */
	public static function prevent_status_change( $new_status, $old_status, $post ) {
		if ( $new_status === $old_status && $new_status === self::$_tao_publish_status ) { return;
		}

		if ( $old_status === self::$_tao_publish_status && 'trash' !== $new_status ) {
			remove_action('save_post', array('ScheduledUpdate', 'save_meta'), 10);

            $post->post_status = self::$_tao_publish_status;
			$u = wp_update_post($post, true);

            add_action('save_post', array('ScheduledUpdate', 'save_meta'), 10, 2);
        } elseif ( 'trash' === $new_status ) {
			wp_clear_scheduled_hook('tao_publish_post', array(
                'ID' => $post->ID,
            ));
        } elseif ( 'trash' === $old_status && $new_status === self::$_tao_publish_status ) {
			wp_schedule_single_event(get_post_meta($post->ID, self::$_tao_publish_status . '_pubdate', true), 'tao_publish_post', array(
                'ID' => $post->ID,
            ));
        }
	}

	/**
	 * Copies an entire post and sets it's status to 'scheduled update'
	 *
	 * @param post $post the post to be copied.
	 *
	 * @return int ID of the newly created post
	 */
	public static function create_publishing_post( $post ) {

		$new_author = wp_get_current_user();

        $original = $post->ID;
		if ( $post->post_status === self::$_tao_publish_status ) {
			$original = get_post_meta($post->ID, self::$_tao_publish_status . '_original', true);
        }

		// create the new post.
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
			'post_status'    => self::$_tao_publish_status,
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
		);

		// insert the new post.
		$new_post_id = wp_insert_post($new_post);

        // copy meta and terms over to the new post.
		self::copy_meta_and_terms( $post->ID, $new_post_id );

		// and finally referencing the original post.
		update_post_meta($new_post_id, self::$_tao_publish_status . '_original', $original);

        /**
		 * Fires when a post has been duplicated.
		 *
		 * @param int     $new_post_id ID of the newly created post.
		 * @param int     $original    ID of the original post.
		 */
		do_action('ScheduledUpdate\\create_publishing_post', $new_post_id, $original);

        return $new_post_id;
	}

	/**
	 * Copies meta and terms from one post to another
	 *
	 * @param int $source_post_id      the post from which to copy.
	 * @param int $destination_post_id the post which will get the meta and terms.
	 *
	 * @return void
	 */
	public static function copy_meta_and_terms( $source_post_id, $destination_post_id ) {

		$source_post = get_post($source_post_id);
        $destination_post = get_post($destination_post_id);

        // abort if any of the ids is not a post.
		if ( ! $source_post || ! $destination_post ) { return;
		}

		/*
		 * remove all meta from the destination,
		 * initialize to emptyarray if not set to prevent error in foreach loop
		 */
		$dest_keys = get_post_custom_keys($destination_post->ID) ?: array();
		foreach ( $dest_keys as $key ) {
			delete_post_meta($destination_post->ID, $key);
        }

		// now for copying the metadata to the new post.
		$meta_keys = get_post_custom_keys($source_post->ID) ?: array();
		foreach ( $meta_keys as $key ) {
			$meta_values = get_post_custom_values($key, $source_post->ID);
            foreach ($meta_values as $value ) {
				$value = maybe_unserialize($value);
                add_post_meta($destination_post->ID, $key, $value);
            }
		}

		// and now for copying the terms.
		$taxonomies = get_object_taxonomies($source_post->post_type);
        foreach ($taxonomies as $taxonomy ) {
			$post_terms = wp_get_object_terms($source_post->ID, $taxonomy, array(
                'orderby' => 'term_order',
            ));
            $terms = array();
			foreach ( $post_terms as $term ) {
				$terms[] = $term->slug;
			}
			// reset taxonomy to empty.
			wp_set_object_terms($destination_post->ID, null, $taxonomy);
            // then add new terms.
			$what = wp_set_object_terms($destination_post->ID, $terms, $taxonomy);
        }

	}


	/**
	 * Saves a post's publishing date.
	 *
	 * @param int  $post_id the post's id.
	 * @param post $post    the post being saved.
	 *
	 * @return mixed
	 */
	public static function save_meta( $post_id, $post ) {
		if ( $post->post_status === self::$_tao_publish_status || get_post_meta($post_id, self::$_tao_publish_status . '_original', true)) {
			$nonce = ScheduledUpdate::$_tao_publish_status . '_nonce';
			$pub = ScheduledUpdate::$_tao_publish_status . '_pubdate';
			$stampchange = false;

			if ( isset( $_POST[ $nonce ] ) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce])), basename(__FILE__)) !== 1 ) {
				return $post_id;
			}
			if ( ! current_user_can(get_post_type_object($post->post_type)->cap->edit_post, $post_id)) {
				return $post_id;
			}

			if ( isset( $_POST[ $pub ] ) && isset( $_POST[ $pub . '_time_hrs' ] ) && isset( $_POST[ $pub . '_time_mins' ] ) && ! empty( $_POST[ $pub ] ) ) {
				$tz = self::get_timezone_object();
				$stamp = DateTime::createFromFormat( 'd.m.Y H:i', sanitize_text_field(wp_unslash($_POST[$pub])) . ' ' . sanitize_text_field(wp_unslash($_POST[$pub . '_time_hrs'])) . ':' . sanitize_text_field(wp_unslash($_POST[$pub . '_time_mins'])), $tz )->getTimestamp(); // WPCS: XSS okay.
				if ( ! $stamp || $stamp <= time() ) {
					$stamp = strtotime( '+5 minutes' );
					$stampchange = true;
				}

				wp_clear_scheduled_hook('tao_publish_post', array(
                    'ID' => $post_id,
                ));
                if ( ! $stampchange || ScheduledUpdate_Options::get( 'tsu_nodate' ) === 'publish' ) {
					update_post_meta($post_id, $pub, $stamp);
                    wp_schedule_single_event($stamp, 'tao_publish_post', array(
                        'ID' => $post_id,
                    ));
                }
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
	 * @param int $post_id the post's id.
	 *
	 * @return int the original post's id
	 */
	public static function publish_post( $post_id ) {

		$orig_id = get_post_meta($post_id, self::$_tao_publish_status . '_original', true);
        // break early if given post is not an actual scheduled post created by this plugin.
		if ( ! $orig_id ) {
			return $post_id;
		}

		$orig = get_post($orig_id);

        $post = get_post($post_id);

        /**
		 * Fires before a scheduled post is being updated
		 *
		 * @param WP_Post $post the scheduled update post.
		 * @param WP_post $orig the original post.
		 */
		do_action('ScheduledUpdate\\before_publish_post', $post, $orig);

        self::copy_meta_and_terms( $post->ID, $orig->ID );

		$post->ID = $orig->ID;
		$post->post_name = $orig->post_name;
		$post->guid = $orig->guid;
		$post->post_parent = $orig->post_parent;
		$post->post_status = $orig->post_status;
		$post_date = date_i18n('Y-m-d H:i:s');

        /**
		 * Filter the new posts' post date
		 *
		 * @param string  $post_date the date to be used, must be in the form of `Y-m-d H:i:s`.
		 * @param WP_Post $post      the scheduled update post.
		 * @param WP_Post $orig      the original post.
		 */
		$post_date = apply_filters('ScheduledUpdate\\publish_post_date', $post_date, $post, $orig);

        $post->post_date = $post_date; // we need this to get wp to recognize this as a newly updated post.
		$post->post_date_gmt = get_gmt_from_date($post_date);

        delete_post_meta($orig->ID, self::$_tao_publish_status . '_original');
        delete_post_meta($orig->ID, self::$_tao_publish_status . '_pubdate');

        wp_update_post($post);
        wp_delete_post($post_id, true);

        return $orig->ID;
	}

	/**
	 * Wrapper function for cron automated publishing
	 * disables the kses filters before and re-enables them after the post has been published
	 *
	 * @param int $post_id the post's id.
	 *
	 * @return void
	 */
	public static function cron_publish_post( $post_id ) {
		kses_remove_filters();
        self::publish_post( $post_id );
		kses_init_filters();
    }


	/**
	 * Reformats a timestamp into human-readable publishing date and time
	 *
	 * @see date_i18n, DateTime, ScheduledUpdate::get_timezone_object
	 *
	 * @param int $stamp unix timestamp to be formatted.
	 *
	 * @return string the formatted timestamp
	 */
	public static function get_pubdate( $stamp ) {
		$date = new DateTime('now', self::get_timezone_object() );
		$date->setTimestamp( $stamp );
		$offset = get_option('gmt_offset') * 3600;
		$str = date_i18n(get_option('date_format') . ' ' . get_option('time_format') . ' \U\T\CO', $date->getTimestamp() + $offset);
        return $str;
	}

}

add_action( 'save_post', array( 'ScheduledUpdate', 'save_meta' ), 10, 2 );
add_action( 'tao_publish_post', array( 'ScheduledUpdate', 'cron_publish_post' ) );

add_action( 'wp_ajax_load_pubdate', array( 'ScheduledUpdate', 'load_pubdate' ) );
add_action( 'init', array( 'ScheduledUpdate', 'init' ), PHP_INT_MAX );
add_action( 'admin_action_workflow_copy_to_publish', array( 'ScheduledUpdate', 'admin_action_workflow_copy_to_publish' ) );
add_action( 'admin_action_workflow_publish_now', array( 'ScheduledUpdate', 'admin_action_workflow_publish_now' ) );
add_action( 'transition_post_status', array( 'ScheduledUpdate', 'prevent_status_change' ), 10, 3 );

add_filter( 'display_post_states', array( 'ScheduledUpdate', 'display_post_states' ) );
add_filter( 'page_row_actions', array( 'ScheduledUpdate', 'page_row_actions' ), 10, 2 );
add_filter( 'post_row_actions', array( 'ScheduledUpdate', 'page_row_actions' ), 10, 2 );
add_filter( 'manage_pages_columns', array( 'ScheduledUpdate', 'manage_pages_columns' ) );
add_filter( 'page_attributes_dropdown_pages_args', array( 'ScheduledUpdate', 'parent_dropdown_status' ) );
