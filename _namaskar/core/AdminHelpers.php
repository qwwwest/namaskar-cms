<?php

namespace Qwwwest\Namaskar;

class AdminHelpers
{


    public static function csvDeleteLinesById($filename, $targetId)
    {
        $tempFile = $filename . '.tmp';

        // Open the original file for reading and a temporary file for writing
        $inputFile = fopen($filename, 'r');
        $outputFile = fopen($tempFile, 'w');

        if (!$inputFile || !$outputFile) {
            throw new \Exception("Error opening files.");
        }

        // Loop through each line of the CSV
        while (($row = fgetcsv($inputFile)) !== false) {
            // Write the row to the new file if the first column does not match the target ID
            if ($row[0] !== $targetId) {
                fputcsv($outputFile, $row);
            }
        }

        fclose($inputFile);
        fclose($outputFile);

        // Replace the original file with the updated file
        if (!rename($tempFile, $filename)) {
            throw new \Exception("Error replacing the original file.");
        }
    }

    public static function swapStatus($filename, $targetId)
    {
        $tempFile = $filename . '.tmp';

        // Open the original file for reading and a temporary file for writing
        $inputFile = fopen($filename, 'r');
        $outputFile = fopen($tempFile, 'w');

        if (!$inputFile || !$outputFile) {
            throw new \Exception("Error opening files.");
        }

        // Loop through each line of the CSV
        while (($row = fgetcsv($inputFile)) !== false) {
            // Write the row to the new file if the first column does not match the target ID
            if ($row[0] === $targetId) {
                $status = count($row) - 1;
                $row[$status] = $row[$status] === '1' ? '0' : '1';
            }
            fputcsv($outputFile, $row);
        }

        fclose($inputFile);
        fclose($outputFile);

        // Replace the original file with the updated file
        if (!rename($tempFile, $filename)) {
            throw new \Exception("Error replacing the original file.");
        }
    }


    public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public static function filesizeFormated($filename)
    {
        return AdminHelpers::formatSizeUnits(\filesize($filename));
    }


    public static function zipFolder($source, $destination)
    {
        // Ensure the source exists and is a directory
        if (!is_dir($source)) {
            throw new \Exception("Source folder does not exist or is not a directory.");
        }

        // Initialize a ZipArchive instance
        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Unable to create ZIP file: " . $destination);
        }

        $source = rtrim(realpath($source), '/'); // Normalize the source path

        // Add files and directories to the ZIP archive
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $relativePath = substr($filePath, strlen($source) + 1); // Relative path within the source folder

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Close the ZIP archive
        $zip->close();

        return true;
    }


    public static function downloadZipFile($filePath, $fileName)
    {
        // Check if the file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            echo "File not found.";
            exit;
        }

        // Set headers for file download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Pragma: no-cache');
        header('Expires: 0');

        // Read and output the file
        readfile($filePath);
        exit;
    }


    public static function fopenOrDie($filePath, $mode)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            die("File not found: $filePath");
        }

        if (!is_readable($filePath)) {
            die("File not not readable: $filePath");
        }
        // Open the file
        $file = fopen($filePath, $mode);
        if ($file === false) {
            die("Error opening the file$: $filePath");
        }
        return $file;

    }
    public static function csvCountLines($filePath)
    {

        // Open the CSV file
        $file = AdminHelpers::fopenOrDie($filePath, 'r');

        $rowCount = 0;

        // Skip the header row
        if (fgetcsv($file) !== false) {
            while (fgetcsv($file) !== false) {
                $rowCount++;
            }
        }

        fclose($file);

        return $rowCount;
    }


    public static function htmlGetBodyContent($html)
    {
        // Use a regex pattern to capture the content inside <body>...</body>
        $pattern = '/<body[^>]*>(.*?)<\/body>/is'; // Case-insensitive and dot matches newline

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1]; // Return the content inside <body>
        }

        return null; // Return null if no <body> tags found
    }

}