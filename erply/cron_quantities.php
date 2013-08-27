<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__).'/ErplyFunctions.class.php');
include_once(dirname(__FILE__).'/Sync.php');

Erply_Sync::importCategories();
Erply_Sync::importProducts();

?>