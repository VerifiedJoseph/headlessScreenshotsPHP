<?php
/*
	headlessScreenshots is a PHP command line script for taking screenshots with headless Google chrome.

	Created: August 02, 2018
	Modifed: August 15, 2018
*/

// Libraries loaded via composer
require __DIR__ . '/vendor/autoload.php';

use League\Csv\Reader;
use League\Csv\Writer;
use mikehaertl\shellcommand\Command;

$climate = new League\CLImate\CLImate;

// Location of url list file
$csv_file = __DIR__ . DIRECTORY_SEPARATOR . 'urls.csv';

// Location of results file
$csv_results_file = __DIR__ . DIRECTORY_SEPARATOR  . 'results.csv';

// Location of screenshots folder
$screenshots_folder = __DIR__ .  DIRECTORY_SEPARATOR . 'screenshots';

// Location of Google Chrome executable or environment variable (eg. chrome)
$chrome_path = "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe";

// Array of check results
$results = array();

// Results Table
$create_results_table = true;

// Command line options
$long_options = array(
	"disable_table", // Disable output table creation
	"urls:", // Use a custom urls.csv file
	"results:", // Use a custom results.csv file
	"screenshots_folder:" // Use a custom screenshots save folder (folder must already exist)
);

$short_options = ''; 

// Set and get options
$cli_options = getopt($short_options, $long_options);

try {
	
	if (php_sapi_name() != 'cli') {
		throw new Exception("headlessScreenshots.php must be run via the command line.");
	}

	// Check for custom urls.csv file
	if (isset($cli_options['urls']) && $cli_options['urls'] != false) {
		$climate->out("urls csv file: " . $cli_options['urls']);
		$csv_file = $cli_options['urls'];
	}
	
	// Check for custom results.csv file
	if (isset($cli_options['results']) && $cli_options['results'] != false) {
		$climate->out("results csv file: " . $cli_options['results']);
		$csv_results_file = $cli_options['results'];
	}
	
	// Check for disable_table option
	if (isset($cli_options['disable_table'])) {
		$climate->out("Disabled output table creation");
		$create_results_table = false;
	}
	
	// Load CSV file
	$reader = Reader::createFromPath($csv_file, 'r');
	
	// Set header offset
	$reader->setHeaderOffset(0);

	// Get Records
	$records = $reader->getRecords();
	
	$climate->out("Creating " . count($reader) . " screenshots");

	// Loop through each url.
	foreach ($records as $index => $row) {
		$result = array();
		$error_message = "";
		
		$url = trim($row['url']);
	
		if (empty($url)) {
			$climate->out("No URL given in csv row " . $index);
			continue;	
		}
		
		// Get domain name from URL
		if (preg_match('/https?:\/\/([a-zA-Z0-9-.]{2,256}\.[a-z]{2,20}(\:[0-9]{2,4})?)/', $url, $matches)) {

			$output_file = $screenshots_folder . DIRECTORY_SEPARATOR . str_replace(".", "-", $matches[1])  . '.png';
		
		} else { // Failed to get domain name, use index number
		
			$output_file = $screenshots_folder .  DIRECTORY_SEPARATOR . 'image' . $index . '.png';
			
		}
		
		// Skip website if screenshot already exists
		if (file_exists($output_file)) {
			
			$climate->out("Screenshot already exists: " . $output_file);
			continue;
			
		}
		
		// Create command
		$command = new Command($chrome_path);
		
		//$command->addArg('--enable-logging');
		
		// Set headless chrome argument
		$command->addArg('--headless');
		
		// Set disable GPU argument (Currently needed if running on Windows)
		$command->addArg('--disable-gpu');
		
		// Set screenshot argument with output file location value
		$command->addArg('--screenshot=', $output_file);
		
		// Set hide scrollbars argument
		$command->addArg('--hide-scrollbars');
		
		// Set window size (1920x1080) argument
		$command->addArg('--window-size=', '1920,1080');
		
		// Set page url
		$command->addArg($url);
		
		if ($command->execute()) {
    
			echo $url . "... \r";
			
			echo $command->getOutput();

			if (file_exists($output_file)) {
			
				$result = array (
					'url' => $url,
					'file' => $output_file,
					'status' => "Success",
					'note' => "",
				);	
			
			} else {
			
				$result = array (
					'url' => $url,
					'file' => $output_file,
					'status' => "Failed",
					'note' => "File does not exist",
				);	
			
			}
			
			$climate->out($index . " " . $url . " | " . $result['status'] . " | " . $result['file'] . " | " . $result['note']);
			
		} else {
    		
			echo $command->getError();
			$exitCode = $command->getExitCode();
		
		}
		
		$results[] = $result;
	}	
	
	if ($create_results_table === true) {
	
		if (count($results) > 0) {
		
			// Output $results as a table.
			$climate->table($results);
		
		}
	
	}	
	
	try {
    	
		$writer = Writer::createFromPath($csv_results_file, 'w+');

		// Insert header
		$writer->insertOne(['url', 'file', 'status', 'note']);
	
		// Insert rows
		$writer->insertAll($results);
	
		$climate->out("Created results file: " . $csv_results_file);
		
	} catch (CannotInsertRecord $e) {
    	
		$climate->out("Failed insert record: " . $e->getRecords());
		$climate->out("Results file not created");
	
	}
	
} catch (Exception $e) {
	$climate->out($e->getMessage());
}