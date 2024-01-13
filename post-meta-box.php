<?php
// Add a meta box to the post editor
function auto_renew_meta_box() {
    add_meta_box('auto_renew_meta_box', 'Auto Renew Post Date', 'auto_renew_meta_box_content', 'post', 'side', 'high');
}
function auto_renew_meta_box_content($post) {
    $auto_renew_enabled = get_post_meta($post->ID, '_auto_renew_enabled', true);
    $auto_renew_frequency = get_post_meta($post->ID, '_auto_renew_frequency', true);
    $auto_renew_custom_frequency = get_post_meta($post->ID, '_auto_renew_custom_frequency', true);
    $auto_renew_custom_time = get_post_meta($post->ID, '_auto_renew_custom_time', true);

    echo '<label><input type="checkbox" name="auto_renew_enabled" value="1" ' . checked($auto_renew_enabled, 1, false) . '> Enable Auto Renew</label>';
    echo '<p>Update Frequency: ';
    echo '<select name="auto_renew_frequency" id="auto_renew_frequency">';
    echo '<option value="7" ' . selected($auto_renew_frequency, '7', false) . '>Every 7 days</option>';
    echo '<option value="15" ' . selected($auto_renew_frequency, '15', false) . '>Every 15 days</option>';
    echo '<option value="custom" ' . selected($auto_renew_frequency, 'custom', false) . '>Custom</option>';
    echo '</select>';

    // Add an input field for custom days
    echo '<input type="text" name="auto_renew_custom_frequency" id="auto_renew_custom_frequency" value="' . esc_attr($auto_renew_custom_frequency) . '" placeholder="Custom days">';

    // Add an input field for custom time
    echo '<label for="auto_renew_custom_time">Custom Time:</label>';
    echo '<input type="time" name="auto_renew_custom_time" id="auto_renew_custom_time" value="' . esc_attr($auto_renew_custom_time) . '">';

    echo '</p>';
}


function save_auto_renew_meta_box($post_id) {
    if (isset($_POST['auto_renew_enabled'])) {
        update_post_meta($post_id, '_auto_renew_enabled', 1);
    } else {
        delete_post_meta($post_id, '_auto_renew_enabled');
    }

    if (isset($_POST['auto_renew_frequency'])) {
        $frequency = sanitize_text_field($_POST['auto_renew_frequency']);
        update_post_meta($post_id, '_auto_renew_frequency', $frequency);

        // If custom frequency is selected, update the custom value
        if ($frequency === 'custom' && isset($_POST['auto_renew_custom_frequency'])) {
            $custom_frequency = absint($_POST['auto_renew_custom_frequency']);
            update_post_meta($post_id, '_auto_renew_custom_frequency', $custom_frequency);
        }
    }

    // If custom time is set, update the custom time value
    if (isset($_POST['auto_renew_custom_time'])) {
        $custom_time = sanitize_text_field($_POST['auto_renew_custom_time']);
        update_post_meta($post_id, '_auto_renew_custom_time', $custom_time);
    }
}


add_action('save_post', 'save_auto_renew_meta_box');


add_action('add_meta_boxes', 'auto_renew_meta_box');
add_action('save_post', 'save_auto_renew_meta_box');
