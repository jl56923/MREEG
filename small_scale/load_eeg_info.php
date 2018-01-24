<?php
    $host = "127.0.0.1";
    $user = "jl56923";
    $pass = "";
    $db = "c9";
    $port = 3306;
    
    $connection = mysqli_connect($host, $user, $pass, $db, $port)or die(mysql_error());
    
    $get_unique_EEGs = "SELECT DISTINCT `EEG_unique_id` FROM `EEG_interpretation_s";
    
    $EEG_unique_id_array = [];
    
    $result = mysqli_query($connection, $get_unique_EEGs);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $EEG_unique_id_array[] = $row['EEG_unique_id'];
        }
    }
    
    echo json_encode($EEG_unique_id_array);
?>