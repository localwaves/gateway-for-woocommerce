<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
if (!class_exists('LocalSettings')) {

    class LocalSettings
    {

        public static function fields()
        {

            return apply_filters('wc_local_settings',

                array(
                    'enabled'     => array(
                        'title'   => __('Enable/Disable', 'local-gateway-for-woocommerce'),
                        'type'    => 'checkbox',
                        'label'   => __('Enable Local payments', 'local-gateway-for-woocommerce'),
                        'default' => 'yes',
                    ),
                    'title'       => array(
                        'title'       => __('Title', 'local-gateway-for-woocommerce'),
                        'type'        => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'local-gateway-for-woocommerce'),
                        'default'     => __('Pay with Local', 'local-gateway-for-woocommerce'),
                        'desc_tip'    => true,
                    ),
                    'description' => array(
                        'title'   => __('Customer Message', 'local-gateway-for-woocommerce'),
                        'type'    => 'textarea',
                        'default' => __('Ultra-fast and secure checkout with Local'),
                    ),
                    'address'     => array(
                        'title'       => __('Destination address', 'local-gateway-for-woocommerce'),
                        'type'        => 'text',
                        'default'     => '',
                        'description' => __('This addresses will be used for receiving funds.', 'local-gateway-for-woocommerce'),
                    ),
                    'show_prices' => array(
                        'title'   => __('Convert prices', 'local-gateway-for-woocommerce'),
                        'type'    => 'checkbox',
                        'label'   => __('Add prices in Local (or asset)', 'local-gateway-for-woocommerce'),
                        'default' => 'no',

                    ),
                    'secret'      => array(
                        'type'    => 'hidden',
                        'default' => sha1(get_bloginfo() . Date('U')),

                    ),
                    'asset_id'     => array(
                        'title'       => __('Asset ID', 'local-gateway-for-woocommerce'),
                        'type'        => 'text',
                        'default'     => null,
                        'description' => __('This is the asset Id used for transactions.', 'local-gateway-for-woocommerce'),
                    ),
                    'asset_code'     => array(
                        'title'       => __('Asset code (short name = currency code = currency symbol)', 'local-gateway-for-woocommerce'),
                        'type'        => 'text',
                        'default'     => null,
                        'description' => __('This is the Asset Currency code for exchange rates. If omitted Local will be used', 'local-gateway-for-woocommerce'),
                    ),
                    'asset_description'     => array(
                        'title'       => __('Asset description', 'local-gateway-for-woocommerce'),
                        'type'        => 'text',
                        'default'     => null,
                        'description' => __('Asset full name', 'local-gateway-for-woocommerce'),
                    ),
                )
            );
        }
    }

}
