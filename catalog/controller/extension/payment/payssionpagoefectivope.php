<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionPagoefectivope extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'pagoefectivo_pe';
}