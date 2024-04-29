# WooCommerce Smooth Generator
A super-smooth generator for products, orders, coupons, customers, and terms. WP-CLI is the preferred interface for using the plugin. There is also a WP Admin UI at Dashboard > Tools > WooCommerce Smooth Generator with (for now) more limited functionality.

## Installation

1. Download the latest release as a zip file from https://github.com/woocommerce/wc-smooth-generator/releases/
1. Install in your WordPress site as you would any other plugin zip file.

## WP-CLI Commands

You can see a summary of all available commands by running `wp help wc generate`, and more detailed guidance for each individual command is available by running `wp help wc generate <command name>`.

### Products

Generate products based on the number of products parameter.
- `wp wc generate products <nr of products>`

Generate products of the specified type. `simple` or `variable`.
- `wp wc generate products <nr of products> --type=simple`

### Orders

Generate orders from existing products based on the number of orders parameter, customers will also be generated to mimic guest checkout.

Generate orders for the current date
- `wp wc generate orders <nr of orders>`

Generate orders with random dates between `--date-start` and the current date.
- `wp wc generate orders <nr of orders> --date-start=2018-04-01`

Generate orders with random dates between `--date-start` and `--date-end`.
- `wp wc generate orders <nr of orders> --date-start=2018-04-01 --date-end=2018-04-24`

Generate orders with a specific status.
- `wp wc generate orders <nr of orders> --status=completed`

#### Order Attribution

Order Attribution represents the origin of data for an order. By default, random values are generated and assigned to the order. Orders with a creation date before 2024-01-09 will not have attribution metadata added, as the feature was not available in WooCommerce at that time.

Skip order attribution meta data genereation.
- `wp wc generate orders <nr of orders> --skip-order-attribution`

### Coupons

Generate coupons based on the number of coupons parameter.
- `wp wc generate coupons <nr of coupons>`

Generate coupons with a minimum discount amount.
- `wp wc generate coupons <nr of coupons> --min=5`

Generate coupons with a maximum discount amount.
- `wp wc generate coupons <nr of coupons> --max=50`

### Customers

Generate customers based on the number of customers parameter.
- `wp wc generate customers <nr of customers>`

### Terms

Generate terms in the Product Categories taxonomy based on the number of terms parameter.
- `wp wc generate terms product_cat <nr of terms>`

Generate hierarchical product categories with a maximum number of sub-levels.
- `wp wc generate terms product_cat <nr of terms> --max-depth=5`

Generate product categories that are all child terms of an existing product category term.
- `wp wc generate terms product_cat <nr of terms> --parent=123`

Generate terms in the Product Tags taxonomy based on the number of terms parameter.
- `wp wc generate terms product_tag <nr of terms>`

## Development

Requirements

* Node.js v14+
* Composer v2+

1. If you use [Node Version Manager](https://github.com/nvm-sh/nvm) (nvm) you can run `nvm use` to ensure your current Node version is compatible.
1. Run `npm run setup` to get started. This will install a pre-commit Git hook that will lint changes to PHP files before they are committed. It uses the same phpcs ruleset that's used by WooCommerce Core.
