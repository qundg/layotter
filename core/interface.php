<?php


/**
 * Load translation files
 *
 * No need to hook this to plugins_loaded because this file is included at that time anyway.
 */
load_plugin_textdomain('layotter', false, basename(dirname(__DIR__)) . '/languages/');


/**
 * Replace TinyMCE with Layotter on Layotter-enabled screens
 */
add_action('admin_head', 'layotter_admin_head');
function layotter_admin_head() {
    if (!Layotter::is_enabled()) {
        return;
    }

    $post_type = get_post_type();

    // remove TinyMCE
    remove_post_type_support($post_type, 'editor');

    // insert layotter
    add_meta_box(
        'layotter_wrapper', // ID
        'Layotter', // title
        'layotter_output_interface', // callback
        $post_type, // post type for which to enable
        'normal', // position
        'high' // priority
    );
}


/**
 * Output backend HTML for Layotter
 *
 * @param $post object Post object as provided by Wordpress
 */
function layotter_output_interface($post) {
    // prepare JSON data for representation in textarea
    $content = get_post_field('post_content', $post->ID);
    $clean_content_for_textarea = htmlspecialchars($content);

    if (Layotter_Settings::is_debug_mode_enabled()) {
        $style = 'width: 100%; height: 200px';
    } else {
        $style = 'width: 1px; height: 1px; position: fixed; top: -999px; left: -999px';
    }
    echo '<textarea id="content" name="content" style="' . $style . '">' . $clean_content_for_textarea . '</textarea>';
    
    require_once __DIR__ . '/../views/editor.php';
}


add_action('save_post', 'layotter_make_search_dump');
function layotter_make_search_dump($post_id) {
    if (!Layotter::is_enabled_for_post($post_id)) {
        return;
    }

    $post = new Layotter_Post($post_id);
    $content = $post->get_frontend_view();
    $clean_content = strip_tags($content, '<img><br><br/><p>');

    remove_action('save_post', 'layotter_make_search_dump');
    wp_update_post(array(
        'ID' => $post_id,
        'post_content' => $clean_content
    ));
    add_action('save_post', 'layotter_make_search_dump');
}