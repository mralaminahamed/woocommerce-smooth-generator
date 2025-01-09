<?php
/**
 * Abstract product generator class
 *
 * @package SmoothGenerator\Abstracts
 */

namespace WC\SmoothGenerator\Generator;

use WC\SmoothGenerator\Util\RandomRuntimeCache;

/**
 * Product data generator.
 */
class Product extends Generator {
	/**
	 * Holds array of product IDs for generating relationships.
	 *
	 * @var array Array of IDs.
	 */
	protected static $product_ids = array();

	/**
	 * Holds array of global attributes new products may reuse.
	 *
	 * @var array Array of attributes.
	 */
	protected static $global_attributes = array(
		'Color'        => array(
			'Green',
			'Blue',
			'Red',
			'Yellow',
			'Indigo',
			'Violet',
			'Black',
			'White',
			'Orange',
			'Pink',
			'Purple',
		),
		'Size'         => array(
			'Small',
			'Medium',
			'Large',
			'XL',
			'XXL',
			'XXXL',
		),
		'Numeric Size' => array(
			'6',
			'7',
			'8',
			'9',
			'10',
			'11',
			'12',
			'13',
			'14',
			'15',
			'16',
			'17',
			'18',
			'19',
			'20',
		),
	);

	/**
	 * Init faker library.
	 */
	protected static function init_faker() {
		parent::init_faker();
		self::$faker->addProvider( new \Bezhanov\Faker\Provider\Commerce( self::$faker ) );
	}

	/**
	 * Return a new product.
	 *
	 * @param bool  $save Save the object before returning or not.
	 * @param array $assoc_args Arguments passed via the CLI for additional customization.
	 * @return \WC_Product The product object consisting of random data.
	 */
	public static function generate( $save = true, $assoc_args = array() ) {
		self::init_faker();

		$type = self::get_product_type( $assoc_args );
		switch ( $type ) {
			case 'simple':
			default:
				$product = self::generate_simple_product();
				break;
			case 'variable':
				$product = self::generate_variable_product();
				break;
		}

		if ( $product ) {
			$product->save();
		}

		// Limit size of stored relationship IDs.
		if ( 100 < count( self::$product_ids ) ) {
			shuffle( self::$product_ids );
			self::$product_ids = array_slice( self::$product_ids, 0, 50 );
		}

		self::$product_ids[] = $product->get_id();

		/**
		 * Action: Product generator returned a new product.
		 *
		 * @since 1.2.0
		 *
		 * @param \WC_Product $product
		 */
		do_action( 'smoothgenerator_product_generated', $product );

		return $product;
	}

	/**
	 * Create multiple products.
	 *
	 * @param int    $amount   The number of products to create.
	 * @param array  $args     Additional args for product creation.
	 *
	 * @return int[]|\WP_Error
	 */
	public static function batch( $amount, array $args = array() ) {
		$amount = self::validate_batch_amount( $amount );
		if ( is_wp_error( $amount ) ) {
			return $amount;
		}

		$product_ids = array();

		for ( $i = 1; $i <= $amount; $i ++ ) {
			$product       = self::generate( true, $args );
			$product_ids[] = $product->get_id();
		}

		// In case multiple batches are being run in one request, refresh the cache data.
		RandomRuntimeCache::reset();

		return $product_ids;
	}

	/**
	 * Create a new global attribute.
	 *
	 * @param string $raw_name Attribute name (label).
	 * @return int Attribute ID.
	 */
	protected static function create_global_attribute( $raw_name ) {
		$slug = wc_sanitize_taxonomy_name( $raw_name );

		$attribute_id = wc_create_attribute( array(
			'name'         => $raw_name,
			'slug'         => $slug,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );

		$taxonomy_name = wc_attribute_taxonomy_name( $slug );
		register_taxonomy(
			$taxonomy_name,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
			apply_filters( 'woocommerce_taxonomy_args_' . $taxonomy_name, array(
				'labels'       => array(
					'name' => $raw_name,
				),
				'hierarchical' => true,
				'show_ui'      => false,
				'query_var'    => true,
				'rewrite'      => false,
			) )
		);

		self::$global_attributes[ $raw_name ] = isset( self::$global_attributes[ $raw_name ] ) ? self::$global_attributes[ $raw_name ] : array();

		delete_transient( 'wc_attribute_taxonomies' );

		return $attribute_id;
	}

	/**
	 * Generate attributes for a product.
	 *
	 * @param integer $qty Number of attributes to generate.
	 * @param integer $maximum_terms Maximum number of terms per attribute to generate.
	 * @return array Array of attributes.
	 */
	protected static function generate_attributes( $qty = 1, $maximum_terms = 10 ) {
		$used_names = array();
		$attributes = array();

		for ( $i = 0; $i < $qty; $i++ ) {
			$attribute = new \WC_Product_Attribute();
			$attribute->set_id( 0 );
			$attribute->set_position( $i );
			$attribute->set_visible( true );
			$attribute->set_variation( true );

			if ( self::$faker->boolean() ) {
				$raw_name = array_rand( self::$global_attributes );

				if ( in_array( $raw_name, $used_names, true ) ) {
					$raw_name = ucfirst( substr( self::$faker->word(), 0, 28 ) );
				}

				$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
				$attribute_name   = array_search( $raw_name, $attribute_labels, true );

				if ( ! $attribute_name ) {
					$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
				}

				$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );

				if ( ! $attribute_id ) {
					$attribute_id = self::create_global_attribute( $raw_name );
				}

				$slug          = wc_sanitize_taxonomy_name( $raw_name );
				$taxonomy_name = wc_attribute_taxonomy_name( $slug );

				$attribute->set_name( $taxonomy_name );
				$attribute->set_id( $attribute_id );

				$used_names[] = $raw_name;

				$num_values      = self::$faker->numberBetween( 1, $maximum_terms );
				$values          = array();
				$existing_values = isset( self::$global_attributes[ $raw_name ] ) ? self::$global_attributes[ $raw_name ] : array();

				for ( $j = 0; $j < $num_values; $j++ ) {
					$value = '';

					if ( self::$faker->boolean( 80 ) && ! empty( $existing_values ) ) {
						shuffle( $existing_values );
						$value = array_pop( $existing_values );
					}

					if ( empty( $value ) || in_array( $value, $values, true ) ) {
						$value = ucfirst( self::$faker->words( self::$faker->numberBetween( 1, 2 ), true ) );
					}

					self::$global_attributes[ $raw_name ][] = $value;

					$values[] = $value;
				}
				$attribute->set_options( $values );
			} else {
				$attribute->set_name( ucfirst( self::$faker->words( self::$faker->numberBetween( 1, 3 ), true ) ) );
				$attribute->set_options( array_filter( self::$faker->words( self::$faker->numberBetween( 2, 4 ), false ) ), 'ucfirst' );
			}
			$attributes[] = $attribute;
		}

		return $attributes;
	}

	/**
	 * Returns a product type to generate. If no type is specified, or an invalid type is specified,
	 * a weighted random type is returned.
	 *
	 * @param array $assoc_args CLI arguments.
	 * @return string A product type.
	 */
	protected static function get_product_type( array $assoc_args ) {
		$type  = $assoc_args['type'] ?? null;
		$types = array(
			'simple',
			'variable',
		);

		if ( ! is_null( $type ) && in_array( $type, $types, true ) ) {
			return $type;
		} else {
			return self::random_weighted_element( array(
				'simple'   => 80,
				'variable' => 20,
			) );
		}
	}

	/**
	 * Generate a variable product and return it.
	 *
	 * @return \WC_Product_Variable
	 */
	protected static function generate_variable_product() {
		$name              = ucwords( self::$faker->productName );
		$will_manage_stock = self::$faker->boolean();
		$product           = new \WC_Product_Variable();

		$gallery    = self::maybe_get_gallery_image_ids();
		$attributes = self::generate_attributes( self::$faker->numberBetween( 1, 3 ), 5 );

		$product->set_props( array(
			'name'              => $name,
			'featured'          => self::$faker->boolean( 10 ),
			'sku'               => sanitize_title( $name ) . '-' . self::$faker->ean8,
			'attributes'        => $attributes,
			'tax_status'        => 'taxable',
			'tax_class'         => '',
			'manage_stock'      => $will_manage_stock,
			'stock_quantity'    => $will_manage_stock ? self::$faker->numberBetween( -100, 100 ) : null,
			'stock_status'      => 'instock',
			'backorders'        => self::$faker->randomElement( array( 'yes', 'no', 'notify' ) ),
			'sold_individually' => self::$faker->boolean( 20 ),
			'upsell_ids'        => self::get_existing_product_ids(),
			'cross_sell_ids'    => self::get_existing_product_ids(),
			'image_id'          => self::get_image(),
			'category_ids'      => self::get_term_ids( 'product_cat', self::$faker->numberBetween( 0, 3 ) ),
			'tag_ids'           => self::get_term_ids( 'product_tag', self::$faker->numberBetween( 0, 5 ) ),
			'gallery_image_ids' => $gallery,
			'reviews_allowed'   => self::$faker->boolean(),
			'purchase_note'     => self::$faker->boolean() ? self::$faker->text() : '',
			'menu_order'        => self::$faker->numberBetween( 0, 10000 ),
		) );
		// Need to save to get an ID for variations.
		$product->save();

		// Create variations, one for each attribute value combination.
		$variation_attributes = wc_list_pluck( array_filter( $product->get_attributes(), 'wc_attributes_array_filter_variation' ), 'get_slugs' );
		$possible_attributes  = array_reverse( wc_array_cartesian( $variation_attributes ) );
		foreach ( $possible_attributes as $possible_attribute ) {
			$price      = self::$faker->randomFloat( 2, 1, 1000 );
			$is_on_sale = self::$faker->boolean( 30 );
			$sale_price = $is_on_sale ? self::$faker->randomFloat( 2, 0, $price ) : '';
			$is_virtual = self::$faker->boolean( 20 );
			$variation  = new \WC_Product_Variation();
			$variation->set_props( array(
				'parent_id'         => $product->get_id(),
				'attributes'        => $possible_attribute,
				'regular_price'     => $price,
				'sale_price'        => $sale_price,
				'date_on_sale_from' => '',
				'date_on_sale_to'   => self::$faker->iso8601( date( 'c', strtotime( '+1 month' ) ) ),
				'tax_status'        => 'taxable',
				'tax_class'         => '',
				'manage_stock'      => $will_manage_stock,
				'stock_quantity'    => $will_manage_stock ? self::$faker->numberBetween( -20, 100 ) : null,
				'stock_status'      => 'instock',
				'weight'            => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
				'length'            => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
				'width'             => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
				'height'            => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
				'virtual'           => $is_virtual,
				'downloadable'      => false,
				'image_id'          => self::get_image(),
			) );
			$variation->save();
		}
		$data_store = $product->get_data_store();
		$data_store->sort_all_product_variations( $product->get_id() );

		return $product;
	}

	/**
	 * Generate a simple product and return it.
	 *
	 * @return \WC_Product
	 */
	protected static function generate_simple_product() {
		$name              = ucwords( self::$faker->productName );
		$will_manage_stock = self::$faker->boolean();
		$is_virtual        = self::$faker->boolean();
		$price             = self::$faker->randomFloat( 2, 1, 1000 );
		$is_on_sale        = self::$faker->boolean( 30 );
		$sale_price        = $is_on_sale ? self::$faker->randomFloat( 2, 0, $price ) : '';
		$product           = new \WC_Product();

		$image_id = self::get_image();
		$gallery  = self::maybe_get_gallery_image_ids();

		$product->set_props( array(
			'name'               => $name,
			'featured'           => self::$faker->boolean(),
			'catalog_visibility' => 'visible',
			'description'        => self::$faker->paragraphs( self::$faker->numberBetween( 1, 5 ), true ),
			'short_description'  => self::$faker->text(),
			'sku'                => sanitize_title( $name ) . '-' . self::$faker->ean8,
			'regular_price'      => $price,
			'sale_price'         => $sale_price,
			'date_on_sale_from'  => '',
			'date_on_sale_to'    => self::$faker->iso8601( date( 'c', strtotime( '+1 month' ) ) ),
			'total_sales'        => self::$faker->numberBetween( 0, 10000 ),
			'tax_status'         => 'taxable',
			'tax_class'          => '',
			'manage_stock'       => $will_manage_stock,
			'stock_quantity'     => $will_manage_stock ? self::$faker->numberBetween( -100, 100 ) : null,
			'stock_status'       => 'instock',
			'backorders'         => self::$faker->randomElement( array( 'yes', 'no', 'notify' ) ),
			'sold_individually'  => self::$faker->boolean( 20 ),
			'weight'             => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
			'length'             => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
			'width'              => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
			'height'             => $is_virtual ? '' : self::$faker->numberBetween( 1, 200 ),
			'upsell_ids'         => self::get_existing_product_ids(),
			'cross_sell_ids'     => self::get_existing_product_ids(),
			'parent_id'          => 0,
			'reviews_allowed'    => self::$faker->boolean(),
			'purchase_note'      => self::$faker->boolean() ? self::$faker->text() : '',
			'menu_order'         => self::$faker->numberBetween( 0, 10000 ),
			'virtual'            => $is_virtual,
			'downloadable'       => false,
			'category_ids'       => self::get_term_ids( 'product_cat', self::$faker->numberBetween( 0, 3 ) ),
			'tag_ids'            => self::get_term_ids( 'product_tag', self::$faker->numberBetween( 0, 5 ) ),
			'shipping_class_id'  => 0,
			'image_id'           => $image_id,
			'gallery_image_ids'  => $gallery,
		) );

		return $product;
	}

	/**
	 * Get a number of random term IDs for a specific taxonomy.
	 *
	 * @param string $taxonomy The taxonomy to get terms for.
	 * @param int    $limit    The number of term IDs to get. Maximum value of 50.
	 *
	 * @return array
	 */
	protected static function get_term_ids( $taxonomy, $limit ) {
		if ( $limit <= 0 ) {
			return array();
		}

		if ( ! RandomRuntimeCache::exists( $taxonomy ) ) {
			$args = array(
				'taxonomy'   => $taxonomy,
				'number'     => 50,
				'orderby'    => 'count',
				'order'      => 'ASC',
				'hide_empty' => false,
				'fields'     => 'ids',
			);

			if ( 'product_cat' === $taxonomy ) {
				$uncategorized = get_term_by( 'slug', 'uncategorized', 'product_cat' );
				if ( $uncategorized ) {
					$args['exclude'] = $uncategorized->term_id;
				}
			}

			$term_ids = get_terms( $args );

			RandomRuntimeCache::set( $taxonomy, $term_ids );
		}

		RandomRuntimeCache::shuffle( $taxonomy );

		return RandomRuntimeCache::get( $taxonomy, $limit );
	}

	/**
	 * Generate an image gallery.
	 *
	 * @return array
	 */
	protected static function maybe_get_gallery_image_ids() {
		$gallery = array();

		$create_gallery = self::$faker->boolean( 10 );

		if ( ! $create_gallery ) {
			return;
		}

		$image_count = wp_rand( 0, 3 );

		for ( $i = 0; $i < $image_count; $i ++ ) {
			$gallery[] = self::get_image();
		}

		return $gallery;
	}

	/**
	 * Get some random existing product IDs.
	 *
	 * @param int $limit Number of term IDs to get.
	 * @return array
	 */
	protected static function get_existing_product_ids( $limit = 5 ) {
		if ( ! self::$product_ids ) {
			self::$product_ids = wc_get_products(
				array(
					'limit'   => $limit,
					'return'  => 'ids',
					'status'  => 'publish',
					'orderby' => 'rand',
				)
			);
		}

		$random_limit = wp_rand( 0, $limit );

		if ( ! $random_limit ) {
			return array();
		}

		shuffle( self::$product_ids );

		return array_slice( self::$product_ids, 0, min( count( self::$product_ids ), $random_limit ) );
	}
}
