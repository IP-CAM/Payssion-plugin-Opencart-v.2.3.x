<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionSsgpaykr extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'ssgpay_kr';
}