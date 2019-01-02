<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBankcardtr extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'bankcard_tr';
}