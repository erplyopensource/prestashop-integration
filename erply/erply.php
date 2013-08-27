<?php
error_reporting(0);
@ini_set('display_errors', 'Off');

//include_once(dirname(__FILE__).'/ErplyAPI.class.php');
include(_PS_MODULE_DIR_.'/erply/ErplyAPI.class.php');
include(_PS_MODULE_DIR_.'/erply/ErplyFunctions.class.php');
//include(_PS_MODULE_DIR_.'/erply/Categories.class.php');
//include(_PS_MODULE_DIR_.'/erply/Orders.class.php');

class Erply extends Module
{
	private $_html = '';
	private $_postErrors = array();

	private $_erp_client_code;
	private $_erp_username;
	private $_erp_password;
	private $_erp_max_products_number;
	private $_pre_max_products_number;

	public function __construct()
	{
		$this->name = 'erply';
		$this->tab = 'migration_tools';
		$this->version = '1.5.1';
		$this->author = 'Inventory.com';
		$this->module_key = '1e68ef1547edaf6abeb6bc9b5d98ea5f';

		$this->_refreshProperties();

		parent::__construct();

		$this->displayName = $this->l('Order Management & Inventory Management');
		$this->description = $this->l('Order & inventory management backend for PrestaShop');
		$this->confirmUninstall = $this->l('Are you sure you want to delete this module');
	}

	public function install()
	{
//		if (!parent::install() OR
//			!$this->registerHook('newOrder') OR
//			!$this->registerHook('postUpdateOrderStatus') OR 
//			!$this->registerHook('paymentConfirm')
//		)return false;
		if(!parent::install()) return false;

		Configuration::updateValue('ERP_MERCHANT_ORDER', 1);
		Configuration::updateValue('ERP_CLIENTCODE', null);
		Configuration::updateValue('ERP_USERNAME', null);
		Configuration::updateValue('ERP_PASSWORD', null);

		Configuration::updateValue('ERPLY_OBJECT_PRIORITY', 'ERPLY');
		Configuration::updateValue('ERPLY_EXPORT_ORDERS_FROM', mktime(0, 0, 0, date('n'), date('j'), date('Y')));
		Configuration::updateValue('ERPLY_PRESTA_ROOT_CATEGORY_ID', 2);

		include(_PS_MODULE_DIR_.'/erply/upgrades/upgrade_1_2_to_2_0.php');

//		Configuration::updateValue('ERP_MAX_PRODUCTS_NUMBER', null);
//		Configuration::updateValue('PRE_MAX_PRODUCTS_NUMBER', null);
//		Configuration::updateValue('ERP_CATEGORY_LAST_SYNC_MAX_TS', 1);
//		Configuration::updateValue('PRE_CATEGORY_LAST_SYNC_MAX_TS', 1);
//		Configuration::updateValue('ERP_PRODUCT_LAST_SYNC_MAX_TS', 1);
//		Configuration::updateValue('PRE_PRODUCT_LAST_SYNC_MAX_TS', 1);
//		 set start date for new orders(the date of module installation)
//		Configuration::updateValue('ERP_ORDERS_LAST_SYNC_TS', time());

//		Configuration::updateValue('ERP_LAST_CRON_TS', 1);

		Configuration::updateValue('ERPLY_VERSION', $this->version);

		return true;
	}

	public function uninstall()
	{
//		Configuration::deleteByName('ERPLY_VERSION');

		Configuration::deleteByName('ERP_MERCHANT_ORDER');
		Configuration::deleteByName('ERP_CLIENTCODE');
		Configuration::deleteByName('ERP_USERNAME');
		Configuration::deleteByName('ERP_PASSWORD');

		Configuration::deleteByName('ERPLY_OBJECT_PRIORITY');
		Configuration::deleteByName('ERPLY_EXPORT_ORDERS_FROM');

		Configuration::deleteByName('ERPLY_ERPLY_CUST_GROUPS_LS_TS');
		Configuration::deleteByName('ERPLY_PRESTA_CUST_GROUPS_LS_TS');
		Configuration::deleteByName('ERPLY_PRESTA_CUSTOMERS_LS_TS');
		Configuration::deleteByName('ERPLY_PRESTA_CUST_ADDR_LS_TS');
		Configuration::deleteByName('ERPLY_ERPLY_CATEGORIES_LS_TS');
		Configuration::deleteByName('ERPLY_PRESTA_CATEGORIES_LS_TS');
		Configuration::deleteByName('ERPLY_ERPLY_PRODUCTS_LS_TS');
		Configuration::deleteByName('ERPLY_PRESTA_PRODUCTS_LS_TS');
		Configuration::deleteByName('ERPLY_PRESTA_ORDER_LS_TS');
		Configuration::deleteByName('ERPLY_PRESTA_ROOT_CATEGORY_ID');

		// Clear mappings from db.
		Db::getInstance()->Execute('TRUNCATE TABLE `'._DB_PREFIX_.'erply_mapping`');

//		Configuration::deleteByName('ERP_MAX_PRODUCTS_NUMBER');
//		Configuration::deleteByName('PRE_MAX_PRODUCTS_NUMBER');
//		Configuration::deleteByName('ERP_CATEGORY_LAST_SYNC_MAX_TS');
//		Configuration::deleteByName('PRE_CATEGORY_LAST_SYNC_MAX_TS');
//		Configuration::deleteByName('ERP_PRODUCT_LAST_SYNC_MAX_TS');
//		Configuration::deleteByName('PRE_PRODUCT_LAST_SYNC_MAX_TS');

//		Configuration::deleteByName('ERP_LAST_CRON_TS');
//		Configuration::deleteByName('ERP_NEW_ORDER_ACTION');
//		Configuration::deleteByName('ERP_ORDERS_LAST_SYNC_TS');

		return parent::uninstall();
	}

	private function _refreshProperties()
	{
		$this->_erp_client_code = Configuration::get('ERP_CLIENTCODE');
		$this->_erp_username = Configuration::get('ERP_USERNAME');
		$this->_erp_password = Configuration::get('ERP_PASSWORD');
		$this->_erp_max_products_number = Configuration::get('ERP_MAX_PRODUCTS_NUMBER');
		$this->_pre_max_products_number = Configuration::get('PRE_MAX_PRODUCTS_NUMBER');

		$this->_merchant_order = intval(Configuration::get('ERP_MERCHANT_ORDER'));
	}

	public function hookNewOrder($params)
	{
	}

	public function hookPaymentConfirm($params){
	}

	public function hookUpdateOrderStatus($params){
	}

	public function hookPostUpdateOrderStatus($params)
	{
	}
	
	public function getContent()
	{
		$this->_html = '<h2>'.$this->displayName.'</h2>';
		$this->_postProcess();
		$this->_displayForm();
		return $this->_html;
	}

	private function _displayForm()
	{
		$tab = Tools::getValue('tab');
		$currentIndex = __PS_BASE_URI__.substr($_SERVER['SCRIPT_NAME'], strlen(__PS_BASE_URI__)).($tab ? '?tab='.$tab : '');
		$token = Tools::getValue('token');

		$this->_html .= '
		<form action="'.$currentIndex.'&token='.$token.'&configure=erply" method="post">
			<fieldset class="width3"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Inventory.com / Erply account information').'</legend>
				<label>'.$this->l('Customer code:').'</label>
				<div class="margin-form">
					<input type="text" name="ERP_CLIENTCODE" value="'.(Tools::getValue('ERP_CLIENTCODE') != NULL ? Tools::getValue('ERP_CLIENTCODE') : Configuration::get('ERP_CLIENTCODE', 1)).'" size="15" />
				</div>
				<label>'.$this->l('Username:').'</label>
				<div class="margin-form">
					<input type="text" name="ERP_USERNAME" value="'.(Tools::getValue('ERP_USERNAME') != NULL ? Tools::getValue('ERP_USERNAME') : Configuration::get('ERP_USERNAME', 1)) . '" size="15" />
				</div>
				<label>'.$this->l('Password:').'</label>
				<div class="margin-form">
					<input type="password" name="ERP_PASSWORD" value="'.(Tools::getValue('ERP_PASSWORD') != NULL ? Tools::getValue('ERP_PASSWORD') : base64_decode(Configuration::get('ERP_PASSWORD', 1))).'" size="15" />
				</div>
				<div class="margin-form">
					<input type="submit" value="'.$this->l('   Save   ').'" name="submitErplyData" class="button" />
				</div>
        		<div style="font-size: 18px; margin-top: 30px; margin-bottom: 30px; text-align: center;">
					Don\'t have Inventory.com account?<br> 
					<a href="http://www.inventory.com/free-management-software?referer=PrestaShop" target="_blank">Click here to sign up!</a>
				</div>
			</fieldset>
		</form>
		<br />
		<form action="'.$currentIndex.'&token='.$token.'&configure=erply" method="post">
			<fieldset class="width3"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('General settings').'</legend>
				<label>'.$this->l('Priority:').'</label>
				<div class="margin-form">
					<select name="ERPLY_OBJECT_PRIORITY" style="width:113px;">';

		$objectPriorityOptionsAry = array(
			array('ERPLY', 'Inventory.com (Erply)')
			, array('Presta', 'PrestaShop')
		);
		$selectedValue = Configuration::get('ERPLY_OBJECT_PRIORITY');
		foreach($objectPriorityOptionsAry as $option)
		{
			$this->_html .= sprintf('<option value="%s" %s>%s</option>'
				, $option[0]
				, ($option[0] == $selectedValue ? 'selected="selected"' : '')
				, $option[1]
			);
		}

		$this->_html .= '
					</select>
					<br>Select which system has higher priority when same object (product, customer etc.) has been changed in both systems since last synchronization.
				</div>
				<label>'.$this->l('Default Tax:').'</label>
				<div class="margin-form">
					<select name="ERPLY_DEFAULT_TAXGROUP">';

		$optionsAry = TaxRulesGroup::getTaxRulesGroupsForOptions();
		$activeTaxgroup = Configuration::get('ERPLY_DEFAULT_TAXGROUP');
		foreach($optionsAry as $option)
		{
			$this->_html .= sprintf('<option value="%s" %s>%s</option>'
				, $option['id_tax_rules_group']
				, ($option['id_tax_rules_group'] == $activeTaxgroup) ? 'selected' : ''
				, $option['name']
			);
		}

		$this->_html .= '
					</select>
					<br>If tax value is not found in product data this value is used.
				</div>
				<label>'.$this->l('Export orders from:').'</label>
				<div class="margin-form">
					<input type="text" name="ERPLY_EXPORT_ORDERS_FROM" value="'.(Configuration::get('ERPLY_EXPORT_ORDERS_FROM') != false ? date('Y-m-d', Configuration::get('ERPLY_EXPORT_ORDERS_FROM')) : '').'" size="15" />
					<br>Format: YYYY-MM-DD
					<br>Initial order export will be made starting from this date. 
				</div>
				
				<label>'.$this->l('PrestaShop root category ID:').'</label>
				<div class="margin-form">
					<input type="text" name="ERPLY_PRESTA_ROOT_CATEGORY_ID" value="'.(Configuration::get('ERPLY_PRESTA_ROOT_CATEGORY_ID') != false ? Configuration::get('ERPLY_PRESTA_ROOT_CATEGORY_ID') : '2').'" size="15" />
					<br>ID of PrestaShop root category. All product groups imported form Inventory.com will appear under this category. Value 2 defaults to "Home".
				</div>
				<div class="margin-form">
					<input type="submit" value="'.$this->l('Save').'" name="saveGeneralSettings" class="button" />
				</div>
			</fieldset>
		</form>
		<br />
		<form action="'.$currentIndex.'&token='.$token.'&configure=erply" method="post">
			<fieldset class="width3"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Manual synchronisation').'</legend>
				<label>'.$this->l('Synchronize:').'</label>
				<div class="margin-form">
					<select name="manual_sync_method">
						<option value="export">from Presta to Inventory.com</option>
						<option value="import">from Inventory.com to Presta</option>
						<option value="sync">both ways</option>
					</select>
					<br>'.$this->l('Changed records are product, customers, orders etc. that have chagend since last synchronization.').'
				</div>
				<label>&nbsp;</label>
				<div class="margin-form">
					<input type="submit" value="'.$this->l('Start').'" name="manualSync" class="button" />
				</div>	
			</fieldset>
		</form>
		<br>
		<form action="'.$currentIndex.'&token='.$token.'&configure=erply" method="post">
			<fieldset class="width3"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Advanced').'</legend>
				<!--<p>
					<input type="submit" name="convert_1_2_to_2_0" value="'.$this->l('Convert data from version 1.2 to 2.0').'" class="button" />
					<br>NB! Use this function only if you upgraded from version 1.2 to 2.0 and you have not executed any synchronization since the upgrade.
					This includes both manual and cron synchronizations.
				</p>-->
				<p>
					<input type="submit" name="advanced" value="'.$this->l('Advanced options').'" class="button" />
				</p>
				</fieldset>
		</form>';

		// Admin functions
		if(Tools::getValue('advanced'))
		{
			$this->_html .= '
		<br>
		<form action="'.$currentIndex.'&token='.$token.'&configure=erply" method="post">
		<input type="hidden" name="advanced" value="1">
		<fieldset class="width3">
			<legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Set last IMPORT timestamps').' (Y-m-d H:i:s)</legend>

			<label>'.$this->l('Categories').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[ERPLY_CATEGORIES]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_ERPLY_CATEGORIES_LS_TS')).'"><br>
				ERPLY_ERPLY_CATEGORIES_LS_TS = '.Configuration::get('ERPLY_ERPLY_CATEGORIES_LS_TS').'
			</div>

			<label>'.$this->l('Products').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[ERPLY_PRODUCTS]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_ERPLY_PRODUCTS_LS_TS')).'"><br>
				ERPLY_ERPLY_PRODUCTS_LS_TS = '.Configuration::get('ERPLY_ERPLY_PRODUCTS_LS_TS').'
			</div>

			<label>'.$this->l('Customer Groups').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[ERPLY_CUST_GROUPS]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_ERPLY_CUST_GROUPS_LS_TS')).'"><br>
				ERPLY_ERPLY_CUST_GROUPS_LS_TS = '.Configuration::get('ERPLY_ERPLY_CUST_GROUPS_LS_TS').'
			</div>

			<label>&nbsp;</label>
			<div class="margin-form">
				<input type="submit" value="'.$this->l('Save').'" name="setTimestamps" class="button" />
			</div>
		</fieldset>
		</form>

		<br>
		<form action="'.$currentIndex.'&token='.$token.'&configure=erply" method="post">
		<input type="hidden" name="advanced" value="1">
		<fieldset class="width3">
			<legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Set last EXPORT timestamps').' (Y-m-d H:i:s)</legend>

			<label>'.$this->l('Categories').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[PRESTA_CATEGORIES]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_PRESTA_CATEGORIES_LS_TS')).'"><br>
				ERPLY_PRESTA_CATEGORIES_LS_TS = '.Configuration::get('ERPLY_PRESTA_CATEGORIES_LS_TS').'
			</div>

			<label>'.$this->l('Products').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[PRESTA_PRODUCTS]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_PRESTA_PRODUCTS_LS_TS')).'"><br>
				ERPLY_PRESTA_PRODUCTS_LS_TS = '.Configuration::get('ERPLY_PRESTA_PRODUCTS_LS_TS').'
			</div>

			<label>'.$this->l('Customer Groups').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[PRESTA_CUST_GROUPS]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_PRESTA_CUST_GROUPS_LS_TS')).'"><br>
				ERPLY_PRESTA_CUST_GROUPS_LS_TS = '.Configuration::get('ERPLY_PRESTA_CUST_GROUPS_LS_TS').'
			</div>

			<label>'.$this->l('Customers').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[PRESTA_CUSTOMERS]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_PRESTA_CUSTOMERS_LS_TS')).'"><br>
				ERPLY_PRESTA_CUSTOMERS_LS_TS = '.Configuration::get('ERPLY_PRESTA_CUSTOMERS_LS_TS').'
			</div>

			<label>'.$this->l('Customer Addresses').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[PRESTA_CUST_ADDR]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_PRESTA_CUST_ADDR_LS_TS')).'"><br>
				ERPLY_PRESTA_CUST_ADDR_LS_TS = '.Configuration::get('ERPLY_PRESTA_CUST_ADDR_LS_TS').'
			</div>

			<label>'.$this->l('Orders').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[PRESTA_ORDER]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_PRESTA_ORDER_LS_TS')).'"><br>
				ERPLY_PRESTA_ORDER_LS_TS = '.Configuration::get('ERPLY_PRESTA_ORDER_LS_TS').'
			</div>

			<label>'.$this->l('Order history').'</label>
			<div class="margin-form">
				<input type="text" name="timestamp[PRESTA_ORDER_HISTORY]" value="'.date('Y-m-d H:i:s', (int)Configuration::get('ERPLY_PRESTA_ORDER_HISTORY_LS_TS')).'"><br>
				ERPLY_PRESTA_ORDER_HISTORY_LS_TS = '.Configuration::get('ERPLY_PRESTA_ORDER_HISTORY_LS_TS').'
			</div>

			<label>&nbsp;</label>
			<div class="margin-form">
				<input type="submit" value="'.$this->l('Save').'" name="setTimestamps" class="button" />
			</div>
		</fieldset>
		</form>

		<br>
		<fieldset class="width3"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Admin').'</legend>
			<p>
				Clear PrestaShop and Inventory.com synchronization.<br>
				<b>NB!</b> Only use this if you really know what you are doing!!!
			</p>

			<form action="'.$currentIndex.'&token='.$token.'&configure=erply" method="post">
				<input type="hidden" name="advanced" value="1">
				<input type="submit" name="deleteMappings" value="'.$this->l('Clear Mappings').'" class="button" />
			</form>

		</fieldset>';

			/* Stats
		<br>
		<fieldset class="width3"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Debug').'</legend>
			<b>Time difference:</b> '.ErplyFunctions::getErplyApi()->getTimeDifference().'<br>
			Import:<br>
			<b>ERPLY_ERPLY_CATEGORIES_LS_TS:</b> '.Configuration::get('ERPLY_ERPLY_CATEGORIES_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_ERPLY_CATEGORIES_LS_TS')).')<br>
			<b>ERPLY_ERPLY_PRODUCTS_LS_TS:</b> '.Configuration::get('ERPLY_ERPLY_PRODUCTS_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_ERPLY_PRODUCTS_LS_TS')).')<br>
			<b>ERPLY_ERPLY_CUST_GROUPS_LS_TS:</b> '.Configuration::get('ERPLY_ERPLY_CUST_GROUPS_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_ERPLY_CUST_GROUPS_LS_TS')).')<br>
			Export:<br>
			<b>ERPLY_PRESTA_CATEGORIES_LS_TS:</b> '.Configuration::get('ERPLY_PRESTA_CATEGORIES_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_PRESTA_CATEGORIES_LS_TS')).')<br>
			<b>ERPLY_PRESTA_PRODUCTS_LS_TS:</b> '.Configuration::get('ERPLY_PRESTA_PRODUCTS_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_PRESTA_PRODUCTS_LS_TS')).')<br>
			<b>ERPLY_PRESTA_CUST_GROUPS_LS_TS:</b> '.Configuration::get('ERPLY_PRESTA_CUST_GROUPS_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_PRESTA_CUST_GROUPS_LS_TS')).')<br>
			<b>ERPLY_PRESTA_CUSTOMERS_LS_TS:</b> '.Configuration::get('ERPLY_PRESTA_CUSTOMERS_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_PRESTA_CUSTOMERS_LS_TS')).')<br>
			<b>ERPLY_PRESTA_CUST_ADDR_LS_TS:</b> '.Configuration::get('ERPLY_PRESTA_CUST_ADDR_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_PRESTA_CUST_ADDR_LS_TS')).')<br>
			<b>ERPLY_PRESTA_ORDER_LS_TS:</b> '.Configuration::get('ERPLY_PRESTA_ORDER_LS_TS').' ('.date('d.m.Y H:i:s', (int)Configuration::get('ERPLY_PRESTA_ORDER_LS_TS')).')<br>
		</fieldset>';
			*/
		}
	}

	private function _postProcess()
	{
		$successMsgTpl = '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />%s</div>';
		$errorMsgTpl = '<div class="alert error">%s</div>';

		if (Tools::isSubmit('submitErplyData'))
		{
			$erp_client_code = array(1 => Tools::getValue('ERP_CLIENTCODE'));
			$erp_username = array(1 => Tools::getValue('ERP_USERNAME'));
			$erp_password = array(1 => base64_encode(Tools::getValue('ERP_PASSWORD')));
		
			if (!Configuration::updateValue('ERP_CLIENTCODE', $erp_client_code)
					|| !Configuration::updateValue('ERP_USERNAME', $erp_username)
					|| !Configuration::updateValue('ERP_PASSWORD', $erp_password)){
				$this->_html .= '<div class="alert error">'.$this->l('Cannot update account information').'</div>';
			}else{
				$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Account information successfully saved').'</div>';
			}
		}
		elseif(Tools::isSubmit('saveGeneralSettings'))
		{
			$success = true;

			// Save object priority.
			$objectPriorityVal = Tools::getValue('ERPLY_OBJECT_PRIORITY');
			if (!Configuration::updateValue('ERPLY_OBJECT_PRIORITY', (string)$objectPriorityVal)){
				$this->_html .= '<div class="alert error">'.$this->l('Cannot save "Priority" value.').'</div>';
				$success = false;
			}

			// Save default taxgroup.
			$defaultTaxgroupVal = Tools::getValue('ERPLY_DEFAULT_TAXGROUP');
			if (!Configuration::updateValue('ERPLY_DEFAULT_TAXGROUP', (int)$defaultTaxgroupVal)){
				$this->_html .= '<div class="alert error">'.$this->l('Cannot save "Default Tax" value.').'</div>';
				$success = false;
			}

			// Save export orders from.
			$exportOrdersFromVal = Tools::getValue('ERPLY_EXPORT_ORDERS_FROM');
			if(empty($exportOrdersFromVal)) {
				$exportOrdersFromTS = 0;
			} else {
				$exportOrdersFromAry = explode('-', Tools::getValue('ERPLY_EXPORT_ORDERS_FROM'));
				if(count($exportOrdersFromAry) == 3) {
					$exportOrdersFromTS = mktime(0, 0, 0, (int)$exportOrdersFromAry[1], (int)$exportOrdersFromAry[2], (int)$exportOrdersFromAry[0]);
				} else {
					$exportOrdersFromTS = 0;
				}
			}
			if (!Configuration::updateValue('ERPLY_EXPORT_ORDERS_FROM', $exportOrdersFromTS)) {
				$this->_html .= '<div class="alert error">'.$this->l('Cannot save "Export orders from" value.').'</div>';
				$success = false;
			}
			
			// presta root category id
			$prestaRootCategoryId = Tools::getValue('ERPLY_PRESTA_ROOT_CATEGORY_ID');
			if (!Configuration::updateValue('ERPLY_PRESTA_ROOT_CATEGORY_ID', (int)$prestaRootCategoryId)){
				$this->_html .= '<div class="alert error">'.$this->l('Cannot save "Root category ID" value.').'</div>';
				$success = false;
			}
			

			if ($success) {
				$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Settings successfully saved').'</div>';
			}
		}
		// Manually sync info
		elseif(Tools::isSubmit('manualSync'))
		{
			include_once(dirname(__FILE__).'/Sync.php');
			try
			{
				switch(Tools::getValue('manual_sync_method'))
				{
					// Changed records
					case 'export':
						Erply_Sync::exportAll();
						break;
					case 'import':
						Erply_Sync::importAll();
						break;
					case 'sync':
						Erply_Sync::syncAll();
						break;
				}
				$this->_html .= sprintf($successMsgTpl, $this->l('Synchronization completed successfully!'));
			}
			catch(Erply_Exception $e)
			{
				$this->_html .= sprintf($errorMsgTpl, $this->l('Error while synchronizing data. ').$e->getCode().' - '.$e->getMessage());
			}
		}
		// Upgrade module from previous version.
		elseif(Tools::isSubmit('convert_1_2_to_2_0'))
		{
			// Upgrade to newer version
			$upgradeFile = sprintf('%s/erply/upgrades/convert_1_2_to_2_0.php', _PS_MODULE_DIR_);
			if(file_exists($upgradeFile)) {
				include($upgradeFile);
			}
		}
		// Delete current mappings. Only for advanced users.
		elseif(Tools::isSubmit('deleteMappings'))
		{
			include_once(dirname(__FILE__).'/ErplyMapping.php');
			ErplyMapping::deleteAll();
		}
		// Set timestamps
		elseif(Tools::isSubmit('setTimestamps'))
		{
			$timestampsAry = Tools::getValue('timestamp');
			if(is_array($timestampsAry)) {
				foreach($timestampsAry as $key=>$val)
				{
					$ts = !empty($val) ? strtotime($val) : 0;
					ErplyFunctions::setLastSyncTS($key, $ts);
				}
			}
			$this->_html .= sprintf($successMsgTpl, $this->l('Synchronization timestamps saved!'));
		}
		// Reset IMPORT timestamps
//		elseif(Tools::isSubmit('resetImportTimestamp'))
//		{
//			if(is_array(Tools::getValue('timestamp'))) {
//				foreach(Tools::getValue('timestamp') as $key)
//				{
//					ErplyFunctions::setLastSyncTS($key, 0);
//				}
//			}
//		}
		// Reset EXPORT timestamps
//		elseif(Tools::isSubmit('resetExportTimestamp'))
//		{
//			if(is_array(Tools::getValue('timestamp'))) {
//				foreach(Tools::getValue('timestamp') as $key)
//				{
//					ErplyFunctions::setLastSyncTS($key, 0);
//				}
//			}
//		}

		$this->_refreshProperties();
	}

	private function getOrderHistoryId($id_order){
		$result = Db::getInstance()->getRow('
		SELECT `id_order_history`
		FROM `'._DB_PREFIX_.'order_history`
		WHERE `id_order` = '.intval($id_order).'
		ORDER BY `date_add` DESC, `id_order_history` DESC');
		if($result){
			return $result['id_order_history'];
		}
		return false;
	}
}

?>