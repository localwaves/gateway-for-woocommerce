<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway class
 */
class WcLocalGateway extends WC_Payment_Gateway
{
    public $id;
    public $title;
    public $form_fields;
    public $addresses;
    private $assetId;
    private $assetCode;
    private $currencyIsLocal = false;

    public function __construct()
    {

        $this->id          			= 'local';
        $this->title       			= $this->get_option('title');
        $this->description 			= $this->get_option('description');
        $this->address   			= $this->get_option('address');
        $this->secret   			= $this->get_option('secret');
        $this->order_button_text 	= __('Awaiting transfer..','local-gateway-for-woocommerce');
        $this->has_fields 			= true;

        // assetCode+id if woocommerce_currency is set to Local-like currency
        $this->currencyIsLocal = in_array(get_woocommerce_currency(), array("LOCAL","WNET","ARTcoin","POL","Wykop Coin","Surfcash","TN","Ecop"));
        if($this->currencyIsLocal) {
            if (get_woocommerce_currency() == "Local") {
                $this->assetCode = 'Local';
                $this->assetId = null;
            } else if (get_woocommerce_currency() == "WNET") {
                $this->assetCode = 'WNET';
                $this->assetId = 'AxAmJaro7BJ4KasYiZhw7HkjwgYtt2nekPuF2CN9LMym';
            } else if (get_woocommerce_currency() == "ARTcoin") {
                $this->assetCode = 'ARTcoin';
                $this->assetId = 'GQe2a2uReaEiHLdjzC8q4Popr9tnKonEpcaihEoZrNiR';
            } else if (get_woocommerce_currency() == "POL") {
                $this->assetCode = 'POL';
                $this->assetId = 'Fx2rhWK36H1nfXsiD4orNpBm2QG1JrMhx3eUcPVcoZm2';
            } else if (get_woocommerce_currency() == "Wykop Coin") {
                $this->assetCode = 'Wykop Coin';
                $this->assetId = 'AHcY2BMoxDZ57mLCWWQYBcWvKzf7rdFMgozJn6n4xgLt';
			} else if (get_woocommerce_currency() == "Surfcash") {
                $this->assetCode = 'Surfcash';
                $this->assetId = 'GcQ7JVnwDizXW8KkKLKd8VDnygGgN7ZnpwnP3bA3VLsE';
			} else if (get_woocommerce_currency() == "TN") {
                $this->assetCode = 'TN';
                $this->assetId = 'HxQSdHu1X4ZVXmJs232M6KfZi78FseeWaEXJczY6UxJ3';
			} else if (get_woocommerce_currency() == "Ecop") {
                $this->assetCode = 'Ecop';
                $this->assetId = 'DcLDr4g2Ys4D2RWpkhnUMjMR1gVNPxHEwNkmZzmakQ9R';
				}
        } else {
            $this->assetId              = $this->get_option('asset_id');
            $this->assetCode            = $this->get_option('asset_code');
        }
        if(empty($this->assetId)) {
            $this->assetId = null;
        }
        if(empty($this->assetCode)) {
            $this->assetCode = 'Local';
        }

        $this->initFormFields();

        $this->initSettings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options',
        ));
        add_action('wp_enqueue_scripts', array($this, 'paymentScripts'));

        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyouPage'));

    }

    public function initFormFields()
    {
        parent::init_form_fields();
        $this->form_fields = LocalSettings::fields();
    }

    public function initSettings()
    {
    	// sha1( get_bloginfo() )
        parent::init_settings();
    }

    public function payment_fields()
    {
    	global $woocommerce;
    	$woocommerce->cart->get_cart();
        $total_converted = $this->get_order_total();
        $rate = null;
        if(!$this->currencyIsLocal) {
            $total_converted = LocalExchange::convertToAsset(get_woocommerce_currency(), $total_converted,$this->assetId);
            $rate = $total_converted / $this->get_order_total();
        }

		// Set decimals for tokens other than default value 8
		if (get_woocommerce_currency() == "Ecop") {
		$total_local = $total_converted * 100000;
		}
		else if (get_woocommerce_currency() == "Surfcash") {
		$total_local = $total_converted * 100;
		}
		else if (get_woocommerce_currency() == "TN") {
		$total_local = $total_converted * 100;
		}
		else {
			$total_local = $total_converted * 100000000;
		}


        $destination_tag = hexdec( substr(sha1(current_time(timestamp,1) . key ($woocommerce->cart->cart_contents )  ), 0, 7) );
        $base58 = new StephenHill\Base58();
        $destination_tag_encoded = $base58->encode(strval($destination_tag));
        // set session data
        WC()->session->set('local_payment_total', $total_local);
        WC()->session->set('local_destination_tag', $destination_tag_encoded);
        WC()->session->set('local_data_hash', sha1( $this->secret . $total_converted ));
        //QR uri
        $url = "local://". $this->address ."?amount=". $total_local."&attachment=".$destination_tag;
        if($this->assetId) {
            $url .= "&asset=".$this->assetId;
        }?>
        <div id="local-form">
            <div class="local-container">
            <div>
                <?if ($this->description) { ?>
                <div class="separator"></div>
                <div id="local-description">
                    <?=apply_filters( 'wc_local_description', wpautop(  $this->description ) )?>
                </div>
                <?}?>
                <div class="separator"></div>
                <div class="local-container">
                <?if($rate!=null){?>
                <label class="local-label">
                    (1<?=get_woocommerce_currency()?> = <?=round($rate,6)?> <?=$this->assetCode?>)
                </label>
                <?}?>
                <p class="local-amount">
                    <span class="copy" data-success-label="<?=__('copied','local-gateway-for-woocommerce')?>"
                          data-clipboard-text="<?=esc_attr($total_converted)?>"><?=esc_attr($total_converted)?>
                    </span> <strong><?=$this->assetCode?></strong>
                </p>
                </div>
            </div>
            <div class="separator"></div>
            <div class="local-container">
                <label class="local-label"><?=__('destination address', 'local-gateway-for-woocommerce')?></label>
                <p class="local-address">
                    <span class="copy" data-success-label="<?=__('copied','local-gateway-for-woocommerce')?>"
                          data-clipboard-text="<?=esc_attr($this->address)?>"><?=esc_attr($this->address)?>
                    </span>
                </p>
            </div>
            <div class="separator"></div>
            <div class="local-container">
                <label class="local-label"><?=__('attachment', 'local-gateway-for-woocommerce')?></label>
                <p class="local-address">
                    <span class="copy" data-success-label="<?=__('copied','local-gateway-for-woocommerce')?>"
                          data-clipboard-text="<?=esc_attr($destination_tag)?>"><?=esc_attr($destination_tag)?>
                    </span>
                </p>
            </div>
            <div class="separator"></div>
            </div>
            <div id="local-qr-code" data-contents="<?=$url?>"></div>
            <div class="separator"></div>
            <div class="local-container">
                <p>
                    <?=sprintf(__('Send a payment of exactly %s to the address above (click the links to copy or scan the QR code). We will check in the background and notify you when the payment has been validated.', 'local-gateway-for-woocommerce'), '<strong>'. esc_attr($total_converted).' '.$this->assetCode.'</strong>' )?>
                </p>
                <strong>DO NOT FORGET THE ATTACHMENT IF YOU USE MANUAL PAYMENT! </strong>
                <p>
                    <?=sprintf(__('Please send your payment within %s.', 'local-gateway-for-woocommerce'), '<strong><span class="local-countdown" data-minutes="10">10:00</span></strong>' )?>
                </p>
                <p class="small">
                    <?=__('When the timer reaches 0 this form will refresh and update the attachment as well as the total amount using the latest conversion rate.', 'local-gateway-for-woocommerce')?>
                </p>
            </div>
            <input type="hidden" name="tx_hash" id="tx_hash" value="0"/>
        </div>
        <?
    }

    public function process_payment( $order_id )
    {
    	global $woocommerce;
        $this->order = new WC_Order( $order_id );

	    $payment_total   = WC()->session->get('local_payment_total');
        $destination_tag = WC()->session->get('local_destination_tag');

	    $ra = new localApi($this->address);
	    $transaction = $ra->getTransaction( $_POST['tx_hash']);

        if($transaction->attachment != $destination_tag) {
	    	exit('destination');
	    	return array(
		        'result'    => 'failure',
		        'messages' 	=> 'attachment mismatch'
		    );
	    }

		if($transaction->assetId != $this->assetId ) {
			return array(
		        'result'    => 'failure',
		        'messages' 	=> 'Wrong Asset'
		    );
		}

	    if($transaction->amount != $payment_total) {
	    	return array(
		        'result'    => 'failure',
		        'messages' 	=> 'amount mismatch'
		    );
	    }

        $this->order->payment_complete();

        $woocommerce->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($this->order)
        );
	}

    public function paymentScripts()
    {
        wp_enqueue_script('qrcode', plugins_url('assets/js/jquery.qrcode.min.js', WcLocal::$plugin_basename), array('jquery'), WcLocal::$version, true);
        wp_enqueue_script('initialize', plugins_url('assets/js/jquery.initialize.js', WcLocal::$plugin_basename), array('jquery'), WcLocal::$version, true);

        wp_enqueue_script('clipboard', plugins_url('assets/js/clipboard.js', WcLocal::$plugin_basename), array('jquery'), WcLocal::$version, true);
        wp_enqueue_script('woocommerce_local_js', plugins_url('assets/js/local.js', WcLocal::$plugin_basename), array(
            'jquery',
        ), WcLocal::$version, true);
        wp_enqueue_style('woocommerce_local_css', plugins_url('assets/css/local.css', WcLocal::$plugin_basename), array(), WcLocal::$version);

        // //Add js variables
        $local_vars = array(
            'wc_ajax_url' => WC()->ajax_url(),
            'nonce'      => wp_create_nonce("local-gateway-for-woocommerce"),
        );

        wp_localize_script('woocommerce_local_js', 'local_vars', apply_filters('local_vars', $local_vars));

    }

}
