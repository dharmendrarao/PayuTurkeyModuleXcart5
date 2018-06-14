<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * X-Cart
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the software license agreement
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.x-cart.com/license-agreement.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to licensing@x-cart.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade X-Cart to newer versions
 * in the future. If you wish to customize X-Cart for your needs please
 * refer to http://www.x-cart.com/ for more information.
 *
 * @category  X-Cart 5
 * @author    Qualiteam software Ltd <info@x-cart.com>
 * @copyright Copyright (c) 2011-2015 Qualiteam software Ltd <info@x-cart.com>. All rights reserved
 * @license   http://www.x-cart.com/license-agreement.html X-Cart 5 License Agreement
 * @link      http://www.x-cart.com/
 */

namespace XLite\Module\XC\PayuTurkey\Model\Payment\Processor;

/**
 * PayU payment processor
 */
class PayuTurkey extends \XLite\Model\Payment\Base\CreditCard
{
    const THOUSAND_DELIMITER = ',';
    const DECIMAL_DELIMITER = '.';
	
	/**
     * Get input template
     *
     * @return string|void
     */
    public function getInputTemplate()
    {
        return 'modules/XC/PayuTurkey/checkout/credit_card_form.tpl';
    }
	
    /**
     * Get operation types
     *
     * @return array
     */
    public function getOperationTypes()
    {
        return array(
            self::OPERATION_SALE,
        );
    }

    /**
     * Get settings widget or template
     *
     * @return string Widget class name or template path
     */
    public function getSettingsWidget()
    {
        return 'modules/XC/PayuTurkey/config.tpl';
    }


    /**
     * Get initial transaction type (used when customer places order)
     *
     * @param \XLite\Model\Payment\Method $method Payment method object OPTIONAL
     *
     * @return string
     */
    public function getInitialTransactionType($method = null)
    {
        return \XLite\Model\Payment\BackendTransaction::TRAN_TYPE_SALE;
    }

    /**
     * Check - payment method is configured or not
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return boolean
     */
    public function isConfigured(\XLite\Model\Payment\Method $method)
    {
       return parent::isConfigured($method) && $method->getSetting('merchantid') && $method->getSetting('secretKey');
    }

    /**
     * Check if the mcrypt function is available
     *
     * @return boolean
     */
    public function isMcryptDecrypt()
    {
        return function_exists('mcrypt_decrypt');
    }

    /**
     * Get return type
     *
     * @return string
     */
    public function getReturnType()
    {
        return self::RETURN_TYPE_HTML_REDIRECT;
    }

    /**
     * Returns the list of settings available for this payment processor
     *
     * @return array
     */
    public function getAvailableSettings()
    {
        return array(
            'merchantid',
            'secretKey',
            'mode',
            'referencePrefix',
        );
    }

    /**
     * Get payment method admin zone icon URL
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return string
     */
    public function getAdminIconURL(\XLite\Model\Payment\Method $method)
    {
        return true;
    }

    /**
     * Check - payment method has enabled test mode or not
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return boolean
     */
    public function isTestMode(\XLite\Model\Payment\Method $method)
    {
        return (bool)$method->getSetting('test');
    }
	
	protected function doInitialPayment()
    {

		//print_r($_REQUEST);die;
	}

    /**
     * Process return
     *
     * @param \XLite\Model\Payment\Transaction $transaction Return-owner transaction
     *
     * @return void
     */
    public function processReturn(\XLite\Model\Payment\Transaction $transaction)
    {
        parent::processReturn($transaction);
    }

	/* PayU specific **********************/
    protected function createAnswer($data)
    {
        $datetime = date("YmdHis");
        $res = array(
            "IPN_PID" => $data[ "IPN_PID" ][0],
            "IPN_PNAME" => $data[ "IPN_PNAME" ][0],
            "IPN_DATE" => $data[ "IPN_DATE" ],
            "DATE" => $datetime,
        );
        $sign = $this->signature($res);
        return "<EPAYMENT>$datetime|$sign</EPAYMENT>";
    }

    protected function checkHashSignature($data)
    {
        $hash = $data["HASH"];
        unset($data["HASH"]);
        $sign = $this->signature($data);
        return ( $hash != $sign ) ? false : true ;
    }

    protected function md5Hmac($data)
    {
        $key = $this->getSetting('key');
        $b = 64; 	# byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key  = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        return md5($k_opad  . pack("H*", md5($k_ipad . $data)));
    }

    protected function signature($data)
    {
        $str = '';
        foreach ($data as $k => $v) {
            if (strpos($k, 'BILL') === false && strpos($k, 'DELIVERY') === false
                && strpos($k, 'BACK_') === false && strpos($k, 'LANG') === false) {
                    $str .= $this->convData($v);
            }
        }
        return $this->md5Hmac($str);

    }

    protected function convString($string)
    {
        return mb_strlen($string, '8bit') . $string;
    }

    protected function convArray($array)
    {
        $return = '';
        foreach ($array as $v) {
            $return .= $this->convString($v);
        }
        return $return;
    }

    protected function convData($val)
    {
        return (is_array($val)) ? $this->convArray($val) : $this->convString($val);
    }
	
}
