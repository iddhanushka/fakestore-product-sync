# Fakestore Product Sync

A WordPress plugin to synchronize products from FakeStore API into WooCommerce.
This plugin was developed as part of a technical assignment.

## Features

Configure API base URL from plugin settings.

Import products from FakeStore API (/products).

Map fields to WooCommerce product fields:

\*Title → Product title

\*Description → Product content

\*Price → Regular price

\*Image → Product thumbnail

Prevent duplicates using \_fakestore_id postmeta.

Manual "Sync Now" button to trigger import.

Show last sync time, imported, and updated counts.

Activation hook sets defaults.

Deactivation hook cleans up options.

Uninstall script removes all plugin data

## Installation

1. Ensure WooCommerce is installed and activated.

2. Download or clone this repository into your WordPress plugins directory:

```python

wp-content/plugins/fakestore-product-sync

```

3. Or, Download ZIP file from github repo and login into Wordpress dashboard, then go to plugin,
   Add plugin → Upload plugin.

4. Activate the plugin from the Plugins menu in WordPress.

5. Navigate to Settings → Fakestore to configure the API URL and run sync.

## Usage

Go to Settings → Fakestore.

Enter your API Base URL (default: https://fakestoreapi.com/products).

Click Save Changes.

Use the Sync Now button to import products.

Last sync details will appear at the bottom of the settings page

Please make sure to update tests as appropriate.

## Security

All inputs are sanitized before saving.

Nonces are used for form submissions.

Capability checks (manage_options) are applied for admin pages

## Development Notes

Built using WordPress best practices.

Relies only on core WordPress + WooCommerce APIs.
