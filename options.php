<?php

class TAO_ScheduleUpdate_Options {

	protected static $TAO_PUBLISH_OPTIONS = array();

	public static function init() {
		register_setting( 'tao_schedule_update', 'tsu_options' );

		add_settings_section(
			'tsu_section',
			'',
			'intval',
			'tsu'
		);

		add_settings_field(
			'tsu_field_nodate',
			__( 'No Date Set', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ),
			array( __CLASS__, 'field_nodate_cb' ),
			'tsu',
			'tsu_section',
			array(
				'label_for' => 'tsu_nodate',
				'class'     => 'tsu_row',
			)
		);

		add_settings_field(
			'tsu_field_visible',
			__( 'Posts Visible', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ),
			array( __CLASS__, 'field_visible_cb' ),
			'tsu',
			'tsu_section',
			array(
				'label_for' => 'tsu_visible',
				'class'     => 'tsu_row',
			)
		);

		add_settings_field(
			'tsu_field_recursive',
			__( 'Recursive scheduling', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ),
			array( __CLASS__, 'field_recursive_cb' ),
			'tsu',
			'tsu_section',
			array(
				'label_for' => 'tsu_recursive',
				'class'     => 'tsu_row',
			)
		);
	}

	public static function load_options() {
		self::$TAO_PUBLISH_OPTIONS = get_option( 'tsu_options' );
	}

	public static function get( $optname ) {
		if ( isset( self::$TAO_PUBLISH_OPTIONS[$optname] ) ) {
			return self::$TAO_PUBLISH_OPTIONS[$optname];
		}
		return null;
	}

	public static function options_page() {
		// add top level menu page
		add_options_page(
			TAO_ScheduleUpdate::$TAO_PUBLISH_LABEL,
			TAO_ScheduleUpdate::$TAO_PUBLISH_LABEL,
			'manage_options',
			'tsu',
			array( __CLASS__, 'options_page_html' )
		);
	}

	public static function field_nodate_cb( $args ) {
		$options = get_option( 'tsu_options' );
?>
		<select id="<?php echo esc_attr( $args['label_for'] ); ?>"
		        name="tsu_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		>
			<option value="publish" <?php echo isset( $options[$args[ 'label_for']] ) ? ( selected( $options[$args[ 'label_for']], 'publish', false ) ) : ( '' ); ?>>
				<?php echo esc_html( __( 'Publish right away', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ) ); ?>
			</option>
			<option value="nothing" <?php echo isset( $options[$args[ 'label_for']] ) ? ( selected( $options[$args[ 'label_for']], 'nothing', false ) ) : ( '' ); ?>>
				<?php echo esc_html( __( 'Don\'t publish', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ) ); ?>
			</option>
		</select>
		<p class="description">
			<?php echo esc_html( __( 'What should happen to a post if it is saved with no date set?', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ) ); ?>
		</p>

		<?php
	}

	public static function field_visible_cb( $args ) {
		$options = get_option( 'tsu_options' );

		$checked = '';
		if ( isset( $options[ $args['label_for']] ) ) {
			$checked = 'checked="checked"';
		}
?>
		<label for="<?php echo esc_attr( $args['label_for'] ); ?>">
			<input id="<?php echo esc_attr( $args['label_for'] ); ?>"
			       type="checkbox"
			       name="tsu_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			       <?php echo $checked ?>
			>
			<?php echo esc_html( __( 'Scheduled posts are visible for anonymous users in the frontend', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ) ); ?>
		</label>
		<?php
	}

	public static function field_recursive_cb( $args ) {
		$options = get_option( 'tsu_options' );

		$checked = '';
		if ( isset( $options[ $args['label_for']] ) ) {
			$checked = 'checked="checked"';
		}
?>
		<label for="<?php echo esc_attr( $args['label_for'] ); ?>">
			<input id="<?php echo esc_attr( $args['label_for'] ); ?>"
			       type="checkbox"
			       name="tsu_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			       <?php echo $checked ?>
			>
			<?php echo esc_html( __( 'Allow recursive scheduling', TAO_ScheduleUpdate::$TAO_PUBLISH_TEXTDOMAIN ) ); ?>
		</label>
		<?php
	}

	public static function options_page_html() {
		// check user capabilities
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		// show error/update messages
		settings_errors( 'tsu_messages' );
?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'tao_schedule_update' ); ?>

				<?php do_settings_sections( 'tsu' ); ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

}

add_action( 'admin_init', create_function( '', 'return TAO_ScheduleUpdate_Options::init();' ) );
add_action( 'admin_menu', create_function( '', 'return TAO_ScheduleUpdate_Options::options_page();' ) );
//since this file gets included inside a `init` callback we can just call this function straight out
TAO_ScheduleUpdate_Options::load_options();