<?php
  // The database name should be dhh
  // The collection should represent the name of the client company
  // Each document that we add should contain the following;
  // 1. source_id: The source as in the config.php file
  // 2. updated_at: This stores the updated_at date of the source file.
  // 3. id: This is normally the cat_no. Used to identify across files.
  //
  // Other fields are dynamically created and generally not indexed.
  // We can specify indexes for the ones that we want in config.php.
  


    // Show PHP errors (during development only)
    error_reporting(E_ALL | E_STRICT);
    ini_set("display_errors", 2);
 
    // Create a Mongo conenction
    $mongo = new MongoClient();
 
    // Choose the database and collection
    $db = $mongo->my_db_name;
    $coll = $db->my_collection_name;
 

    $benchstart = microtime(true);
    // Same a document to the collection
    // Current testing suggests that a batch size of 1000 might be optimal,
    // but we need to test. Also, we might have to increase the memory in
    // php.ini
    // On my MBA with SSD, we are taking a bit less than 2 seconds for 100,000 simple docs.
    $batch_size = 1000;
    $number_of_items = 100000;
    $batch = array();
    for ($i=0; $i < $number_of_items; $i++) { 
      array_push($batch, array(
          "name" => "Jack Sparrow",
          "age" => 34,
          "occupation" => "Pirate"
      ));
      if (($i + 1) % $batch_size == 0 || ($i + 1) == $number_of_items) {
        $coll->batchInsert($batch);
        $batch = array();
      } 
    }

    echo "time ".(microtime(true) - $benchstart)."<br>";

    echo "Number of uploaded items ".$coll->count()."<br>";
    // Retrieve the document and display it
    $item = $coll->findOne();
 
    echo "My name is " . $item['name'] . ". I am " . $item['age'] . " years old and work full-time as a " . $item['occupation'] . ".";

    $coll->drop();