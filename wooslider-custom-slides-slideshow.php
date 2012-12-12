<?php
/*
Plugin Name: WooSlider - Custom Slides Slideshow
Plugin URI: http://matty.co.za/
Description: A customised copy of the "slides" slideshow type's output for WooSlider. Displays the image with the slide content in an overlay.
Version: 1.0.0
Author: Matty Cohen
Author URI: http://matty.co.za/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
/*  Copyright 2012  Matty  (email : nothanks@idontwantspam.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * WooSlider Custom Slideshow Class
 *
 * All functionality pertaining to the custom slideshow class.
 *
 * @package WordPress
 * @subpackage WooSlider
 * @category Extension
 * @author Matty
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - init()
 * - add_slideshow_type()
 * - add_popup_fields()
 * - display_fields()
 * - get_fields()
 * - get_slides()
 */
add_action( 'plugins_loaded', array( 'WooSlider_Custom_Slides_Slideshow', 'init' ), 0 );


class WooSlider_Custom_Slides_Slideshow {
    /**
     * Initialize the plugin, check the environment and make sure we can act.
     * @since  1.0.0
     * @return  void
     */
    public function init () {
        // Make sure WooSlider is active.
        $active_plugins = apply_filters( 'active_plugins', get_option('active_plugins' ) );
        if ( ! in_array( 'wooslider/wooslider.php', $active_plugins ) ) return;

        // Add the slideshow type into WooSlider.
        add_filter( 'wooslider_slider_types', array( 'WooSlider_Custom_Slides_Slideshow', 'add_slideshow_type' ) );
        // Add the slideshow type's fields into the WooSlider popup.
        add_action( 'wooslider_popup_conditional_fields', array( 'WooSlider_Custom_Slides_Slideshow', 'display_fields' ) );
        // Print some CSS to adjust the slideshows slightly.
        add_action( 'wp_print_styles', array( 'WooSlider_Custom_Slides_Slideshow', 'print_css' ) );
    } // End init()

    /**
     * Integrate the slideshow type into WooSlider.
     * @since  1.0.0
     * @param  array $types Existing slideshow types.
     * @return array $types Modified array of types.
     */
    public function add_slideshow_type ( $types ) {
        if ( is_array( $types ) ) {
            // Make sure to add an array, at our desired key, consisting of a "name" and the "callback" function to get the slides for this slideshow type.
            $types['custom_slides'] = array( 'name' => __( 'Custom Slides', 'wooslider-custom-slides-slideshow' ), 'callback' => array( 'WooSlider_Custom_Slides_Slideshow', 'get_slides' ) );
        }
        return $types;
    } // End add_slideshow_type()

    /**
     * Display conditional fields for this slideshow type, when generating the shortcode.
     * @since  1.0.0
     * @return  void
     */
    public function display_fields () {
        global $wooslider;

        // Get an array of the fields, and their settings, to be generated in the popup form for conditional fields for this slideshow type.
        $fields = self::get_fields();

        // Make sure that the DIV tag below has a CSS class of "conditional-slideshowtype", where "slideshowtype" is our newly added type.
?>
<div class="conditional conditional-custom_slides">
    <table class="form-table">
        <tbody>
<?php foreach ( $fields as $k => $v ) { ?>
            <tr valign="top">
                <th scope="row"><?php echo $v['name']; ?></th>
                <td>
                    <?php
                        // Use WooSlider's admin object to generate the desired field according to it's type.
                        $wooslider->admin->generate_field_by_type( $v['type'], $v['args'] );
                    ?>
                    <?php if ( $v['description'] != '' ) { ?><p><span class="description"><?php echo $v['description']; ?></span></p><?php } ?>
                </td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</div><!--/.conditional-->
<?php
    } // End display_fields()

    /**
     * Generate an array of the data to be used to generate the fields for display in the WooSlider admin.
     * @since  1.0.0
     * @return array Field data.
     */
    private function get_fields () {
        global $wooslider;

        $images_url = $wooslider->plugin_url . '/assets/images/';
        $fields = array();

        // Categories.
        $terms = get_terms( 'slide-page' );
        $terms_options = array();
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $k => $v ) {
                $terms_options[$v->slug] = $v->name;
            }
        }

        $categories_args = array( 'key' => 'slide_page', 'data' => array( 'options' => $terms_options ) );

        $layout_types = WooSlider_Utils::get_posts_layout_types();
        $layout_options = array();

        foreach ( (array)$layout_types as $k => $v ) {
            $layout_options[$k] = $v['name'];
        }

        $layout_images = array(
                                'text-left' => esc_url( $images_url . 'text-left.png' ), 
                                'text-right' => esc_url( $images_url . 'text-right.png' ), 
                                'text-top' => esc_url( $images_url . 'text-top.png' ), 
                                'text-bottom' => esc_url( $images_url . 'text-bottom.png' )
                            );
        $layouts_args = array( 'key' => 'layout', 'data' => array( 'options' => $layout_options, 'images' => $layout_images ) );

        $overlay_images = array(
                                'none' => esc_url( $images_url . 'default.png' ), 
                                'full' => esc_url( $images_url . 'text-bottom.png' ), 
                                'natural' => esc_url( $images_url . 'overlay-natural.png' )
                            );

        $overlay_options = array( 'none' => __( 'None', 'wooslider-custom-slides-slideshow' ), 'full' => __( 'Full', 'wooslider-custom-slides-slideshow' ), 'natural' => __( 'Natural', 'wooslider-custom-slides-slideshow' ) );

        $overlay_args = array( 'key' => 'overlay', 'data' => array( 'options' => $overlay_options, 'images' => $overlay_images ) );

        $limit_options = array();
        for ( $i = 1; $i <= 20; $i++ ) {
            $limit_options[$i] = $i;
        }
        $limit_args = array( 'key' => 'limit', 'data' => array( 'options' => $limit_options, 'default' => 5 ) );
        $thumbnails_args = array( 'key' => 'thumbnails', 'data' => array() );
        $display_featured_image_args = array( 'key' => 'display_featured_image', 'data' => array() );

        // Create final array.
        $fields['limit'] = array( 'name' => __( 'Number of Slides', 'wooslider-custom-slides-slideshow' ), 'type' => 'select', 'args' => $limit_args, 'description' => __( 'The maximum number of slides to display', 'wooslider-custom-slides-slideshow' ) );
        $fields['layout'] = array( 'name' => __( 'Layout', 'wooslider-custom-slides-slideshow' ), 'type' => 'images', 'args' => $layouts_args, 'description' => __( 'The layout to use when displaying posts', 'wooslider-custom-slides-slideshow' ) );
        $fields['overlay'] = array( 'name' => __( 'Overlay', 'wooslider-custom-slides-slideshow' ), 'type' => 'images', 'args' => $overlay_args, 'description' => __( 'The type of overlay to use when displaying the post text', 'wooslider-custom-slides-slideshow' ) );
        $fields['slide_page'] = array( 'name' => __( 'Slide Groups', 'wooslider-custom-slides-slideshow' ), 'type' => 'multicheck', 'args' => $categories_args, 'description' => __( 'The slide groups from which to display slides', 'wooslider-custom-slides-slideshow' ) );
        $fields['thumbnails'] = array( 'name' => __( 'Use thumbnails for Pagination', 'wooslider-custom-slides-slideshow' ), 'type' => 'checkbox', 'args' => $thumbnails_args, 'description' => __( 'Use thumbnails for pagination, instead of "dot" indicators (uses featured image)', 'wooslider-custom-slides-slideshow' ) );

        return $fields;
    } // End get_fields()

    /**
     * Get the slides for the "slides" slideshow type.
     * @since  1.0.0
     * @param  array $args Array of arguments to determine which slides to return.
     * @return array       An array of slides to render for the slideshow.
     */
    public function get_slides ( $args = array() ) {
        global $post;
        $slides = array();

        $defaults = array(
                        'limit' => '5', 
                        'slide_page' => '', 
                        'thumbnails' => '', 
                        'size' => 'large'
                        );

        $args = wp_parse_args( $args, $defaults );

        // Determine and validate the layout type.
        $supported_layouts = WooSlider_Utils::get_posts_layout_types();
        if ( ! in_array( $args['layout'], array_keys( $supported_layouts ) ) ) { $args['layout'] = $defaults['layout']; }

        // Determine and validate the overlay setting.
        if ( ! in_array( $args['overlay'], array( 'none', 'full', 'natural' ) ) ) { $args['overlay'] = $defaults['overlay']; }

        $query_args = array( 'post_type' => 'slide', 'numberposts' => intval( $args['limit'] ) );
        
        if ( $args['slide_page'] != '' ) {
            $cats_split = explode( ',', $args['slide_page'] );
            $query_args['tax_query'] = array();
            foreach ( $cats_split as $k => $v ) {
                $query_args['tax_query'][] = array(
                        'taxonomy' => 'slide-page',
                        'field' => 'slug',
                        'terms' => esc_attr( trim( rtrim( $v ) ) )
                    );
            }
        }

        $posts = get_posts( $query_args );

        if ( ! is_wp_error( $posts ) && ( count( $posts ) > 0 ) ) {
            $class = 'layout-' . esc_attr( $args['layout'] ) . ' overlay-' . esc_attr( $args['overlay'] );

            foreach ( $posts as $k => $post ) {
                setup_postdata( $post );
                $content = wpautop( get_the_content() );
                
                $image = get_the_post_thumbnail( get_the_ID(), $args['size'] );
                if ( '' == $image ) { $image = '<img src="' . esc_url( WooSlider_Utils::get_placeholder_image() ) . '" />'; }

                $content = $image . '<div class="slide-excerpt">' . $content . '</div>';
                if ( $args['layout'] == 'text-top' ) {
                    $content = '<div class="slide-excerpt">' . $content . '</div>' . $image;
                }

                $content = '<div class="' . esc_attr( $class ) . '">' . $content . '</div>' . "\n";

                $data = array( 'content' => $content );
                if ( 'true' == $args['thumbnails'] || 1 == $args['thumbnails'] ) {
                    $thumb_url = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'medium' );
                    if ( ! is_bool( $thumb_url ) && isset( $thumb_url[0] ) ) {
                        $data['attributes'] = array( 'data-thumb' => esc_url( $thumb_url[0] ) );
                    } else {
                        $data['attributes'] = array( 'data-thumb' => esc_url( WooSlider_Utils::get_placeholder_image() ) );
                    }
                }
                $slides[] = $data;
            }
            wp_reset_postdata();
        }

        return $slides;
    } // End get_slides()

    /**
     * Print some CSS styles, to adjust the slider.
     * @return void
     */
    public function print_css () {
        echo '<style type="text/css">' . "\n" . 'body .wooslider.wooslider-type-custom_slides ul.slides, body .wooslider.wooslider-type-custom_slides .wooslider-control-nav { margin-left: 0; }' . "\n" . 'body .wooslider.wooslider-type-custom_slides .slide-excerpt, body .entry .wooslider.wooslider-type-custom_slides p { color: #FFFFFF; }' . "\n" . '</style>' . "\n";
    } // End print_css()
} // End Class
?>