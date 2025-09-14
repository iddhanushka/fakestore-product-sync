<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )  exit;

delete_option('fsp_api_url');
delete_option('fsp_last_sync');
delete_option('fsp_last_imported');
delete_option('fsp_last_updated');

?>