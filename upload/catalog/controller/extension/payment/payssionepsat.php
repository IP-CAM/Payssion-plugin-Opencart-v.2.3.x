<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionEpsat extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'eps_at';
}