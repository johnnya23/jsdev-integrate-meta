<?php
/*
Plugin Name: JMA Integrate Meta Slider into Header for 7.2
Description: This plugin integrates the meta slider plugin with jma child theme header for 7.2
Version: 1.3
Author: John Antonacci
Author URI: http://cleansupersites.com
License: GPL2
*/
function jma_meta_files()
{
    wp_enqueue_style('jma_meta_css', plugins_url('/jma_meta_css.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'jma_meta_files');

function meta_slider_array_filter($slider_selections)
{
    $sliders = '';
    $posts = get_posts(array(
        'post_type' => 'ml-slider',
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'ASC',
        'posts_per_page' => -1
    ));
    if (count($posts)) {
        foreach ($posts as $post) {
            $sliders[$post->ID] = $post->post_title;
        }
    }

    if (is_array($sliders)) {
        $slider_selections['meta-slider'] = $sliders;
    }

    return $slider_selections;
}
function jma_base_meta_slider_array_filter($html)
{
    return str_replace(array('class="nivo-prevNav">', 'class="nivo-nextNav">'), array('class="nivo-prevNav"><i class="sf-sub-indicator fas fa-angle-left"></i>', 'class="nivo-nextNav"><i class="sf-sub-indicator fas fa-angle-right"></i>'), $html);
}
//add_filter('metaslider_image_nivo_slider_markup', 'jma_base_meta_slider_array_filter', 1);

function jma_meta_slider_filter($return, $type_id)
{
    $slider_array = explode('|', $type_id);
    if ($slider_array[0] == 'meta-slider') {
        $return = do_shortcode("[metaslider id=" . $slider_array[1] . "]");
    }
    return $return;
}

function jma_integrate_meta()
{
    add_filter('slider_array_filter', 'meta_slider_array_filter');
    add_filter('return_display_header_slider', 'jma_meta_slider_filter', 10, 2);
}
add_action('after_setup_theme', 'jma_integrate_meta');

/* add  tabs */
/* edit the display */
function jma_int_meta_nivo_slider_image_attributes($x, $slide)
{
    $slide_id = $slide['id'];
    $url = $slide['url'];
    $current = $jma_title_class = $jma_caption_class = $button = $jma_caption_position = $jma_class_final = '';

    extract(get_post_meta($slide_id, '_meta_slider_jma_field', true));

    if ($x['data-caption'] || $jma_button || $jma_title) {
        if ($jma_caption_position) {
            $jma_class_final .= str_replace('-', ' ', $jma_caption_position);
        }
        if ($jma_class) {
            $jma_class_final .= ' ' . $jma_class;
        }
        $jma_title_class_html = $jma_title_class? ' class="' . $jma_title_class . '"': '';
        $jma_title = $jma_title? '<h2' . $jma_title_class_html . '>' . $jma_title . '</h2>': '';

        $jma_button_class_html = $jma_button_class? ' class="' . $jma_button_class . '"': '';
        if ($jma_button) {
            $target = get_post_meta($slide_id, 'ml-slider_new_window', true) ? '_blank' : '_self';
            $button = '<a' . $jma_button_class_html . ' href="' . $url . '" target="' . $target . '">' . $jma_button . '</a>';
        }
        //there may not be a main caption para (so check here)
        if ($x['data-caption']) {
            $current = '<div class="ml-caption-content ' . $jma_caption_class . '">' . $x['data-caption'] . '</div>';
        }
        $x['data-caption'] = '<div class="jma-wrapper ' . $jma_class_final .'">' .$jma_title . $current . $button . '</div>';
    }
    return $x;
}
add_filter('metaslider_nivo_slider_image_attributes', 'jma_int_meta_nivo_slider_image_attributes', 10, 2);

function jma_int_meta_image_slide_tabs($tabs, $slide, $slider, $settings)
{
    $slide_id = $slide->ID;
    $jma_class = $jma_title_class = $jma_caption_class = $jma_title = $jma_button = $jma_button_class = '';
    extract(get_post_meta($slide_id, '_meta_slider_jma_field', true));

    $caption = $slide->post_excerpt;

    if (!$jma_button_class) {
        $jma_button_class = 'btn btn-default';
    }
    $url = esc_attr(get_post_meta($slide_id, 'ml-slider_url', true));
    $target = get_post_meta($slide_id, 'ml-slider_new_window', true) ? 'checked=checked' : '';

    /* general tab */
    echo $jma_title_class;
    ob_start();
    include 'tabs/general.php';
    $general_tab = ob_get_contents();
    ob_end_clean();

    $tabs['general'] = array(
            'title' => __('General', 'ml-slider'),
            'content' => $general_tab
        );

    /* position tab */

    if (!$jma_caption_position) {
        $jma_caption_position = 'center-middle';
    }

    // Adds schedule tab
    ob_start();
    include 'tabs/position.php';
    $position_tab = ob_get_contents();
    ob_end_clean();

    $tabs['position'] = array(
            'title' => __('Position', 'ml-slider'),
            'content' => $position_tab
        );
    return $tabs;
}
add_filter('metaslider_image_slide_tabs', 'jma_int_meta_image_slide_tabs', 5, 5);

function jma_int_meta_save_settings($slide_id, $slider_id, $fields)
{
    update_post_meta($slide_id, '_meta_slider_jma_field', $fields);
}
add_action('metaslider_save_image_slide', 'jma_int_meta_save_settings', 20, 3);
