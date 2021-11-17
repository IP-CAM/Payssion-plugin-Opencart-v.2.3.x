<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionPayconiq extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'payconiq';
}