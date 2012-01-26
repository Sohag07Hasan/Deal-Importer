<?php 
/*
 * plugin name: TRDM Deals AND Deal Importer
 * author: Mahibul Hasan
 * description: creates a new posttype deal and a csv uploader under tools
 * version: 2.0.1
 * plugin uri: http://therealdeal.com/
 * author uri: http://sohag.me
 * 
 * */

define('TRDM_CSV_FILE', __FILE__);
define('TRDM_CSV_DIR', dirname(__FILE__));
define('TRDM_CSV_CLASS', TRDM_CSV_DIR . '/classes');


include TRDM_CSV_CLASS . '/options.php';
include TRDM_CSV_CLASS . '/DataSource.php';
include TRDM_CSV_CLASS . '/csv_importer.php';

?>
