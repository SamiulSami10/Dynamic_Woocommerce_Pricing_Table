<?php
/**
 * Plugin Name: Pricing Table for WooCommerce
 * Description: Display dynamic product pricing tables on WooCommerce category pages using a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Register Shortcode
function ptwc_pricing_table_shortcode( $atts ) {
    // Shortcode attributes
    $atts = shortcode_atts( array(
        'category' => '',
        'limit'    => -1,
        'products' => '',
    ), $atts, 'pricing_table' );

    // If specific products are passed
    if ( ! empty( $atts['products'] ) ) {
        $product_ids = array_map( 'intval', explode( ',', $atts['products'] ) );
        $args = array(
            'post_type'      => 'product',
            'post__in'       => $product_ids,
            'posts_per_page' => -1,
            'orderby'        => 'post__in'
        );
    } else {
        // Detect category if none is passed
        $category = $atts['category'];
        if ( empty( $category ) && is_product_category() ) {
            $category = get_queried_object()->slug;
        }

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => intval( $atts['limit'] ),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $category,
                )
            )
        );
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '<p>No products found.</p>';
    }

    // Start table
    $output = '<table class="ptwc-table" style="width:100%;border-collapse:collapse;margin:15px 0;">
        <thead>
            <tr>
                <th style="border:1px solid #ccc;padding:8px;text-align:left;">Product</th>
                <th style="border:1px solid #ccc;padding:8px;text-align:left;">Price</th>
            </tr>
        </thead>
        <tbody>';

    while ( $query->have_posts() ) {
        $query->the_post();
        $product = wc_get_product( get_the_ID() );

        $output .= '<tr>
            <td style="border:1px solid #ccc;padding:8px;">' . esc_html( get_the_title() ) . '</td>
            <td style="border:1px solid #ccc;padding:8px;">' . $product->get_price_html() . '</td>
        </tr>';
    }

    $output .= '</tbody></table>';

    wp_reset_postdata();

    return $output;
}
add_shortcode( 'pricing_table', 'ptwc_pricing_table_shortcode' );
