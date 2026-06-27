<?php
function auto_renew_add_custom_schedule() {
    if ( ! wp_next_scheduled( 'auto_renew_cron_hook' ) ) {
        wp_schedule_event( time(), 'hourly', 'auto_renew_cron_hook' );
    }
}

add_action( 'wp', 'auto_renew_add_custom_schedule' );
add_action( 'auto_renew_cron_hook', 'auto_renew_update_post_dates' );

function auto_renew_update_post_date_if_needed( $post_id, $frequency, $custom_time = null ) {
    $last_push_date = get_post_meta( $post_id, '_auto_renew_last_push_date', true );

    $elapsed = $last_push_date ? ( time() - strtotime( $last_push_date ) ) : PHP_INT_MAX;

    if ( $elapsed >= ( $frequency * DAY_IN_SECONDS ) ) {
        auto_renew_update_post_date( $post_id, $custom_time );
        update_post_meta( $post_id, '_auto_renew_last_push_date', current_time( 'mysql' ) );
    }
}

// Single query across all CPTs; branches on frequency value inside the loop.
function auto_renew_update_post_dates() {
    $posts = get_posts( array(
        'post_type'   => 'any',
        'numberposts' => -1,
        'meta_query'  => array(
            array(
                'key'   => '_auto_renew_enabled',
                'value' => 1,
            ),
        ),
    ) );

    foreach ( $posts as $post ) {
        $frequency   = get_post_meta( $post->ID, '_auto_renew_frequency', true );
        $custom_time = get_post_meta( $post->ID, '_auto_renew_custom_time', true );

        if ( $frequency === 'custom' ) {
            $frequency = absint( get_post_meta( $post->ID, '_auto_renew_custom_frequency', true ) );
        } else {
            $frequency = absint( $frequency );
        }

        if ( $frequency >= 1 && $frequency <= 30 ) {
            auto_renew_update_post_date_if_needed( $post->ID, $frequency, $custom_time );
        }
    }
}

function auto_renew_update_post_date( $post_id, $custom_time = null ) {
    $current_time = current_time( 'mysql' );

    if ( $custom_time && preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $custom_time ) ) {
        $current_time = date( 'Y-m-d', strtotime( $current_time ) ) . ' ' . $custom_time . ( strlen( $custom_time ) === 5 ? ':00' : '' );
    }

    wp_update_post( array(
        'ID'            => $post_id,
        'post_date'     => $current_time,
        'post_date_gmt' => get_gmt_from_date( $current_time ),
    ) );
}
