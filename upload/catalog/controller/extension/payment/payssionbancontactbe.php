<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBancontactbe extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'bancontact_be';
}