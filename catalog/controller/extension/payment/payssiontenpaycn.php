<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionTenpaycn extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'tenpay_cn';
}