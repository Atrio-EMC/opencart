<?php
namespace Application\Controller\Startup;
class Startup extends \System\Engine\Controller {
	public function index() {
		// Settings
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'"); 
		
		foreach ($query->rows as $setting) {
			if (!$setting['serialized']) {
				$this->config->set($setting['key'], $setting['value']);
			} else {
				$this->config->set($setting['key'], json_decode($setting['value'], true));
			}
		}

		// Set time zone
		if ($this->config->get('config_timezone')) {
			date_default_timezone_set($this->config->get('config_timezone'));

			// Sync PHP and DB time zones.
			$this->db->query("SET time_zone = '" . $this->db->escape(date('P')) . "'");
		}

		// Session
		if (isset($this->request->cookie[$this->config->get('session_name')])) {
			$session_id = $this->request->cookie[$this->config->get('session_name')];
		} else {
			$session_id = '';
		}

		$this->session->start($session_id);

		// Require higher security for session cookies
		$option = array(
			'max-age'  => time() + $this->config->get('session_expire'),
			'path'     => !empty($_SERVER['PHP_SELF']) ? dirname($_SERVER['PHP_SELF']) . '/' : '',
			'domain'   => $this->request->server['HTTP_HOST'],
			'secure'   => $this->request->server['HTTPS'],
			'httponly' => false,
			'SameSite' => 'strict'
		);

		oc_setcookie($this->config->get('session_name'), $this->session->getId(), $option);

		// Response output compression level
		if ($this->config->get('config_compression')) {
			$this->response->setCompression($this->config->get('config_compression'));
		}

		// Language
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($this->config->get('config_admin_language')) . "'");
		
		if ($query->num_rows) {
			$this->config->set('config_language_id', $query->row['language_id']);
		}
		
		// Language
		$language = new \System\Library\Language($this->config->get('config_admin_language'));
		$language->load($this->config->get('config_admin_language'));
		$this->registry->set('language', $language);
		
		// Customer
		$this->registry->set('customer', new \System\Library\Cart\Customer($this->registry));

		// Currency
		$this->registry->set('currency', new \System\Library\Cart\Currency($this->registry));
	
		// Tax
		$this->registry->set('tax', new \System\Library\Cart\Tax($this->registry));
		
		if ($this->config->get('config_tax_default') == 'shipping') {
			$this->tax->setShippingAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		if ($this->config->get('config_tax_default') == 'payment') {
			$this->tax->setPaymentAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		$this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));

		// Weight
		$this->registry->set('weight', new \System\Library\Cart\Weight($this->registry));
		
		// Length
		$this->registry->set('length', new \System\Library\Cart\Length($this->registry));
		
		// Cart
		$this->registry->set('cart', new \System\Library\Cart\Cart($this->registry));
		
		// Encryption
		$this->registry->set('encryption', new \System\Library\Encryption());
	}
}
