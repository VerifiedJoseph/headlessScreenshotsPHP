<?php
/*
	headlessScreenshots is a PHP command line script for taking screenshots with headless Google chrome.

	Created: August 02, 2018
	Modifed: August 09, 2018
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

try {
	
	if (php_sapi_name() != 'cli') {
		throw new Exception("headlessScreenshots.php must be run via the command line.");
	}

	// Load CSV file
	$reader = Reader::createFromPath($csv_file, 'r');
	
	// Set header offset
	$reader->setHeaderOffset(0);

	// Get Records
	$records = $reader->getRecords();
	
	$climate->out("Screenshotting... " . count($reader) . " websites");

	// Loop through each url.
	foreach ($records as $index => $row) {
		$result = array();
		$error_message = "";
		
		$url = trim($row['website']);
		
		// Get domain name from URL
		if (preg_match('/https?:\/\/([a-zA-Z0-9-.]{2,256}\.[a-z]{2,20}(\:[0-9]{2,4})?)/', $url, $matches)) {

			$output_file = $screenshots_folder . DIRECTORY_SEPARATOR . str_replace(".", "-", $matches[1])  . '.png';
		
		} else { // Failed to get domain name, use index number 
		
			$output_file = $screenshots_folder .  DIRECTORY_SEPARATOR . 'image' . $index . '.png';
			
		}
		
		// Skip website if screenshot already exists
		if (file_exists($output_file)) {
			
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
	
	if (count($results) > 0) {
	
		// Output $results as a table.
		$climate->table($results);
		
	}
	
	
	// Save results to disk.
	$writer = Writer::createFromPath($csv_results_file, 'w+');
	
	// Insert header
	$writer->insertOne(['url', 'file', 'status', 'note']);
	
	// Insert rows
	$writer->insertAll($results);
	
} catch (Exception $e) {
	$climate->out($e->getMessage());
}