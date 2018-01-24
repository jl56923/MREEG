<?php
    $host = "127.0.0.1";
    $user = "jl56923";
    $pass = "";
    $db = "c9";
    $port = 3306;
    
    $connection = mysqli_connect($host, $user, $pass, $db, $port)or die(mysql_error());
    
    if (array_key_exists("EEG_unique_id", $_POST)) {
        $background_med_array = [];
        $get_EEG_background_meds = "SELECT EEG_indications, medications FROM EEG_interpretation_s WHERE scoring_template=0 AND EEG_unique_id=".$_POST['EEG_unique_id'];
        $result = mysqli_query($connection, $get_EEG_background_meds);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $background_med_array['EEG_indications'] = $row['EEG_indications'];
            $background_med_array['medications'] = $row['medications'];
        }
        
        echo json_encode($background_med_array);
        
        #echo "<pre>".print_r($background_med_array, true)."</pre>";
    }
?>