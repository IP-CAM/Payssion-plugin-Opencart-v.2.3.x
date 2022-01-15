<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionShopeepayid extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'shopeepay_id';
}