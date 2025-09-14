<?php 
    /*
    Plugin Name: Fakestore Product Sync
    Description: Sync products from FakeStore API into WooCommerce.
    Version: 1.0
    Author: Dhanushka Iddamaldeniya
    Requires Plugins: woocommerce
  */

  if ( ! defined( 'ABSPATH' ) ) exit;

  class FakestoreProductSyncPlugin {
    function __construct() {
      add_action('admin_menu', array($this, 'adminPage'));
      add_action('admin_init', array($this, 'settings'));
    }

    public static function activate() {
      if ( get_option('fsp_api_url') === false ) {
        update_option('fsp_api_url', 'https://fakestoreapi.com/products');
      }
      update_option('fsp_last_sync', '');
      update_option('fsp_last_imported', 0);
      update_option('fsp_last_updated', 0);
    }

    public static function deactivate() {
      delete_option('fsp_api_url');
      delete_option('fsp_last_sync');
      delete_option('fsp_last_imported');
      delete_option('fsp_last_updated');
    }

    function settings() {
      add_settings_section('fsp_first_section', null, null, 'fakestore-settings-page');
      add_settings_field('fsp_api_url', 'API Base URL', array($this, 'apiHTML'), 'fakestore-settings-page', 'fsp_first_section');
      register_setting('fakestoreplugin', 'fsp_api_url', array('sanitize_callback' => array($this, 'sanitizeAPIURL'), 'default' => 'https://fakestoreapi.com/products')); 
    }

    function sanitizeAPIURL($input) {
      if (empty($input)) {
        add_settings_error('fsp_api_url', 'fsp_apiurl_error', 'API URL must be entered.');
        return get_option('fsp_api_url');
      }

      return $input;
    }

    function apiHTML() { ?>
      <input type="url" name="fsp_api_url" value="<?php echo esc_attr(get_option('fsp_api_url', 'https://fakestoreapi.com/products')); ?>" size="60">
    <?php }

    function adminPage() {
      add_options_page('Fakestore Settings', 'Fakestore', 'manage_options', 'fakestore-settings-page', array($this, 'pageHTML'));
    }

    function pageHTML() { ?>
      <div class="wrap">
        <h1>Fakestore Settings</h1>
        <form action="options.php" method="POST">
          <?php 
            settings_fields('fakestoreplugin');
            do_settings_sections('fakestore-settings-page');
            submit_button(); 
          ?>
        </form>

        <h2>Sync Products</h2>
        <form method="post">
          <?php wp_nonce_field('fsp_sync_action', 'fsp_sync_nonce'); ?>
          <input type="submit" name="fsp_sync_now" class="button button-primary" value="Sync Now">
        </form>

        <?php
          if (isset($_POST['fsp_sync_now']) && check_admin_referer('fsp_sync_action', 'fsp_sync_nonce')) {
            $this->runSync();
          }
        ?>

      </div>
    <?php }
  }

  $fakestoreProductSyncPlugin = new FakestoreProductSyncPlugin;

  // Register activation and deactivation hooks
  register_activation_hook(__FILE__, array('FakestoreProductSyncPlugin', 'activate'));
  register_deactivation_hook(__FILE__, array('FakestoreProductSyncPlugin', 'deactivate'));

?>