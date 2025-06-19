<?php
/**
 * Plugin Name:       All Export
 * Plugin URI:        https://example.com/
 * Description:       Export all WordPress core data like Users, Posts, Pages, Categories, and Taxonomies.
 * Version:           1.0.0
 * Author:            Gemini
 * Author URI:        https://gemini.google.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       all-export
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Add the admin menu page
function aex_add_admin_menu() {
    add_menu_page(
        'All Export',
        'All Export',
        'manage_options',
        'all-export',
        'aex_admin_page_html',
        'dashicons-download'
    );
}
add_action( 'admin_menu', 'aex_add_admin_menu' );

// Admin page HTML
function aex_admin_page_html() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Select the data you want to export and click the "Export" button.</p>

        <form method="post" action="">
            <select name="aex_export_action">
                <option value="export_users">Users (CSV)</option>
                <option value="export_posts">Posts (CSV)</option>
                <option value="export_pages">Pages (CSV)</option>
                <option value="export_categories">Categories (CSV)</option>
                <option value="export_taxonomies">All Taxonomies (CSV)</option>
                <option value="export_all_json">Export All (JSON)</option>
            </select>
            <?php submit_button('Export'); ?>
        </form>
    </div>
    <?php
}

function aex_handle_export_actions() {
    if ( isset( $_POST['aex_export_action'] ) ) {
        $action = sanitize_text_field( $_POST['aex_export_action'] );

        switch ( $action ) {
            case 'export_users':
                aex_export_users();
                break;
            case 'export_posts':
                aex_export_posts();
                break;
            case 'export_pages':
                aex_export_pages();
                break;
            case 'export_categories':
                aex_export_categories();
                break;
            case 'export_taxonomies':
                aex_export_taxonomies();
                break;
            case 'export_all_json':
                aex_export_all_json();
                break;
        }
    }
}
add_action( 'admin_init', 'aex_handle_export_actions' );

function aex_export_users() {
    $users = get_users();
    $filename = 'users-export-' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fputcsv($output, array('ID', 'Username', 'Email', 'Display Name', 'Password (Hashed)', 'Metadata'));

    foreach ($users as $user) {
        fputcsv($output, array(
            $user->ID,
            $user->user_login,
            $user->user_email,
            $user->display_name,
            $user->user_pass,
            json_encode(get_user_meta($user->ID))
        ));
    }

    fclose($output);
    exit;
}

function aex_export_posts() {
    $posts = get_posts(array('numberposts' => -1, 'post_type' => 'post', 'post_status' => 'any'));
    $filename = 'posts-export-' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fputcsv($output, array('ID', 'Title', 'Content', 'Excerpt', 'Date', 'Status', 'Permalink', 'Featured Image URL', 'Metadata'));

    foreach ($posts as $post) {
        fputcsv($output, array(
            $post->ID,
            $post->post_title,
            $post->post_content,
            $post->post_excerpt,
            $post->post_date,
            $post->post_status,
            get_permalink($post->ID),
            get_the_post_thumbnail_url($post->ID, 'full'),
            json_encode(get_post_meta($post->ID))
        ));
    }

    fclose($output);
    exit;
}

function aex_export_pages() {
    $pages = get_posts(array('numberposts' => -1, 'post_type' => 'page', 'post_status' => 'any'));
    $filename = 'pages-export-' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fputcsv($output, array('ID', 'Title', 'Content', 'Excerpt', 'Date', 'Status', 'Permalink', 'Featured Image URL', 'Metadata'));

    foreach ($pages as $page) {
        fputcsv($output, array(
            $page->ID,
            $page->post_title,
            $page->post_content,
            $page->post_excerpt,
            $page->post_date,
            $page->post_status,
            get_permalink($page->ID),
            get_the_post_thumbnail_url($page->ID, 'full'),
            json_encode(get_post_meta($page->ID))
        ));
    }

    fclose($output);
    exit;
}

function aex_export_categories() {
    $categories = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
    $filename = 'categories-export-' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fputcsv($output, array('ID', 'Name', 'Slug', 'Description', 'Count', 'Metadata'));

    foreach ($categories as $category) {
        fputcsv($output, array(
            $category->term_id,
            $category->name,
            $category->slug,
            $category->description,
            $category->count,
            json_encode(get_term_meta($category->term_id))
        ));
    }

    fclose($output);
    exit;
}

function aex_export_taxonomies() {
    $taxonomies = get_taxonomies(array('public' => true), 'objects');
    $filename = 'taxonomies-export-' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fputcsv($output, array('Taxonomy', 'Term ID', 'Name', 'Slug', 'Description', 'Count', 'Metadata'));

    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array('taxonomy' => $taxonomy->name, 'hide_empty' => false));
        if (is_wp_error($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            fputcsv($output, array(
                $taxonomy->name,
                $term->term_id,
                $term->name,
                $term->slug,
                $term->description,
                $term->count,
                json_encode(get_term_meta($term->term_id))
            ));
        }
    }

    fclose($output);
    exit;
}

function aex_export_all_json() {
    $data = array(
        'users' => array(),
        'post_types' => array(),
        'taxonomies' => array(),
        'navigation_menus' => array()
    );

    // Get Users
    $users = get_users();
    foreach ($users as $user) {
        $userdata = $user->to_array();
        $userdata['user_pass'] = $user->user_pass; // The hashed password
        $userdata['metadata'] = get_user_meta($user->ID);
        $data['users'][] = $userdata;
    }

    // Get all public post types
    $post_types = get_post_types(array('public' => true), 'names');
    foreach ($post_types as $post_type) {
        $data['post_types'][$post_type] = array();
        $posts = get_posts(array('numberposts' => -1, 'post_type' => $post_type, 'post_status' => 'any'));
        foreach ($posts as $post) {
            $postdata = $post->to_array();
            $postdata['permalink'] = get_permalink($post->ID);
            $postdata['metadata'] = get_post_meta($post->ID);
            $postdata['featured_image_url'] = get_the_post_thumbnail_url($post->ID, 'full');
            $post_taxonomies = get_object_taxonomies($post, 'objects');
            $postdata['terms'] = array();
            foreach ($post_taxonomies as $tax_slug => $taxonomy) {
                $postdata['terms'][$tax_slug] = wp_get_post_terms($post->ID, $tax_slug, array('fields' => 'all'));
            }
            $data['post_types'][$post_type][] = $postdata;
        }
    }

    // Get all taxonomies and their terms
    $taxonomies = get_taxonomies(array('public' => true), 'objects');
    foreach ($taxonomies as $taxonomy) {
        $data['taxonomies'][$taxonomy->name] = array();
        $terms = get_terms(array('taxonomy' => $taxonomy->name, 'hide_empty' => false));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $termdata = $term->to_array();
                $termdata['metadata'] = get_term_meta($term->term_id);
                $data['taxonomies'][$taxonomy->name][] = $termdata;
            }
        }
    }

    // Get Navigation Menus
    $menus = get_terms(array('taxonomy' => 'nav_menu', 'hide_empty' => false));
    foreach($menus as $menu) {
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        $data['navigation_menus'][$menu->name] = $menu_items;
    }

    $filename = 'all-export-' . date('Y-m-d') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
} 