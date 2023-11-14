<?php
/**
 * Options page and database layer for saving/loading options
 *
 * @package PostScheduler
 */

namespace PostScheduler;
/**
 * TAO Schedule Update options class
 */
class ScheduledUpdate_Options {

	/**
	 * Holds all the options
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $_tao_publish_options = array();

	/**
	 * Registers all needed options via the wordpress settings API
	 *
	 * @see register_settig, add_settings_section, add_settings_field
	 *
	 * @return void
	 */
	public static function init() {
		register_setting('tao_schedule_update', 'tsu_options');

		add_settings_section(
            'tsu_section',
            '',
            'intval',
            'tsu'
        );

		add_settings_field(
            'tsu_field_nodate',
            __('No Date Set', 'tao-scheduleupdate-td'),
            array(__CLASS__, 'field_nodate_cb'),
            'tsu',
            'tsu_section',
            array(
                'label_for' => 'tsu_nodate',
                'class' => 'tsu_row',
            )
        );

		add_settings_field(
            'tsu_field_visible',
            __('Posts Visible', 'tao-scheduleupdate-td'),
            array(__CLASS__, 'field_visible_cb'),
            'tsu',
            'tsu_section',
            array(
                'label_for' => 'tsu_visible',
                'class' => 'tsu_row',
            )
        );

		add_settings_field(
            'tsu_field_recursive',
            __('Recursive scheduling', 'tao-scheduleupdate-td'),
            array(__CLASS__, 'field_recursive_cb'),
            'tsu',
            'tsu_section',
            array(
                'label_for' => 'tsu_recursive',
                'class' => 'tsu_row',
            )
        );
	}

	/**
	 * Loads the saved options from the database
	 *
	 * @return void
	 */
	public static function load_options() {
		self::$_tao_publish_options = get_option('tsu_options');
	}

	/**
	 * Get a option value
	 *
	 * @param string $optname name of the option.
	 *
	 * @return mixed Value of the requested option
	 */
	public static function get( $optname ) {
		if ( isset( self::$_tao_publish_options[ $optname ] ) ) {
			return self::$_tao_publish_options[ $optname ];
		}
		return null;
	}

	/**
	 * Registers the option page within wordpress
	 *
	 * @see add_options_page
	 *
	 * @return void
	 */
	public static function options_page() {
		// add top level menu page.
		add_options_page(
            ScheduledUpdate::$tao_publish_label,
            ScheduledUpdate::$tao_publish_label,
            'manage_options',
            'Post Scheduled Updates',
            array(__CLASS__, 'options_page_html')
        );
	}

	/**
	 * Renders the settings field for `nodate`
	 *
	 * @param array $args array of arguments, passed by do_settings_fields.
	 *
	 * @return void
	 */
	public static function field_nodate_cb( $args ) {
		$options = get_option('tsu_options');
?>
		<select id="<?php echo esc_attr($args['label_for']); ?>"
				name="tsu_options[<?php echo esc_attr($args['label_for']); ?>]"
		>
			<option value="publish" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected($options[$args['label_for']], 'publish', false)) : ( '' ); ?>>
				<?php echo esc_html(__('Publish right away', 'tao-scheduleupdate-td')); ?>
			</option>
			<option value="nothing" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected($options[$args['label_for']], 'nothing', false)) : ( '' ); ?>>
				<?php echo esc_html(__('Don\'t publish', 'tao-scheduleupdate-td')); ?>
			</option>
		</select>
		<p class="description">
			<?php echo esc_html(__('What should happen to a post if it is saved with no date set?', 'tao-scheduleupdate-td')); ?>
		</p>

		<?php
	}

	/**
	 * Renders the settings field for `visible`
	 *
	 * @param array $args array of arguments, passed by do_settings_fields.
	 *
	 * @return void
	 */
	public static function field_visible_cb( $args ) {
		$options = get_option('tsu_options');

		$checked = '';
		if ( isset( $options[ $args['label_for'] ] ) ) {
			$checked = 'checked="checked"';
		}
?>
		<label for="<?php echo esc_attr($args['label_for']); ?>">
			<input id="<?php echo esc_attr($args['label_for']); ?>"
				   type="checkbox"
				   name="tsu_options[<?php echo esc_attr($args['label_for']); ?>]"
					<?php echo $checked; // WPCS: XSS okay. ?>
			>
			<?php echo esc_html(__('Scheduled posts are visible for anonymous users in the frontend', 'tao-scheduleupdate-td')); ?>
		</label>
		<?php
	}

	/**
	 * Renders the settings field for `recursive`
	 *
	 * @param array $args array of arguments, passed by do_settings_fields.
	 *
	 * @return void
	 */
	public static function field_recursive_cb( $args ) {
		$options = get_option('tsu_options');

		$checked = '';
		if ( isset( $options[ $args['label_for'] ] ) ) {
			$checked = 'checked="checked"';
		}
?>
		<label for="<?php echo esc_attr($args['label_for']); ?>">
			<input id="<?php echo esc_attr($args['label_for']); ?>"
				   type="checkbox"
				   name="tsu_options[<?php echo esc_attr($args['label_for']); ?>]"
					<?php echo $checked; // WPCS: XSS okay. ?>
			>
			<?php echo esc_html(__('Allow recursive scheduling', 'tao-scheduleupdate-td')); ?>
		</label>
		<?php
	}

	/**
	 * Renders the options page html
	 *
	 * @return void
	 */
	public static function options_page_html() {
		// check user capabilities.
		if ( ! current_user_can('manage_options')) {
			return;
		}

		// show error/update messages.
		settings_errors('tsu_messages');
?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields('tao_schedule_update'); ?>

				<?php do_settings_sections('tsu'); ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

}

add_action( 'admin_init', array( 'ScheduledUpdate_Options', 'init' ) );
add_action( 'admin_menu', array( 'ScheduledUpdate_Options', 'options_page' ) );
// since this file gets included inside a `init` callback we can just call this function straight out.
ScheduledUpdate_Options::load_options();
