<?php
// Admin settings page
function auto_renew_settings_page() {
    // Handle actions (delete, edit, clear)
    auto_renew_handle_actions();

    // Display the settings page
    echo '<div class="wrap"><h2>Auto Renew Post Date Settings</h2>';

    // Display the list of posts with auto renew enabled
    auto_renew_display_post_list();

    echo '</div>';
}



function auto_renew_display_post_list() {
    $args = array(
        'post_type' => 'post',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_auto_renew_enabled',
                'value' => 1,
            ),
        ),
    );

    $posts = get_posts($args);

    if (!empty($posts)) {
        echo '<table class="widefat">';
        echo '<thead><tr><th>Post Title</th><th>Frequency</th><th>Next Push Date</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        foreach ($posts as $post) {
            $frequency = get_post_meta($post->ID, '_auto_renew_frequency', true);
            $next_push_date = auto_renew_calculate_next_push_date($post->ID, $frequency);

            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>';
            echo '<form class="auto-renew-update-form" action="" method="post">';
            echo '<span class="auto-renew-edit" data-post-id="' . esc_attr($post->ID) . '" data-action="edit" data-field="frequency" contenteditable="true">' . esc_html($frequency) . '</span>';
            echo '</td>';
            echo '<td>' . esc_html($next_push_date) . '</td>';
            echo '<td>';
            echo '<a href="?page=auto_renew_settings&action=delete&post_id=' . esc_attr($post->ID) . '">Delete</a> | ';
            echo '<button class="auto-renew-update" data-post-id="' . esc_attr($post->ID) . '" type="button">Update</button>';
            echo '<input type="hidden" name="auto_renew_update_nonce" value="' . wp_create_nonce('auto_renew_update_nonce') . '">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No posts found with Auto Renew enabled.</p>';
    }
}

function auto_renew_update_frequency() {
    check_ajax_referer('auto_renew_update_nonce', 'auto_renew_update_nonce');

    if (isset($_POST['post_id']) && isset($_POST['frequency'])) {
        $post_id = absint($_POST['post_id']);
        $frequency = sanitize_text_field($_POST['frequency']);

        // Update the frequency
        update_post_meta($post_id, '_auto_renew_frequency', $frequency);

        // You may also need to update the next push date if needed
        // auto_renew_calculate_next_push_date($post_id, $frequency);

        // You can send a response if needed
        echo 'Success'; // Adjust as needed
    }

    die();
}

add_action('wp_ajax_auto_renew_update_frequency', 'auto_renew_update_frequency');

function auto_renew_handle_actions() {
    if (isset($_GET['action']) && isset($_GET['post_id'])) {
        $action = sanitize_text_field($_GET['action']);
        $post_id = absint($_GET['post_id']);

        switch ($action) {
            case 'delete':
                auto_renew_delete_post($post_id);
                break;

            case 'edit':
                // Redirect to the post editor for editing
                wp_redirect(get_edit_post_link($post_id));
                exit;

            case 'clear':
                auto_renew_clear_list();
                break;
        }
    }
}

function auto_renew_delete_post($post_id) {
    delete_post_meta($post_id, '_auto_renew_enabled');
    delete_post_meta($post_id, '_auto_renew_frequency');
}

function auto_renew_clear_list() {
    $args = array(
        'post_type' => 'post',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_auto_renew_enabled',
                'value' => 1,
            ),
        ),
    );

    $posts = get_posts($args);

    foreach ($posts as $post) {
        delete_post_meta($post->ID, '_auto_renew_enabled');
        delete_post_meta($post->ID, '_auto_renew_frequency');
    }
}

function auto_renew_calculate_next_push_date($post_id, $frequency) {
    $current_date = strtotime(current_time('mysql'));
    $last_push_date = get_post_meta($post_id, '_auto_renew_last_push_date', true);

    // If it's the first time or last push date is not set, set it to the current date
    if (empty($last_push_date)) {
        update_post_meta($post_id, '_auto_renew_last_push_date', date('Y-m-d H:i:s', $current_date));
        return date('Y-m-d H:i:s', $current_date);
    }

    $interval_days = 1; // Default interval is set to 1 day (daily)

    if ($frequency === '7') {
        $interval_days = 7;
    } elseif ($frequency === '15') {
        $interval_days = 15;
    } elseif ($frequency === 'custom') {
        // Get custom frequency if set
        $custom_frequency = get_post_meta($post_id, '_auto_renew_custom_frequency', true);
        $interval_days = empty($custom_frequency) ? 1 : absint($custom_frequency);
    }

    // Calculate the next push date
    $next_push_date = strtotime($last_push_date) + ($interval_days * 24 * 60 * 60);

    // Update the last push date
    update_post_meta($post_id, '_auto_renew_last_push_date', date('Y-m-d H:i:s', $next_push_date));

    return date('Y-m-d H:i:s', $next_push_date);
}

function auto_renew_menu() {
    add_menu_page('Auto Renew Settings', 'Auto Renew', 'manage_options', 'auto_renew_settings', 'auto_renew_settings_page');
}

add_action('admin_menu', 'auto_renew_menu');

function auto_renew_inline_editing_script() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var updateButtons = document.querySelectorAll('.auto-renew-update');

            updateButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var postId = button.getAttribute('data-post-id');
                    var frequency = document.querySelector('[data-post-id="' + postId + '"][data-field="frequency"]').innerText.trim();

                    // Send an AJAX request to update the frequency
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', ajaxurl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

                    var data = 'action=auto_renew_update_frequency&post_id=' + postId + '&frequency=' + encodeURIComponent(frequency);
                    xhr.send(data);

                    // Handle the response if needed
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            // Update the UI or handle success
                        } else {
                            // Handle errors
                        }
                    };
                });
            });
        });
    </script>
    <?php
}

add_action('admin_footer', 'auto_renew_inline_editing_script');

