<?php

use Automattic\Jetpack\Sync\Functions;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Epg Transfer Payment Gateway.
 *
 * Provides a Epg Payment Gateway. Based on code by Mike Pepper.
 *
 * @class       WC_Gateway_Epg
 * @extends     WC_Payment_Gateway
 * @version     0.0.1
 * @package     WooCommerce\Classes\Payment
 */
class WC_Gateway_Epg extends WC_Payment_Gateway
{

	/**
	 * Array of locales
	 *
	 * @var array
	 */
	public $locale;

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		$this->id                 = 'epg';
		$this->icon               = apply_filters('woocommerce_epg_icon', '');
		$this->has_fields         = false;
		$this->icon               = apply_filters('woocommerce_icon_epg', plugins_url('../assets/img/logo.png', __FILE__));
		$this->method_title       = __('Ecom Payment Gateway', 'epg');
		$this->method_description = __('Thanh toán qua cổng thanh toán EPG', 'epg');
		$this->supports           = array('products', 'refunds');
		global $wp_session;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option('title');
		$this->description  = $this->get_option('description');
		$this->epg_Url  = $this->get_option('epg_Url');
		$this->secretkey  = $this->get_option('secretkey');
		$this->epg_clientPrivateKey  = $this->get_option('epg_clientPrivateKey');
		$this->epg_paymentMethodCode  = $this->get_option('epg_paymentMethodCode');
		$this->epg_prefix  = $this->get_option('epg_prefix');
		$this->locale  = $this->get_option('locale');
		$this->provider_pk = $this->get_public_key($this->epg_Url);
		
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_epg', array($this, 'thankyou_page'));
		add_action( 'woocommerce_api_wc_gateway_epg', array( $this, 'check_ipn_response' ) );

		if ($this->epg_clientPrivateKey){
			$this->epg_clientPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $this->epg_clientPrivateKey . "\n-----END RSA PRIVATE KEY-----";
		}
	}

	private function get_public_key($url){
		$url_ = parse_url($url);
		$TEST = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwSoL0klKbH9+5Wzu3GgmDeCgR
EbetmEcsgS0K6eLYGgC4rI29n98dIpmPCoEYB/7byc02Ak3hPmzLVhfHj3RT8wu3
xZ/80KBofIjZzQ/3ElA9DZGTlibDlDS4OZCSm97s/rdvKc5dPVT7qAOmYES4meoF
D5DdAwiYCYxfJZWK8wIDAQAB
-----END PUBLIC KEY-----';

		$PROD = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA5B0wNbcbt3PixZdajvXD
BZgITw4Ov3aQgBy+ahToU+bM5OnYMp6XJK5CVSudxdLMuXkfiHoCUy/SJw0PL1De
3r8iH1M3VV0wfRz7QJV6RjO/oDSLpnPxA8KcT0ZeZNytucr+n2RwG++T9zcfR4g1
xBX1P9eLnfx9gPSwt61K1W+iSgLf4D7G2xDB3nbF5c6Sclt4iEj8AevMzl9Veisq
7ILAviTKtmtypiV3IzOJoA+98BfO6ZHvEdlPc7r+FUblyLSOsr1NMUM79fBT9BZr
e3QOYqJJp52HpUJasde7sbAzbCre/lU4lcNyhOvTgqbBf8XU+1tPOOh77TwJJ5d6
QXc4Bx4ITEf6gyKykNPlu87cY91cuKNoiC18vcBh5tOmsU/ZV3I3ROJqcqU1ZOMQ
US0qXSdPFTeNjmL4bD+pElJJ49ivzfSX3zG7OYYqdRii/n/yXjzVG3EOQPC55y11
Ew6SuGm9ATqLA88o/mwfio1zbdgmZnlyEox2gOAhXhGHMmcVuwUfHjeUo2tPqYcD
6gaZCc/vjBDRWtWujCrt24ObHijUJCk1Fvol/t719mqTGIR20Zo6rcD385ikTioQ
11ENW5WYSDayfRExFQBnQW17IGm1ToxjMsKrbxChHKaGy2X8UjhrRu3iCf9b1jS7
0EVZN5FCCLEBzPzKZv3hXJcCAwEAAQ==
-----END PUBLIC KEY-----';
		switch ($url_['host']) {
			case 'tapi.ecomit.vn':
			case 'localhost':
				return $TEST;
			default:
				return $PROD;
		}
	}

	private function validate_signature($data, $signature){
		return openssl_verify($data, base64_decode($signature), $this->provider_pk, OPENSSL_ALGO_SHA256);
	}
	
	/**
	 * Initialise Gateway Settings Form Fields. 
	 * 
	 */
	public function console_log($output, $with_script_tags = true)
	{
		$js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
			');';
		if ($with_script_tags) {
			$js_code = '<script>' . $js_code . '</script>';
		}
		echo $js_code;
	}
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled'         => array(
				'title'   => __('Enable/Disable', 'woocommerce'),
				'type'    => 'checkbox',
				'label'   => __('Enable EPG', 'woocommerce'),
				'default' => 'true',
			),
			'title'           => array(
				'title'       => __('Title', 'woocommerce'),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
				'default'     => __('Ecom Payment Gateway', 'epg'),
				'desc_tip'    => true,
			),
			'description'     => array(
				'title'       => __('Description', 'woocommerce'),
				'type'        => 'textarea',
				'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
				'default'     => __('Thanh toán qua cổng EPG', 'epg'),
				'desc_tip'    => true,
			),
			'epg_Url' => array(
                'title' => __('EPG URL', 'woocommerce'),
                'type' => 'text',
                'description' => 'API URL',
                'default' => '',
                'desc_tip' => true
            ),
            'secretkey' => array(
                'title' => __('Secret Key', 'woocommerce'),
                'type' => 'password',
                'description' => 'EPG Secret Token',
                'default' => '',
                'desc_tip' => true
            ),
            'epg_clientPrivateKey' => array(
                'title' => __('Private key', 'woocommerce'),
                'type' => 'password',
                'description' => 'Private Key',
                'default' => '',
                'desc_tip' => true
            ),
            'epg_paymentMethodCode' => array(
                'title' => __('Mã phương thức thanh toán', 'woocommerce'),
                'type' => 'text',
                'description' => 'PTTT: ALL, ATM, MASTERCARD ,VISACARD,JCBCARD',
                'default' => 'ALL',
                'desc_tip' => true
            ),
			'epg_prefix' => array(
                'title' => __('RequestID Prefix', 'epg'),
                'type' => 'text',
                'description' => 'Request ID Prefix',
                'default' => 'WCORDER',
                'desc_tip' => true
            ),
			'receipt_return_url' => array(
                'title' => __('Success Page', 'woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('Choose success page', 'woocommerce'),
                'desc_tip' => true,
                'default' => '',
                'options' => $this->getPagesList()
            ),
			'payment_cancel_url' => array(
                'title' => __('Payment Cancel Page', 'woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('Choose cancel page', 'woocommerce'),
                'desc_tip' => true,
                'default' => '',
                'options' => $this->getPagesList()
            ),
            'locale' => array(
                'title' => __('Locale', 'woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('Choose your locale', 'woocommerce'),
                'desc_tip' => true,
                'default' => 'vi',
                'options' => array(
                    'vi' => 'vi',
                    'en' => 'en'
                )
            ),
		);
	}

	public function clean_prefix($string){
		$string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
		if (strlen($string) > 15) {
			$string = substr($string, 0, 15);
		}
		return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}

	public function getPagesList() {
        $pagesList = array();
        $pages = get_pages();
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $pagesList[$page->ID] = $page->post_title;
            }
        }
        return $pagesList;
    }

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou($order_id)
	{
        return $this->get_option('receipt_return_url');
	}
	public function cancel($order_id)
	{
        return $this->get_option('payment_cancel_url');
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		if ($order->get_total() > 0) {
			// Mark as on-hold (we're awaiting the payment).
			$order->update_status(apply_filters('woocommerce_epg_process_payment_order_status', 'on-hold', $order), __('Awaiting payment', 'woocommerce'));
		} else {
			$order->payment_complete();
		}

		$order->update_status('on-hold');
        $order->add_order_note(__('Giao dịch chờ thanh toán hoặc chưa hoàn tất', 'woocommerce'));
        $si = $this->generateRandomString(10);
        
		$guid = $this->epg_prefix.$order_id;
		update_post_meta($order_id, 'epg_success_indicator', $si);
        update_post_meta($order_id, 'epg_request_id', $guid);
        
        $amount = $order->order_total;
        $apiUrl = $this->epg_Url;

        $epg_Returnurl = str_replace( 'https:', 'http:', add_query_arg( array(
			'wc-api' => 'WC_Gateway_Epg',
			'oid' => $order_id,
		), home_url( '/' ) ) );
        $SecretKey = $this->secretkey;
        $epg_Locale = $this->locale;
        $epg_paymentMethodCode = $this->epg_paymentMethodCode;
        $epg_IpAddr = $_SERVER['REMOTE_ADDR'];
        
        //convert vi to en: Bill
        $forenamefw = $order->get_billing_first_name();
        $forename = $this->convert_vi_to_en($forenamefw);
        $surnamefw = $order->get_billing_last_name();
        $surname = $this->convert_vi_to_en($surnamefw);
        $mobile = $order->get_billing_phone();
        $emailfw = $order->get_billing_email();
        $email = $this->convert_vi_to_en($emailfw);
        $addressfw = $order->get_billing_address_1();
        $address = $this->convert_vi_to_en($addressfw);
        $cityfw = $order->get_billing_city();
        $city = $this->convert_vi_to_en($cityfw);
        $countryfw = $order->get_billing_country();
        $country = $this->convert_vi_to_en($countryfw);
            
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $ispTxnRequest = array(
            "requestId" => $guid,
            "orderDescription" => $this->epg_prefix.$order_id,
            "amount" => $amount,
            "currency" => "VND",
            "buyerFullname" => $forename . " " . $surname,
            "buyerMobile" => $mobile,
            "buyerEmail" => $email,
            "buyerAddress" => $address,
            "returnURL" => $epg_Returnurl . '&result=success&successIndicator=' . $si,
            "cancelURL" => $epg_Returnurl . '&result=cancel',
            "language" => $epg_Locale,
            "paymentMethodCode" => $epg_paymentMethodCode,
        );
        $ispTxn = $this->call_api("create", $ispTxnRequest);
        if($ispTxn['code'] == 1){
			update_post_meta($order_id, 'epg_trans_id', $ispTxn['id']);
			WC()->cart->empty_cart();
			// Redirect to payment gateway
			return array(
				'result'   => 'success',
				'redirect' => $ispTxn["checkout_url"],
			);
        } else {
			//show error?
			$error_message = 'Thanh toán thất bại. Vui lòng liên hệ quản trị website để được hỗ trợ hoặc thử lại sau .' . ' -Mã lỗi:'.$ispTxn['code'] .' -Mô tả mã lỗi:'.$ispTxn['message'].' -Mã giao dịch:' .$order_id;
			wc_add_notice( __('Payment error:', 'epg') . $error_message, 'error' );
			return array(
				'result' => 'failure',
				'redirect' => '',
				'messages' => $error_message,
			);
        }
	}
	function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
	function convert_vi_to_en($str) {
		$str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
		$str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
		$str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
		$str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
		$str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
		$str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
		$str = preg_replace("/(đ)/", 'd', $str);    
		$str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
		$str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
		$str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
		$str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
		$str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
		$str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
		$str = preg_replace("/(Đ)/", 'D', $str);
		return $str;
	}

	public function call_api($action, $data){
        $url = $this->epg_Url . "/" . "payment-request/" . $this->secretkey . "/" . $action;
        $curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
		if ($this->epg_clientPrivateKey){
			$jsdata = json_encode($data);
			error_log($jsdata);
			openssl_sign($jsdata, $sign, $this->epg_clientPrivateKey, OPENSSL_ALGO_SHA256);
			$payload = json_encode(array(
				'data' => $jsdata,
				'signature' => base64_encode($sign),
			));
		}else{
			$payload = json_encode($data);
			error_log($payload);
		}
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		// EXECUTE:
		$result = curl_exec($curl);
		if(!$result){die("Connection Failure");}
		curl_close($curl);
		$js = json_decode($result, true);
		error_log(print_r($js['data'], true));
		$sign_ok = $this->validate_signature($js['data'], $js['signature']);
		if (!$sign_ok){
			error_log('Invalid signature from provider');
			return array();
		}
		return json_decode($js['data'], true);
    }

	public function check_ipn_response()
	{
		$params = array(
			'oid' => $_GET['oid'],
			'result' => $_GET['result'],
			'successIndicator' => $_GET['successIndicator'],
		);
		$order = wc_get_order($params['oid']);

		if ($order->status == 'on-hold'){
			if ($params['result'] == 'success' && get_post_meta($params['oid'], 'epg_success_indicator', true) == $params['successIndicator']){
				//call recheck api to make sure it valid
				$epg_request_id = get_post_meta($params['oid'], 'epg_request_id', true);
				$res = $this->call_api('recheck', array('requestId' => $epg_request_id));
				if ($res['code'] == 1 && $res['transaction']['status'] == 3){
					//update order and redirect to thankyou page
					$order->payment_complete();
					$order->add_order_note('Thanh toán toành công');
					$url = get_page_link($this->thankyou($params['oid']));
					wp_redirect($url);
				}else{
					error_log('Recheck result is not success');
				}
			} else if ($params['result'] == 'cancel'){
				$order->update_status('canceled', __( 'User canncelled payment', 'epg' ));
				$url = get_page_link($this->cancel($params['oid']));
				wp_redirect($url);
			} else{
				error_log("Invalid IPN request");
			}
		}else{
			error_log('IPN callback. Order status changed. now: ' . $order->status);
		}
	}

	public static function log( $message, $level = 'info' ) {
		if ( empty( self::$log ) ) {
			self::$log = wc_get_logger();
		}
		self::$log->log( $level, $message, array( 'source' => 'epg') );
	}

	public function process_refund($order_id, $amount = null, $reason = '') {
		$order = wc_get_order($order_id);
		$order_total = $order->get_total();
		$order_currency = $order->get_currency();
		$trans_id = get_post_meta('epg_trans_id', true);
		$revert_result = null;
		if(!isset($amount)) {
			//Refund entirely if no amount is specified
			$amount = $order_total;
		}
		try {
			$payload = array(
				'requestId' => 'Refund-'.$order_id.'-'.date('isv'),
				'transId' => $trans_id,
				'amount' => $amount
			);
			$revert_result = $this->call_api('refund', $payload);
		} catch(Exception $ex) {
			$this->log($ex, WC_Log_Levels::ERROR);
		}

		if(!empty($revert_result)) {
			if ($revert_result['code'] == 1){
				$order->add_order_note();
				$message = sprintf(__('Refund of %1$s %2$s via %3$s approved', 'epg'), $amount, $order_currency, $this->title);
				$this->log($message, WC_Log_Levels::INFO);
			 	$order->add_order_note($message);
				return true;
			}
		}
		$message = sprintf(__('Refund of %1$s %2$s via %3$s failed', 'epg'), $amount, $order_currency, $this->title);
		$order->add_order_note($message);
		$this->log($message, WC_Log_Levels::ERROR);
		// $this->logs_admin_notice();
		return false;
	}
}
