<?php
require_once(dirname(__FILE__).'/queried_data_source_base.php');

// QueriedDataSource implement a DataSource that allows us to 
// filter on the query target using a query that has the following syntax;
//
// array('field_name' => 'query_keyword', 'field_name' => 'query_keyword', ...)
//
// The filter will be an AND on each key=>value set.
// `value` must exactly match query (except for case: this class is case insensitive).
//
// To allow more flexible and powerful matching, you can set
// the $query_expanders global in `config.php`. This will allow
// you to use partial matches for the egrep phase and
// regular expressions for the `confirm_assoc_list_matches_query`
// phase. See the comments on `config.php` for details
class QueriedDataSource extends QueriedDataSourceBase {

  // Get the expanded query from the `$query_expanders`
  // global set in `config.php`.
  protected function get_expanded_query_in_key($key, $query) {
    $query_expanders = $GLOBALS["query_expanders"];
    $key = strtolower($key);
    $query = strtolower($query);
    if (array_key_exists($key, $query_expanders) && 
        array_key_exists($query, $query_expanders[$key]) && 
        $query_expanders[$key][$query][0]) {
        return $query_expanders[$key][$query];
    } else {
        return array($query, $query);
    }
  }

  // Takes an array of tokens and generates
  // a regular expression string that will
  // to an AND match.
  private function partial_match_regex($tokens) {
    $regexp = "";
    foreach($tokens as $token) {
        if (strlen($token) > 0) {
            $regexp .= "(?=.*".preg_quote($token).")";
        }
    }
    return $regexp;
  }

  // Read the CSV file and collect all
  // rows that match the egrep regex for the $ids.
  //
  // For performance, we only check for the presence of the query values
  // and we don't check for exact matches.
  // We have to do a double check, which is easier after the
  // assoc_list is generated.
  protected function get_csv_rows_for_query($source, $encoding){
    if (!$this->query)
      return array();
    $result = array();

    // Prepare the regex for grep matching
    $partial_match_regexes = array();
    $partial_match_field_names = array_keys($this->partial_match_fields);
    $regexp = "";

    foreach(array_keys($this->query) as $key) {
        if (in_array($key, $partial_match_field_names)) {
            $regexp = $this->partial_match_regex($this->partial_match_fields[$key]);
        } else {
            $query = $this->query;
            $expanded_query = $this->get_expanded_query_in_key($key, $query[$key]);
            $regexp .= ".*".preg_quote($expanded_query[0]);            
        }
    }

    $lines = array();
    // Quickly filter the file with 'egrep'.
    // There are several ways to use this.
    // One option is to use egrep with different encodings by
    // using something like "LANG=SJIS egrep". The grep in Mavericks (grep (BSD grep) 2.5.1-FreeBSD)
    // apparently works well with this, but the grep on my CentOS server (grep (GNU grep) 2.5.1)
    // does not. Looking around the web, it seems that encoding support in grep is rather
    // spotty. For GNU grep, LANG affects how character groups are treated, but it does not
    // do character encoding.
    //
    // This means that to support GNU grep, we have to convert encoding to UTF-8 prior to
    // feeding to egrep. UTF-8 works fine with GNU egrep, probably due to the design
    // of UTF-8 coding.
    //
    // We can use NKF or iconv for this. After a quick benckmark,
    // iconv seems to be an order of magnitude faster than NKF. In fact
    // piping iconv output to GNU egrep seems to be as fast as FreeBSD egrep.
    //
    // There are also other differences between FreeBSD grep and GNU grep.
    // FreeBSD egrep seems to have more features (available syntaxes) than
    // GNU egrep. On the other hand, FreeBSD grep does not have pcre.
    // Therefore, we should keep the regex syntax rather simple.
    // There is a good listing of available regex syntaxes here (http://www.kt.rim.or.jp/~kbk/regex/regex.html#EGREP).
    //
    // Another note.
    // We are currently using `"` for the quotes in the shell code.
    // This is because we have "F(ab')2" type stuff. We should
    // add code to escape quotes.
    // exec("LANG=".$encoding." egrep \"(?:$regexp)\" $source", $lines);
    // error_log("LANG=".$encoding." egrep \"(?:$regexp)\" $source");
    //
    // Update:
    //
    // I found that perl is actually much, much faster than egrep.
    // Instead of 'egrep \"$regexp\"', the following is much faster;
    // perl -n -e 'print $_ if ($_ =~ /$regexp/)'
    //
    // It's at least twice as fast.
    //
    // We've also tried to do the decoding within perl like
    // perl -e 'use Encode; use utf8; while(<>){my $s = Encode::decode("sjis", $_);print Encode::encode("utf-8", $s) if ($s =~ /Jackson.*Bovine（ウシ）.*Whole IgG/u)}
    // but this is quite slow. It's twice as fast to do 
    // 
    // /opt/local/bin/iconv --from-code SJIS --to-code UTF-8 /Applications/MAMP/htdocs/iwai-chem_15ff4e_ddh/ddh/../data/jackson100.csv | perl -n -e 'use encoding "utf8"; print $_ if ($_ =~ /Jackson.*Bovine（ウシ）.*Whole IgG/)'
    //
    // There might be some problems with encoding and regular expressions, but
    // we'll address them as necessary.
    //
    // Update on Update:
    // As I wrote on data_source.php, egrep seems to be faster than perl on Linux.
    // Hence I reverted to egrep.
    //
    // Update on Update on Update (2014/08/27)
    // To enable partial_matching, we use postive lookahead `(?=.*antibody)`. This
    // requires us to use Perl regexes which the Mac version of egrep does not support.
    // Therefore, we install GNUgrep from MacPorts and will use that.

    $iconv_path = $GLOBALS["iconv_path"];
    if (!$iconv_path) {
      die ('$iconv_path is not set in config.php');
    }

    $gnugrep_path = $GLOBALS["gnugrep_path"];
    if (!$gnugrep_path) {
      die ('$gnugrep_path is not set in config.php');
    }
    
    // $perl_command = 'use encoding "utf8"; print $_ if ($_ =~ /'.$regexp.'/)';
    // $escaped_perl_command = escapeshellarg($perl_command);
    // error_log("$iconv_path --from-code $encoding --to-code UTF-8 $source | perl -n -e $escaped_perl_command");
    // exec("$iconv_path --from-code $encoding --to-code UTF-8 $source | perl -n -e $escaped_perl_command", $lines);

    // If you are encountering errors with running programs from the command line,
    // add "2>&1" to the command to redirect standard error to the output.
    //
    // If you are encountering errors on MAMP, then try using
    // $iconv_path = "export DYLD_LIBRARY_PATH=/usr/lib/:$DYLD_LIBRARY_PATH;/usr/bin/iconv";
    // in the config file. What happens is MAMP sets DYLD_LIBRARY_PATH without /usr/lib
    // which can cause issues.
    $escaped_regexp = escapeshellarg($regexp);
    // error_log("$iconv_path --from-code $encoding --to-code UTF-8 $source | LANG_ALL=UTF-8 egrep -i $escaped_regexp");
    // exec("$iconv_path --from-code $encoding --to-code UTF-8 $source | LANG_ALL=UTF-8 egrep -i $escaped_regexp", $lines);

    error_log("$iconv_path --from-code $encoding --to-code UTF-8 $source | LANG_ALL=UTF-8 $gnugrep_path -i -P $escaped_regexp");
    exec("$iconv_path --from-code $encoding --to-code UTF-8 $source | LANG_ALL=UTF-8 $gnugrep_path -i -P $escaped_regexp", $lines);

    foreach ($lines as $line) {
      $row = str_getcsv($line);
      $result[$row[0]] = $row;
    }
    return $result;
  }

  protected function confirm_assoc_list_matches_query($assoc_list){
    $partial_match_regexes = array();
    $partial_match_field_names = array_keys($this->partial_match_fields);
    $regexp = "";
   
   foreach($this->query as $field => $value) {
        if (in_array($field, $partial_match_field_names)) {
            $regexp = $this->partial_match_regex($this->partial_match_fields[$field]);
            if (!preg_match("/$regexp/i", $assoc_list[$field])) {
                return false;
            }
        } else {
            $expanded_query = $this->get_expanded_query_in_key($field, $value);
            if (preg_match("/^\/.*\/$/", $expanded_query[1])) {
                if (!preg_match($expanded_query[1], strtolower($assoc_list[$field]))) {
                    // If the query is a regular expression
                    // and a field failed to match
                    return false;
                }
            } else if (strtolower($assoc_list[$field]) != strtolower($expanded_query[1])) {
                // If the query is not a regular expression
                // and a field failed to match
                return false;
            }            
        }

    }
    // If all fields matched
    return true;
  }

}