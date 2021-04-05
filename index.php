<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "Database.php";

$ch = curl_init();
$conn = (new Database())->createConnection();
$stmt = $conn->prepare("DELETE FROM actions");
$stmt->execute();
$stmt = $conn->prepare("DELETE FROM lectures");
$stmt->execute();

// curl_setopt($ch, CURLOPT_URL, "https://github.com/lukas-danis/curlTest");
curl_setopt($ch, CURLOPT_URL, "https://github.com/apps4webte/curldata2021");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
if (curl_error($ch)) {
    die('chyba');
}

preg_match_all("!>[^\s]*?Te2.csv</a>!", $result, $filesPaths);
$filesPaths = $filesPaths[0];

$dataPoints = array();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaznamy</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>
    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.2/dist/chart.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>


<body>
    <div class="wd70">
        <table id="myTable" class="display">
            <?php
            echo "<thead><tr><th class='nosort'>Meno</th>";
            foreach ($filesPaths as $fileIndex => $filePath) {
                $filePath = substr($filePath, 1);
                $filePath = substr($filePath, 0, -4);

                // curl_setopt($ch, CURLOPT_URL, "https://raw.githubusercontent.com/lukas-danis/curlTest/master/$filePath");
                curl_setopt($ch, CURLOPT_URL, "https://raw.githubusercontent.com/apps4webte/curldata2021/main/$filePath");
                $csv = iconv('UTF-16LE', 'UTF-8', curl_exec($ch));
                if (curl_error($ch)) {
                    die('chyba2');
                }

                //TODO      urobit stmt->insert do lectures, parsnut datum z nazvu suboru ako timestamp  //DONE
                $fileDate = substr($filePath, 0, 8);
                $timestamp = date("d.m.Y", date_create_from_format("Ymd", $fileDate)->getTimestamp());
                echo "<th class='nosort'>Pr. " . $fileIndex + 1 . ", " . $timestamp . "</th>";
                
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $conn->prepare("INSERT INTO lectures (timestamp) VALUES (:timestamp)");
                $stmt->bindParam(':timestamp', $timestamp);
                $stmt->execute();
                $lecture_id = $conn->lastInsertId();
               
                //TODO      $csv urobit explode po riadkoch "$lines = explode("\n", $output);", rad radom pridat vsetky udaje do actions // DONE
                $stmt = $conn->prepare("INSERT INTO actions (lecture_id, name, action, timestamp)
        VALUES (:lecture_id, :name, :action, :timestamp)");
                $stmt->bindParam(':lecture_id', $lecture_id);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':action', $action);
                $stmt->bindParam(':timestamp', $timestamp);
                $lines = explode("\n", $csv);

                foreach ($lines as $index => $line) {
                    $arr = str_getcsv($line, "\t");
                    if ($index > 0 && $arr[0]) {
                        $namesPart = explode(" ", $arr[0]);
                        $name = $namesPart[1] . " " . $namesPart[0];
                        $action = $arr[1];
                        if (strlen($arr[2]) > 20) {
                            $parsed = substr($arr[2], 0, -2);
                            $parsed = trim($parsed);
                            $timestamp = date("Y-m-d H:i:s", date_create_from_format("m/d/Y, H:i:s", $parsed)->getTimestamp());
                        } else {
                            $parsed = $arr[2];
                            $timestamp = date("Y-m-d H:i:s", date_create_from_format("d/m/Y, H:i:s", $parsed)->getTimestamp());
                        }
                        $stmt->execute();
                    }
                }

            //TODO      vytvorit graf pod tabulkou  // DONE
                $stmt = $conn->prepare("SELECT COUNT(DISTINCT name) as pocet FROM actions where lecture_id=:lecture_id");
                $stmt->bindParam(':lecture_id', $lecture_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                array_push($dataPoints, array("x" => $fileIndex, "label" => "Prednáška " . $fileIndex + 1, "y" => $result["pocet"]));
            }
            echo "<th class='nosort'>Počet účastí</th><th class='nosort'>Spolu minút</th>";
            echo "</tr></thead>";

            //TODO      nacitame vsetky "lectures" z DB a pre kazde jedno ID vyhladame vsetkych studentov (cez foreach)  // DONE
            $stmt = $conn->prepare("SELECT * FROM lectures");
            $stmt->execute();
            $allLectures = $stmt->fetchAll();

            $stmt = $conn->prepare("SELECT DISTINCT name FROM actions");
            $stmt->execute();
            $allJoined = $stmt->fetchAll();

            //TODO      urobit vypisovanie do tabluky aby pripisovalo cas ku danemu studentovi  // DONE
            foreach ($allJoined as $oneJoinedPerson) {
                echo "<tr>";
                echo "<td>" . $oneJoinedPerson["name"] . "</td>";
                $lastLeft = 0;
                $sumTime = 0;
                $attendCount = 0;
                foreach ($allLectures as $oneLecture) {
                    //TODO      zistit posledny cas odchodu z prednasky  // DONE
                    $stmt = $conn->prepare("SELECT *
                    FROM actions where action=:action and lecture_id=:lecture_id
                    ORDER BY timestamp DESC limit 1;");
                    $action = "Left";
                    $stmt->bindParam(':action', $action);
                    $stmt->bindParam(':lecture_id', $oneLecture["id"]);
                    $stmt->execute();
                    $oneLeftRow = $stmt->fetch(PDO::FETCH_ASSOC);
                    $lastLeft = strtotime($oneLeftRow["timestamp"]);

                    $stmt = $conn->prepare("SELECT * FROM actions where name=:name and lecture_id=:lecture_id");
                    $stmt->bindParam(':name', $oneJoinedPerson["name"]);
                    $stmt->bindParam(':lecture_id', $oneLecture["id"]);
                    $stmt->execute();
                    $allPersonRows = $stmt->fetchAll();
                    $totalTime = 0;
                    if ($allPersonRows) {
                        $attendCount++;
                        $left = false;
                        foreach ($allPersonRows as $row) {
                            if ($row["action"] == "Joined") {
                                $timest = strtotime($row["timestamp"]);
                                $totalTime -= $timest;
                                $left = false;
                            } else if ($row["action"] == "Left") {
                                $timest = strtotime($row["timestamp"]);
                                $totalTime += $timest;
                                $left = true;
                            }
                        }
                        if ($left == false) {
                            $totalTime = $totalTime + $lastLeft;
                        }
                    }
                    $sumTime += round($totalTime / 60, 0);
                    $namesPart = explode(" ", $oneJoinedPerson["name"]);
                    $trasnfName = '';
                    foreach ($namesPart as $word) {
                        $trasnfName .= $word;
                        $trasnfName .= "_";
                    }
                    echo "<td><a href='javascript:void(0)' class='btn get_id' data-id=" . $oneLecture["id"] . " data-usname=" . $trasnfName . " data-toggle='modal' data-target='#myModal'>" . round($totalTime / 60, 0) . "</a></td>";
                }
                echo "<td>" . $attendCount . "</td>";
                echo "<td>" . $sumTime . "</td>";
                echo "</tr>" . "\n";
            }
            ?>
        </table>

        <div id="chartContainer" class="graph-style"></div>
        <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

        <div id="myModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-body" id="load_data">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    window.onload = function() {
        var chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: true,
            exportEnabled: true,
            theme: "light1",
            title: {
                text: "Celková účasť na prednáškach"
            },
            data: [{
                type: "column",
                dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
            }]
        });
        chart.render();
    }
</script>

</html>