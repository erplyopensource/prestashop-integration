<?php

error_reporting(E_ALL);
@ini_set('display_errors', 'on');

// Init PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Init Erply
include_once(dirname(__FILE__).'/ErplyAPI.class.php');
include_once(dirname(__FILE__).'/ErplyFunctions.class.php');
include_once(dirname(__FILE__).'/ErplyMapping.php');
include_once(dirname(__FILE__).'/Sync/Categories.php');
include_once(dirname(__FILE__).'/Sync/Products.php');
include_once(dirname(__FILE__).'/Sync/CustomerGroups.php');
include_once(dirname(__FILE__).'/Sync/Customers.php');
include_once(dirname(__FILE__).'/Sync/CustomerAddresses.php');
include_once(dirname(__FILE__).'/Sync/Orders.php');
include_once(dirname(__FILE__).'/Sync/OrderHistory.php');

$c = new Category(3);
ErplyFunctions::debug('$c', $c); exit;

?>