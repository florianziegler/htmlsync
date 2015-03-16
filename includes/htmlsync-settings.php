<?php defined( 'ABSPATH' ) OR exit;

/**
 * @package htmlsync
 * @since 0.0.1
 */



/**
 * Register settings
 *
 * @since 0.1.0
 */

add_action( 'admin_init', 'htmlsync_register_settings' );

function htmlsync_register_settings() {
    // Plugin settings
    register_setting( 'htmlsync_settings', 'htmlsync_settings', 'htmlsync_settings_validate' );
}


/**
 * Validate settings
 *
 * @since 0.0.1
 */

function htmlsync_settings_validate( $args ) {

    // Check if path exists
    if ( ! empty( $args['filepath'] ) AND is_dir( $args['filepath'] ) ) {
        return $args;
    }
    else {
        // Unset if it does not validate
        unset( $args['filepath'] );

        // Define the settings error to display
        add_settings_error(
          'filepath',
          'invalid_filepath',
          __( 'The path you provided does not exist.', 'htmlsync' ),
          'error'
        );
    }
}


/**
 * Register settings menu 
 *
 * @since 0.0.1
 */

add_action( 'admin_menu', 'htmlsync_menu' );

function htmlsync_menu() {
    add_options_page(
        'HTML Sync',
        'HTML Sync',
        'manage_options',
        'htmlsync',
        'htmlsync_load_menu_page'
    );
}


/**
 * Content of the settings page
 * 
 * @since 0.0.1
 */

function htmlsync_load_menu_page() {
    ?>
    <div class="wrap">
        <h2><?php _e( 'HTML Sync Settings', 'htmlsync' ); ?></h2>
        <form class="htmlsync-settings-form" method="post" action="options.php" enctype="multipart/form-data">
            <?php
                settings_fields( 'htmlsync_settings' );
                $options = get_option( 'htmlsync_settings' );
            ?>
            <p><label style="display: block; margin-bottom: 5px;" for="htmlsync_filepath"><?php _e( 'Enter the path where the files are located on your server', 'htmlsync' ); ?>:</label> <input style="width: 50%" type="text" id="htmlsync_filepath" name="htmlsync_settings[filepath]" value="<?php if ( isset( $options['filepath'] ) && $options['filepath'] != '' ) { echo $options['filepath']; } ?>" /></p>
            <p><input type="submit" name="htmlsync-save-settings" value="<?php _e( 'Save Settings', 'htmlsync'); ?>" class="button button-primary" /></p>
        </form>
    </div>

<?php }