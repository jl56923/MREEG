<?php
    $host = "127.0.0.1";
    $user = "jl56923";
    $pass = "";
    $db = "c9";
    $port = 3306;
    
    $message = "";
    
    $connection = mysqli_connect($host, $user, $pass, $db, $port)or die(mysql_error());
    
    if (array_key_exists("submit", $_POST)) {
        
        print_r($_POST);
        
        /* So you want to go through every key (which is the parameter name) and its corresponding value.
        You attempt to look up the parameter_int_value of each key-> value pair; if there is no int value,
        then you insert the value of that key directly into the databse. If there is an int value, then you
        insert the int value instead. There are going to be two insert statements built inside of this foreach
        loop: one that inserts values into the Interpretation_s table, and the other that inserts values into
        the epi_s table.*/
        
        $interp_query_array = $_POST['EEG_Interpretation_s'];
        
        # Loop through $interp_query_array to replace the strings with int values where appropriate. Otherwise, put single quotes around the value because then the value is a string and needs to be contained within quotes in order to be inserted into a table.
        # Perform this loop before adding in the EEG_unique_ID, the resident_ID, etc, because 1) you'll be looping through those values unnecessarily when in fact you are setting those key-value pairs manually, and so you *know* already that there is no point in looking up the int_value, and then 2) since these do not have lookup value, you will end up getting single quotes around them inappropriately.
        foreach ($interp_query_array as $key => $value) {
            $query_parameter_lookup = "SELECT parameter_int_value FROM Values_dictionary WHERE parameter_name='".$key."' AND parameter_text_value='".$value."' LIMIT 1";
            $result = mysqli_query($connection, $query_parameter_lookup);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $interp_query_array[$key] = $row['parameter_int_value'];
            } else {
                $temp = "'";
                $temp .= $value;
                $temp .= "'";
                $interp_query_array[$key] = $temp;
            }
        }
        
        $interp_query_array["EEG_unique_ID"] = 'NULL';
        $interp_query_array["resident_ID"] = 1;
        $interp_query_array["scoring_template"] = 0;
        $interp_query_array["spikes"] = count($_POST['EEG_epi_s']);
        
        $query_insert_interp_master = "INSERT INTO EEG_Interpretation_s ";
        $interp_temp_string_col = "(";
        $interp_temp_string_col .= implode(", ", array_keys($interp_query_array));
        $interp_temp_string_col .= ") ";
        
        $interp_temp_string_value = "VALUES (";
        $interp_temp_string_value .= implode(", ", array_values($interp_query_array));
        $interp_temp_string_value .= ")";
        
        $query_insert_interp_master .= $interp_temp_string_col;
        $query_insert_interp_master .= $interp_temp_string_value;
        $message .= "The query to insert a record into interp is: ";
        $message .= $query_insert_interp_master;
        $message .= "<br>";
        
        mysqli_query($connection, $query_insert_interp_master);
        $eeg_ID = mysqli_insert_id($connection);
        
        /****************/
        $epi_query_array = $_POST['EEG_epi_s'];
        $epi_queries = [];
        
        print_r($epi_query_array);
        
        # Loop through each epi finding, and replace the text with the appropriate int value. Also add the other parameters needed to create query, specifically values for the other columns.
        for ($i = 1; $i <= count($epi_query_array); $i++) {
            foreach($epi_query_array[$i] as $key => $value) {
                $query_parameter_lookup = "SELECT parameter_int_value FROM Values_dictionary WHERE parameter_name='".$key."' AND parameter_text_value='".$value."' LIMIT 1";
                $result = mysqli_query($connection, $query_parameter_lookup);
                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_array($result);
                    $epi_query_array[$i][$key] = $row['parameter_int_value'];
                }
            }
            
            $epi_query_array[$i]["epi_ID"] = 'NULL';
            $epi_query_array[$i]["EEG_unique_ID"] = $eeg_ID;
            $epi_query_array[$i]["resident_ID"] = 1;
            $epi_query_array[$i]["scoring_template"] = 0;
        }
        
        print_r($epi_query_array);
        
        # Now build the epi_queries array.
        for ($i = 1; $i <= count($epi_query_array); $i++) {
            $epi_queries[$i] = "INSERT INTO EEG_epi_s ";
            $epi_temp_string_col = "(";
            $epi_temp_string_col .= implode(", ", array_keys($epi_query_array[$i]));
            $epi_temp_string_col .= ") ";
            
            $epi_temp_string_value = "VALUES (";
            $epi_temp_string_value .= implode(", ", array_values($epi_query_array[$i]));
            $epi_temp_string_value .= ")";
            
            $epi_queries[$i] .= $epi_temp_string_col;
            $epi_queries[$i] .= $epi_temp_string_value;
        }
        
        for ($i = 1; $i <= count($epi_queries); $i++) {
            mysqli_query($connection, $epi_queries[$i]);
            $message .= "The query to insert a record into epi is: ";
            $message .= $epi_queries[$i];
            $message .= "<br>";
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
        
        #EEG-display {
            background-color: lavender;
            float: left;
            text-align: center;
            height: 90%;
        }
        
        #EEG-interpretation {
            background-color: aliceblue;
            float: left;
            text-align: center;
            height: 90%;
            max-height: 90%;
            overflow-y: auto;
        }
        
        #EEG-placeholder {
            width: 100%;
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
    
    <!-- This is the section of the page that displays the EEG. In the mockup version, this is a static PNG EEG image.
    Eventually, the plan is to either use Blaze EEG (Peters' R shiny display of EEG data) or D3 JS to dynamically read
    and display EEG data, but for now the focus is on getting the database part of MR EEG to work.
    <div class="container-fluid col-md-7" id="EEG-display">
        <h1>EEG Display: EEG #</h1>
        <img src="/EEG_image.jpg" alt="EEG Image" id="EEG-placeholder">
    </div>
    -->
    
    <div class="container-fluid col-md-12" id="EEG-interpretation">
        <h1>EEG Interpretation</h1>
        
        <div id="message"><?php 
        echo $message;
        ?></div>
        
        <form method="post" id="EEG_interpretation_form">
        <section>
            <h3>Background information</h3>
            
            <div class="form-group row">
                <label for="EEG_indications" class="col-sm-3 col-form-label">EEG indications</label>
                <div class="col-sm-8">
                    <textarea class="form-control" id="EEG_indications" name="EEG_Interpretation_s[EEG_indications]" rows="3"></textarea>
                </div>
            </div>
            
            <div class="form-group row">
                <label for="medications" class="col-sm-3 col-form-label">Current medications</label>
                <div class="col-sm-8">
                    <input class="form-control" type="text" id="medications" name="EEG_Interpretation_s[medications]">
                </div>
            </div>
        </section>
        <!-- Textarea for overall interpretation; not sure how this was being used in original MREEG. Would definitely have to clean the input from this textarea to avoid SQL injection or other hacks.
        <div class="form-group row">
            <label for="comments" class="col-sm-3 col-form-label">Comments/free text</label>
            <div class="col-sm-8">
                <textarea class="form-control" id="comments" rows="5"></textarea>
            </div>
        </div>
        -->
        <section>
            <h3>EEG findings</h3>
            
            <div class="form-group row">
                <label for="pdr_value" class="col-sm-3 col-form-label">PDR</label>
                    <select class="form-control col-sm-8" id="pdr_value" name="EEG_Interpretation_s[pdr_value]">
                        <option>none</option>
                        <option>&lt;5</option>
                        <option>5</option>
                        <option>6</option>
                        <option>7</option>
                        <option>8</option>
                        <option>9</option>
                        <option>10</option>
                        <option>11</option>
                        <option>12</option>
                        <option>13</option>
                        <option>14</option>
                    </select>
            </div>
                
            <!-- Could do radio buttons instead of select multiple from a list as well. If you are going to allow the user to select multiple values, will have to figure out how to enter these as a multivalued field. Will plan to use implode/explode to store multiple values as a comma delimited string. -->
            <div class="form-group row">
                <label for="normal_variants" class="col-sm-3 col-form-label">Normal variants</label>
                    <select multiple class="form-control col-sm-8" id="normal_variants" name="EEG_Interpretation_s[normal_variants]">
                        <option>none applicable</option>
                        <option>rhythmic midtemporal theta of drowsiness (RMTD)</option>
                        <option>POSTS</option>
                        <option>6 Hz phantom spike-wave</option>
                        <option>SREDA</option>
                        <option>Ciganek rhythm (midline theta)</option>
                        <option>lambda</option>
                        <option>mu</option>
                        <option>breach rhythm (skull defect) - right</option>
                        <option>breach rhythm (skull defect) - left</option>
                        <option>wicket waves</option>
                        <option>benign epileptiform transients of sleep (BETS or SSS)</option>
                        <option>posterior slowing of youth</option>
                    </select>
            </div>
        
        <!-- This is the beginning of the 3 subtables that are part of the EEG interpretation: EEG_slow, EEG_sz, and EEG_epi.
        The user can enter as many entries as they like tof slow, sz or epi 'findings'; they can add another sub-form for
        each additional entry/finding, and they can also remove it if they decide that they actually don't want it.
        You are going to base this adding/deleting fields dynamically from this website: http://formvalidation.io/examples/adding-dynamic-field/-->
        
        <section>
            <h3>Spikes/epileptiform findings</h3>
            <p class="spike">Spike 1</p>
            <fieldset>
            <div class="form-group row">
                <label for="spike_lateralization" class="col-sm-3 col-form-label">Spike lateralization</label>
                    <select class="form-control col-sm-8" class = "spike_lateralization" name="EEG_epi_s[1][spike_lateralization]">
                        <option>bilateral R>L</option>
                        <option>bilateral L>R</option>
                        <option>left</option>
                        <option>right</option>
                        <option>vertex</option>
                        <option>bilateral L=R</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="spike_localization" class="col-sm-3 col-form-label">Spike localization</label>
                    <select class="form-control col-sm-8" class = "spike_localization" name="EEG_epi_s[1][spike_localization]">
                        <option>generalized</option>
                        <option>frontal</option>
                        <option>temporal</option>
                        <option>parietal</option>
                        <option>occipital</option>
                        <option>central</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="spike_prevalence" class="col-sm-3 col-form-label">Spike prevalence</label>
                    <select class="form-control col-sm-8" class = "spike_prevalence" name="EEG_epi_s[1][spike_prevalence]">
                        <option>continuous</option>
                        <option>every few seconds</option>
                        <option>every few minutes</option>
                        <option>rare</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="spike_modifier" class="col-sm-3 col-form-label">Spike modifier</label>
                    <select class="form-control col-sm-8" class = "spike_modifier" name="EEG_epi_s[1][spike_modifier]">
                        <option>with stimulation</option>
                        <option>periodic</option>
                        <option>low amplitude</option>
                        <option>high amplitude</option>
                        <option>polyspike</option>
                        <option>triphasic</option>
                        <option>sleep augmented</option>
                    </select>
            </div>
            <br>
            </fieldset>
            <button id="addMoreSpike">Add another spike</button>
            <br>
        </section>
        <br>
        <section>
            <h3>Overall Assessment</h3>
        <div class="form-group row">
            <label for="abn_summary" class="col-sm-3 col-form-label">Overall assessment</label>
                <select class="form-control col-sm-8" id="abn_summary" name="EEG_Interpretation_s[abn_summary]">
                    <option>excessive beta likely reflecting a medication effect</option>
                    <option>focal slowing</option>
                    <option>multifocal slowing</option>
                    <option>generalized slowing</option>
                    <option>focal epileptiform discharges</option>
                    <option>multifocal epileptiform discharges</option>
                    <option>generalized epileptiform discharges</option>
                    <option>focal seizure(s)</option>
                    <option>generalized seizure(s)</option>
                    <option>fragmented sleep</option>
                    <option>hypoxia (low SpO2)</option>
                </select>
        </div>
        
        <div class="form-group row">
            <label for="interpretation" class="col-sm-3 col-form-label">Interpretation</label>
                <select class="form-control col-sm-8" id="interpretation" name="EEG_Interpretation_s[interpretation]">
                    <option>indicate diffuse encephalopathy</option>
                    <option>indicate cortical dysfunction</option>
                    <option>indicate cortical irritability</option>
                    <option>are abnormal but not definitively epileptic</option>
                    <option>suggest epilepsy</option>
                    <option>suggest NES</option>
                    <option>may indicate a sleep disorder</option>
                </select>
        </div>
        </section>
        <input type="submit" class="btn btn-info" name="submit" value="create_eeg">
        </form>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    
    <script>
        $(function() {
            var spike_count = 1;
            $("#addMoreSpike").click(function(e) {
                e.preventDefault();
                spike_count++;
                $("fieldset:last").after("<p class='spike'>Spike "+ spike_count + "</p><fieldset><div class='form-group row'><label for='spike_lateralization' class='col-sm-3 col-form-label'>Spike lateralization</label><select class='form-control col-sm-8' class = 'spike_lateralization' name='EEG_epi_s["+ spike_count +"][spike_lateralization]'>option>bilateral R>L</option><option>bilateral L>R</option><option>left</option><option>right</option><option>vertex</option><option>bilateral L=R</option></select></div><div class='form-group row'><label for='spike_localization' class='col-sm-3 col-form-label'>Spike localization</label><select class='form-control col-sm-8' class = 'spike_localization' name='EEG_epi_s["+ spike_count +"][spike_localization]'><option>generalized</option><option>frontal</option><option>temporal</option><option>parietal</option><option>occipital</option><option>central</option></select></div><div class='form-group row'><label for='spike_prevalence' class='col-sm-3 col-form-label'>Spike prevalence</label><select class='form-control col-sm-8' class = 'spike_prevalence' name='EEG_epi_s["+ spike_count +"][spike_prevalence]'><option>continuous</option><option>every few seconds</option><option>every few minutes</option><option>rare</option></select></div><div class='form-group row'><label for='spike_modifier' class='col-sm-3 col-form-label'>Spike modifier</label><select class='form-control col-sm-8' class = 'spike_modifier' name='EEG_epi_s["+ spike_count +"][spike_modifier]'><option>with stimulation</option><option>periodic</option><option>low amplitude</option><option>high amplitude</option><option>polyspike</option><option>triphasic</option><option>sleep augmented</option></select></div><br></fieldset>");
            });
        });
    </script>
    
  </body>
</html>