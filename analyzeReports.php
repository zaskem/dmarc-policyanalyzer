<?php
  $startTime = microtime(true);

  // Editable variables
  $collectedDataPath = __DIR__;
  $rawFailureOutputDataset = $collectedDataPath . '/rawfailuredata.csv';
  // END Editable variables
  
  // Define variables
  $failureAnalysisArray = array(array(
    'org_name',
    'begin_timestamp',
    'end_timestamp',
    'end_datetime',
    'end_year',
    'end_month',
    'end_day',
    'domain_policy',
    'p',
    'sp',
    'source_ip',
    'count',
    'disposition',
    'header_from'
  ));
  $reportArray = array();
  $summaryArray = array();

  /**
   * recursiveGlobXMLToArray($dir, $ext = 'xml')
   * 
   * Recursively load files (with matching $ext) at $dir to PHP array
   * 
   * @args $dir : [string] root directory path
   * @args $ext (default 'xml') : [string] match files with this extension
   * 
   * Appends multdimensional array of XML data to the global $reportArray variable.
   */
  function recursiveGlobXMLToArray($dir, $ext = 'xml') {
    global $reportArray;
    $globFiles = glob("$dir/*.$ext");
    $globDirs  = glob("$dir/*", GLOB_ONLYDIR);

    foreach ($globDirs as $dir) {
      recursiveGlobXMLToArray($dir);
    }

    foreach ($globFiles as $file) {
      $reportName = explode($ext, basename($file))[0];
      $reportArray[$file] = json_decode(json_encode(simplexml_load_file($file)), true);
    }
  }

  // Recursively load raw XML data for processing
  recursiveGlobXMLToArray($collectedDataPath);
  print "Total Report Files Scanned: " . count($reportArray) . "\n";

  // Walk the array for dkim fail and spf fail records
  foreach ($reportArray as $reportFile => $reportDetail) {
    if (!isset($reportDetail['record']['row'])) { // Multiple "records" per report have a slightly different structure
      foreach ($reportDetail['record'] as $reportRecord) {
        if (('fail' == strtolower($reportRecord['row']['policy_evaluated']['dkim'])) && ('fail' == strtolower($reportRecord['row']['policy_evaluated']['spf']))) {
          $summaryArray[$reportDetail['report_metadata']['org_name'].'-'.$reportDetail['report_metadata']['date_range']['end']] = array(
            'end'=>$reportDetail['report_metadata']['date_range']['end'],
            'source_ip'=>$reportRecord['row']['source_ip'],
            'count'=>$reportRecord['row']['count'],
            'header_from'=>$reportRecord['identifiers']['header_from'],
            'filename'=>basename($reportFile)
          );
          $failureAnalysisArray[] = array(
            $reportDetail['report_metadata']['org_name'],
            $reportDetail['report_metadata']['date_range']['begin'],
            $reportDetail['report_metadata']['date_range']['end'],
            date('Y-m-d h:m:s', $reportDetail['report_metadata']['date_range']['end']),
            date('Y', $reportDetail['report_metadata']['date_range']['end']),
            date('m', $reportDetail['report_metadata']['date_range']['end']),
            date('d', $reportDetail['report_metadata']['date_range']['end']),
            $reportDetail['policy_published']['domain'],
            $reportDetail['policy_published']['p'],
            $reportDetail['policy_published']['sp'],
            $reportRecord['row']['source_ip'],
            $reportRecord['row']['count'],
            $reportRecord['row']['policy_evaluated']['disposition'],
            $reportRecord['identifiers']['header_from']
          );
        }
      }
    } else { // Single "record" per report
      if (('fail' == strtolower($reportDetail['record']['row']['policy_evaluated']['dkim'])) && ('fail' == strtolower($reportDetail['record']['row']['policy_evaluated']['spf']))) {
        $summaryArray[$reportDetail['report_metadata']['org_name'].'-'.$reportDetail['report_metadata']['date_range']['end']] = array(
          'end'=>$reportDetail['report_metadata']['date_range']['end'],
          'source_ip'=>$reportDetail['record']['row']['source_ip'],
          'count'=>$reportDetail['record']['row']['count'],
          'header_from'=>$reportDetail['record']['identifiers']['header_from'],
          'filename'=>basename($reportFile)
        );
        $failureAnalysisArray[] = array(
          $reportDetail['report_metadata']['org_name'],
          $reportDetail['report_metadata']['date_range']['begin'],
          $reportDetail['report_metadata']['date_range']['end'],
          date('Y-m-d h:m:s', $reportDetail['report_metadata']['date_range']['end']),
          date('Y', $reportDetail['report_metadata']['date_range']['end']),
          date('m', $reportDetail['report_metadata']['date_range']['end']),
          date('d', $reportDetail['report_metadata']['date_range']['end']),
          $reportDetail['policy_published']['domain'],
          $reportDetail['policy_published']['p'],
          $reportDetail['policy_published']['sp'],
          $reportDetail['record']['row']['source_ip'],
          $reportDetail['record']['row']['count'],
          $reportDetail['record']['row']['policy_evaluated']['disposition'],
          $reportDetail['record']['identifiers']['header_from']
        );
      }
    }
  }

  print "Distinct Reports with 'fail/fail' Records: " . count($summaryArray) . "\n";
  print "Total number of failures: " . array_sum(array_column($summaryArray, 'count')) . "\n\n";

  // Sort the summary array to output easier to read console results
  asort($summaryArray);
  foreach ($summaryArray as $summaryReport => $summaryData) {
    print date('Y-m-d h:m:s', $summaryData['end']) . " : $summaryData[count] failures from source IP $summaryData[source_ip] as $summaryData[header_from] (see $summaryData[filename])\n";
  }

  // Write failure data to file
  $fp = fopen($rawFailureOutputDataset, 'w');
  foreach ($failureAnalysisArray as $row) {
    fputcsv($fp, $row);
  }
  fclose($fp);

  print "\nFailure analysis dataset created at $rawFailureOutputDataset\n";

  $endTime = microtime(true);
  die("\nAnalysis completed in " . round(($endTime - $startTime), 4) . " seconds.\n");
?>