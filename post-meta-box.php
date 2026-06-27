<?php
// Register meta box for all public post types (including CPTs)
function auto_renew_meta_box() {
    $post_types = get_post_types( array( 'public' => true ), 'names' );
    foreach ( $post_types as $post_type ) {
        add_meta_box( 'auto_renew_meta_box', 'Auto Renew Post Date', 'auto_renew_meta_box_content', $post_type, 'side', 'high' );
    }
}

function auto_renew_meta_box_content( $post ) {
    wp_nonce_field( 'auto_renew_meta_box_nonce', 'auto_renew_meta_box_nonce' );

    $auto_renew_enabled          = get_post_meta( $post->ID, '_auto_renew_enabled', true );
    $auto_renew_frequency        = get_post_meta( $post->ID, '_auto_renew_frequency', true );
    $auto_renew_custom_frequency = get_post_meta( $post->ID, '_auto_renew_custom_frequency', true );
    $auto_renew_custom_time      = get_post_meta( $post->ID, '_auto_renew_custom_time', true );

    echo '<label><input type="checkbox" name="auto_renew_enabled" value="1" ' . checked( $auto_renew_enabled, 1, false ) . '> Enable Auto Renew</label>';
    echo '<p>Update Frequency: ';
    echo '<select name="auto_renew_frequency" id="auto_renew_frequency">';
    echo '<option value="7" '      . selected( $auto_renew_frequency, '7', false )      . '>Every 7 days</option>';
    echo '<option value="15" '     . selected( $auto_renew_frequency, '15', false )     . '>Every 15 days</option>';
    echo '<option value="custom" ' . selected( $auto_renew_frequency, 'custom', false ) . '>Custom</option>';
    echo '</select>';
    echo '<input type="text" name="auto_renew_custom_frequency" id="auto_renew_custom_frequency" value="' . esc_attr( $auto_renew_custom_frequency ) . '" placeholder="Custom days">';
    echo '<label for="auto_renew_custom_time">Custom Time:</label>';
    echo '<input type="time" name="auto_renew_custom_time" id="auto_renew_custom_time" value="' . esc_attr( $auto_renew_custom_time ) . '">';
    echo '</p>';
}

function save_auto_renew_meta_box( $post_id ) {
    if ( ! isset( $_POST['auto_renew_meta_box_nonce'] )
        || ! wp_verify_nonce( $_POST['auto_renew_meta_box_nonce'], 'auto_renew_meta_box_nonce' ) ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['auto_renew_enabled'] ) ) {
        update_post_meta( $post_id, '_auto_renew_enabled', 1 );
    } else {
        delete_post_meta( $post_id, '_auto_renew_enabled' );
    }

    if ( isset( $_POST['auto_renew_frequency'] ) ) {
        $frequency = sanitize_text_field( $_POST['auto_renew_frequency'] );
        update_post_meta( $post_id, '_auto_renew_frequency', $frequency );

        if ( $frequency === 'custom' && isset( $_POST['auto_renew_custom_frequency'] ) ) {
            update_post_meta( $post_id, '_auto_renew_custom_frequency', absint( $_POST['auto_renew_custom_frequency'] ) );
        }
    }

    if ( isset( $_POST['auto_renew_custom_time'] ) ) {
        $custom_time = sanitize_text_field( $_POST['auto_renew_custom_time'] );
        // Accept HH:MM or HH:MM:SS only
        if ( $custom_time === '' || preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $custom_time ) ) {
            update_post_meta( $post_id, '_auto_renew_custom_time', $custom_time );
        }
    }
}

add_action( 'add_meta_boxes', 'auto_renew_meta_box' );
add_action( 'save_post', 'save_auto_renew_meta_box' );
