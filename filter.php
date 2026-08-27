<?php
include_once "classes.php";

$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';
$jarIds = isset($_POST['jarIds']) ? json_decode($_POST['jarIds'], true) : [];
$minTemp = isset($_POST['minTemp']) ? $_POST['minTemp'] : null;
$maxTemp = isset($_POST['maxTemp']) ? $_POST['maxTemp'] : null;


$filteredData = $app->filter($startDate, $endDate, $jarIds, $minTemp, $maxTemp);
foreach ($filteredData as $key => $measurement) {
    echo "<tr>
            <td>$measurement->dateTime</td>
            <td>$measurement->jarId</td>
            <td>$measurement->temperature</td>
        </tr>";
}
?>
