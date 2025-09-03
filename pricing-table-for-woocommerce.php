<?php
/**
 * Plugin Name: Pricing Table for WooCommerce
 * Description: Display dynamic product pricing tables on WooCommerce category pages using a shortcode.
 * Version: 1.1.0
 * Author: Your Name
 * Text Domain: pricing-table-for-woocommerce
 * Domain Path: /languages
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

// Enqueue CSS
function ptwc_enqueue_assets()
{
    wp_enqueue_style(
        'ptwc-styles',
        plugin_dir_url(__FILE__) . 'assets/css/pricing-table.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'ptwc_enqueue_assets');

// Register Shortcode
function ptwc_pricing_table_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'category' => '',
        'limit' => -1,
        'products' => '',
    ), $atts, 'pricing_table');

    if (!empty($atts['products'])) {
        $product_ids = array_map('intval', explode(',', $atts['products']));
        $args = array(
            'post_type' => 'product',
            'post__in' => $product_ids,
            'posts_per_page' => -1,
            'orderby' => 'post__in'
        );
    } else {
        $category = $atts['category'];
        if (empty($category) && is_product_category()) {
            $category = get_queried_object()->slug;
        }

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => intval($atts['limit']),
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $category,
                )
            )
        );
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p>' . __('No products found.', 'pricing-table-for-woocommerce') . '</p>';
    }

    $output = '<table class="ptwc-table">
        <thead>
            <tr>
                <th>' . __('Product', 'pricing-table-for-woocommerce') . '</th>
                <th>' . __('Price', 'pricing-table-for-woocommerce') . '</th>
            </tr>
        </thead>
        <tbody>';

    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());

        $output .= '<tr>
            <td><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></td>
    <td>' . $product->get_price_html() . '</td>
        </tr>';
    }

    $output .= '</tbody></table>';

    wp_reset_postdata();

    return $output;
}
add_shortcode('pricing_table', 'ptwc_pricing_table_shortcode');
