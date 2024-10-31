<?php
/*
Plugin Name: Really Simple Responsive Image Slider
Plugin URI: http://bakadesign.dk/plugins/
Description: A very simple slider. Choose which slide to show and paste in the short code to display the slider.
Version: 2.2
Author: Jens Farvig Thomsen
Author URI: http://bakadesign.dk
License: GPLv2
*/

function rsris_ms_load_scripts() {   
  wp_enqueue_style( 'admin', plugins_url( 'really-simple-responsive-image-slider/css/admin.css' ) );
  wp_enqueue_script( 'admin', plugins_url( 'really-simple-responsive-image-slider/js/admin.js' ) );
}
add_action('admin_enqueue_scripts', 'rsris_ms_load_scripts');

function rsris_load_frontend_scripts() {

  wp_enqueue_style( 'frontend', plugins_url( 'really-simple-responsive-image-slider/css/frontend.css' ) );
  wp_enqueue_script( 'frontend', plugins_url( 'really-simple-responsive-image-slider/js/frontend.js' ), array('jquery') );
}
add_action('wp_enqueue_scripts', 'rsris_load_frontend_scripts');


add_action( 'admin_init', 'rsris_add_metaboxes' );
function rsris_add_metaboxes() {

  // This will register our metabox for all post types
  $post_types = get_post_types();
  // This will remove the meta box from our slides post type
  unset($post_types['rsris_slides']);
      foreach ( $post_types as $post_type ){
        // Box for your posts for inserting your slider element.
        add_meta_box('rsris_multipeselect_metabox', 'Slider', 'rsris_multipeselect_metabox', $post_type, 'normal', 'core');
      }
  // Box for inserting the link the slide should link to.
  add_meta_box('rsris_slide_link_box', 'Slide link', 'rsris_slide_link_box', 'rsris_slides', 'normal', 'core');
  add_meta_box('rsris_slide_embed_box', 'Youtube Share link', 'rsris_slide_embed_box', 'rsris_slides', 'normal', 'core');
}
 
// Our metabox for choosing the slides
function rsris_multipeselect_metabox() {
   global $post;
   
   wp_nonce_field( plugin_basename( __FILE__ ), 'rsris_ms_metabox_nonce' );
   
   $rsris_ms_posts = get_posts( array(
   'post_type' => 'rsris_slides',
   'numberposts' => -1

   ));
   $rsris_slides = get_post_meta( $post->ID, 'rsris_slide', true );

   $rsris_ms_output = '<div class="rsris-select-wrapper"><div class="rsris-select-left"><div class="rsris-search-field-wrapper"><input type="text" id="rsris-search-field" placeholder="search"></div><ul class="rsris-items">';
   foreach ($rsris_ms_posts as $rsris_ms_post){
     if (!in_array($rsris_ms_post->ID, $rsris_slides) ){
       
       $rsris_ms_output .= '<li class="rsris-item" data-rsris-item-id="'.$rsris_ms_post->ID.'">';
       $rsris_ms_output .= get_the_post_thumbnail( $rsris_ms_post->ID );
       $rsris_ms_output .= '<span class="rsris-item-title">'.$rsris_ms_post->post_title.'</span></li>';
     
     }
   }
   $rsris_ms_output .= '</ul></div><div class="rsris-select-right"><ul class="rsris-items-selected">';
   
   foreach ($rsris_ms_posts as $rsris_ms_post){
     if ( in_array($rsris_ms_post->ID, $rsris_slides) ){
      $rsris_ms_output .= '<li class="rsris-item" data-rsris-item-id="'.$rsris_ms_post->ID.'">';
      $rsris_ms_output .= get_the_post_thumbnail( $rsris_ms_post->ID );
      $rsris_ms_output .= '<span class="rsris-item-title">'.$rsris_ms_post->post_title.'</span><input type="hidden" name="rsris_slide[]" value="'.$rsris_ms_post->ID.'" /><span class="rsris-remove">x</span></li>';
     }
   }
   $rsris_ms_output .= '</ul></div></div>';
   $rsris_ms_output .= '<div style="clear:both;"></div>';
   echo $rsris_ms_output;
}
 
// Save data from meta box
add_action('save_post', 'rsris_checkbox_metabox_save');
function rsris_checkbox_metabox_save($post_id) {
  // verify nonce
  if ( !wp_verify_nonce( $_POST['rsris_ms_metabox_nonce'], plugin_basename( __FILE__ ) ) )
        return;
 
  // check autosave
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
 
  // check permissions
  if (!current_user_can('edit_post', $post_id))
    return;
 
        $old['rsris_slide'] = get_post_meta( $post_id, 'rsris_slide', true );
        $new['rsris_slide'] = $_POST['rsris_slide'];
       
        if ( $new['rsris_slide'] && $new['rsris_slide'] != $old['rsris_slide'] ) {
          update_post_meta($post_id, 'rsris_slide', $new['rsris_slide']);
        } elseif ( '' == $new['rsris_slide'] && $old['rsris_slide'] ) {
          delete_post_meta($post_id, 'rsris_slide', $old['rsris_slide']);
        }
}


// A short code for showing the slider
add_shortcode( "rsris_slider", "rsris_display_slider" );
    function rsris_display_slider( $atts ) {
      extract(shortcode_atts(array(
        'slides' => null,
     ), $atts));

      if($slides){
        $rsris_slides_id = explode(',', $slides);
      }

      global $post;
      if(get_post_meta( $post->ID, 'rsris_slide', true )){
        $rsris_slides_id = get_post_meta( $post->ID, 'rsris_slide', true );
      }

      if($rsris_slides_id){
        $rsris_slider_output = '<div class="rslides_container"><ul class="rslides">';
          foreach ($rsris_slides_id as $iv_slide_id[]=>$slide_id) {
            
            // If we have embeded content
            if(get_post_meta( $slide_id, 'rsris_slide_embed', true )){
              $rsris_slider_output .= '<li>'.rsris_embed_video($slide_id, $width = 680, $height = 360).'</li>';
            }elseif(get_the_post_thumbnail($slide_id)){

            // If we have a link for the slide, print it
            if( get_post_meta( $slide_id, 'rsris_slide_link', true )){
              $rsris_slides_link =  get_post_meta( $slide_id, 'rsris_slide_link', true );
            }
  
            $rsris_slider_output .='<li><a href="'.$rsris_slides_link.'" title="'.get_the_title($slide_id).'">';          
            $rsris_slider_output .= get_the_post_thumbnail($slide_id);
            $rsris_slider_output .= '</a></li>';
          }
          }
        $rsris_slider_output .= '</ul></div>';
        return $rsris_slider_output;
      }
}

function register_rsris_slides() {

/**
* Register a custom post type
*/
register_post_type( 'rsris_slides', array(
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'rsris_slides' ),
    'has_archive' => false,
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'thumbnail' ),
    'taxonomies' => array(),
    'capability_type' => 'post',
    'capabilities' => array(),
    'labels' => array(
        'name' => __( 'Slides', 'textdomain' ),
        'singular_name' => __( 'Slide', 'textdomain' ),
        'add_new' => __( 'Add New', 'textdomain' ),
        'add_new_item' => __( 'Add New Slide', 'textdomain' ),
        'edit_item' => __( 'Edit Slide', 'textdomain' ),
        'new_item' => __( 'New Slide', 'textdomain' ),
        'all_items' => __( 'All Slides', 'textdomain' ),
        'view_item' => __( 'View Slide', 'textdomain' ),
        'search_items' => __( 'Search Slides', 'textdomain' ),
        'not_found' =>  __( 'No Slides found', 'textdomain' ),
        'not_found_in_trash' => __( 'No Slides found in Trash', 'textdomain' ),
        'parent_item_colon' => '',
        'menu_name' => 'Slides'
    )
) );
}
add_action( 'init', 'register_rsris_slides' );

/**
* Register meta boxes for inserting a links and embeds
*/
function rsris_slide_link_box() {
  global $post;
  $rsris_slide_link = get_post_meta( $post->ID, 'rsris_slide_link', true );
  
  wp_nonce_field( plugin_basename( __FILE__ ), 'rsris_slide_link_box_nounce' );
  
  $rsris_slide_link_output = '<label for="rsris_slide_link"><span class="howto">(http://wwww...) - will not work with video slides</span></label>';
  $rsris_slide_link_output .= '<input type="text" name="rsris_slide_link" id="rsris_slide_link" class="widefat" value="'.$rsris_slide_link.'" />';
  echo $rsris_slide_link_output;
}

function rsris_slide_embed_box() {
  global $post;
  $rsris_slide_embed = get_post_meta( $post->ID, 'rsris_slide_embed', true );
  
  wp_nonce_field( plugin_basename( __FILE__ ), 'rsris_slide_embed_box_nounce' );
  
  $rsris_slide_embed_output = rsris_embed_video( $post->ID, 260, 120);
  $rsris_slide_embed_output .= '<label for="rsris_slide_embed"><span class="howto">Copy and paste the link to your YouTube video</span></label>';
  $rsris_slide_embed_output .= '<input type="text" name="rsris_slide_embed" id="rsris_slide_embed" class="widefat" value="'.$rsris_slide_embed.'" />';
  echo $rsris_slide_embed_output;
}



add_action( 'save_post', 'rsris_link_save' );  
function rsris_link_save( $post_id )  
{  
    // Bail if we're doing an auto save  
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return; 
     
    // verify nonce
    if ( !wp_verify_nonce( $_POST['rsris_slide_link_box_nounce'], plugin_basename( __FILE__ ) ) )
        return; 
     
    // if our current user can't edit this post, bail  
    if( !current_user_can( 'edit_post' ) ) return; 

    if( isset( $_POST['rsris_slide_link'] ) )  
        update_post_meta( $post_id, 'rsris_slide_link', wp_kses( $_POST['rsris_slide_link']) );

    if( isset( $_POST['rsris_slide_embed'] ) )  
        update_post_meta( $post_id, 'rsris_slide_embed', wp_kses( $_POST['rsris_slide_embed']) );

}


// Showo the imbed in backend
function rsris_embed_video($post_id, $width = 680, $height = 360) {
    $rsris_slide_embed = get_post_meta($post_id, 'rsris_slide_embed', true);
 
    preg_match('%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $rsris_slide_embed, $youTubeMatch);
 
    if ($youTubeMatch[1])
        return '<iframe width="'.$width.'" height="'.$height.'" src="http://ww'.
               'w.youtube.com/embed/'.$youTubeMatch[1].'?rel=0&showinfo=0'.
               '&autoplay=0&modestbranding=1" frameborder="0" allowfulls'.
               'creen style="max-width:100%;"></iframe>';
    else
        return null;
}
