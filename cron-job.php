<?php
// Add a custom cron schedule for auto-renew
function auto_renew_add_custom_schedule() {
    if (!wp_next_scheduled('auto_renew_cron_hook')) {
        wp_schedule_event(time(), 'hourly', 'auto_renew_cron_hook');
    }
}

add_action('wp', 'auto_renew_add_custom_schedule');

// Hook into the auto-renew cron job
add_action('auto_renew_cron_hook', 'auto_renew_update_post_dates');

// Function to update post dates if needed
function auto_renew_update_post_date_if_needed($post_id, $frequency, $custom_time = null) {
    $last_update_time = get_post_meta($post_id, '_auto_renew_last_update', true);
    
    // If last update time is not set or the specified frequency of days has passed
    if (!$last_update_time || (time() - strtotime($last_update_time)) >= ($frequency * 24 * 60 * 60)) {
        auto_renew_update_post_date($post_id, $frequency, $custom_time);
        
        // Update the last update time to the current time
        update_post_meta($post_id, '_auto_renew_last_update', current_time('mysql'));
    }
}

// Function to update post dates
function auto_renew_update_post_dates() {
    $args = array(
        'post_type' => 'post',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_auto_renew_enabled',
                'value' => 1,
            ),
            array(
                'key' => '_auto_renew_frequency',
                'value' => 'custom',
                'compare' => '!=' // Exclude posts with custom frequency
            ),
        ),
    );

    $posts = get_posts($args);

    foreach ($posts as $post) {
        $frequency = get_post_meta($post->ID, '_auto_renew_frequency', true);
        $custom_time = get_post_meta($post->ID, '_auto_renew_custom_time', true);

        // Check if the frequency is a valid number between 1 and 30
        if (is_numeric($frequency) && $frequency >= 1 && $frequency <= 30) {
            auto_renew_update_post_date_if_needed($post->ID, $frequency, $custom_time);
        }
    }

    // Process posts with custom frequency
    $args_custom = array(
        'post_type' => 'post',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_auto_renew_enabled',
                'value' => 1,
            ),
            array(
                'key' => '_auto_renew_frequency',
                'value' => 'custom',
            ),
        ),
    );

    $posts_custom = get_posts($args_custom);

    foreach ($posts_custom as $post) {
        $custom_frequency = get_post_meta($post->ID, '_auto_renew_custom_frequency', true);
        $custom_time = get_post_meta($post->ID, '_auto_renew_custom_time', true);

        // Check if the custom frequency is a valid number between 1 and 30
        if (is_numeric($custom_frequency) && $custom_frequency >= 1 && $custom_frequency <= 30) {
            auto_renew_update_post_date_if_needed($post->ID, $custom_frequency, $custom_time);
        }
    }
}

// Function to update the post date
function auto_renew_update_post_date($post_id, $frequency, $custom_time = null) {
    $current_time = current_time('mysql');
    
    // If custom time is set, update the post date to the custom date and time
    if ($custom_time) {
        $current_time = date('Y-m-d', strtotime($current_time)) . ' ' . $custom_time;
    }

    wp_update_post(
        array(
            'ID' => $post_id,
            'post_date' => $current_time,
            'post_date_gmt' => get_gmt_from_date($current_time),
        )
    );
	        update_post_meta($post_id, '_auto_renew_enabled', 1);

}
