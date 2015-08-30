<?php
// The MongoDBDataSource class is the base class for MongoDB backed
// DDH. We plan to move everything from the original DataSource class
// to the MonogDBDataSource as soon as possible. The advantages of
// using MongoDB are;
//
// 1. Faster query speed
// 2. Better history management (snapshot management)
// 3. More complex queries
// 4. Better scaling
// 5. etc.
//
// The DataSource class is the core class that allows us to
// extract information from multiple CSV files and to join them
// based on their IDs (the leftmost column values).
//
// #### Usage
//
// As with the original DataSource class, the MongoDBDataSource class
// only works when a set of $ids is given. It is ideal to use for embedding
// data when the catalog_number or some other ID is specified in the tag.
//
// To do more complex queries, use a QueriedDataSource which inherits
// from the MongoDBDataSource class.
//
// The public methods for MongoDBDataSource are;
//
// `__construct()`: Constructor
// `set_ids()`: Set the IDs. Unlike the DataSource class, we don't set the
//              IDs inside the constructor.
// `ids()`: Get an array of the IDs.
// `total_rows()`: The number of rows that were retrieved.
// `rows()`: The rows that were retrieved. This is an array of
//           DataRow objects.
// `row($id)`: The DataRow object for a certain $id.
// 
// First initialize a DataSource object with the $datasource
// array and a list of IDs. Then you can query the object based
// on IDs to retrieve data.
//
// $data_source = new MongoDBDataSource($source_parameters);
// // then we set the ids like so;
// $data_source->set_ids($ids);
//
// $data_source->rows()
// $data_source->total_rows()
// $data_source->row($id)
// $data_source->ids()
// // etc...
//
// #### Some notes on the implementation.
//
// 1. Snapshot based version management
// A snapshot remembers which file versions were published at each
// point in time. We have special "preview" and "current" snapshots.
//
// Each snapshot has a list of $source_ids and the versions of files that
// were used (`updated_at` timestamps of the CSV files).
//
// This allows us to retrieve the data for any published date.
//
// 2. CSV representation in MongoDB
// Each CSV file has its own MongoDB collection. The name of the MongoDB
// collection is the same at the $source_id.
//
// Each row of a CSV file is inserted into the collection as a separate
// document. The `updated_at` timestamp of the file is stored into each 
// document together with the `id`, the `row` (as an associated list) and 
// the `row_num`.
//
// Because we store the `updated_at` we can store multiple versions of the
// CSV files.
// 
// #### Mongo DB structure ####
// 
// 1. The name of the MongoDB database
// The name of the MongoDB database is derived from the name of the 
// implementation folder. It will be something like `ddh_iwai-chem_15ff4e`
//
// 2. Collections
// The database has a special collection named `snapshots` and collections
// for each CSV file.
// - snapshots
// The snapshot collection manages the metadata for each snapshot. This
// includes published_at, sources (the versions of each source CSV),
// whether it is the current snapshot and the associated comment.
// - collections for each CSV
// These collections contain the data from the CSV file (in the `row` as an assocated list)
// and `updated_at`, `row_num` and `id` (which is a unique identifier for that product)
//
// ##### Upload schemes #######
// Files may be uploaded either through direct access to the filesystem
// through methods like ftp, ssh or WebDAV. This is best if we want
// to interface with ERP backends which could automatically update
// these files.
//
// 1. Directory access
// At predefined intervals, a certain directory will be scanned for the
// presence of new files. If files exist, then they will be uploaded 
// into MongoDB.
// This will create a new preview snapshot. The files will then be deleted.
// The preview snapshot will then be published.
//
// 2. Web access
// When files are uploaded via the web interface, the contents will
// immediately be uploaded into MongoDB into a preview snapshot.
// When using the web interface, publishing of the preview snapshot has
// to happen though human interaction.
//
// 
class MongoDBDataSource {
  protected $mongo;
  // TODO: accessed from mongodb_preview.php and select_options.php. Want to think about encapsulation
  public $db; 
  protected $snapshots;
  protected $staging_directory;
  protected $source_parameters;
  protected $snapshot_version;
  protected $snapshot;

  protected $data;
  protected $ids;
  protected $maximum_results;

  ///// Reflection methods ////
  // Methods that tell us about the database state

  // We get the db_name from the parent directory of the `ddh` directory.
  // By convention, this will be something like `ddh_iwai-chem_15ff4e`
  protected function db_name() {
    $ddh_folder = __DIR__;
    $client_implementation_folder = dirname($ddh_folder);
    return basename($client_implementation_folder);
  }

  ///// Initialization methods //////

  // $snapshot_version is used when we want to get a version other than 'current'
  // for preview purposes.
  // $snapshot_version is either the published_at time, 'current' or 'preview'.
  // Default is 'current'
  // Usually, you would use `preview_version()` to get the version in
  // the controller (jsonp.php).
  function __construct($source_parameters, $snapshot_version = 'current') {
    $db_name = $this->db_name();
    $this->snapshot_version = $snapshot_version;
    $this->source_parameters = $source_parameters;
    // Create a Mongo conenction
    $this->mongo = new MongoClient();
    $this->db = $this->mongo->$db_name;
    $this->snapshots = $this->db->snapshots;
    // $this->snapshots->createIndex(array('published_at' => 1), array('unique' => true));
    $this->current = $this->db->current;

    $this->staging_directory = dirname(__FILE__).'/../data/preview/';
  }

  public function set_ids($ids) {
    $this->ids = $ids;
  }

  // If this data source has any fields that have predefined
  // values. Override this function and define them as a
  // nested associative array.
  //
  // Field_values allow us to get a list of all values that occur in the
  // data without looking through the whole CSV file, and we use them
  // when we initially populate the select menu options.
  //
  // TODO: MongoDB should allow us to use these more effectively and more easily.
  // We will look into this. Look at #28 of select_options.php
  // Also look into http://docs.mongodb.org/master/core/aggregation-pipeline/
  public function field_values($field){
    return null;
  }


  ////// Data retrieval methods ////////

  // Populate $this->data with a hash representing data from all data sources.
  // The $this->data format is keys are the $ids, and the values are DataRow objects.
  //
  // The $this->ids are searched in all sources and data is joined together.
  //
  // If $this->sort_callback() exists, then it is called to sort the data.
  // sort_callback($a, $b) should return an integer indicating sort order.
  protected function retrieve_data() {
    if (!isset($this->data)){
      $this->data = array();
      foreach($this->sources() as $source_id) {
        $this->update_from_source_id($source_id);
      }     
      if (method_exists($this, "sort_callback")) {
        uasort($this->data, array($this, "sort_callback"));
      }
    }
  }

  // Update $this->data with the data from a single $source_id 
  // for the ids in $this->ids.
  //
  // Previous values in $this->data will be overwritten with the new data.
  protected function update_from_source_id($source_id) {
    if (!isset($this->ids)) {
      throw new Exception("ids have not been set on MongoDBDataSource object.");
    }
    $assoc_list = $this->get_assoc_list_for_ids($this->ids, $source_id);
    $this->update_data_with_assoc_list($assoc_list);    
  }

  // Get data for $ids as an associated list from a single $source_id
  private function get_assoc_list_for_ids($ids, $source_id) {
    $result = array();
    $snapshot = $this->snapshot();
    $source_updated_at = $snapshot['sources'][$source_id];

    $cursor = $this->db->$source_id->
                find(['$and' => [['id' => ['$in' => $ids]],
                                 ['updated_at' => $source_updated_at]]])->
                sort(['row_num' => 1]);
    $id_field = $this->source_parameters[$source_id]['id_field'];
    foreach ($cursor as $id => $value) {
      $row = $value['row'];

      $result[$row[$id_field]] = $row;
    }
    return $result;
  }

  // Update $this->data with values in $assoc_list.
  //
  // Will overwrite previous values
  protected function update_data_with_assoc_list($assoc_list) {
    foreach($assoc_list as $id => $values) {
      if (!isset($this->data[$id]))
        $this->data[$id] = new DataRow();
      foreach($values as $field => $value) {
        $this->data[$id]->set($field, $value);
      }
    }    
  }

  // Get all ids present in the data.
  public function ids(){
    $this->retrieve_data();
    return array_keys($this->data);
  }

  // Get all rows in the data.
  public function rows(){
    $this->retrieve_data();
    return array_values($this->data);
  }

  // Get total row count.
  public function total_rows(){
    $this->retrieve_data();
    return count($this->data);    
  }

  // Get row corresponding to $id.
  public function row($id){
    $this->retrieve_data();
    return $this->data[$id];
  }

  // Used for generating the cache keys.
  // Tells us the timestamp for the data that we
  // are currently viewing.
  //
  // Use use the newest timestamp in the $this->snapshot()['sources']
  public function last_updated_at(){
    return max(array_values($this->snapshot()['sources']));
  }

  ////// Snapshot methods /////////

  public function snapshots() {
    $result = array();

    $cursor = $this->snapshots->find()->sort(['published_at' => -1]);
    foreach ($cursor as $id => $value) {
      $published_at = $value['published_at'] ? $value['published_at'] : 'preview';
      $result[$published_at] = $value;
    }
    return $result;
  }

  // Returns the snapshot for the current object.
  // (corresponds to $snapshot_version in the constructor)
  protected function snapshot() {
    if ($this->snapshot) {
      return $this->snapshot;
    } else {
      if ($this->snapshot_version == "preview") {
        $snapshot = $this->current_preview_snapshot();
      } else if ($this->snapshot_version == "current") {
        $snapshot = $this->current_snapshot();
      } else {
        $snapshot = $this->snapshots->findOne(['published_at' => $this->snapshot_version]);
        if (!$snapshot) {
          throw new Exception("No snapshot found with version ".$this->snapshot_version."\n");
        }
      }
      return $snapshot;      
    }
  }

  // This gives us the snapshot that is currently being published.
  public function current_snapshot() {
    return $this->snapshots->findOne(["current" => 1]);
  }

  // Synthesize and return the current_preview from new_sources in the staging directory
  // and current sources in the current_snapshot.
  //
  // If there is not preview snapshot, then returns the current_snapshot.
  // (preview snapshots are created when new files are uploaded into MongoDB and
  //  deleted when the preview is published.)
  //
  // This is used to generate preview data.
  public function current_preview_snapshot() {
    $preview_snapshot = $this->preview_snapshot();
    $current_snapshot = $this->current_snapshot();
    if ($preview_snapshot) {
      $new_sources = array_keys($preview_snapshot['sources']);
      $all_sources = array_keys($this->source_parameters);
      foreach(array_diff($all_sources, $new_sources) as $source_only_in_current) {
        $preview_snapshot['sources'][$source_only_in_current] = $current_snapshot['sources'][$source_only_in_current];
      }
      return $preview_snapshot;
    } else {
      return $current_snapshot;
    }
  }

  // Returns the preview_snapshot as is.
  // If the preview snapshot does not yet include links to all the 
  // sources, then this snapshot will be incomplete.
  //
  // To supplant this snapshot with the links in the current snapshot, 
  // use $this->current_preview_snapshot()
  public function preview_snapshot() {
    return $this->snapshots->findOne(["published_at" => null]);
  }

  ///////// Data upload methods /////////

  public function sources() {
    return array_keys($this->source_parameters);
  }

  public function drop_database() {
    $this->db->drop();
  }

  public function staging_directory() {
    return $this->staging_directory;
  }

  // Convert the row (an array of values) that we get out of CSV parsing
  // functions into a associated list with the field names as keys.
  //
  // We also trim each value removing spaces from the begining and end.
  protected function convert_row_to_assoc_list($row, $field_names) {
    $result = array();
    for ($i = 0; $i < count($field_names); $i++) {
      $value = isset($row[$i]) ? $row[$i] : null;
      $result[$field_names[$i]] = $this->trim($value);
    }
    return $result;
  }

  // This returns the new sources files (as the corresponding source_ids) 
  // in the staging_directory.
  // This only return sources that are registered in config.php.
  //
  // We use this to get a list of sources to upload.
  protected function new_sources() {
    $source_files = $this->source_files();
    $result = array();
    foreach(scandir($this->staging_directory) as $file) {
      if (in_array($file, array_keys($source_files))) {
        array_push($result, $source_files[$file]);
      }
    }
    return $result;
  }

  // This returns the an associated list which takes
  // filenames as keys and the $source_id as the value.
  //
  // We use this to identify which source information we
  // should use to process each file we find in the 
  // staging directory.
  protected function source_files() {
    $result = array();
    foreach ($this->source_parameters as $source_id => $value) {
      $result[$value['filename']] = $source_id;
    }
    return $result;
  }

  // This searches the contents of the staging_directory
  // and uploads any new files using $this->upload_source($source_id).
  public function load_new_sources() {
    $message = "";
    foreach ($this->new_sources() as $source_id) {
      $message .= $this->upload_source($source_id);
    }
    $this->delete_all_from_staging_directory();
    return $message;
  }

  // The preview snapshot is a special snapshot in
  // that it doesn't contain links to all the files in
  // config.php. Instead, if there are any files
  // that are missing, it will refer to the currently
  // published snapshot and use those links.
  //
  // Publishing a snapshot consists of setting the 
  // `current` flag to 1, and updating the `published_at` timestamp.
  public function publish_preview_snapshot($comment) {
    // We have to cache $current_preview_snapshot because
    // it will change during the operation.
    $current_preview_snapshot = $this->current_preview_snapshot();
    if ($current_preview_snapshot != $this->current_snapshot()) {
      $this->snapshots->update(["current" => 1],
                               ['$set' => ["current" => 0]]);
      $this->snapshots->update(["published_at" => null],
                               ['$set' => ["published_at" => time(),
                                           'current' => 1,
                                           'comment' => $comment,
                                           'sources' => $current_preview_snapshot['sources']]]);
    }    
  }

  public function publish_snapshot($published_at) {
    $snapshot_to_publish = $this->snapshots->findOne(["published_at" => (int)$published_at]);
    if ($snapshot_to_publish &&
        $this->current_snapshot() != $snapshot_to_publish) {
      $this->snapshots->update(["current" => 1],
                               ['$set' => ["current" => 0]]);
      $this->snapshots->update(["published_at" => (int)$published_at],
                               ['$set' => ["current" => 1]]);
    }
  }

  public function delete_all_from_staging_directory() {
    $staging_directory = $this->staging_directory();
    array_map('unlink', glob($this->staging_directory()."/*"));
  }

  // Clean up non-numeric characters from a string and
  // coerce the result into a float.
  //
  // Note that these to_numeric fields should not generally
  // be used for exact equality comparisions, because they
  // are floats. Use them to compare values.
  protected function to_numeric($string) {
    $clean_string = preg_replace("/[^0-9^\.]/", "", $string);
    return (float)$clean_string;
  }

  // This uploads the $source_id that was found in the staging_directory.
  // The preview snapshot will be automatically updated to include a link
  // to the newly updated source.
  //
  // The `updated_at` and `id` combination must be unique. Hence
  // if a company does not use a unique catalog_number (different SKUs
  // share the same catalog_number), then we can't use the catalog_number
  // as the `id`. In that case, modify the original excel file to
  // contain a unique id and use that.
  protected function upload_source($source_id) {
    $benchstart = microtime(true);
    $message = "";

    if (array_search($source_id, $this->sources()) !== false) {
      $source_config = $this->source_parameters[$source_id];
      $source_path = $this->staging_directory.$source_config['filename'];
      $encoding = $source_config['encoding'];
      $delimiter = isset($source_config['delimiter']) ? $source_config['delimiter'] : ",";
      $updated_at = filemtime($source_path);
      
      $preview_snapshot = $this->snapshots->findOne(["published_at" => null]);
      if ($preview_snapshot &&
          isset($preview_snapshot['sources'][$source_id]) && 
          $preview_snapshot['sources'][$source_id] == $updated_at ) {
          $message .= "$source_id updated at $updated_at is already loaded";
      } else {
        $line_counter = 0;
        $batch_counter = 0;
        $batch_size = 1000;
        $batch = array();
        $numeric_fields = isset($source_config["numeric_fields"]) ?
                            $source_config["numeric_fields"] :
                            array();

        $collection = $this->db->$source_id;
        $collection->ensureIndex(array('updated_at' => 1, 'id' => 1), array('unique' => true));
        $collection->ensureIndex(array('row_num' => 1));

        $this->each_line_from_file($source_path, $encoding, function($line)
                              use ($delimiter, $updated_at, $source_config, 
                                   $collection, &$batch, &$batch_counter, 
                                   $batch_size, &$line_counter, $numeric_fields) {
          $row = str_getcsv($line, $delimiter);          
          $row_as_assoc_list = $this->convert_row_to_assoc_list($row, $source_config['fields']);
          foreach ($numeric_fields as $numeric_field) {
            $row_as_assoc_list[$numeric_field] = $this->to_numeric($row_as_assoc_list[$numeric_field]);
          }
          $document = array('updated_at' => $updated_at, 
                            'id' => $row_as_assoc_list[$source_config['id_field']],
                            'row_num' => $line_counter,
                            'row' => $row_as_assoc_list);

          array_push($batch, $document);
          if (($batch_counter + 1) % $batch_size == 0) {
            $collection->batchInsert($batch);
            $batch = array();
          }
          $batch_counter = $batch_counter + 1;
          $line_counter = $line_counter + 1;
        });
        if (count($batch)) {
          $collection->batchInsert($batch); // insert leftovers        
        }        
      }

      // $preview_snapshot = $this->snapshots->findoOne(["published_at" => null]);
      // $sources
      $this->snapshots->update(["published_at" => null], 
                               ['$set' =>["published_at" => null, 
                                          "sources.$source_id" => $updated_at]],
                               ['upsert' => true]);

      return "$line_counter 行をデータベースにアップロードしました ".(microtime(true) - $benchstart)." 秒.<br>";
    } else {
      return "Error: $source_id が config.php で設定されていません";
    }
  }

  // Read in each line with encoding conversion. Send
  // the line to the $callback function.
  //
  // If the return value of the $callback is false (===), then 
  // break stop processing the lines.
  protected function each_line_from_file($source, $encoding, $callback) {
    $iconv_path = $GLOBALS["iconv_path"];
    if (!$iconv_path) {
      die ('$iconv_path is not set in config.php');
    }

    error_log("$iconv_path --from-code $encoding --to-code UTF-8 $source");

    // Instead of pulling in all the lines from the grep result, we process
    // line-by-line. This is because if we have a huge number of lines, we can
    // easily overwhelm PHP's memory limit.
    $handle = popen("$iconv_path --from-code $encoding --to-code UTF-8//IGNORE//TRANSLIT $source", "r");
    while (($line = fgets($handle)) !== false) {
      if ($callback($line) === false) {
        break;
      };
    }
    fclose($handle);
  }

  ///// Utility function ///////

  // Use a custom character mask which takes care of
  // all UTF-8 whitespace characters.
  // http://stackoverflow.com/questions/4166896/trim-unicode-whitespace-in-php-5-2
  // http://php.net/manual/en/regexp.reference.unicode.php
  protected function trim($string) {
    return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u','',$string);
  }

  /////////////////////////////////////////////////
  // Functions for getting facet information
  /////////////////////////////////////////////////

  // Set the labels of the fields that will be returned in 
  // the results from $this->facets().
  public function set_facet_fields($fields) {
    $this->facet_fields = $fields;
  }

  // This returns the count of values for each $field in $fields.
  //
  // The returned value is
  // array('field_name_1' => array('value_1_1' => [count for value_1_1 in field_name_1],
  //                               'value_1_2' => [count for value_1_2 in field_name_1]...),
  //       'field_name_2' => array('value_2_1' => [count for value_2_1 in field_name_2],
  //                               'value_2_2' => [count for value_2_2 in field_name_2]...))
  //
  // Since it used $this->data as the data source, the
  // facets are sorted in the same order as they would be 
  // displayed.
  public function facets() {
  	if (isset($this->facets)) {
  		return $this->facets;
  	} else {
      if ($this->maximum_results_was_reached()) {
        // If $this->maximum_results_was_reached(),
        // then it's no use showing facets anyway.
        return array();
      } else {
        $this->retrieve_facets();
        $this->sort_facets();
        return $this->facets;     
      }
  	}
  }

  // Only show facets if we have rows to display and
  // we have not terminated due to reaching the maximum limit.
  public function should_show_facets() {
    return $this->total_rows() && !$this->maximum_results_was_reached();
  }

  // Get the raw facet data. Not sorted or cached.
  // Takes less than 30ms on reactivity:human, so it's OK to do the loops in PHP
  public function retrieve_facets() {
		$this->retrieve_data();

    $start_time = microtime(TRUE);
  	$fields = $this->facet_fields;
  	$result = array();
  	// Initialize $results array
  	foreach($fields as $field) {
  		$result[$field] = array();
  	}
  	// Count facets
  	foreach ($this->data as $row) {
  		foreach($fields as $field) {
  			$value = $row->get($field);
        if (!$value)
          continue;

  			if (!isset($result[$field][$value])) {
  				$result[$field][$value] = 0;
  			}
  			$result[$field][$value]++;
  		}
  	}

    $end_time = microtime(TRUE);
    error_log("BENCHMARK retrieve_facets(calculate facets from data): ".($end_time - $start_time));

  	$this->facets = $result;
  	return $this->facets;  	
  }

  // Sort facets.
  // If a field has been set in $this->field_values(),
  // then that order will be used. Otherwise
  // we will not resort (hence facets will be returned in order of the results)
  public function sort_facets() {
    $result = $this->facets;
    $fields = $this->facet_fields;
    foreach ($fields as $field) {
      if ($this->field_values($field)) {
        $results_for_field = $result[$field];
        uksort($results_for_field, function($a, $b) use ($field) {
          return $this->cmp_in_array($a, $b, $this->field_values($field));
        });
        $result[$field] = $results_for_field;
      }
    }
    $this->facets = $result;
    return $this->facets;
  }



  //////// Functions for table display /////////

	// Add $field."_rowspan" keys to $data_source to allow
	// the table to be displayed using rowspans for 
	// repetitive cells. If $field."_rowspan" is "-1"
	// then that <td> will not be drawn. Otherwise,
	// the <td> will have a "colspan" of $field."_rowspan".
	//
	// This compares a field value to that which directly
	// precedes it, and if they are identical, then it
	// tags it for a rowspan.
	//
	// Only the fields which are included in "rowspanable" in the
	// source_parameters will be rowspaned.
	public function add_rowspans() {
	  $previous_row = array();
	  $previous_id = null;
	  // Hash that stores the id of the span start row.
	  // $span_start_id[field_name] contains the row id.
	  $span_start_id = array(); 
	  foreach($this->ids() as $id) {
	    $row = $this->row($id);
	    foreach($row->fields() as $field) {
	    	if (!in_array($field, $this->rowspanable()))
	    		continue;
	      if ($previous_row &&
	          $previous_row->get($field) == $row->get($field)) {
	        // If this is the second row (the first time we need to set span)
	        if (!isset($span_start_id[$field]) || !$span_start_id[$field]){
	          $span_start_id[$field] = $previous_id;
	          $this->row($span_start_id[$field])->set($field."_rowspan", 1);
	        }
	        $this->row($span_start_id[$field])->increment($field."_rowspan");
	        // Setting $field."_rowspan" to -1 tells the view helper that the <td>
	        // for this cell should not be drawn.
	        $this->row($id)->set($field."_rowspan", -1);
	      } else {
	        $span_start_id[$field] = null;
	      }
	    }
	    $previous_row = $row;
	    $previous_id = $id;
	  }
	  return $this;
	}

	// Check whether this column should have cells
	// which span rows. This is set in the $source_parameters in config.php.
	public function rowspanable() {
		if (isset($this->rowspanable)) {
			return $this->rowspanable;			
		} else {
			$result = array();
			foreach($this->source_parameters as $key => $value) {
				if (isset($value['rowspanable'])) {
					$result = array_merge($result, $value['rowspanable']);
				}
			}
			return $this->rowspanable = $result;			
		}
	}


  ////////////////////////////////////////////////
  // Functions for pagination
  ////////////////////////////////////////////////
  public function set_maximum_results($maximum_results) {
    $this->maximum_results = $maximum_results;
  }

  public function maximum_results() {
    return $this->maximum_results;
  }

  public function maximum_results_was_reached() {
    return $this->over_limit;
  }



	/////////////////////////////////////////////////
	// Functions for sorting
	/////////////////////////////////////////////////

	// The PHP strcasecmp function return value is not
	// limited to -1, 0, 1, which makes it difficult to
	// use when we have multiple sort criteria.
	// strcasecmp_norm only returns -1, 0, 1.
	protected function strcasecmp_norm($a, $b) {
	  return $this->cmp_norm(strcasecmp($a, $b));
	}

	// Compares $a and $b based on their indices in
	// $array. If they are not present, then they will 
	// be sent to the end of the array.
	protected function cmp_in_array($a, $b, $array) {
	  $a_pos = array_search($a, $array);
	  $b_pos = array_search($b, $array);
    if ($a_pos === false)
      $a_pos = 9999999;
    if ($b_pos === false)
      $b_pos = 9999999;
    return $this->cmp_norm($a_pos - $b_pos);
	}

	// Converts signed integer to -1, 0, 1
	protected function cmp_norm($cmp) {
	  if ($cmp > 0) {
	    return 1;
	  } else if ($cmp < 0) {
	    return -1;
	  } else {
	    return 0;
	  }    
	}

}

