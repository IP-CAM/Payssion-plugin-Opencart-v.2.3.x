<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionAlfamartid extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'alfamart_id';
}