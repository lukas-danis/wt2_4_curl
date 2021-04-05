<?php
require_once "Database.php";
$conn = (new Database())->createConnection();
$output = '';

if (!empty($_POST)) {
    $lec_id = $_POST['id'];

    $dataExplode = explode("_", $_POST['usname']);
    $name = '';
    foreach ($dataExplode as $word) {
        $name .= $word;
        $name .= " ";
    }
    $name = trim($name);
    $stmt = $conn->prepare("SELECT * FROM actions where name=:name and lecture_id=:lecture_id");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':lecture_id', $lec_id);
    $stmt->execute();
    $result = $stmt->fetchAll();
    if ($result) {
        $output .= "<div class='row'>
                        <div class='col-sm-6'>
                            Meno: " . $name . "
                        </div>
                    </div>";
        foreach ($result as $row) {
            $output .= "<div class='row'>
                            <div class='col-sm-6'>
                                " . $row["action"] . "
                            </div>
                            <div class='col-sm-6'>
                                čas: " . $row["timestamp"] . "
                            </div>
                        </div>";
        }
    } else {
        $output .= "<div class='row'>
                        <div class='col-sm-6'>
                            " . $name . " sa nenachádzal na prednáške
                        </div>
                    </div>";
    }
    echo $output;
} else {
    $output .= "<div class='row'>
                    <div class='col-sm-6'>
                        CHYBA
                    </div>
                </div>";
    echo $output;
}
