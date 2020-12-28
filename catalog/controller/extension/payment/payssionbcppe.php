<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBCPpe extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'bcp_pe';
}