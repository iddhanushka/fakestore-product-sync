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

    // sync logic with product import
    function runSync() {
      $apiUrl = get_option('fsp_api_url', 'https://fakestoreapi.com/products');
      $response = wp_remote_get($apiUrl);

      if (is_wp_error($response)) {
        echo '<div class="error"><p>API request failed.</p></div>';
        return;
      }

      $products = json_decode(wp_remote_retrieve_body($response));
      if (empty($products)) {
        echo '<div class="error"><p>No products returned from API.</p></div>';
        return;
      }

      $imported = 0;
      $updated  = 0;

      foreach ($products as $product) {
        $result = $this->importProduct($product);
        if ($result === 'imported') $imported++;
        if ($result === 'updated') $updated++;
      }

      update_option('fsp_last_sync', current_time('mysql'));
      update_option('fsp_last_imported', $imported);
      update_option('fsp_last_updated', $updated);

      echo '<div class="updated"><p>Imported: '.esc_html($imported).' | Updated: '.esc_html($updated).'</p></div>';
    }

    function importProduct($product) {
      $existingId = $this->getProductByFakestoreId($product->id);

      $postData = array(
        'post_title'   => sanitize_text_field($product->title),
        'post_content' => sanitize_textarea_field($product->description),
        'post_status'  => 'publish',
        'post_type'    => 'product',
      );

      if ($existingId) {
        $postData['ID'] = $existingId;
        $productId = wp_update_post($postData);
        $status = 'updated';
      } else {
        $productId = wp_insert_post($postData);
        update_post_meta($productId, '_fakestore_id', $product->id);
        $status = 'imported';
      }

      if ($productId) {
        update_post_meta($productId, '_regular_price', $product->price);
        update_post_meta($productId, '_price', $product->price);

        if (!empty($product->image)) {
          $this->setProductImage($productId, $product->image);
        }
      }

      return $status;
    }

    function getProductByFakestoreId($fakestoreId) {
      $args = array(
        'post_type'  => 'product',
        'meta_key'   => '_fakestore_id',
        'meta_value' => $fakestoreId,
        'fields'     => 'ids',
        'posts_per_page' => 1
      );
      
      $query = new WP_Query($args);
      return $query->have_posts() ? $query->posts[0] : false;
    }

    function setProductImage($productId, $imageUrl) {
      require_once ABSPATH . 'wp-admin/includes/image.php';
      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/media.php';

      media_sideload_image(esc_url_raw($imageUrl), $productId);
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