<?php

error_reporting(E_ALL & ~8192);
ini_set('display_errors', 1);

require_once "cli_core.php";

if ($argc < 2 || $argc > 3) {
    fprintf(STDERR, "Usage: php cli_script.php <file.txt> <validateSpecialChars (optional, default: false)>\n");
    exit(1);
}

/**
 * Reads lines from a file and yields each line as a generator.
 *
 * @param string $filename The path to the file to be read.
 * @return Generator Yields each line of the file as a string.
 * @throws Exception If the file does not exist or cannot be opened.
 */
function getHostnameFromFile(string $filename): Generator
{
    // Verify that the file exists
    if (!file_exists($filename)) {
        throw new Exception("File does not exist: {$filename}");
    }

    $inputFile = fopen($filename, 'r');
    if (!$inputFile) {
        throw new Exception("Unable to open input file: {$inputFile}");
    }

    try {
        while (($hostname = fgets($inputFile)) !== false) {
            yield $hostname;
        }
    } finally {
        fclose($inputFile);
    }
}

$domain_cache = [];
$total_rows = 0;

try {
    $validator = new DomainValidator(isset($argv[2]));

    $outputFile = fopen(OUTPUT_FILE, 'w');
    if (!$outputFile) {
        throw new Exception('Could not open file for writing.');
    }
    // Iterate over each hostname retrieved from the input file
    foreach (getHostnameFromFile($argv[1]) as $hostname) {
        $processedData = $validator->execute($hostname);

        $total_rows++;

        $name = $processedData['name'];
        $valid = $processedData['valid'] ? 'true' : 'false';

        if ($valid)
            if (!isset($domain_cache[$name])) {
                $domain_cache[$name] = "";
                fwrite(
                    $outputFile, $name."\n"
                );
            }

        if ($total_rows%100000==0){
            echo $total_rows."\n";
        }
    }
    fclose($outputFile);
} catch (Exception $e) {
    fprintf(STDERR, "Error: %s\n", $e->getMessage());
    exit(1);
}