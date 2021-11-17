<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionMybank extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'mybank';
}