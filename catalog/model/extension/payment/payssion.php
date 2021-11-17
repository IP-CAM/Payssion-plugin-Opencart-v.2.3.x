<?php 
class ModelExtensionPaymentPayssion extends Model {
  	public function getMethod($address) {
		$this->load->language('extension/payment/payssion');
		$title = $this->language->get('text_title');
		$class_name = get_class($this);
		$index = strrpos($class_name, 'Payssion');
		$id = strtolower(substr($class_name, $index));
		
		$title = $this->language->get("text_title_");
		$channel = false;
		if (strlen($class_name) - $index > 8) {
			$channel = true;
			$pm = substr($class_name, $index + 8);
			$key = "text_title_" . strtolower($pm);
			$title = $this->language->get($key);
			$title = ($title && $title != $key ? $title : $pm) . ' (Payssion)';
		}
		
		if ($channel && $this->config->get($id . '_status')) {
			$geo_zone_id = $id . '_geo_zone_id';
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get($geo_zone_id) . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
			if (!$this->config->get($geo_zone_id)) {
				$status = TRUE;
			} elseif ($query->num_rows) {
				$status = TRUE;
			} else {
				$status = FALSE;
			}	
		} else {
			$status = FALSE;
		}
		$method_data = array();
		
		if ($status) {  
			$method_data = array( 
				'code'		 => $id,
				'title'		 => $title,
				'terms'      => '',
				'sort_order' => $this->config->get($id . '_sort_order')
			);
		}
		return $method_data;
	}
	
	public function getOrderProducts($order_id) {
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
	    
	    return $query->rows;
	}
	
	public function getCouponDetails($orderID) {
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$orderID . "' AND code = 'coupon'");
	    return $query->row;
	}
	
	public function getVoucherDetails($orderID) {
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$orderID . "' AND code = 'voucher'");
	    return $query->row;
	}
	
	public function getRewardPointDetails($orderID) {
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$orderID . "' AND code = 'reward'");
	    return $query->row;
	}
	
	public function getOtherOrderTotals($orderID) {
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$orderID . "' AND code != 'shipping' AND code != 'tax' AND code != 'voucher' AND code != 'sub_total' AND code != 'coupon' AND code != 'reward' AND code != 'total'");
	    
	    return $query->rows;
	}
}

