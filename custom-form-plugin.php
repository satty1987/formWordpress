<?php
/*
Plugin Name: Custom Form Plugin
Description: A custom form plugin to handle form submissions and display them in the admin panel.
Version: 1.0
Author: Your Name
*/

// Function to create custom form table
function create_custom_form_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_form_entries';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        message text NOT NULL,
        submitted_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add logging to verify table creation
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log("Table $table_name not created.");
    } else {
        error_log("Table $table_name successfully created.");
    }
}
register_activation_hook(__FILE__, 'create_custom_form_table');

// Function to handle form submission
function handle_custom_form_submission() {
    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['message'])) {
        global $wpdb;
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);

        $table_name = $wpdb->prefix . 'custom_form_entries';

        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'message' => $message,
                'submitted_at' => current_time('mysql')
            )
        );

        // Redirect back to the form page with a success message
        wp_redirect(add_query_arg('form_submitted', 'true', wp_get_referer()));
        exit;
    }
}
add_action('admin_post_custom_form_submit', 'handle_custom_form_submission');
add_action('admin_post_nopriv_custom_form_submit', 'handle_custom_form_submission');

// Shortcode to display the form
function custom_form_shortcode() {
    ob_start();
    ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="custom_form_submit">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br>
        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea>
        <br>
        <input type="submit" value="Submit">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_form', 'custom_form_shortcode');

// Function to add admin menu
function custom_form_admin_menu() {
    add_menu_page(
        'Form Entries',
        'Form Entries',
        'manage_options',
        'custom-form-entries',
        'custom_form_entries_page',
        'dashicons-list-view',
        6
    );
}
add_action('admin_menu', 'custom_form_admin_menu');

// Function to display form entries in admin page
function custom_form_entries_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_form_entries';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">';
    echo '<h1>Form Entries</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Submitted At</th></tr></thead>';
    echo '<tbody>';
    if (!empty($results)) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->name) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>' . esc_html($row->message) . '</td>';
            echo '<td>' . esc_html($row->submitted_at) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">No entries found.</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
?>
