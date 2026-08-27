<?php
    class Measurement {
        public string $jarId;
        public string $dateTime;
        public float $temperature;

        public function __construct(string $jarId, string $dateTime, float $temperature) {
            $this->jarId = $jarId;
            $this->dateTime = $dateTime;
            $this->temperature = $temperature;
        }
    }
    class App {
        private $conn;
        public static $measurements = [];
        public function __construct() {
            $this->conn = mysqli_connect("localhost", "root", "", "homero");
            if (!$this->conn) {
                die("Adatbázis kapcsolat hiba: " . mysqli_connect_error());
            }
            mysqli_set_charset($this->conn, "UTF8");
            $this->loadMeasurements();
        }
        public function loadMeasurements(): void {
            self::$measurements = [];
            $query = "SELECT jar_id, m_time, jar_temp FROM adatok";
            $result = mysqli_query($this->conn, $query);
    
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    self::$measurements[] = new Measurement(
                        $row['jar_id'],
                        $row['m_time'],
                        (float)$row['jar_temp']
                    );
                }
            } else {
                die("Adatbázis lekérdezés hiba: " . mysqli_error($this->conn));
            }
        }
        public function __destruct() {
            mysqli_close($this->conn);
        }
        public function filter($startDate, $endDate, $jarIds, $minTemp, $maxTemp){
            $filteredData = self::$measurements;
        
            if ($startDate != '' && $endDate != '') {
                foreach ($filteredData as $key => $measurement) {
                    $measurementDate = new DateTime($measurement->dateTime);
                    $startDateObj = new DateTime($startDate);
                    $endDateObj = new DateTime($endDate);
                
                    if ($measurementDate < $startDateObj || $measurementDate > $endDateObj) {
                        unset($filteredData[$key]);
                    }
                }
            }
        
            if (count($jarIds) > 0) {
                foreach ($filteredData as $key => $measurement) {
                    if (!in_array($measurement->jarId, $jarIds)) {
                        unset($filteredData[$key]);
                    }
                }
            }
        
            if ($minTemp != null) {
                foreach ($filteredData as $key => $measurement) {
                    if ($minTemp > round($measurement->temperature / 1000, 2)) {
                        unset($filteredData[$key]);
                    }
                }
            }
        
            if ($maxTemp != null) {
                foreach ($filteredData as $key => $measurement) {
                    if ($maxTemp < round($measurement->temperature / 1000, 2)) {
                        unset($filteredData[$key]);
                    }
                }
            }
        
            return $filteredData;
        }
        
    }
    class FileProcessor {
        private string $fileName;
        private string $filePath;
        private $conn;
    
        public function __construct(string $fileName, string $filePath, $conn) {
            $this->fileName = $fileName;
            $this->filePath = $filePath;
            $this->conn = $conn;
        }
    
        public function extractDate(): string {
            if (preg_match('/(\d{4})(\d{2})(\d{2})/', $this->fileName, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
    
                return "{$year}-{$month}-{$day}";
            } else {
                throw new Exception("Érvénytelen fájlnév formátum.");
            }
        }
    
        public function processFile(): void {
            if (!file_exists($this->filePath)) {
                throw new Exception("A fájl nem található: {$this->filePath}");
            }
    
            $file = fopen($this->filePath, 'r');
            if (!$file) {
                throw new Exception("Nem sikerült megnyitni a fájlt: {$this->filePath}");
            }
    
            $formattedDate = $this->extractDate();
    
            while (($line = fgets($file)) !== false) {
                $line = trim($line);
                $fields = explode(' ', $line);
    
                if (count($fields) >= 4) {
                    $time = $fields[0];
                    $jarId = $fields[1];
                    $temperature = $fields[3];
                    
                    $query2 = "SELECT * FROM adatok WHERE jar_id = '{$jarId}' AND m_time = '{$formattedDate} {$time}'";
                    $result = mysqli_query($this->conn, $query2);

                    if ($result && mysqli_num_rows($result) > 0) {
                        $update = "UPDATE adatok SET jar_temp = {$temperature} WHERE jar_id = '{$jarId}' AND m_time = '{$formattedDate} {$time}'";
                        $doupdate = mysqli_query($this->conn, $update);
                    }else{
                        $query3 = "INSERT INTO adatok (jar_id, m_time, jar_temp) VALUES ('{$jarId}', '{$formattedDate} {$time}', {$temperature})";
                        $upload = mysqli_query($this->conn, $query3);
                    }
                } else {
                    throw new Exception("Érvénytelen sor formátum.");
                }
            }
            echo "<script>alert('Kész a feltöltés')</script>;";
    
            fclose($file);
            $app = new App();
            $app->loadMeasurements();
        }
        public function exportToCSV() {

        }
    }
    class OszlopDia {
        private $conn;
    
        public function __construct() {
            $this->conn = mysqli_connect("localhost", "root", "", "homero");
            if (!$this->conn) {
                die("Adatbázis kapcsolat hiba: " . mysqli_connect_error());
            }
            mysqli_set_charset($this->conn, "UTF8");
        }
    
        public function last7day(){
            if (empty(App::$measurements)) {
                return json_encode([
                    'labels' => [],
                    'minTemps' => [],
                    'maxTemps' => [],
                ]);
            }
            $last7Days = [];
            $jarTemperatures = [];

            foreach (App::$measurements as $measurement) {
                $date = substr($measurement->dateTime, 0, 10);

                if (!in_array($date, $last7Days)) {
                    $last7Days[] = $date;
                }
            }
            rsort($last7Days);
            $last7Days = array_slice($last7Days, 0, 7);

            foreach ($last7Days as $day) {
                foreach (App::$measurements as $measurement) {
                    $measurementDate = substr($measurement->dateTime, 0, 10);
        
                    if ($measurementDate === $day) {
                        $jarId = $measurement->jarId;
                        $temp = round($measurement->temperature / 1000, 2);
        
                        if (!isset($jarTemperatures[$jarId])) {
                            $jarTemperatures[$jarId] = [];
                        }
                        $jarTemperatures[$jarId][] = $temp;
                    }
                }
            }
            $labels = [];
            $data = [];
            foreach ($jarTemperatures as $jarId => $temps) {
                $averageTemp = array_sum($temps) / count($temps);

                $labels[] = "Jar ID: {$jarId}";
                $data[] = round($averageTemp, 2);
            }

            return json_encode(['labels' => $labels, 'data' => $data]);
        }
        public function minMaxTemp() {
            $minTemps = [];
            $maxTemps = [];
            $labels = [];
        
            if (empty(App::$measurements)) {
                return json_encode([
                    'labels' => [],
                    'minTemps' => [],
                    'maxTemps' => [],
                ]);
            }
        
            foreach (App::$measurements as $measurement) {
                $jarId = $measurement->jarId;
                $temperature = $measurement->temperature;
        
                if (!isset($minTemps[$jarId]) || $temperature < $minTemps[$jarId]) {
                    $minTemps[$jarId] = $temperature;
                }
        
                if (!isset($maxTemps[$jarId]) || $temperature > $maxTemps[$jarId]) {
                    $maxTemps[$jarId] = $temperature;
                }
            }
        
            foreach ($minTemps as $jarId => $minTemp) {
                $labels[] = $jarId;
                $minTemps[$jarId] = round($minTemp / 1000, 2);
                $maxTemps[$jarId] = round($maxTemps[$jarId] / 1000, 2);
            }
        
            return json_encode([
                'labels' => $labels,
                'minTemps' => array_values($minTemps),
                'maxTemps' => array_values($maxTemps),
            ]);
        }
        public function tempMed() {
            $temperaturesByJar = [];
            $labels = [];
        
            foreach (App::$measurements as $measurement) {
                $jarId = $measurement->jarId;
                $temperature = $measurement->temperature;
        
                $temperaturesByJar[$jarId][] = $temperature;
            }
        
            $medians = [];
            foreach ($temperaturesByJar as $jarId => $temperatures) {
                sort($temperatures); 
        
                $count = count($temperatures);
                $middle = floor($count / 2);
        
                if ($count % 2 == 0) {
                    $median = ($temperatures[$middle - 1] + $temperatures[$middle]) / 2;
                } else {
                    $median = $temperatures[$middle];
                }
        
                $medians[$jarId] = round($median, 2);
                $labels[] = $jarId;
            }
        
            return json_encode([
                'labels' => $labels,
                'medians' => array_values($medians),
            ]);
        }

    }
    $app = new App();
?>