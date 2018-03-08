<?php
    $host = "127.0.0.1";
    $user = "jl56923";
    $pass = "";
    $db = "c9";
    $port = 3306;
    
    $connection = mysqli_connect($host, $user, $pass, $db, $port)or die(mysql_error());
    
    $message = "";
    
    $epi_table_html = "";
    $epi_table_header = "<table class='table'><thead><tr><th scope='col'>Parameter</th><th scope='col'>Value</th><th scope='col'>Score</th></tr></thead><tbody>";
    $epi_table_rows = [];

    if (array_key_exists("create_eeg", $_POST)) {
        
        #print_r($_POST);
        
        # You will have to distinguish between the $spike_count variable created here, when $_POST is submitted by create_eeg, vs $spike_count_user variable, which is when $_POST is submitted by read_eeg (that is, when a user reads an eeg and submits an interpretation).
        if ($_POST['spike_present'] === "spike_present") {
            $spike_count = count($_POST['EEG_epi_s']);
        } else {
            $spike_count = 0;
        }
        
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
        
        $find_EEG_epi_id = "SELECT MAX(EEG_epi_id) FROM EEG_epi_s WHERE user_ID=1 LIMIT 1";
        $max_eeg_epi_id = 0;
        $result = mysqli_query($connection, $find_EEG_epi_id);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            if (is_null($row['MAX(EEG_epi_id)'])) {
                $max_eeg_epi_id = 0;
            } else {
                $max_eeg_epi_id = $row['MAX(EEG_epi_id)'];
            }
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
                    if ($parameter_name === 'normal_variants') {
                        $normal_variant_array = [];
                        foreach($parameter_value as $normal_variant_value) {
                            $normal_variant_array[] = lookup_int_value('normal_variants', $normal_variant_value, $connection);
                        }
                        print_r($normal_variant_array);
                        $normal_variant_string = "'".implode(",", $normal_variant_array)."'";
                        $query_interpretation .= ", ".$normal_variant_string;
                        $message .= "The normal_variant_string is: ".$normal_variant_string."<br>";
                    } else {
                        $query_interpretation .= ", ";
                        $query_interpretation .= lookup_int_value($parameter_name, $parameter_value, $connection);
                    }
                }
                $query_interpretation .= ")";
                mysqli_query($connection, $query_interpretation);
                $message .= "The query to insert interpretation values is: ";
                $message .= $query_interpretation."<br>";
            }
            
            if ($table_name === "EEG_interpretation_score") {
                $query_interpretation_score = "INSERT INTO EEG_interpretation_s (EEG_interpretation_row, EEG_unique_id, user_ID, scoring_template, spikes, ";
                $query_interpretation_score .= implode(", ", array_keys($parameters));
                $query_interpretation_score .= ") VALUES ('NULL', ".$eeg_unique_id.", 1, 1, ".$spike_count.", ";
                $query_interpretation_score .= implode(", ", array_values($parameters));
                $query_interpretation_score .= ")";
                mysqli_query($connection, $query_interpretation_score);
                $message .= "The query to insert interpretation scores is: ";
                $message .= $query_interpretation_score."<br>";
            }
            
            if ($table_name === "EEG_epi_s" && $_POST['spike_present'] === "spike_present") {
                foreach($_POST[$table_name] as $spike_num => $spike_parameters) {
                    $query_epi = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                    $query_epi .= implode(", ", array_keys($spike_parameters));
                    $query_epi .= ") VALUES ('NULL', ".($max_eeg_epi_id + $spike_num).", ".$eeg_unique_id.", 1, 0";
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
                    $query_epi_score = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                    $query_epi_score .= implode(", ", array_keys($spike_parameters));
                    $query_epi_score .= ") VALUES ('NULL', ".($max_eeg_epi_id + $spike_num).", ".$eeg_unique_id.", 1, 1, ";
                    $query_epi_score .= implode(", ", array_values($spike_parameters));
                    $query_epi_score .= ")";
                    foreach ($spike_parameters as $spike_parameter_name => $spike_parameter_score) {
                        $epi_table_rows[$spike_num][$spike_parameter_name] .= "<td>".$spike_parameter_score."</td></tr>";
                    }
                    mysqli_query($connection, $query_epi_score);
                    #$message .= "The query to insert epi score values is: ";
                    #$message .= $query_epi_score."<br>";
                }
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
    }
    
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
        <h1>EEG <?php echo $eeg_unique_id; ?></h1>
        <p>You successfully created a new EEG record.</p>
    
        <div id="message"><?php 
        echo $message;
        ?></div>
    
        <h3>Background information</h3>
        <p>EEG indications: <?php echo $_POST["EEG_interpretation_s"]["EEG_indications"]; ?></p> 
        <p>Current medications: <?php echo $_POST["EEG_interpretation_s"]["medications"]; ?></p>
        
        <br>
        
        <h3>EEG findings</h3>
        
        <table class="table">
          <thead>
            <tr>
              <th scope="col">Parameter</th>
              <th scope="col">Value</th>
              <th scope="col">Score</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row">PDR</th>
              <td><?php echo $_POST["EEG_interpretation_s"]["pdr_value"]; ?></td>
              <td><?php echo $_POST["EEG_interpretation_score"]["pdr_value"]; ?></td>
            </tr>
            <tr>
              <th scope="row">Normal variants</th>
              <td><?php echo $_POST["EEG_interpretation_s"]["normal_variants"]; ?></td>
              <td><?php echo $_POST["EEG_interpretation_score"]["normal_variants"]; ?></td>
            </tr>
            <tr>
              <th scope="row">Overall assessment</th>
              <td><?php echo $_POST["EEG_interpretation_s"]["abn_summary"]; ?></td>
              <td><?php echo $_POST["EEG_interpretation_score"]["abn_summary"]; ?></td>
            </tr>
            <tr>
              <th scope="row">Interpretation</th>
              <td><?php echo $_POST["EEG_interpretation_s"]["interpretation"]; ?></td>
              <td><?php echo $_POST["EEG_interpretation_score"]["interpretation"]; ?></td>
            </tr>
          </tbody>
        </table>
        
        <br>
        
        <h3>Spikes/epileptiform findings</h3>
        <div id="spike_count">
            <?php echo $epi_table_html; ?>
        </div>
    
    <form action="create_eeg_s.php">
        <input type="submit" class="btn btn-info" value="Create another EEG">
    </form>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    
  </body>
</html>