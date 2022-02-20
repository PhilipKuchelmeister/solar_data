<?php

$servername = "localhost";
$username = "solar";
$password = "xxxxxxxxxxxx";
$dbname = "solar";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$logPath = "logs";

$aDirectories = glob($logPath . '/*', GLOB_ONLYDIR);

$aColumns = array(
      0 => array('name' => 'datetime', 'type' => 'string')
    , 1 => array('name' => 'grid', 'type' => 'int')
    , 2 => array('name' => 'energy_yield', 'type' => 'float')
    , 3 => array('name' => '000', 'type' => 'skip')
    , 4 => array('name' => '000', 'type' => 'skip')
    , 5 => array('name' => 'pv_voltage', 'type' => 'int')
    , 6 => array('name' => '000', 'type' => 'skip')
    , 7 => array('name' => 'grid_voltage', 'type' => 'int')
    , 8 => array('name' => '000', 'type' => 'skip')
    , 9 => array('name' => '000', 'type' => 'skip')
    , 10 => array('name' => 'current_to_grid', 'type' => 'int')
    , 11 => array('name' => '000', 'type' => 'skip')
    , 12 => array('name' => '000', 'type' => 'skip')
    , 13 => array('name' => 'current_panels', 'type' => 'int')
    , 14 => array('name' => '000', 'type' => 'skip')
    , 15 => array('name' => '000', 'type' => 'skip')
    , 16 => array('name' => '000', 'type' => 'skip')
    , 17 => array('name' => '000', 'type' => 'skip')
    , 18 => array('name' => 'grid_freq', 'type' => 'float')
    , 19 => array('name' => '000', 'type' => 'skip')
    , 20 => array('name' => 'status', 'type' => 'string')
    , 21 => array('name' => 'error', 'type' => 'string')
    , 22 => array('name' => 'total_hours', 'type' => 'float')
    , 23 => array('name' => '000', 'type' => 'skip')
    , 24 => array('name' => '000', 'type' => 'skip')
    , 25 => array('name' => '000', 'type' => 'skip')
);

// 2022-02-12T13:12:37+0000,493,1800.61,,,329,,228,,,2166,,,1588,,,,,49.99,,Mpp,ok,7433.79,

foreach ($aDirectories as $dir) {

    
    $sConverter = substr($dir, strrpos($dir, '/') + 1);
    echo "<br>" .  $sConverter . "<br>";
    $files = glob($dir . "/*.csv");
    foreach ($files as $filepath) {
        // echo $filepath . "<br>";
        $sFile = substr($filepath, strrpos($filepath, '/') + 1);
        echo $sFile . "<br><br><br>";

        $sDate = strtok($sFile, '.csv');
        echo "Date: " . $sDate . "<br>";
        
        $aCsvData = file($filepath);
        $i=0;

        // add id_hotel as first element of each row:
        $aValues = array($sConverter);

        $iCountRows = count($aCsvData);
        for ($iRow = 1; $iRow < $iCountRows; $iRow++) { // process rows

            $aCsvLine = str_getcsv(str_replace("\t", ',', $aCsvData[$iRow]), ',');
            // 
            // print_r($aCsvLine);

            // add id_hotel as first element of each row:
            $aValues = array("'" . $sConverter . "'");

            $sConverterID = substr($sConverter, strrpos($sConverter, '_') + 1);
            $aValues[] = $sConverterID;

            // echo count($aCsvLine) . "<br>";
            for ($iCol = 0; $iCol < count($aCsvLine); $iCol++) { // process columns
                // echo $iCol . "<br>";
                // check for valid column:

                if ($aColumns[$iCol]['type'] != 'skip') { // add data to output only for non skipping columns!
                    if ($aCsvLine[$iCol] === '') {
                        $aValues[] = 'NULL';
                    } else {
                        //correct date
                        if($iCol == 0){
                            $aCsvLine[$iCol] = substr($aCsvLine[$iCol], 0, -5);
                        }
                        // check if enclosure chars are needed for db column:
                        $sValueEnclose = ($aColumns[$iCol]['type'] == 'string') ? "'" : "";
                        $aValues[] = $sValueEnclose . $aCsvLine[$iCol] . $sValueEnclose;
                    }
                }
            }

				// add crawl_date at the end of each row:
				$aValues[] = "UNIX_TIMESTAMP()";

				// prepare insert values array:
				$aValueRows[] = '(' . implode(',', $aValues) . ')';

        }
        // echo "<pre>";
        // print_r($aValueRows);
        // fclose($aCsvData);

        // DB insert into ext table:
        if (count($aValueRows) > 0) {

            // INSERT new data:

            // generate db-columns listing and on-duplicate-update-string
            $sColumnNames = "";
            $sOnDuplicateKeyUpdate = " ON DUPLICATE KEY UPDATE "; // id_hotel is not needed as part of unique key, but is used here to be able to glue the columns toghether by comma
            foreach ($aColumns as $iColIndex => $aColumn) {
                if ($aColumns[$iColIndex]['type'] != 'skip') { // add data to output only for non skipping columns!
                    $sColumnNames .= ", " . $aColumn['name'];
                    $sOnDuplicateKeyUpdate .= " " . $aColumn['name'] . "=VALUES(" . $aColumn['name'] .  "), ";
                }
            }

            $sQuery = "INSERT INTO solar_data (converter_no, converter_id, " . trim($sColumnNames, ', ') . ", update_time)
							VALUES " . implode(',', $aValueRows) .
                trim($sOnDuplicateKeyUpdate, ', ');

            // echo "<br><br>" . $sQuery; exit;


            if ($conn->query($sql) === TRUE) {
                echo "New record created successfully";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }

        }
    }
}

$conn->close();

?>
