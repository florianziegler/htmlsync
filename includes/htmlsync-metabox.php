<?php defined( 'ABSPATH' ) OR exit;
/**
 * HTML Sync Meta Box
 *
 * @package htmlsync
 * @since 0.0.1
 */


/**
 * Register the metabox
 *
 * @since 0.0.1
 */

function htmlsync_add_metabox() {

    $current_user = wp_get_current_user();
    if ( ! ( $current_user instanceof WP_User ) OR ! current_user_can( 'edit_post', $post_id ) ) { return; }
    
    // Check the permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    add_meta_box(
        'htmlsync-submit-metabox',
        __( 'HTML Sync', 'htmlsync' ),
        'htmlsync_metabox',
        'page',
        'normal',
        'high'
    );

}

add_action( 'add_meta_boxes', 'htmlsync_add_metabox' );



/**
 * Build the metabox
 *
 * @since 0.0.1
 */

function htmlsync_metabox( $post ) {

    $post_id = $post->ID;

    wp_nonce_field( 'htmlsync_metabox', 'htmlsync_metabox_nonce' );

    // Load filename (if it was set before)
    $htmlsync_filename = get_post_meta( $post_id, '_htmlsync_filename', true );

    // Load files

    // Check if filepath is set and path exists
    $settings = get_option( 'htmlsync_settings' );
    if ( isset( $settings['filepath'] ) AND ! empty( $settings['filepath'] ) AND is_dir( $settings['filepath'] ) ) {
        $filepath = $settings['filepath'];

    $files_temp = scandir( $filepath );
    // Get rid of the dots
    $files_temp = array_diff( $files_temp, array( '..', '.' ) );

    // Only use html files
    foreach ( $files_temp as $file ) {
        if ( pathinfo( $file, PATHINFO_EXTENSION ) == 'html' ) {
            $files[] = $file;
        }
    }

    // Get all assigned files
    global $wpdb;
    $assigned_files = $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_htmlsync_filename'" );
    // Filter the file, which is assigned to the current post
    $assigned_files = array_diff( $assigned_files, array( $htmlsync_filename ) );

    // Check, if there are files for assigning left
    $temp = array_diff( $files, $assigned_files );

    if ( empty ( $temp) ) {

         echo '<p>' . __( 'All files are already assigned.', 'htmlsync' ) . '</p>';

    }
    else {

        $options = '';

        foreach ( $files as $filename ) {

            if ( ! in_array( $filename, $assigned_files ) ) {

                $options .= '<option value="' . $filename . '"';
                if ( $filename == $htmlsync_filename ) {
                    $options .= ' selected="selected"';
                }
                $options .= '>' . $filename . ' &ndash; updated: ' . date ("d.m.Y, H:i:s", filemtime( $filepath . '/' . $filename ) ) . '</option>';
            }

        } ?>

        <div class="htmlsync-metabox-inside">
            <p><?php _e( 'The content of the file you choose beneath, will replace the content of this post.', 'htmlsync'); ?></p>
            <p><label for="htmlsync-filename"><strong><?php _e( 'Load content from this file', 'htmlsync' ); ?></strong></label>
                <select name="htmlsync-filename" id="htmlsync-filename">
                    <option value=""><?php _e( 'none', 'htmlsync' ); ?></option>
                    <?php echo $options; ?>
                </select></p>
        </div>

<?php }
    } else {
        echo '<p>' . __( 'File path is either not defined or does not exist. <a href="options-general.php?page=htmlsync">Set a working path.</a>', 'htmlsync' ) . '</p>';
    }
}



/**
 * Save Post Meta Info on save
 *
 * @since 0.0.1
 */

function htmlsync_save_post_meta( $post_id ) {

    // Check if nonce is set.
    if ( ! isset( $_POST['htmlsync_metabox_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['htmlsync_metabox_nonce'], 'htmlsync_metabox' ) ) {
        return;
    }

    // Don't do anything, if this is an autosave or revision
    if ( ( wp_is_post_revision( $post_id) OR wp_is_post_autosave( $post_id ) ) ) {
        return;
    }

    // Check the permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Check if there is a file name.
    if ( ! isset( $_POST['htmlsync-filename'] ) ) {
        return;
    }

    // Get the file path.
    $settings = get_option( 'htmlsync_settings' );
    $filepath = $settings['filepath'];

    // Check if the file exists.
    if ( realpath( $filepath . '/' . htmlspecialchars( $_POST['htmlsync-filename'] ) ) ) {
        $htmlsync_filename = $_POST['htmlsync-filename'];
    }
    else {
        $htmlsync_filename = '';
    }

    // If file is not set, delete the meta data.
    if ( empty ( $htmlsync_filename ) ) {
        delete_post_meta( $post_id, '_htmlsync_filename' );
    }
    else {
        // Update file name
        update_post_meta( $post_id, '_htmlsync_filename', $htmlsync_filename );
        // Update sync time
        update_post_meta( $post_id, '_htmlsync_time', time() );

        // Sanitize and replace the content of this post with the content of the assigned file
        $allowed_tags = wp_kses_allowed_html( 'post' );
        $filecontent = wp_kses( file_get_contents( $filepath . '/' . $htmlsync_filename ), $allowed_tags );

        $this_post = array( 
            'ID'           => $post_id,
            'post_content' => $filecontent
        );

        // Update the post, unhook this action, to avoid infinite loop
        if ( ! wp_is_post_revision( $post_id ) AND ! empty( $htmlsync_filename ) ) {

            // Unhook this function so it doesn't loop infinitely
            remove_action('save_post', 'htmlsync_save_post_meta');

            // Update the post, which calls save_post again
            wp_update_post( $this_post );

            // Re-hook this function
            add_action('save_post', 'htmlsync_save_post_meta');
        }
    }
}

add_action( 'save_post', 'htmlsync_save_post_meta' );