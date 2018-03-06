<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionP24pl extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'p24_pl';
}