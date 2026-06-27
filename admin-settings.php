<?php
// Admin settings page
function auto_renew_settings_page() {
    auto_renew_enable_all_posts_handler();
    auto_renew_handle_actions();

    echo '<div class="wrap"><h2>Auto Renew Post Date Settings</h2>';
    auto_renew_display_enable_all_form();
    auto_renew_display_post_list();
    echo '</div>';
}

function auto_renew_display_enable_all_form() {
    ?>
    <h3>Enable on All Posts (All Post Types)</h3>
    <form method="post">
        <?php wp_nonce_field( 'auto_renew_enable_all_nonce', 'auto_renew_enable_all_nonce' ); ?>
        <label>
            Update Frequency:
            <select name="auto_renew_all_frequency">
                <option value="7">Every 7 days</option>
                <option value="15">Every 15 days</option>
                <option value="custom">Custom</option>
            </select>
        </label>
        <?php submit_button( 'Enable Auto Renew on All Posts', 'primary', 'auto_renew_enable_all', false ); ?>
    </form>
    <hr>
    <?php
}

function auto_renew_enable_all_posts_handler() {
    if ( ! isset( $_POST['auto_renew_enable_all'] ) ) {
        return;
    }

    check_admin_referer( 'auto_renew_enable_all_nonce', 'auto_renew_enable_all_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.' ) );
    }

    $valid_frequencies = array( '7', '15', 'custom' );
    $frequency         = isset( $_POST['auto_renew_all_frequency'] )
        ? sanitize_text_field( $_POST['auto_renew_all_frequency'] )
        : '7';

    if ( ! in_array( $frequency, $valid_frequencies, true ) ) {
        $frequency = '7';
    }

    $post_ids = get_posts( array(
        'post_type'   => 'any',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields'      => 'ids',
    ) );

    foreach ( $post_ids as $post_id ) {
        update_post_meta( $post_id, '_auto_renew_enabled', 1 );
        update_post_meta( $post_id, '_auto_renew_frequency', $frequency );
    }

    $count = count( $post_ids );
    echo '<div class="notice notice-success is-dismissible"><p>'
        . sprintf( esc_html__( 'Auto Renew enabled on %d posts.' ), $count )
        . '</p></div>';
}

function auto_renew_display_post_list() {
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

    if ( ! empty( $posts ) ) {
        echo '<table class="widefat">';
        echo '<thead><tr><th>Post Title</th><th>Post Type</th><th>Frequency</th><th>Next Push Date</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        foreach ( $posts as $post ) {
            $frequency      = get_post_meta( $post->ID, '_auto_renew_frequency', true );
            $next_push_date = auto_renew_calculate_next_push_date( $post->ID, $frequency );
            $delete_url     = wp_nonce_url(
                admin_url( 'admin.php?page=auto_renew_settings&action=delete&post_id=' . $post->ID ),
                'auto_renew_delete_' . $post->ID,
                'auto_renew_nonce'
            );

            echo '<tr>';
            echo '<td>' . esc_html( $post->post_title ) . '</td>';
            echo '<td>' . esc_html( $post->post_type ) . '</td>';
            echo '<td>';
            echo '<form class="auto-renew-update-form" action="" method="post">';
            echo wp_nonce_field( 'auto_renew_update_nonce', 'auto_renew_update_nonce', true, false );
            echo '<span class="auto-renew-edit" data-post-id="' . esc_attr( $post->ID ) . '" data-field="frequency" contenteditable="true">' . esc_html( $frequency ) . '</span>';
            echo '</td>';
            echo '<td>' . esc_html( $next_push_date ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url( $delete_url ) . '">Delete</a> | ';
            echo '<button class="auto-renew-update" data-post-id="' . esc_attr( $post->ID ) . '" type="button">Update</button>';
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
    check_ajax_referer( 'auto_renew_update_nonce', 'auto_renew_update_nonce' );

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( 'Insufficient permissions.', 403 );
    }

    if ( ! isset( $_POST['frequency'] ) ) {
        wp_send_json_error( 'Missing frequency.' );
    }

    $frequency = sanitize_text_field( $_POST['frequency'] );
    update_post_meta( $post_id, '_auto_renew_frequency', $frequency );
    wp_send_json_success( 'Frequency updated.' );
}

add_action( 'wp_ajax_auto_renew_update_frequency', 'auto_renew_update_frequency' );

function auto_renew_handle_actions() {
    if ( ! isset( $_GET['action'], $_GET['post_id'] ) ) {
        return;
    }

    $action  = sanitize_text_field( $_GET['action'] );
    $post_id = absint( $_GET['post_id'] );

    if ( $action === 'delete' ) {
        check_admin_referer( 'auto_renew_delete_' . $post_id, 'auto_renew_nonce' );
        auto_renew_delete_post( $post_id );
    } elseif ( $action === 'clear' ) {
        check_admin_referer( 'auto_renew_clear', 'auto_renew_nonce' );
        auto_renew_clear_list();
    }
}

function auto_renew_delete_post( $post_id ) {
    delete_post_meta( $post_id, '_auto_renew_enabled' );
    delete_post_meta( $post_id, '_auto_renew_frequency' );
}

function auto_renew_clear_list() {
    $post_ids = get_posts( array(
        'post_type'   => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
        'meta_query'  => array(
            array(
                'key'   => '_auto_renew_enabled',
                'value' => 1,
            ),
        ),
    ) );

    foreach ( $post_ids as $post_id ) {
        delete_post_meta( $post_id, '_auto_renew_enabled' );
        delete_post_meta( $post_id, '_auto_renew_frequency' );
    }
}

// Pure calculation — no database writes. Reads _auto_renew_last_push_date set by the cron.
function auto_renew_calculate_next_push_date( $post_id, $frequency ) {
    $last_push_date = get_post_meta( $post_id, '_auto_renew_last_push_date', true );

    if ( empty( $last_push_date ) ) {
        return current_time( 'mysql' );
    }

    $interval_days = 1;

    if ( $frequency === '7' ) {
        $interval_days = 7;
    } elseif ( $frequency === '15' ) {
        $interval_days = 15;
    } elseif ( $frequency === 'custom' ) {
        $custom = get_post_meta( $post_id, '_auto_renew_custom_frequency', true );
        $interval_days = empty( $custom ) ? 1 : absint( $custom );
    }

    return date( 'Y-m-d H:i:s', strtotime( $last_push_date ) + ( $interval_days * DAY_IN_SECONDS ) );
}

function auto_renew_menu() {
    add_menu_page( 'Auto Renew Settings', 'Auto Renew', 'manage_options', 'auto_renew_settings', 'auto_renew_settings_page' );
}

add_action( 'admin_menu', 'auto_renew_menu' );

function auto_renew_inline_editing_script() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.auto-renew-update').forEach(function (button) {
                button.addEventListener('click', function () {
                    var form      = button.closest('form');
                    var postId    = button.getAttribute('data-post-id');
                    var nonceEl   = form.querySelector('[name="auto_renew_update_nonce"]');
                    var freqEl    = form.querySelector('[data-field="frequency"]');
                    var nonce     = nonceEl ? nonceEl.value : '';
                    var frequency = freqEl  ? freqEl.innerText.trim() : '';

                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', ajaxurl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    xhr.send(
                        'action=auto_renew_update_frequency'
                        + '&post_id='                  + encodeURIComponent(postId)
                        + '&frequency='                + encodeURIComponent(frequency)
                        + '&auto_renew_update_nonce='  + encodeURIComponent(nonce)
                    );
                });
            });
        });
    </script>
    <?php
}

add_action( 'admin_footer', 'auto_renew_inline_editing_script' );
