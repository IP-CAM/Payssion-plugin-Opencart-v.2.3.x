<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionAtmid extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'atm_id';
}