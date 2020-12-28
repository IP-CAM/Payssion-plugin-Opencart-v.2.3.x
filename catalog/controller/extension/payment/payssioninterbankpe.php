<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionInterbankpe extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'interbank_pe';
}