<?php defined( 'ABSPATH' ) OR exit;

/*
Plugin Name: HTML Sync
Description: A WordPress Plugin to sync static HTML files with posts and pages
Version: 0.0.2
Author: Florian Ziegler
Author URI: http://florianziegler.de/
License: GPL
Text-Domain: htmlsync
*/

// TODO: Make content non-editable when file sync is enabled? Or maybe show an indicator of sorts?

/**
 * Include functions for HTML Sync
 *
 * @package htmlsync
 * @since 0.0.1
 */

if ( ! function_exists( 'htmlsync_setup' ) ) {

    function htmlsync_setup() {

        // Define Include Path for this Plugin
        define( 'HTMLSYNC_PATH', plugin_dir_path(__FILE__) );

        // Define URL for this Plugin
        define( 'HTMLSYNC_URL', plugin_dir_url(__FILE__) );

        // Settings page
        require HTMLSYNC_PATH . 'includes/htmlsync-settings.php';

        // Include custom Metabox
        require HTMLSYNC_PATH . 'includes/htmlsync-metabox.php';

    }
}

add_action( 'after_setup_theme', 'htmlsync_setup' );



/**
 * Setup the cron job
 *
 * @package htmlsync
 * @since 0.0.1
 */

add_action( 'wp', 'htmlsync_setup_schedule' );

function htmlsync_setup_schedule() {

    if ( ! wp_next_scheduled( 'htmlsync_hourly_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'htmlsync_hourly_event');
    }
}



/**
 * On the scheduled action hook, run a function
 *
 * @package htmlsync
 * @since 0.0.1
 */
add_action( 'htmlsync_hourly_event', 'htmlsync_do_this_hourly' );

function htmlsync_do_this_hourly() {

    // Get file path
    $settings = get_option( 'htmlsync_settings' );

    // Check if filepath is set and exists
    if ( isset( $settings['filepath'] ) AND is_dir( $settings['filepath'] ) ) {

        $filepath = $settings['filepath'];

        // Get post ids and filenames form the database
        global $wpdb;
        $post_files = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_htmlsync_filename'" );
    
        foreach( $post_files as $file ) {
            // Get file changedate
            $changedate =  filemtime( $filepath . '/' . $file->meta_value );
            // Get sync time
            $synctime = get_post_meta( $file->post_id, '_htmlsync_time', true );

            // Update if changedate is newer than sync date
            if  ( $changedate < $synctime ) {
                // Do noting...
            }
            else {
                // Update post content for this file
                $update_post = array(
                    'ID'           => $file->post_id,
                    'post_content' => wp_kses_post( file_get_contents( $filepath . '/' . $file->meta_value ) )
                );

                // Update the post
                wp_update_post( $update_post );

                // Update sync time
                update_post_meta( $file->post_id, '_htmlsync_time', time() );
            }
        }
    }
}



/**
 * Add HTML Sync column in the page overview
 *
 * @since 0.0.1
 */

function htmlsync_add_column( $columns ) {

    $new = array();

    foreach( $columns as $key => $title ) {
        if ( $key == 'author' ) { // Put the Thumbnail column before the Author column
            $new['htmlsync_column'] = __( 'HTML Sync', 'htmlsync' );
        }
        $new[$key] = $title;
    }

    return $new;
}

add_filter( 'manage_edit-page_columns', 'htmlsync_add_column' );


/**
 * Add filename to the column
 *
 * @since 0.0.1
 */

function htmlsync_column_content( $column, $post_id ) {

    switch ( $column ) {
        case 'htmlsync_column':
            $htmlsync_filename = get_post_meta( $post_id, '_htmlsync_filename', true );
            echo $htmlsync_filename;
        break;
    }
}

add_action( 'manage_pages_custom_column' , 'htmlsync_column_content', 10, 2 );