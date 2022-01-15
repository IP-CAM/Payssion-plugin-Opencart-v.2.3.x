<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionPaynowsg extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'paynow_sg';
}