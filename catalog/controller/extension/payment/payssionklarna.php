<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionKlarna extends ControllerExtensionPaymentPayssion {
    protected $pm_id = 'klarna';
    protected $template = 'payssion_klarna';
    
    protected function fillFormData($order_info) {
        $data = parent::fillFormData($order_info);
        $data['billing_address']  = $this->addBillingAddress($order_info);
        $data['order_items']  = $this->getOrderLines($order_info);
        return $data;
    }
    
    protected function addBillingAddress($order)
    {
        $billing_address = array(
            'city'            => $order['payment_city'],
            'country'         => $order['payment_iso_code_2'],
            'email'           => $order['email'],
            'last_name'       => $order['payment_lastname'],
            'first_name'      => $order['payment_firstname'],
            'postal_code'     => $order['payment_postcode'],
            'region'          => $order['payment_zone'],
            'line1'           => $order['payment_address_1'],
            'line2'           => $order['payment_address_2'],
        );
        
        return base64_encode(json_encode($billing_address, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    
    protected function getOrderLines($order) {
        $amount = $order['total'];
        
        //Order line data
        $orderProducts = $this->getOrderProducts((int)$order['order_id']);
        $lines = array();
        
        $this->load->model('catalog/product');
        foreach($orderProducts as $orderProduct) {
            $productDetails = $this->model_catalog_product->getProduct($orderProduct['product_id']);
            $tax_rates = $this->tax->getRates($orderProduct['price'], $productDetails['tax_class_id']);
            $rates = $this->getTaxRate($tax_rates);
            //Since Mollie only supports VAT so '$rates' must contains only one(VAT) rate.
            $vatRate = isset($rates[0]) ? $rates[0] : 0;
            $total = ($orderProduct['price'] + $orderProduct['tax']) * $orderProduct['quantity'];
            
            // Fix for qty < 1
            $qty = (int)$orderProduct['quantity'];
            if($qty < 1) {
                $qty = 1;
                $price = $orderProduct['price'] * $orderProduct['quantity'];
                $tax = $orderProduct['tax'] * $orderProduct['quantity'];
            } else {
                $qty = (int)$orderProduct['quantity'];
                $price = $orderProduct['price'];
                $tax = $orderProduct['tax'];
            }
            
            $vatAmount = $total * ( $vatRate / (100 +  $vatRate));
            $lines[] = array(
                'type'          =>  'physical',
                'name'          =>  $orderProduct['name'],
                'quantity'      =>  $qty,
                'unit_price'    =>  (string)$this->numberFormat($price + $tax),
                'amount'        =>  (string)$this->numberFormat($total),
                'tax_rate'       =>  (string)$this->numberFormat($vatRate),
                'tax_amount'     =>  (string)$this->numberFormat($vatAmount),
            );
        }
        
        //Check for shipping fee
        if(isset($this->session->data['shipping_method'])) {
            $title = $this->session->data['shipping_method']['title'];
            $cost = $this->session->data['shipping_method']['cost'];
            $taxClass = $this->session->data['shipping_method']['tax_class_id'];
            $tax_rates = $this->tax->getRates($cost, $taxClass);
            $rates = $this->getTaxRate($tax_rates);
            $vatRate = isset($rates[0]) ? $rates[0] : 0;
            $costWithTax = $this->tax->calculate($cost, $taxClass, true);
            $costWithTax = $this->numberFormat($costWithTax);
            $shippingVATAmount = $costWithTax * ( $vatRate / (100 +  $vatRate));
            $lineForShipping[] = array(
                'type'          =>  'shipping',
                'name'          =>  $title,
                'quantity'      =>  1,
                'unit_price'    =>  (string)$costWithTax,
                'amount'        =>  (string)$costWithTax,
                'tax_rate'      =>  (string)$this->numberFormat($vatRate),
                'tax_amount'    =>  (string)$this->numberFormat($shippingVATAmount),
            );
            
            $lines = array_merge($lines, $lineForShipping);
        }
        
        //Check if coupon applied
        if(isset($this->session->data['coupon'])) {
            //Get coupon data
            if(version_compare(VERSION, '2.1', '<')) {
                $this->load->model('checkout/coupon');
                $coupon = 'model_checkout_coupon';
            } elseif (version_compare(VERSION, '2.3', '<')) {
                $this->load->model('total/coupon');
                $coupon = 'model_total_coupon';
            } else {
                $this->load->model('extension/total/coupon');
                $coupon = 'model_extension_total_coupon';
            }
            
            $coupon_info = $this->{$coupon}->getCoupon($this->session->data['coupon']);
            
            if ($coupon_info) {
                $discount_total = 0;
                $couponVATAmount = 0;
                
                if (!$coupon_info['product']) {
                    $sub_total = $this->cart->getSubTotal();
                } else {
                    $sub_total = 0;
                    
                    foreach ($this->cart->getProducts() as $product) {
                        if (in_array($product['product_id'], $coupon_info['product'])) {
                            $sub_total += $product['total'];
                        }
                    }
                }
                
                if ($coupon_info['type'] == 'F') {
                    $coupon_info['discount'] = min($coupon_info['discount'], $sub_total);
                }
                
                foreach ($this->cart->getProducts() as $product) {
                    $discount = 0;
                    
                    if (!$coupon_info['product']) {
                        $status = true;
                    } else {
                        $status = in_array($product['product_id'], $coupon_info['product']);
                    }
                    
                    if ($status) {
                        if ($coupon_info['type'] == 'F') {
                            $discount = $coupon_info['discount'] * ($product['total'] / $sub_total);
                        } elseif ($coupon_info['type'] == 'P') {
                            $discount = $product['total'] / 100 * $coupon_info['discount'];
                        }
                        
                        if ($product['tax_class_id']) {
                            $tax_rates = $this->tax->getRates($product['total'] - ($product['total'] - $discount), $product['tax_class_id']);
                            
                            foreach ($tax_rates as $tax_rate) {
                                if ($tax_rate['type'] == 'P') {
                                    $couponVATAmount += $tax_rate['amount'];
                                }
                            }
                        }
                    }
                    
                    $discount_total += $discount;
                }
                
                if ($coupon_info['shipping'] && isset($this->session->data['shipping_method'])) {
                    if (!empty($this->session->data['shipping_method']['tax_class_id'])) {
                        $tax_rates = $this->tax->getRates($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id']);
                        
                        foreach ($tax_rates as $tax_rate) {
                            if ($tax_rate['type'] == 'P') {
                                $couponVATAmount += $tax_rate['amount'];
                            }
                        }
                    }
                    
                    $discount_total += $this->session->data['shipping_method']['cost'];
                }
                
                $vatRate = ($couponVATAmount * 100) / ($discount_total);
                
                $vatRate = $this->numberFormat($vatRate);
                
                $unitPriceWithTax = $this->numberFormat($discount_total + $couponVATAmount);
                
                $couponVATAmount = $this->numberFormat($couponVATAmount);
                
                // Rounding fix
                $couponVATAmount1 = $unitPriceWithTax * ($vatRate / (100 + $vatRate));
                $couponVATAmount1 = $this->numberFormat($couponVATAmount1);
                if($couponVATAmount != $couponVATAmount1) {
                    if($couponVATAmount1 > $couponVATAmount) {
                        $couponVATAmount = $couponVATAmount + ($couponVATAmount1 - $couponVATAmount);
                    } else {
                        $couponVATAmount = $couponVATAmount - ($couponVATAmount - $couponVATAmount1);
                    }
                }
                
                $lineForCoupon[] = array(
                    'type'          =>  'discount',
                    'name'          =>  $coupon_info['name'],
                    'quantity'      =>  1,
                    'unit_price'    =>  (string)$this->numberFormat($unitPriceWithTax),
                    'amount'        =>  (string)$this->numberFormat($unitPriceWithTax),
                    'tax_rate'      =>  (string)$vatRate,
                    'tax_amount'    =>  (string)$this->numberFormat($couponVATAmount),
                );
                
                $lines = array_merge($lines, $lineForCoupon);
            }
        }
        
        //Check if gift card applied
        if(isset($this->session->data['voucher'])) {
            //Get voucher data
            $voucher = $this->getVoucherDetails($order['order_id']);
            $lineForVoucher[] = array(
                'type'          =>  'gift_card',
                'name'          =>  $voucher['title'],
                'quantity'      =>  1,
                'unit_price'    =>  (string)$this->numberFormat($voucher['value']),
                'amount'        =>  (string)$this->numberFormat($voucher['value']),
                'tax_rate'      =>  "0.00",
                'tax_amount'    =>  (string)$this->numberFormat(0.00),
            );
            
            $lines = array_merge($lines, $lineForVoucher);
        }
        
        //Check for reward points
        if(isset($this->session->data['reward'])) {
            //Get reward point data
            $rewardPoints = $this->getRewardPointDetails($order['order_id']);
            
            foreach ($this->cart->getProducts() as $product) {
                if ($product['points']) {
                    if ($product['tax_class_id']) {
                        $taxClass = $product['tax_class_id'];
                        $tax_rates = $this->tax->getRates($rewardPoints['value'], $taxClass);
                        $rates = $this->getTaxRate($tax_rates);
                        $vatRate = $rates[0];
                        break;
                    }
                }
            }
            
            if(!isset($vatRate) || empty($vatRate)) {
                $vatRate = 0;
            }
            
            $unitPriceWithTax = $this->tax->calculate($rewardPoints['value'], $taxClass, true);
            $unitPriceWithTax = $this->numberFormat($unitPriceWithTax);
            
            $rewardVATAmount = $unitPriceWithTax * ( $vatRate / (100 +  $vatRate));
            
            $lineForRewardPoints[] = array(
                'type'          =>  'discount',
                'name'          =>  $rewardPoints['title'],
                'quantity'      =>  1,
                'unit_price'    => (string)$unitPriceWithTax,
                'amount'        =>  (string)$unitPriceWithTax,
                'tax_rate'      =>  (string)$this->numberFormat($vatRate),
                'tax_amount'    =>  (string)$this->numberFormat($rewardVATAmount),
            );
            
            $lines = array_merge($lines, $lineForRewardPoints);
        }
        
        // Gift Voucher
        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $key => $voucher) {
                $voucherData[] = array(
                    'type'            => 'physical',
                    'name'            => $voucher['description'],
                    'quantity'        => 1,
                    'unit_price'      =>  (string)$this->numberFormat($voucher['amount']),
                    'amount'          =>  (string)$this->numberFormat($voucher['amount']),
                    'tax_rate'        =>  "0.00",
                    'tax_amount'      =>  (string)$this->numberFormat(0.00),
                );
            }
            
            $lines = array_merge($lines, $voucherData);
        }
        
        //Check for other totals (if any)
        $otherOrderTotals = $this->getOtherOrderTotals($order['order_id']);
        if(!empty($otherOrderTotals)) {
            $otherTotals = array();
            
            if(version_compare(VERSION, '3.0', '>=')) {
                $typePrefix = 'total_';
            } else {
                $typePrefix = '';
            }
            
            foreach($otherOrderTotals as $orderTotals) {
                
                if($this->config->get($typePrefix . $orderTotals['code'] . '_tax_class_id')) {
                    $taxClass = $this->config->get($typePrefix . $orderTotals['code'] . '_tax_class_id');
                } else {
                    $taxClass = 0;
                }
                
                $tax_rates = $this->tax->getRates($orderTotals['value'], $taxClass);
                $rates = $this->getTaxRate($tax_rates);
                $vatRate = isset($rates[0]) ? $rates[0] : 0;
                $unitPriceWithTax = $this->tax->calculate($orderTotals['value'], $taxClass, true);
                $totalsVATAmount = $unitPriceWithTax * ( $vatRate / (100 +  $vatRate));
                
                $type = 'discount';
                if($orderTotals['value'] > 0) {
                    $type = 'surcharge';
                }
                
                $otherTotals[] = array(
                    'type'          =>  $type,
                    'name'          =>  $orderTotals['title'],
                    'quantity'      =>  1,
                    'unit_price'    => (string)$unitPriceWithTax,
                    'amount'        =>  (string)$unitPriceWithTax,
                    'tax_rate'      =>  (string)$this->numberFormat($vatRate),
                    'tax_amount'    =>  (string)$this->numberFormat($totalsVATAmount),
                );
            }
            
            $lines = array_merge($lines, $otherTotals);
        }
        
        //Check for rounding off issue in a general way (for all possible totals)
        $orderTotal = $this->numberFormat($amount);
        $orderLineTotal = 0;
        
        foreach($lines as $line) {
            $orderLineTotal += $line['amount'];
        }
        
        $orderLineTotal = $this->numberFormat($orderLineTotal);
        
        if($orderTotal > $orderLineTotal) {
            $amountDiff = $this->numberFormat(($orderTotal - $orderLineTotal));
            $lineForDiscount[] = array(
                'type'         =>  'discount',
                'name'         =>  $this->language->get("roundoff_description"),
                'quantity'     =>  1,
                'price'        =>  (string)$amountDiff,
                'amount'       =>  (string)$amountDiff,
                'tax_rate'     =>  "0",
                'tax_amount'   =>  (string)$this->numberFormat(0.00),
            );
            
            $lines = array_merge($lines, $lineForDiscount);
        }
        
        if($orderTotal < $orderLineTotal) {
            $amountDiff = $this->numberFormat(-($orderLineTotal - $orderTotal));
            $lineForSurcharge[] = array(
                'type'          =>  'surcharge',
                'name'          =>  $this->language->get("roundoff_description"),
                'quantity'      =>  1,
                'unit_price'    =>  (string)$amountDiff,
                'amount'        =>  (string)$amountDiff,
                'tax_rate'      =>  "0",
                'tax_amount'    =>  (string)$this->numberFormat(0.00),
            );
            
            $lines = array_merge($lines, $lineForSurcharge);
        }
        return base64_encode(json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
    }
    
    //Get order products
    protected function getOrderProducts($order_id)
    {
        $model = $this->getModuleModel();
        
        return $model->getOrderProducts($order_id);
    }
    
    //Get order products
    protected function getOtherOrderTotals($order_id)
    {
        $model = $this->getModuleModel();
        
        return $model->getOtherOrderTotals($order_id);
    }
    
    /**
     * @return ModelExtensionPaymentMollie
     */
    protected function getModuleModel()
    {
        $model_name = 'model_extension_payment_payssion';
        if (!isset($this->$model_name)) {
            $this->load->model('extension/payment/payssion');
        }
        
        return $this->$model_name;
    }
    
    //Get tax rate
    protected function getTaxRate($tax_rates = array())
    {
        $rates = array();
        if(!empty($tax_rates)) {
            foreach($tax_rates as $tax) {
                $rates[] = $tax['rate'];
            }
        }
        return $rates;
    }
    
    protected function numberFormat($amount) {
        // 	    $currency = $this->getCurrency();
        // 	    $intCurrencies = array("ISK", "JPY");
        // 	    if(!in_array($currency, $intCurrencies)) {
        // 	        $formattedAmount = number_format((float)$amount, 2, '.', '');
        // 	    } else {
        // 	        $formattedAmount = number_format($amount, 0);
        // 	    }
        // 	    return $formattedAmount;
        
        return $amount;
    }
}