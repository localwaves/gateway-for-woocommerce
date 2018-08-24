<?php

/**
 * Copyright 2018 Localwaves.xyz
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WcLocal')) {

    class WcLocal
    {

        private static $instance;
        public static $version = '0.4.1';
        public static $plugin_basename;
        public static $plugin_path;
        public static $plugin_url;

        protected function __construct()
        {
        	self::$plugin_basename = plugin_basename(__FILE__);
        	self::$plugin_path = trailingslashit(dirname(__FILE__));
        	self::$plugin_url = plugin_dir_url(self::$plugin_basename);
            add_action('plugins_loaded', array($this, 'init'));
        }

        public static function getInstance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function init()
        {
            $this->initGateway();
        }

        public function initGateway()
        {

            if (!class_exists('WC_Payment_Gateway')) {
                return;
            }

            if (class_exists('WC_Local_Gateway')) {
	            return;
	        }

	        /*
	         * Include gateway classes
	         * */
	        include_once plugin_basename('includes/base58/src/Base58.php');
	        include_once plugin_basename('includes/base58/src/ServiceInterface.php');
	        include_once plugin_basename('includes/base58/src/GMPService.php');
	        include_once plugin_basename('includes/base58/src/BCMathService.php');
	        include_once plugin_basename('includes/class-local-gateway.php');
	        include_once plugin_basename('includes/class-local-api.php');
	        include_once plugin_basename('includes/class-local-exchange.php');
	        include_once plugin_basename('includes/class-local-settings.php');
	        include_once plugin_basename('includes/class-local-ajax.php');

	        add_filter('woocommerce_payment_gateways', array($this, 'addToGateways'));
            add_filter('woocommerce_currencies', array($this, 'LocalCurrencies'));
            add_filter('woocommerce_currency_symbol', array($this, 'LocalCurrencySymbols'), 10, 2);

	        add_filter('woocommerce_get_price_html', array($this, 'LocalFilterPriceHtml'), 10, 2);
	        add_filter('woocommerce_cart_item_price', array($this, 'LocalFilterCartItemPrice'), 10, 3);
	        add_filter('woocommerce_cart_item_subtotal', array($this, 'LocalFilterCartItemSubtotal'), 10, 3);
	        add_filter('woocommerce_cart_subtotal', array($this, 'LocalFilterCartSubtotal'), 10, 3);
	        add_filter('woocommerce_cart_totals_order_total_html', array($this, 'LocalFilterCartTotal'), 10, 1);

	    }

	    public static function addToGateways($gateways)
	    {
	        $gateways['local'] = 'WcLocalGateway';
	        return $gateways;
	    }

        public function LocalCurrencies( $currencies )
        {
            $currencies['LOCAL'] = __( 'Local', 'local' );
            $currencies['WNET'] = __( 'Wavesnode.NET', 'wnet' );
            $currencies['ARTcoin'] = __( 'ARTcoin', 'ARTcoin' );
            $currencies['POL'] = __( 'POLTOKEN.PL', 'POL' );
            $currencies['Wykop Coin'] = __( 'WYKOP.PL', 'Wykop Coin' );
			$currencies['Surfcash'] = __( 'Surfcash', 'surfcash' );
			$currencies['TN'] = __( 'TurtleNode', 'tn' );
			$currencies['Ecop'] = __( 'Ecop', 'Ecop' );
            return $currencies;
        }

        public function LocalCurrencySymbols( $currency_symbol, $currency ) {
            switch( $currency ) {
                case 'LOCAL': $currency_symbol = 'LOCAL'; break;
                case 'WNET': $currency_symbol = 'WNET'; break;
                case 'ARTcoin': $currency_symbol = 'ARTcoin'; break;
                case 'POL': $currency_symbol = 'POL'; break;
                case 'Wykop Coin': $currency_symbol = 'Wykop Coin'; break;
				case 'Surfcash': $currency_symbol = 'surfcash'; break;
				case 'TN': $currency_symbol = 'TN'; break;
				case 'Ecop': $currency_symbol = 'Ecop'; break;
            }
            return $currency_symbol;
        }

	    public function LocalFilterCartTotal($value)
	    {
	        return $this->convertToLocalPrice($value, WC()->cart->total);
	    }

	    public function LocalFilterCartItemSubtotal($cart_subtotal, $compound, $that)
	    {
	        return $this->convertToLocalPrice($cart_subtotal, $that->subtotal);
	    }

	    public function LocalFilterPriceHtml($price, $that)
	    {
	        return $this->convertToLocalPrice($price, $that->price);
	    }

	    public function LocalFilterCartItemPrice($price, $cart_item, $cart_item_key)
	    {
	        $item_price = ($cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']) / $cart_item['quantity'];
	        return $this->convertToLocalPrice($price,$item_price);
	    }

	    public function LocalFilterCartSubtotal($price, $cart_item, $cart_item_key)
	    {
	        $subtotal = $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
	        return $this->convertToLocalPrice($price, $subtotal);
	    }

	    private function convertToLocalPrice($price_string, $price)
	    {
            $options = get_option('woocommerce_local_settings');
            if(!in_array(get_woocommerce_currency(), array("LOCAL","WNET","ARTcoin","POL","Wykop Coin","Surfcash","TN","Ecop")) && $options['show_prices'] == 'yes') {
                $local_currency = $options['asset_code'];
                if(empty($local_currency)) {
                    $local_currency = 'Local';
                }
                $local_assetId = $options['asset_id'];
                if(empty($local_assetId)) {
                    $local_assetId = null;
                }
                $local_price = LocalExchange::convertToAsset(get_woocommerce_currency(), $price,$local_assetId);
                if ($local_price) {
                    $price_string .= '&nbsp;(<span class="woocommerce-price-amount amount">' . $local_price . '&nbsp;</span><span class="woocommerce-price-currencySymbol">'.$local_currency.')</span>';
                }
            }
	        return $price_string;
	    }
    }

}

WcLocal::getInstance();

function localGateway_textdomain() {
    load_plugin_textdomain( 'local-gateway-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'localGateway_textdomain' );
