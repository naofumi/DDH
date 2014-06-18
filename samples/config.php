<?php 

///////////////////////////////////////////////////////
// Cache configuration
//////////////////////////////////////////////////////
// Set $use_cache to true to turn caching on.
// Caches will be saved in 'tmp/cache/'
$use_cache = true;
// Set the expiration time for the response cache.
// This caches the whole JSONP response.
$cache_expire = 3600; // seconds

////////////////////////////////////////////////////////
// Data sources
////////////////////////////////////////////////////////
// List the CSV files that we will use to
// generate JSONP responses.
//
// "encoding" is the encoding for the source data file.
//
// "fields" is an array of the field names. The field names
// are used throughout the system to identify data 
// and should not be changed without great consideration.
//
// "filename" is the basename of the CSV file.
//
// The leftmost column of the CSV file is always used as the
// product ID which will be used to query for product information.
// This cannot be changed.
$source_parameters = array(
  "価格表" => array(  "encoding" => "UTF-8",
                     "fields" => array('cat_no','package','price','name'),
                     "rowspanable" => array('name'),
                     "filename" => "pricelist.csv"
                     ),
  "キャンペーン" => array(
                         "encoding" => "UTF-8",
                         "fields" => array('cat_no','campaign_price','campaign_message','starts_at', 'ends_at', 'campaign_link'),
                         "filename" => "campaign.csv"
                         )  
);

////////////////////////////////////////////////////
// Account settings
////////////////////////////////////////////////////
// "salt" is 16-letters, must start with "$6$" for CRYPT_SHA512
//
// Register accounts with login id as the key.
// You can restrict access by calling `basic_auth($admin_users)`
// or `basic_auth($dealer_users)` depending on which users
// you want to grant access to.
$admin_users = array("boo" => array("salt" => "$6$2asd8xi1coasd", 
                                    "hashed_password" => crypt("hoo", $salt)));

$dealer_users = array("kung" => array("salt" => "$6$2asd8xi1coasd", 
                                    "hashed_password" => crypt("fu", $salt)));

////////////////////////////////////////////////////
// Set time zone
////////////////////////////////////////////////////
date_default_timezone_set ("Asia/Tokyo");


////////////////////////////////////////////////////
// Add methods to the DataSource and DataRow classes
// below to create calculated data fields.
////////////////////////////////////////////////////
class DataSource extends DataSourceBase {
}

// If you define a public function named 'my_function',
// then you can call $data_row->get('my_function') to
// get the value.
// You can even override fields in the data_source, but
// be careful not to create infinte loops.
//
// Use this to provide simple formatting or custom field calculations.
class DataRow extends DataRowBase {
  public function price() {
    if ($this->is_campaign()){
      if ($this->get('campaign_link') && $this->get('campaign_message')) {
        $message = "<a href='".$this->get('campaign_link')."' style='font-weight:bold;text-decoration:none;color:red'>".
                   $this->get('campaign_message')."</a><br />\n";
      } else {
        $message = $this->get('campaign_message')."<br />\n";
      }
      return $message."<s>¥".$this->row['price']."</s> <span style='color:red'>".$this->get('campaign_price')."</span>";      
    } else {
      return "¥".$this->row['price'];
    }
  }
}

////////////////////////////////////////////////////
// Additional view helpers
////////////////////////////////////////////////////
//
// Add any custom view helpers here as global functions.

////////////////////////////////////////////////////
// Server configuaration
////////////////////////////////////////////////////
$iconv_path = "/opt/local/bin/iconv";