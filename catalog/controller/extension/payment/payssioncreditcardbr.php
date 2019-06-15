<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionCreditcardbr extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'creditcard_br';
}