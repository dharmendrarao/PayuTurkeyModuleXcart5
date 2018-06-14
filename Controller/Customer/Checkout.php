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
 * @copyright Copyright (c) 2011-2016 Qualiteam software Ltd <info@x-cart.com>. All rights reserved
 * @license   http://www.x-cart.com/license-agreement.html X-Cart 5 License Agreement
 * @link      http://www.x-cart.com/
 */

namespace XLite\Module\XC\PayuTurkey\Controller\Customer;

/**
 * Checkout 
 *
 */
class Checkout extends \XLite\Controller\Customer\Checkout implements \XLite\Base\IDecorator
{

    /**
     * Public wrapper for check checkout action
     *
     * @return void
     */
    public function isCheckoutReady()
    {
        return $this->getCart() 
            && $this->getCart()->getProfile() 
            && $this->getCart()->getProfile()->getLogin()
            && (
                $this->getCart()->getProfile()->getBillingAddress()
                || (
                    $this->getCart()->getProfile()->getShippingAddress()
                    && $this->getCart()->getProfile()->getShippingAddress()->isCompleted(\XLite\Model\Address::SHIPPING)
                )
            );
    }

    /**
     * Checkout. Recognize iframe and save that 
     *
     * @return void
     */
    public function handleRequest()
    {
		if ('checkout' == \XLite\Core\Request::getInstance()->action && 1 == \XLite\Core\Request::getInstance()->payuturkey) {
			
			$url = "https://secure.payu.com.tr/order/alu.php";
 
			$secretKey = 'SECRET_KEY';
			$arParams = array(
				//The Merchant's ID
				"MERCHANT" => "OPU_TEST",
				//order external reference number in Merchant's system
				"ORDER_REF" => time(),
				"ORDER_DATE" => gmdate('Y-m-d H:i:s'),
				 
				//First product details begin
				"ORDER_PNAME[0]" => "Ticket1",
				"ORDER_PCODE[0]" => "TCK1",
				"ORDER_PINFO[0]" => "Barcelona flight",
				"ORDER_PRICE[0]" => "100",
				"ORDER_QTY[0]" => "1",
				//First product details end
				 
				//Second product details begin
				"ORDER_PNAME[1]" => "Ticket2",
				"ORDER_PCODE[1]" => "TCK2",
				"ORDER_PINFO[1]" => "London flight",
				"ORDER_PRICE[1]" => "200",
				"ORDER_QTY[1]" => "1",
				//Second product details end
			 
				"PRICES_CURRENCY" => "TRY",
				"PAY_METHOD" => "CCVISAMC",//to remove
				"SELECTED_INSTALLMENTS_NUMBER" => "3",
				"CC_NUMBER" => \XLite\Core\Request::getInstance()->cc_number,
				"EXP_MONTH" => \XLite\Core\Request::getInstance()->cc_expire_month,
				"EXP_YEAR" => \XLite\Core\Request::getInstance()->cc_expire_year,
				"CC_CVV" => \XLite\Core\Request::getInstance()->cc_cvv2,
				"CC_OWNER" => \XLite\Core\Request::getInstance()->cc_name,
				 
				//Return URL on the Merchant webshop side that will be used in case of 3DS enrolled cards authorizations.
				"BACK_REF" => "https://www.example.com/alu/3ds_return.php",
				"CLIENT_IP" => "127.0.0.1",
				"BILL_LNAME" => "John",
				"BILL_FNAME" => "Doe",
				"BILL_EMAIL" => "shopper@payu.ro",
				"BILL_PHONE" => "1234567890",
				"BILL_COUNTRYCODE" => "TR",
				 
				//Delivery information
				"DELIVERY_FNAME" => "John",
				"DELIVERY_LNAME" => "Smith",
				"DELIVERY_PHONE" => "0729581297",
				"DELIVERY_ADDRESS" => "3256 Epiphenomenal Avenue",
				"DELIVERY_ZIPCODE" => "55416",
				"DELIVERY_CITY" => "Minneapolis",
				"DELIVERY_STATE" => "Minnesota",
				"DELIVERY_COUNTRYCODE" => "MN",
			);
			 
			//begin HASH calculation
			ksort($arParams);
			 
			$hashString = "";
			 
			foreach ($arParams as $key=>$val) {
				$hashString .= strlen($val) . $val;
			}
			 
			$arParams["ORDER_HASH"] = hash_hmac("md5", $hashString, $secretKey);
			//end HASH calculation
			 
			$ch = curl_init();
			 
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arParams));
			 
			$response = curl_exec($ch);
			 
			$curlerrcode = curl_errno($ch);
			$curlerr = curl_error($ch);
			 
			curl_close($ch);
			 
			if (empty($curlerr) && empty($curlerrcode)) {
				$parsedXML = @simplexml_load_string($response);
				if ($parsedXML !== FALSE) {
			 
					//Get PayU Transaction reference.
					//Can be stored in your system DB, linked with your current order, for match order in case of 3DSecure enrolled cards
					//Can be empty in case of invalid parameters errors
					$payuTranReference = $parsedXML->REFNO;
					//echo '<pre>';print_r($parsedXML);echo '</pre>';die;
					if ($parsedXML->STATUS == "SUCCESS") {
			 
						//In case of 3DS enrolled cards, PayU will return the extra XML tag URL_3DS that contains a unique url for each 
						//transaction. For example https://secure.payu.com.tr            /order/alu_return_3ds.php?request_id=2Xrl85eakbSBr3WtcbixYQ%3D%3D.
						//The merchant must redirect the browser to this url to allow user to authenticate. 
						//After the authentification process ends the user will be redirected to BACK_REF url
						//with payment result in a HTTP POST request - see 3ds return sample. 
						if (($parsedXML->RETURN_CODE == "3DS_ENROLLED") && (!empty($parsedXML->URL_3DS))) {
							header("Location:" . $parsedXML->URL_3DS);
							die();
						}
			 			\XLite\Core\TopMessage::addInfo("SUCCESS [PayU reference number: " . $payuTranReference . "]");
						//echo "SUCCESS [PayU reference number: " . $payuTranReference . "]";
					} else {
						\XLite\Core\TopMessage::addError("FAILED: " . nl2br($parsedXML->RETURN_MESSAGE));
						//echo "FAILED: " . $parsedXML->RETURN_MESSAGE . " [" . $parsedXML->RETURN_CODE . "]";
						if (!empty($payuTranReference)) {
							//the transaction was register to PayU system, but some error occured during the bank authorization.
							//See $parsedXML->RETURN_MESSAGE and $parsedXML->RETURN_CODE for details                
							//echo " [PayU reference number: " . $payuTranReference . "]";
						}
					}
				}
			} else {
				//Was an error comunication between servers
				\XLite\Core\TopMessage::addError("cURL error: " . $curlerr);
				//echo "cURL error: " . $curlerr;
			}
			
			$urlParams['error'] = 'error';
			$this->redirect($this->buildURL('checkout'));
		}
		parent::handleRequest();
		return false;
	}


    /**
     * Get payment method id
     *
     * @return integer
     */
    public function getPaymentId()
    {
        return ($this->getCart() && $this->getCart()->getPaymentMethod())
            ? $this->getCart()->getPaymentMethod()->getMethodId()
            : 0;
    }

    /**
     * Order placement is success
     *
     * @param boolean $fullProcess Full process or not OPTIONAL
     *
     * @return void
     */
    public function processSucceed($fullProcess = true)
    {
        if (!\XLite\Core\Session::getInstance()->xpc_skip_process_success) {
            parent::processSucceed($fullProcess);
        } else {
            parent::processSucceed(false);
            \XLite\Core\Session::getInstance()->xpc_skip_process_success = null;
        }
    }

    /**
     * Save data of the checkout form (notes and flag to save card)
     *
     * @return void
     */
    protected function doActionSaveCheckoutFormData()
    {
        if (\XLite\Core\Request::getInstance()->notes) {
            $this->getCart()->setNotes(\XLite\Core\Request::getInstance()->notes);
        }

        if ('Y' == \XLite\Core\Request::getInstance()->save_card) {
            \XLite\Core\Session::getInstance()->cardSavedAtCheckout = 'Y';
        } else {
            \XLite\Core\Session::getInstance()->cardSavedAtCheckout = 'N';
        }

        \XLite\Core\Database::getEM()->flush();

    }

    /**
     * Clear init data from session and redirrcet back to checkout
     *
     * @return void
     */
    protected function doActionClearInitData()
    {
        $this->setHardRedirect();
        $this->setReturnURL($this->buildURL('cart', 'checkout'));
        $this->doRedirect();
    }

    /**
     * Return from payment gateway
     *
     * @return void
     */
    protected function doActionReturn()
    {
		
	}

    /**
     * Update profile
     *
     * @return void
     */
    protected function doActionUpdateProfile()
    {
        parent::doActionUpdateProfile();

        $showSaveCardBox = $this->showSaveCardBox()
            ? 'Y'
            : 'N';

        $checkCheckoutAction = $this->checkCheckoutAction()
            ? 'Y'
            : 'N';

        \XLite\Core\Event::xpcEvent(
            array(
                'showSaveCardBox' => $showSaveCardBox,
                'checkCheckoutAction' => $checkCheckoutAction
            )
        );
    }

}
