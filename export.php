<?php
require_once 'classes.php';
$tableData = isset($_POST['tableData']) ? json_decode($_POST['tableData'], true) : [];

function deleteDirectoryContents($dir) {
    if (!is_dir($dir)) {
        echo "A megadott elérési út nem mappa!";
        return;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_file($filePath)) {
            unlink($filePath);
        } elseif (is_dir($filePath)) {
            deleteDirectoryContents($filePath);
            rmdir($filePath);
        }
    }
}

$fileName = "export_" . date('Y-m-d_H-i-s') . ".csv";
$fileDirectory = "downloads/";
deleteDirectoryContents(__DIR__ . '/' . $fileDirectory);
$filePath = __DIR__ . '/' . $fileDirectory . $fileName;
if (!is_dir(__DIR__ . '/' . $fileDirectory)) {
    mkdir(__DIR__ . '/' . $fileDirectory, 0777, true);
}

$csvContent = "\xEF\xBB\xBF";
$csvContent .= "Mérés időpontja;Konzerv id-ja;Hőmérséklet\n";
    
foreach ($tableData as $rowIndex => $row) {
    $sor = ""; 
    foreach ($row as $cellIndex => $cell) {
        $sor .= "{$cell},";
    }
    $csvContent .= rtrim($sor, ',') . "\n";
}

file_put_contents($filePath, $csvContent);
                    
$downloadLink = $fileDirectory . $fileName;
echo 'A fájl elkészült: <a href="' . $downloadLink . '" download>Letöltés</a>';
?>
