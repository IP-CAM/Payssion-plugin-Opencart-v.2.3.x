<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionKakaopaykr extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'kakaopay_kr';
}