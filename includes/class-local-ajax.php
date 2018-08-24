<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax class
 */
class LocalAjax
{

    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        add_action('wp_ajax_check_local_payment', array(__CLASS__, 'checkLocalPayment'));
    }

    public function checkLocalPayment()
    {
        global $woocommerce;
        $woocommerce->cart->get_cart();

        $options = get_option('woocommerce_local_settings');

        $payment_total   = WC()->session->get('local_payment_total');
        $destination_tag = WC()->session->get('local_destination_tag');

        $ra     = new LocalApi($options['address']);
        $result = $ra->findByDestinationTag($destination_tag);

        $result['match'] = ($result['amount'] == $payment_total ) ? true : false;

        echo json_encode($result);
        exit();
    }

}

LocalAjax::getInstance();
