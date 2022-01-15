<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionQrisid extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'qris_id';
}