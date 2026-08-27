<?php
    include_once "classes.php";
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hőmérős feladat</title>
    <link rel="icon" type="image/x-icon" href="icon.ico">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css.css">
</head>
<body>
    <h1>HŐMÉRSÉKLET VIZSGÁLAT</h1>
    <div class="all">
        <div class="datas">
            <form action="" method="post" enctype="multipart/form-data">
                <label for="uploaded_file">Új adatok felvétele:</label>
                <input type="file" name="uploaded_file" id="uploaded_file" required>
                <button type="submit">Feltöltés</button>
            </form>
            <div class="filter">
                <form id="filterForm">
                    <label for="startDate">Kezdő dátum:</label>
                    <input type="date" id="startDate" name="startDate">

                    <label for="endDate">Befejező dátum:</label>
                    <input type="date" id="endDate" name="endDate">

                    <label for="jarId">Jar ID:</label>
                    <select id="jarId" name="jarId[]" multiple>
                        <?php
                        $jarIds = array_unique(array_map(function($measurement) {
                            return $measurement->jarId;
                        }, App::$measurements));

                        foreach ($jarIds as $jarId): ?>
                            <option value="<?= $jarId ?>"><?= ucfirst($jarId) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="minTemp">Minimális hőmérséklet:</label>
                    <input type="number" id="minTemp" name="minTemp" step="0.1">

                    <label for="maxTemp">Maximális hőmérséklet:</label>
                    <input type="number" id="maxTemp" name="maxTemp" step="0.1">
                </form>
                <button onclick="szures()">Szűrés</button>
                <button name="export" onclick="exportFile()">Exportálás Excel-be</button>
                <p id="exLink"></p>
                <?php
                    /*if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
                        $filteredData = $app->filter();

                        $fileName = "export_" . date('Y-m-d_H-i-s') . ".csv";
                        $fileDirectory = "downloads/";
                        $filePath = __DIR__ . '/' . $fileDirectory . $fileName;
                    
                        if (!is_dir(__DIR__ . '/' . $fileDirectory)) {
                            mkdir(__DIR__ . '/' . $fileDirectory, 0777, true);
                        }
                    
                        $csvContent = "\xEF\xBB\xBF";
                        $csvContent .= "Mérés időpontja;Konzerv id-ja;Hőmérséklet\n";
                        foreach ($filteredData as $measurement) {
                            $sor = "{$measurement->dateTime};{$measurement->jarId};{$measurement->temperature}\n";
                            $csvContent .= $sor;
                        }
                    
                        file_put_contents($filePath, $csvContent);
                    
                        $downloadLink = $fileDirectory . $fileName;
                        echo '<p>A fájl elkészült: <a href="' . $downloadLink . '" download>Letöltés</a></p>';
                    }*/
                ?>
            </div>
        </div>
        <div class="filtdata">
            <table>
                <tr>
                    <th>Mérés időpontja</th>
                    <th>Konzerv id-ja</th>
                    <th>Hőmérséklet</th>
                </tr>
                <tbody id="tbody">

                </tbody>
            </table>
        </div>
        <div class="dias">
            <div class="dia">
                <canvas id="myChart"></canvas>
                <canvas id="tempChart"></canvas>
                <canvas id="tempMedChart"></canvas>
            </div>
        </div>
    </div>
    <!--7 napos átlag -->
    <script>
        var chartData = <?php echo (new OszlopDia())->last7day(); ?>;
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Átlagos hőmérséklet (°C)', 
                    data: chartData.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <!-- minmax -->
    <script>
        const minMaxTempData = <?php echo (new OszlopDia())->minMaxTemp(); ?>;

        const ctxMinMax  = document.getElementById('tempChart').getContext('2d');
        const tempChart = new Chart(ctxMinMax, {
            type: 'bar',
            data: {
                labels: minMaxTempData.labels,
                datasets: [
                    {
                        label: 'Legkisebb hőmérséklet (°C)',
                        data: minMaxTempData.minTemps,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Legnagyobb hőmérséklet (°C)',
                        data: minMaxTempData.maxTemps,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Temperature (°C)',
                        }
                    }
                }
            }
        });
    </script>
    <!-- median -->
    <script>
        const tempMedData = <?php echo (new OszlopDia())->tempMed(); ?>;

        const ctxTempMed = document.getElementById('tempMedChart').getContext('2d');
        const tempMedChart = new Chart(ctxTempMed, {
            type: 'bar',
            data: {
                labels: tempMedData.labels,
                datasets: [
                    {
                        label: 'Median hőmérséklet (°C)',
                        data: tempMedData.medians,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Temperature (°C)',
                        }
                    }
                }
            }
        });
    </script>
    <!-- filter/export-->
    <script>
        function szures(){
            let xhr = new XMLHttpRequest();
            var startDate = document.getElementById("startDate").value;
            var endDate = document.getElementById("endDate").value;

            var jarIdOptions = Array.from(document.getElementById("jarId").selectedOptions);
            var jarIds = jarIdOptions.map(option => option.value);

            var minTemp = document.getElementById("minTemp").value;
            var maxTemp = document.getElementById("maxTemp").value;
            xhr.open("POST", "filter.php", true);
            xhr.onload = () => {
                if (xhr.readyState === 4) { // A readyState ellenőrzése
                    if (xhr.status === 200) {
                        let data = xhr.response;
                        document.getElementById("tbody").innerHTML = data;
                    
                    }
                }
            }
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            let params = "startDate=" + encodeURIComponent(startDate) +
                        "&endDate=" + encodeURIComponent(endDate) +
                        "&jarIds=" + encodeURIComponent(JSON.stringify(jarIds)) +
                        "&minTemp=" + encodeURIComponent(minTemp) +
                        "&maxTemp=" + encodeURIComponent(maxTemp);
            xhr.send(params);
        }
        function exportFile(){
            let tbody = document.getElementById("tbody");
            let rows = tbody.getElementsByTagName("tr");
            let tableData = []
            for (let row of rows) {
                let cells = row.getElementsByTagName("td");
                let rowData = [];
                for (let cell of cells) {
                    rowData.push(cell.textContent);
                }
                tableData.push(rowData);
            }

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "export.php", true);
            xhr.onload = () => {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        let data = xhr.response;
                        document.getElementById("exLink").innerHTML = data;
                        //console.log(data);
                    }
                }
            }
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("tableData=" + encodeURIComponent(JSON.stringify(tableData)));
        }
    </script>
</body>
</html>