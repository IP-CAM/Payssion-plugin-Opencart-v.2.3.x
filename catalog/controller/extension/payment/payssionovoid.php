<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionOvoid extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'ovo_id';
}