<?php 
    /*
    Plugin Name: Fakestore Product Sync
    Description: Sync products from FakeStore API into WooCommerce.
    Version: 1.0
    Author: Dhanushka Iddamaldeniya
  */

  if ( ! defined( 'ABSPATH' ) ) exit;

  add_filter('the_content', 'addToEndOfPost'); 

  function addToEndOfPost($content) { 
    return $content . '<p>My name is Dhanushka.</p>';
  }
?>