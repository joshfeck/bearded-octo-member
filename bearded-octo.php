<?php
/**
 * Plugin Name: Bearded Octo Event Member Restriction
 * Plugin URI: https://github.com/joshfeck/bearded-octo-member
 * Description: A tiny plugin for Event Espresso 4 that lets you restrict event registration to members of your WP web site
 * Version: 1.0
 * Author: Josh Feck, Event Espresso
 * Author URI: http://eventespresso.com/
 * License: GPL2
 */

// Add meta box
function bearded_octo_member_only_add_meta_box() {
	add_meta_box( 
    	'ee-member-id', 
    	__( 'Restrict Registrations' ), 
    	'bearded_octo_member_only_meta_box_callback', 
    	'espresso_events', 
    	'advanced', 
    	'high'
    );
}
add_action( 'add_meta_boxes_espresso_events', 'bearded_octo_member_only_add_meta_box' );

// Output the meta box
function bearded_octo_member_only_meta_box_callback( $post ) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field( 'bearded_octo_member_only_meta_box', 'bearded_octo_member_only_meta_box_nonce' );

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $value = get_post_meta( $post->ID, '_bearded_octo_member_only', true );

    echo '<label for="bearded_octo_member_only_new_field">';
    _e( 'Member only event?', 'event_espresso' );
    echo '</label><br/>';
    ?>
    <input type="radio" name="bearded_octo_member_only_new_field" value="yes" <?php if ($value == 'yes') echo "checked=1";?>> <?php _e('Yes', 'event_espresso') ?><br/>
    <input type="radio" name="bearded_octo_member_only_new_field" value="no" <?php if ($value == 'no') echo "checked=1";?>> <?php _e('No', 'event_espresso') ?><br/>
    <?php
}

// Save meta value
function bearded_octo_member_only_save_meta_box_data( $post_id ) {

    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */

    // Check if our nonce is set.
    if ( ! isset( $_POST['bearded_octo_member_only_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['bearded_octo_member_only_meta_box_nonce'], 'bearded_octo_member_only_meta_box' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    /* OK, it's safe for us to save the data now. */
    
    // Make sure that it is set.
    if ( ! isset( $_POST['bearded_octo_member_only_new_field'] ) ) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_key( $_POST['bearded_octo_member_only_new_field'] );

    // Update the meta field in the database.
    update_post_meta( $post_id, '_bearded_octo_member_only', $my_data );
}
add_action( 'save_post', 'bearded_octo_member_only_save_meta_box_data' );

// Remove the ticket selector if the event is set to member only
function bearded_octo_member_only_remove_ticket_selector() {
    $post_type = get_post_type();
    $value = get_post_meta( get_the_ID(), '_bearded_octo_member_only', true );
    
    if ( ! is_user_logged_in() ) {
        if ( $post_type == 'espresso_events' ) {
            if ( is_singular() && $value == 'yes' ){
                add_action( 'AHEE_event_details_before_post', 'bearded_octo_member_only_register_site_first_message' );
                add_filter( 'FHEE_disable_espresso_ticket_selector', 'bearded_octo_member_only_filter_ticket_selector' );
            }
            if ( is_archive() ){
                add_filter ('the_content', 'bearded_octo_member_only_remove_ticket_selector_from_archive', 100 );
            }
        }
    }
}
add_action( 'get_template_part_content', 'bearded_octo_member_only_remove_ticket_selector' );

// The message if not a member or not logged in
function bearded_octo_member_only_register_site_first_message() {
    $log_in_link    = '<a href="';
    $log_in_link   .= wp_login_url();
    $log_in_link   .= '">';
    $log_in_link   .= __( 'log in', 'event_espresso' );
    $log_in_link   .= '</a>';
    $register_link  = '<a href="';
    $register_link .= wp_registration_url();
    $register_link .= '">';
    $register_link .= __( 'register', 'event_espresso' );
    $register_link .= '</a>';
    echo '<p style="margin: 1em;" class="member-only-event-message">';
    echo sprintf( __( 'Howdy, it looks like you\'re currently logged out of this site. You\'ll need to %1$s or %2$s to be a member before you can register for this event.', 'event_espresso' ), $log_in_link, $register_link );
    echo '</p>';
}

function bearded_octo_member_only_filter_ticket_selector() {
    return 'FALSE';
}

function bearded_octo_member_only_remove_ticket_selector_from_archive( $content ) {
    $value = get_post_meta( get_the_ID(), '_bearded_octo_member_only', true );
        if ( $value == 'yes' ) {
            remove_filter( 'the_content', array( 'EED_Events_Archive', 'event_tickets' ), 120, 1 );
            remove_filter( 'the_excerpt', array( 'EED_Events_Archive', 'event_tickets' ), 120, 1 );
        }
    return $content;
}

