<?php
    $host = "127.0.0.1";
    $user = "jl56923";
    $pass = "";
    $db = "c9";
    $port = 3306;
    
    $connection = mysqli_connect($host, $user, $pass, $db, $port)or die(mysql_error());
    
    $message = "";
    
    $epi_html = "<div class='container'>";
    $epi_html_header = "<div class='row'><div class='col-sm'><h4>Your findings</h4></div><div class='col-sm'><h4>Correct findings</h4></div><div class='col-sm'><h4>Score</h4></div></div>";

    if (array_key_exists("read_eeg", $_POST)) {
        
        print_r($_POST);
        
        # You will have to distinguish between the $spike_count variable created here, when $_POST is submitted by create_eeg, vs $spike_count_user variable, which is when $_POST is submitted by read_eeg (that is, when a user reads an eeg and submits an interpretation).
        if ($_POST['spike_present'] === 'spike_absent') {
            $user_spike_count = 0;
        } else {
            $user_spike_count = count($_POST['EEG_epi_s']);
        }
        
        # The steps here should be: 1) Look up answer key for the EEG that the user just read and store it in a string, 2) Look up the scoring template for the EEG that the user just read and store it in a string, 3) compare the parameters that the user entered and the answer key, 4) after comparing the user's interpretation and the answer key, generate a score for the user's interpretation. (For spikes, seizures, and slowing, this is going to requiring iterating through each sub-entry and comparing each of the user's sub-entry with the key's sub-entries, and matching them up to maximize the user's score.) 5) Insert the user's score into the database, with the parameter values now representing how many points the user got for that specific parameter. I'm not actually sure if this really would save time, because in reality you can always recalculate the user's score based on their entry and the answer key, but whatever.
        $EEG_unique_id = $_POST['EEG_unique_id'];
        
        function lookup_text_value($parameter_name, $parameter_value, $connection) {
            $query_lookup_text_value = "SELECT parameter_text_value FROM values_dictionary WHERE parameter_name='".$parameter_name."' AND parameter_int_value='".$parameter_value."' LIMIT 1";
            $result = mysqli_query($connection, $query_lookup_text_value);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                return $row['parameter_text_value'];
            } else {
                return $parameter_value;
            }
        }
        
        $relevant_EEG_parameters = "pdr_value, normal_variants, abn_summary, interpretation, spikes";
        
        $retrieve_EEG_key = "SELECT ".$relevant_EEG_parameters." FROM EEG_interpretation_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=0 LIMIT 1";
        $result = mysqli_query($connection, $retrieve_EEG_key);
        $EEG_key = mysqli_fetch_array($result);
        foreach ($EEG_key as $parameter_name => $parameter_value) {
            if ($parameter_name === 'normal_variants') {
                $normal_variant_string = $parameter_value;
                $normal_variant_array = explode(",", $normal_variant_string);
                foreach($normal_variant_array as $index => $normal_variant_int_value) {
                    $normal_variant_array[$index] = lookup_text_value('normal_variants', $normal_variant_int_value, $connection);
                }
                $EEG_key['normal_variants'] = $normal_variant_array;
            } else {
                $EEG_key[$parameter_name] = lookup_text_value($parameter_name, $parameter_value, $connection);
            }
        }
        
        // Have to cast key_spike_count as an int, otherwise it retrieves it from the database as a string. This is important later when calculating $max_spike_count.
        $key_spike_count = intval($EEG_key['spikes']);
        
        $retrieve_EEG_scoring_template = "SELECT ".$relevant_EEG_parameters." FROM EEG_interpretation_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=1 LIMIT 1";
        $result = mysqli_query($connection, $retrieve_EEG_scoring_template);
        $EEG_scoring_template = mysqli_fetch_array($result);
        
        $message .= "The values for this EEG are: ";
        $message .= "<pre>".print_r($EEG_key, true)."</pre>";
        $message .= "The scoring template for this EEG are: ";
        $message .= "<pre>".print_r($EEG_scoring_template, true)."</pre>";
        
        if ($key_spike_count > 0) {
            $relevant_epi_parameters = "EEG_epi_id, spike_lateralization, spike_localization, spike_prevalence, spike_modifier";
            
            $retrieve_EEG_epi_key = "SELECT ".$relevant_epi_parameters." FROM EEG_epi_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=0 LIMIT ".$key_spike_count;
            $result = mysqli_query($connection, $retrieve_EEG_epi_key);
            $EEG_epi_key = [];
            while ($row = mysqli_fetch_array($result)) {
                $EEG_epi_key[] = $row;
            }
            
            // turn epi/spikes into text.
            $EEG_epi_text = [];
            foreach ($EEG_epi_key as $spike => $parameters) {
                foreach($parameters as $parameter_name => $parameter_value) {
                    $EEG_epi_text[$spike+1][$parameter_name] = lookup_text_value($parameter_name, $parameter_value, $connection);
                }
            }
            
            $retrieve_EEG_epi_scoring_template = "SELECT ".$relevant_epi_parameters." FROM EEG_epi_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=1 LIMIT ".$key_spike_count;
            $result = mysqli_query($connection, $retrieve_EEG_epi_scoring_template);
            $EEG_epi_scoring_template = [];
            while ($row = mysqli_fetch_array($result)) {
                $EEG_epi_scoring_template[] = $row;
            }
            $message .= "The values for the epi findings for this EEG are: ";
            $message .= "<pre>".print_r($EEG_epi_key, true)."</pre>";
            $message .= "The text version for the epi findings for this EEG are: ";
            $message .= "<pre>".print_r($EEG_epi_text, true)."</pre>";
            $message .= "The scoring template for the epi findings for this EEG are: ";
            $message .= "<pre>".print_r($EEG_epi_scoring_template, true)."</pre>";
        } else {
            $message .= "<p>The key for this EEG states that there are no spikes or epileptiform findings.</p>";
        }
        
        // Section to populate the epi html section:
        $max_spike_count = max($key_spike_count, $user_spike_count);
        $epi_html .= $epi_html_header;
        
        // If both $key_spike_count and $user_spike_count are 0, then you need to populate the $padded_user_epi_array and $padded_key_epi_array with the same value, which is: 'You did not enter any spikes/epileptiform findings for this EEG.', or 'There are no spikes/epileptiform findings for this EEG.'
        $padded_key_epi_array = [];
        $padded_user_epi_array = [];
        if ($max_spike_count === 0) {
            $padded_user_epi_array[1] = "<p>You did not enter any spikes/epileptiform findings for this EEG.</p>";
            $padded_key_epi_array[1] = "<p>There are no spikes/epileptiform findings for this EEG.</p>";
            var_dump($key_spike_count);
        } else {
            // First, copy over the user's answers over into another padded array; this padded array is used to generate the html needed for the first column of 'Your findings.'
            //$padded_user_epi_array = [];
            if ($user_spike_count === 0) {
                for ($i = 1; $i <= $max_spike_count; $i++) {
                    if ($i === 1) {
                        $padded_user_epi_array[$i] = "<p>You did not enter any spikes/epileptiform findings for this EEG.</p>";
                    } else {
                        $padded_user_epi_array[$i] = "<p></p>";
                    }
                }
            } else for ($i = 1; $i <= $max_spike_count; $i++) {
                if ($i <= $user_spike_count) {
                    $padded_user_epi_array[$i] = $_POST['EEG_epi_s'][$i];
                    
                    $padded_user_epi_array[$i] = "<p>Spike lateralization: ".$_POST['EEG_epi_s'][$i]['spike_lateralization']."</p>";
                    $padded_user_epi_array[$i] .= "<p>Spike localization: ".$_POST['EEG_epi_s'][$i]['spike_localization']."</p>";
                    $padded_user_epi_array[$i] .= "<p>Spike prevalence: ".$_POST['EEG_epi_s'][$i]['spike_prevalence']."</p>";
                    $padded_user_epi_array[$i] .= "<p>Spike modifier: ".$_POST['EEG_epi_s'][$i]['spike_modifier']."</p>";
                } else {
                    $padded_user_epi_array[$i] = "<p></p>";
                }
            }
            
            // Second, copy over the key's answers into another padded array; this padded array is used to generate the html needed for the second column of 'Correct findings'.
            //$padded_key_epi_array = [];
            if ($key_spike_count === 0) {
                for ($i = 1; $i <= $max_spike_count; $i++) {
                    if ($i === 1) {
                        $padded_key_epi_array[$i] = "<p>There are no spikes/epileptiform findings for this EEG.</p>";
                    } else {
                        $padded_key_epi_array[$i] = "<p></p>";
                    }
                }
            } else for ($i = 1; $i <= $max_spike_count; $i++) {
                if ($i <= $key_spike_count) {
                    $padded_key_epi_array[$i] = "<p>Spike lateralization: ".$EEG_epi_text[$i]['spike_lateralization']."</p>";
                    $padded_key_epi_array[$i] .= "<p>Spike localization: ".$EEG_epi_text[$i]['spike_localization']."</p>";
                    $padded_key_epi_array[$i] .= "<p>Spike prevalence: ".$EEG_epi_text[$i]['spike_prevalence']."</p>";
                    $padded_key_epi_array[$i] .= "<p>Spike modifier: ".$EEG_epi_text[$i]['spike_modifier']."</p>";
                } else {
                    $padded_key_epi_array[$i] = "<p></p>";
                }
            }
        }
        //print_r($padded_user_epi_array);
        //print_r($padded_key_epi_array);
        
        for ($i = 1; $i <= count($padded_key_epi_array); $i++) {
            $epi_html .= "<hr><div class='row'>";
            $epi_html .= "<div class='col-sm'>".$padded_user_epi_array[$i]."</div>";
            $epi_html .= "<div class='col-sm'>".$padded_key_epi_array[$i]."</div>";
            $epi_html .= "<div class='col-sm'><p>Score: </p></div>";
            $epi_html .= "</div>";
        }
        
        $epi_html .= "</div><br>";
        
    }
    // This is the code coped from report_eeg that created the queries to insert the parameter values into the database. You need to adapt this so that it now inserts the parameter values that the *user* entered; since you have to cycle through all of the parameters that the user entered, you also use this in order to build the necessary html to display the actual real answers, at least for the spikes/epileptiform findings. The other parameters you've already individually displayed in the html directly.
    
    
    
    /* 
    if (array_key_exists("create_eeg", $_POST)) {   
        $find_EEG_id = "SELECT MAX(EEG_unique_id) FROM EEG_interpretation_s WHERE user_ID=1 LIMIT 1";
        $eeg_unique_id = 0;
        $result = mysqli_query($connection, $find_EEG_id);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            if (is_null($row['MAX(EEG_unique_id)'])) {
                $eeg_unique_id = 1;
            } else {
                $eeg_unique_id = $row['MAX(EEG_unique_id)'] + 1;
            }
        }
        
        function lookup_text_value($parameter_name, $parameter_value, $connection) {
            $query_lookup_text_value = "SELECT parameter_text_value FROM values_dictionary WHERE parameter_name='".$parameter_name."' AND parameter_int_value='".$parameter_value."' LIMIT 1";
            $result = mysqli_query($connection, $query_lookup_text_value);
            $row = mysqli_fetch_array($result);
            return $row['parameter_text_value'];
        }
        
        function lookup_int_value($parameter_name, $parameter_value, $connection) {
            $query_lookup_int_value = "SELECT parameter_int_value FROM values_dictionary WHERE parameter_name='".$parameter_name."' AND parameter_text_value='".$parameter_value."' LIMIT 1";
            $result = mysqli_query($connection, $query_lookup_int_value);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                return $row['parameter_int_value'];
            } else {
                return "'".htmlspecialchars($parameter_value)."'";
            }
        }
        
        # Iterate through each subtable of the $_POST array in order to build appropriate queries to insert necessary data into the database for this EEG.
        foreach ($_POST as $table_name => $parameters) {
            if ($table_name === "EEG_interpretation_s") {
                $query_interpretation = "INSERT INTO EEG_interpretation_s (EEG_interpretation_row, EEG_unique_id, user_ID, scoring_template, spikes, ";
                $query_interpretation .= implode(", ", array_keys($parameters));
                $query_interpretation .= ") VALUES ('NULL', ".$eeg_unique_id.", 1, 0, ".$spike_count;
                foreach ($parameters as $parameter_name => $parameter_value) {
                    $query_interpretation .= ", ";
                    $query_interpretation .= lookup_int_value($parameter_name, $parameter_value, $connection);
                }
                $query_interpretation .= ")";
                mysqli_query($connection, $query_interpretation);
                #$message .= "The query to insert interpretation values is: ";
                #$message .= $query_interpretation."<br>";
            }
            
            if ($table_name === "EEG_interpretation_score") {
                $query_interpretation_score = "INSERT INTO EEG_interpretation_s (EEG_interpretation_row, EEG_unique_id, user_ID, scoring_template, spikes, ";
                $query_interpretation_score .= implode(", ", array_keys($parameters));
                $query_interpretation_score .= ") VALUES ('NULL', ".$eeg_unique_id.", 1, 1, ".$spike_count.", ";
                $query_interpretation_score .= implode(", ", array_values($parameters));
                $query_interpretation_score .= ")";
                mysqli_query($connection, $query_interpretation_score);
                #$message .= "The query to insert interpretation scores is: ";
                #$message .= $query_interpretation_score."<br>";
            }
            
            if ($table_name === "EEG_epi_s" && $_POST['spike_present'] === "spike_present") {
                foreach($_POST[$table_name] as $spike_num => $spike_parameters) {
                    $query_epi = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_unique_id, user_ID, scoring_template, ";
                    $query_epi .= implode(", ", array_keys($spike_parameters));
                    $query_epi .= ") VALUES ('NULL', ".$eeg_unique_id.", 1, 0";
                    foreach($spike_parameters as $spike_parameter_name => $spike_parameter_value) {
                        $query_epi .= ", ";
                        $query_epi .= lookup_int_value($spike_parameter_name, $spike_parameter_value, $connection);
                        $epi_table_rows[$spike_num][$spike_parameter_name] = "<tr><th scope='row'>".$spike_parameter_name."</th><td>".$spike_parameter_value."</td>";
                    }
                    $query_epi .= ")";
                    mysqli_query($connection, $query_epi);
                    #$message .= "The query to insert epi values is: ";
                    #$message .= $query_epi."<br>";
                }
            }
        
            if ($table_name === "EEG_epi_score" && $_POST['spike_present'] === "spike_present") {
                foreach($_POST[$table_name] as $spike_num => $spike_parameters) {
                    $query_epi_score = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_unique_id, user_ID, scoring_template, ";
                    $query_epi_score .= implode(", ", array_keys($spike_parameters));
                    $query_epi_score .= ") VALUES ('NULL', ".$eeg_unique_id.", 1, 1, ";
                    $query_epi_score .= implode(", ", array_values($spike_parameters));
                    $query_epi_score .= ")";
                    foreach ($spike_parameters as $spike_parameter_name => $spike_parameter_score) {
                        $epi_table_rows[$spike_num][$spike_parameter_name] .= "<td>".$spike_parameter_score."</td></tr>";
                    }
                    mysqli_query($connection, $query_epi_score);
                    #$message .= "The query to insert epi score values is: ";
                    #$message .= $query_epi_score."<br>";
                }
            } else {
                #$message .= "There is no query to insert epi score values because there are no spikes.";
            }
        }
        
        #echo "<pre>".print_r($epi_table_rows, true)."</pre>";
        
        if ($_POST['spike_present'] === 'spike_present') {
            for ($i = 1; $i <= $spike_count; $i++) {
                $epi_table_html .= "<br><h4>Spike ".$i."</h4>";
                $epi_table_html .= $epi_table_header;
                $epi_table_html .= implode("", array_values($epi_table_rows[$i]));
                $epi_table_html .= "</tbody></table>";
            }    
        } else {
            $epi_table_html .= "There are no spikes/epileptiform discharges in this EEG.";
        }
    }*/
    
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <style>
        html {
            height: 100%;
        }
        
        body {
            font-family: Verdana, sans-serif;
            font-size: 90%;
            height: 100%;
        }
        
        .btn {
            margin: 20px;
        }
        
        #EEG-report {
            background-color: aliceblue;
            float: left;
            text-align: center;
            height: 90%;
            max-height: 90%;
            overflow-y: auto;
        }
        
        #message {
            color: red;
        }
        
    </style>
    
    </head>
    <body>
    <nav class="navbar navbar-light bg-light">
        <a class="navbar-brand" href="#">
            <img src="/brainlogo.svg" width="63" height="50" alt="brainlogo"> MR.EEG 
        </a>
    <form class="form-inline">
        <input class="form-control" id="username" type="text" placeholder="Username" aria-label="Username">
        <input class="form-control" id="password" type="text" placeholder="Password" aria-label="Password">
        <button class="btn btn-outline-success" type="submit">Sign in</button>
    </form>
    </nav>


    <div class="container-fluid col-md-12" id="EEG-report">
        <h1>EEG <?php echo $EEG_unique_id; ?></h1>
        <p>You successfully read a EEG record.</p>
    
        <div id="message"><?php 
        echo $message;
        ?></div>
        
        <div id="EEG findings">
            <h3>EEG findings</h3>
            <h5>PDR value</h5>
            <p>Your answer: <?php echo $_POST['EEG_interpretation_s']['pdr_value'] ?></p>
            <p>Correct answer: <?php echo $EEG_key['pdr_value'] ?></p>
            <p>Score: /<?php echo $EEG_scoring_template['pdr_value'] ?></p>
            
            <h5>Normal variants</h5>
            <p>Your answer: <?php echo implode(", ", $_POST['EEG_interpretation_s']['normal_variants']); ?></p>
            <p>Correct answer: <?php echo implode(", ", $EEG_key['normal_variants']); ?></p>
            <p>Score: /<?php echo $EEG_scoring_template['normal_variants'] ?></p>
        </div>
        
        <div id="spikes">
            <h3>Spikes/Epileptiform findings</h3>
            <?php echo $epi_html; ?>
        </div>

        <div id="Overall assessment">
            <h3>Overall Assessment</h3>
            <h5>Overall Assessment</h5>
            <p>Your answer: <?php echo $_POST['EEG_interpretation_s']['abn_summary'] ?></p>
            <p>Correct answer: <?php echo $EEG_key['abn_summary'] ?></p>
            <p>Score: /<?php echo $EEG_scoring_template['abn_summary'] ?></p>
            <br>
            <h5>Interpretation</h5>
            <p>Your answer: <?php echo $_POST['EEG_interpretation_s']['interpretation'] ?></p>
            <p>Correct answer: <?php echo $EEG_key['interpretation'] ?></p>
            <p>Score: /<?php echo $EEG_scoring_template['interpretation'] ?></p>
        </div>
    
    <form action="read_eeg_s.php">
        <input type="submit" class="btn btn-info" value="Read another EEG">
    </form>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    
  </body>
</html>