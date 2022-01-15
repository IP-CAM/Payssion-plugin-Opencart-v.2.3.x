<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionGcashph extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'gcash_ph';
}