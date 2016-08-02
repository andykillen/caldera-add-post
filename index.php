<?php
/* 
  Plugin Name: Caldera Extension
  Plugin URI: https://calderawp.com/caldera-forms/
  Description: Extension to Caldera to make it do extra clever stuff
  Author: Andrew Killen
  Version: 1.0  
 */

require('save-to-post.php');


add_action("wp_enqueue_scripts", "caldera_extension_scrips_and_styles");


function caldera_extension_scrips_and_styles(){
   // if(is_singular()){
   wp_enqueue_script('tiny_mce', trailingslashit( get_bloginfo('url') ).'wp-includes/js/tinymce/tinymce.min.js');
   
         if (function_exists('wp_tiny_mce')){
             wp_tiny_mce();
         }
         
        error_log(plugin_dir_url( __FILE__ ) . "js/ext-script.js");
        wp_enqueue_script('caldera-extension', plugin_dir_url( __FILE__ ) . "js/ext-script.js",array('jquery'), null,1);
   // }
}
