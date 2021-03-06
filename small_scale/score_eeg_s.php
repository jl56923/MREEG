<?php
    $host = "127.0.0.1";
    $user = "jl56923";
    $pass = "";
    $db = "c9";
    $port = 3306;
    
    $connection = mysqli_connect($host, $user, $pass, $db, $port)or die(mysql_error());
    
    $message = "";

    if (array_key_exists("read_eeg", $_POST)) {
        
        #print_r($_POST);
        
        $EEG_unique_id = $_POST['EEG_unique_id'];
        
        if ($_POST['spike_present'] === 'spike_absent') {
            $user_spike_count = 0;
        } else {
            $user_spike_count = count($_POST['EEG_epi_s']);
        }
        
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
        
        function lookup_int_value($parameter_name, $parameter_value, $connection) {
            $query_lookup_int_value = "SELECT parameter_int_value FROM values_dictionary WHERE parameter_name='".$parameter_name."' AND parameter_text_value='".$parameter_value."' LIMIT 1";
            $result = mysqli_query($connection, $query_lookup_int_value);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                return $row['parameter_int_value'];
            } else {
                return $parameter_value;
            }
        }
        
        $relevant_EEG_parameters = ['pdr_value', 'normal_variants', 'abn_summary', 'interpretation', 'spikes'];
        $relevant_EEG_parameters_string = implode(", ", $relevant_EEG_parameters);
        
        // Retrieve the EEG key for this specific EEG. Query returns int values, so convert this to text values for display to the users. The EEG key retrieved directly from the database returns as $EEG_key, and the normal_variants comma-delimited string is exploded to return an array. The EEG key is converted to the text values using the function lookup_text_value, and this is stored as $EEG_key_text.
        $retrieve_EEG_key = "SELECT ".$relevant_EEG_parameters_string." FROM EEG_interpretation_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=0 LIMIT 1";
        $result = mysqli_query($connection, $retrieve_EEG_key);
        $EEG_key = mysqli_fetch_array($result);
        $EEG_key_text = [];
        foreach ($EEG_key as $parameter_name => $parameter_value) {
            if ($parameter_name === 'normal_variants') {
                $normal_variant_string = $parameter_value;
                $normal_variant_array = explode(",", $normal_variant_string);
                $EEG_key['normal_variants'] = $normal_variant_array;
                foreach($normal_variant_array as $index => $normal_variant_int_value) {
                    $normal_variant_array[$index] = lookup_text_value('normal_variants', $normal_variant_int_value, $connection);
                }
                $EEG_key_text['normal_variants'] = $normal_variant_array;
            } else {
                $EEG_key_text[$parameter_name] = lookup_text_value($parameter_name, $parameter_value, $connection);
            }
        }
        
        // Have to cast key_spike_count as an int, otherwise it retrieves it from the database as a string. This is important later when calculating $max_spike_count.
        $key_spike_count = intval($EEG_key['spikes']);
        
        // Now, unset spikes from $relevant_EEG_parameters because you no longer need it.
        
        // Retrieve the scoring template for this specific EEG.
        $retrieve_EEG_scoring_template = "SELECT ".$relevant_EEG_parameters_string." FROM EEG_interpretation_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=1 LIMIT 1";
        $result = mysqli_query($connection, $retrieve_EEG_scoring_template);
        $EEG_scoring_template = mysqli_fetch_array($result);
        
        // Now, convert the $_POST array into the int values, 1) so that you can compare the int values to the key's int values, and 2) so that you can prepare to build the queries to insert the user's answers into the database.
        $EEG_user_int = [];
        foreach ($relevant_EEG_parameters as $parameter_name) {
            if ($parameter_name === 'normal_variants') {
                $normal_variant_array = $_POST['EEG_interpretation_s']['normal_variants'];
                foreach ($normal_variant_array as $index => $normal_variant_value) {
                    $normal_variant_array[$index] = lookup_int_value('normal_variants', $normal_variant_value, $connection);
                }
                $normal_variant_string = implode(",", $normal_variant_array);
                $EEG_user_int[$parameter_name] = "'".$normal_variant_string."'";
                $EEG_user_int['normal_variant_int_array'] = $normal_variant_array;
            } else if ($parameter_name === 'spikes') {
                $EEG_user_int[$parameter_name] = $user_spike_count;
            } else {
                $EEG_user_int[$parameter_name] = lookup_int_value($parameter_name, $_POST['EEG_interpretation_s'][$parameter_name], $connection);
            }
        }
        
        // Scoring for normal EEG parameters. Unset the 'spikes' element because you don't need to use it for scoring the normal EEG parameters. Once you implement the full-fledged version, you will also unset the 'seizures' element. Unset deletes an element from an array.
        $unset_spikes = array_search('spikes', $relevant_EEG_parameters);
        unset($relevant_EEG_parameters[$unset_spikes]);
        
        $score_EEG_parameters = [];
        foreach($relevant_EEG_parameters as $parameter_name) {
            if ($parameter_name === 'normal_variants') {
                $nv_common = array_intersect($EEG_user_int['normal_variant_int_array'], $EEG_key['normal_variants']);
                $score_EEG_parameters[$parameter_name] = count($nv_common)/max(count($EEG_key['normal_variants']), count($EEG_user_int['normal_variant_int_array'])) * $EEG_scoring_template[$parameter_name];
            } else if ($EEG_key_text[$parameter_name] === $_POST['EEG_interpretation_s'][$parameter_name]) {
                $score_EEG_parameters[$parameter_name] = 1 * $EEG_scoring_template[$parameter_name];
            } else {
                $score_EEG_parameters[$parameter_name] = 0;
            }
        }
        
        unset($EEG_user_int['normal_variant_int_array']);
        
        // Build query to insert user values for normal EEG parameters into the database.
        $query_user_interpretation = "INSERT INTO EEG_interpretation_s (EEG_interpretation_row, EEG_unique_id, user_ID, scoring_template, ";
        $query_user_interpretation .= implode(", ", array_keys($EEG_user_int));
        $query_user_interpretation .= ") VALUES ('NULL', ".$EEG_unique_id.", 2, 0, ";
        $query_user_interpretation .= implode(", ", $EEG_user_int);
        $query_user_interpretation .= ")";
        mysqli_query($connection, $query_user_interpretation);
        
        // Build query to insert score for normal EEG parameters into the database.
        $query_user_interp_score = "INSERT INTO EEG_interpretation_s (EEG_interpretation_row, EEG_unique_id, user_ID, scoring_template, ";
        $query_user_interp_score .= implode(", ", array_keys($score_EEG_parameters));
        $query_user_interp_score .= ") VALUES ('NULL', ".$EEG_unique_id.", 2, 1, ";
        $query_user_interp_score .= implode(", ", $score_EEG_parameters);
        $query_user_interp_score .= ")";
        mysqli_query($connection, $query_user_interp_score);
        
        $relevant_epi_parameters_array = ['EEG_epi_id', 'spike_lateralization', 'spike_localization', 'spike_prevalence', 'spike_modifier'];
        $relevant_epi_parameters = "EEG_epi_id, spike_lateralization, spike_localization, spike_prevalence, spike_modifier";
        
        // Retrieve the spikes parameter values and also scoring template from the database. These are retrieved as ints.
        if ($key_spike_count > 0) {
            
            $retrieve_EEG_epi_key = "SELECT ".$relevant_epi_parameters." FROM EEG_epi_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=0 LIMIT ".$key_spike_count;
            $result = mysqli_query($connection, $retrieve_EEG_epi_key);
            $EEG_epi_key = [];
            while ($row = mysqli_fetch_array($result)) {
                $EEG_epi_key[] = $row;
            }
            // You need to make the array for both $EEG_epi_key and $EEG_epi_scoring_template start counting from 1, because otherwise this gets really annoying and you can't use for loops that will iterate through both the user array and the key arrays synchronously; if one array starts indexing from 0 and the other array from 1, then it's asynchronous or you have to use subtraction to make the counter sync up, which is probably a worse solution.
            array_unshift($EEG_epi_key, null);
            unset($EEG_epi_key[0]);
            
            // Turn epi/spikes into text. The retrieval of the key creates an array indexed from 0, but converting $EEG_epi_text to start indexing its array from 1 makes building the necessary html easier.
            $EEG_epi_text = [];
            foreach ($EEG_epi_key as $spike => $parameters) {
                foreach($parameters as $parameter_name => $parameter_value) {
                    $EEG_epi_text[$spike][$parameter_name] = lookup_text_value($parameter_name, $parameter_value, $connection);
                }
            }
            
            $retrieve_EEG_epi_scoring_template = "SELECT ".$relevant_epi_parameters." FROM EEG_epi_s WHERE EEG_unique_id=".$EEG_unique_id." AND user_ID = 1 AND scoring_template=1 LIMIT ".$key_spike_count;
            $result = mysqli_query($connection, $retrieve_EEG_epi_scoring_template);
            $EEG_epi_scoring_template = [];
            while ($row = mysqli_fetch_array($result)) {
                $EEG_epi_scoring_template[] = $row;
            }
            array_unshift($EEG_epi_scoring_template, null);
            unset($EEG_epi_scoring_template[0]);
        }
        
        // Now, convert the user's spikes/epileptiform text values into int values, since you will be comparing the int values in order to generate a score.
        if ($user_spike_count > 0) {
            $epi_user_int = [];
            foreach ($_POST['EEG_epi_s'] as $spike => $parameters) {
                foreach($parameters as $parameter_name => $parameter_value) {
                    $epi_user_int[$spike][$parameter_name] = lookup_int_value($parameter_name, $parameter_value, $connection);
                }
            }
        }
        
        // Scoring spikes/epileptiform discharges:
        // Scenario 1: User enters zero spikes or epileptiform discharges.
        // Structure of $spike_scores: $spike_scores[spike_num] -> array of [key_spike_index => int, parameters_matched => int between 0 and 4, epi_score => the total number of points that the user gets for this spike, based on how many parameters matched.]
        $spike_scores = [];
        
        if ($user_spike_count === 0) {
            if ($key_spike_count === 0) {
                $spike_scores[1]['key_index'] = -1;
                $spike_scores[1]['matched_parameters'] = [];
                $spike_scores[1]['score'] = [];
            } else for ($i = 1; $i <= $key_spike_count; $i++) {
                $spike_scores[$i]['key_index'] = -1;
                $spike_scores[$i]['matched_parameters'] = [];
                $spike_scores[$i]['score'] = [];
                
                foreach($relevant_epi_parameters_array as $parameter_name) {
                    if ($parameter_name != 'EEG_epi_id') {
                        $spike_scores[$i]['full_score'] += $EEG_epi_scoring_template[$i][$parameter_name];
                    }
                }
            }
        }
        // Scenario 2: User_spike is at least 1. If the user has entered at least 1 spike, then you go through the matching algorithm.
        else {
            // If there are no spikes in the key, then you set the $spike_scores array such that a penalty gets assessed for each extraneous spike/epileptiform discharge that the user found.
            if ($key_spike_count === 0) {
                for ($i = 1; $i <= $user_spike_count; $i++) {
                    $spike_scores[$i]['key_index'] = -1;
                    $spike_scores[$i]['matched_parameters'] = [];
                    
                    $unset_epi_id = array_search('EEG_epi_id', $relevant_epi_parameters_array);
                    unset($relevant_epi_parameters_array[$unset_epi_id]);
                    
                    foreach($relevant_epi_parameters_array as $index => $parameter_name) {
                        if ($parameter_name === 'spike_lateralization') {
                            $spike_scores[$i]['score'][$parameter_name] = -5;
                        } else {
                            $spike_scores[$i]['score'][$parameter_name] = 0;
                        }
                    }
                    
                }
                
                for ($i = 1; $i <= $user_spike_count; $i++) {
                    $query_user_epi_values = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                    $query_user_epi_values .= implode(", ", array_keys($epi_user_int[$i]));
                    $query_user_epi_values .= ") VALUES ('NULL', -1, ".$EEG_unique_id.", 2, 0, ";
                    $query_user_epi_values .= implode(", ", $epi_user_int[$i]);
                    $query_user_epi_values .= ")";
                    //$message .= "<p style='color:magenta;'>The query to insert this user spike's values is: ".$query_user_epi_values."</p>";
                    mysqli_query($connection, $query_user_epi_values);

                    $query_user_epi_score = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                    $query_user_epi_score .= implode(", ", array_keys($spike_scores[$i]['score']));
                    $query_user_epi_score .= ") VALUES ('NULL', -1, ".$EEG_unique_id.", 2, 1, ";
                    $query_user_epi_score .= implode(", ", $spike_scores[$i]['score']);
                    $query_user_epi_score .= ")";
                    //$message .= "<p style='color:magenta;'>The query to insert this user spike's score is: ".$query_user_epi_score."</p>";
                    mysqli_query($connection, $query_user_epi_score);
                }
                
            } else {
                $copy_epi_user_int = $epi_user_int;
                $copy_EEG_epi_key = $EEG_epi_key;
                
                for ($i = 1; $i <= $user_spike_count; $i++) {
                    $spike_scores[$i]['key_index'] = -1;
                    $spike_scores[$i]['matched_parameters'] = [];
                    $spike_scores[$i]['score'] = [];
                }
                
                // This is the for loop that does the actual matching between user spikes and key spikes. It counts down the number of parameters that match, and during each iteration you compare each user_spike to each key_spike and see how many match. Then, at the end once you've gone through all the spikes, you store the ones that match the max_match (which is initially defined as the total number of relevant epi parameters minus 1), and reset all the other spikes for the next iteration of the loop.
                $used_key_indexes = [];
                for ($max_match = count($relevant_epi_parameters_array) - 1; $max_match > 0; $max_match--) {
                    
                    foreach($copy_epi_user_int as $user_spike_index => $user_parameters) {
                        foreach($copy_EEG_epi_key as $key_spike_index => $key_parameters) {
                            
                            $temp_array = [];
                            foreach($key_parameters as $parameter_name => $parameter_value) {
                                if ($parameter_name != 'EEG_epi_id') {
                                    // Check to see if the values of each parameter match up between the specific user_spike and key_spike that we're checking right now:
                                    if ($copy_epi_user_int[$user_spike_index][$parameter_name] === $copy_EEG_epi_key[$key_spike_index][$parameter_name]) {
                                        $temp_array[] = $parameter_name;
                                    }
                                }
                            }
                            
                            // Now, check to see how many matched parameters there are in $temp_array. If count($temp_array) > count($spike_scores[$user_spike_index]['matched_parameters']), then you set $temp array equal to $spike_scores[$user_spike_index]['matched_parameters'] and you also set $spike_scores[$user_spike_index]['key_index'] = $key_spike_index, because this new $key_spike that you just found is better than what was stored previously.
                            // You could have a check here to make sure that $temp_array *includes* laterality, because if you could make an argument that if the laterality doesn't match, then even if the other three spike parameters match, then you shouldn't give any credit. Can bring that up to discuss.
                            if (count($temp_array) > count($spike_scores[$user_spike_index]['matched_parameters'])) {
                                $spike_scores[$user_spike_index]['matched_parameters'] = $temp_array;
                                $spike_scores[$user_spike_index]['key_index'] = $key_spike_index;
                            }
                        }
                    }
                    
                    // Here, once each user spike has been compared against each key spike, you check to see if count['matched_parameters'] equals $max_match, which initially starts out at 4 (all parameters match). If the user spike does have max_match, then you remove both the user and the key spike from the array.
                    
                    foreach($spike_scores as $user_spike_index => $spike_score) {
                        
                        if (count($spike_scores[$user_spike_index]['matched_parameters']) === $max_match && !in_array($spike_scores[$user_spike_index]['key_index'], $used_key_indexes)) {
                            $linked_key_spike_index = $spike_scores[$user_spike_index]['key_index'];
                            $used_key_indexes[$user_spike_index] = $linked_key_spike_index;
                            $spike_scores[$user_spike_index]['EEG_epi_id'] = $copy_EEG_epi_key[$linked_key_spike_index]['EEG_epi_id'];
                            unset($copy_epi_user_int[$user_spike_index]);
                            unset($copy_EEG_epi_key[$linked_key_spike_index]);
                        }
                        // For this else if, you have to check both conditions because you want to reset all the user spikes that *didn't* meet the max_match parameters (where the number of matched parameters was less then the max_match), but you also want to make sure that any spikes that *did* meet the max_match but were not caught in the first if loop prior to this are again reset so that they can participate in matching again.
                        else if (count($spike_scores[$user_spike_index]['matched_parameters']) < $max_match || (count($spike_scores[$user_spike_index]['matched_parameters']) === $max_match && in_array($spike_scores[$user_spike_index]['key_index'], $used_key_indexes))) {
                            $spike_scores[$user_spike_index]['key_index'] = -1;
                            $spike_scores[$user_spike_index]['matched_parameters'] = [];
                            $spike_scores[$user_spike_index]['score'] = [];
                        }
                    }
                }
                
                // Now that you've matched all the user and key spikes as best possible, you need to calculate the scores associated with each matched user/key spike pair. You are going to store the score in the spike_score template.
                foreach($spike_scores as $spike_num => $parameters) {
                    
                    $unset_epi_id = array_search('EEG_epi_id', $relevant_epi_parameters_array);
                    unset($relevant_epi_parameters_array[$unset_epi_id]);
                    
                    // check to see if the spike has a key index of -1; if it does NOT, then that means it has been matched to a key spike.
                    if ($spike_scores[$spike_num]['key_index'] != -1) {
                        // if the user spike has been matched with a key spike, then set the score to an empty array, because you are going to store which parameters matched and what the score is for getting each matched parameter correct.
                        $spike_scores[$spike_num]['score'] = [];
                        
                        $EEG_epi_id = $spike_scores[$spike_num]['EEG_epi_id'];
                        
                        // Find the index of the element in EEG epi scoring template that matches the EEG epi id, and then cycle through that element's scoring template for each of the individual parameters.
                        foreach($EEG_epi_scoring_template as $spike_index => $parameters) {
                            if ($EEG_epi_scoring_template[$spike_index]['EEG_epi_id'] === $EEG_epi_id) {
                                $scoring_template_index = $spike_index;
                            }
                        }
                        
                        foreach($relevant_epi_parameters_array as $index => $parameter_name) {
                            $spike_scores[$spike_num]['full_score'] += $EEG_epi_scoring_template[$scoring_template_index][$parameter_name];
                            if (in_array($parameter_name, $spike_scores[$spike_num]['matched_parameters'])) {
                                $spike_scores[$spike_num]['score'][$parameter_name] = $EEG_epi_scoring_template[$scoring_template_index][$parameter_name];
                            } else {
                                $spike_scores[$spike_num]['score'][$parameter_name] = 0;
                            }
                        }
                        
                    } else {
                        // The penalty here for finding extraneous spikes/epileptiform discharges is -5. Therefore, set the score for all unmatched user spikes to equal this penalty. Later, you can figure out some other type of penalty, if something else works better.
                        
                        foreach($relevant_epi_parameters_array as $index => $parameter_name) {
                            if ($parameter_name === 'spike_lateralization') {
                                $spike_scores[$spike_num]['score'][$parameter_name] = -5;
                            } else {
                                $spike_scores[$spike_num]['score'][$parameter_name] = 0;
                            }
                        }
                    }
                }
                
                // $message .= "<p>This is the epi user int array: <pre>".print_r($epi_user_int, true)."</pre></p>";
                
                // $message .= "<p style='color:blue;'>This is the updated spike scores array which includes the score for each matched spike: ";
                // $message .="<pre>".print_r($spike_scores, true)."</pre></p>";
                
                // Section to build queries to insert user epi findings into database. This requires using both the epi_user_int table as well as the spike_scores table.
                // This loop is going to have to get moved to after the scores for the spikes are calculated.
                for ($i = 1; $i <= $user_spike_count; $i++) {
                    if ($spike_scores[$i]['key_index'] != -1) {
                        $query_user_epi_values = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                        $query_user_epi_values .= implode(", ", array_keys($epi_user_int[$i]));
                        $query_user_epi_values .= ") VALUES ('NULL', ".$spike_scores[$i]['EEG_epi_id'].", ".$EEG_unique_id.", 2, 0, ";
                        $query_user_epi_values .= implode(", ", $epi_user_int[$i]);
                        $query_user_epi_values .= ")";
                        mysqli_query($connection, $query_user_epi_values);
                        //$message .= "<p style='color:purple; font-weight:bold;'>The query to enter this matched user spike into the database is: ".$query_user_epi_values."</p>";
                        
                        $query_user_epi_score = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                        $query_user_epi_score .= implode(", ", array_keys($spike_scores[$i]['score']));
                        $query_user_epi_score .= ") VALUES ('NULL', ".$spike_scores[$i]['EEG_epi_id'].", ".$EEG_unique_id.", 2, 1, ";
                        $query_user_epi_score .= implode(", ", $spike_scores[$i]['score']);
                        $query_user_epi_score .= ")";
                        mysqli_query($connection, $query_user_epi_score);
                        //$message .= "<p style='color:purple; font-weight:bold;'>The query to enter this matched user spike's score into the database is: ".$query_user_epi_score."</p>";
                        
                    } else {
                        $query_user_epi_values = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                        $query_user_epi_values .= implode(", ", array_keys($epi_user_int[$i]));
                        $query_user_epi_values .= ") VALUES ('NULL', -1, ".$EEG_unique_id.", 2, 0, ";
                        $query_user_epi_values .= implode(", ", $epi_user_int[$i]);
                        $query_user_epi_values .= ")";
                        mysqli_query($connection, $query_user_epi_values);
                        //$message .= "<p style='color:purple; font-weight:bold;'>The query to enter this unmatched user spike into the database is: ".$query_user_epi_values."</p>";
                        
                        $query_user_epi_score = "INSERT INTO EEG_epi_s (EEG_epi_row, EEG_epi_id, EEG_unique_id, user_ID, scoring_template, ";
                        $query_user_epi_score .= implode(", ", array_keys($spike_scores[$i]['score']));
                        $query_user_epi_score .= ") VALUES ('NULL', -1, ".$EEG_unique_id.", 2, 1, ";
                        $query_user_epi_score .= implode(", ", $spike_scores[$i]['score']);
                        $query_user_epi_score .= ")";
                        mysqli_query($connection, $query_user_epi_score);
                        //$message .= "<p style='color:purple; font-weight:bold;'>The query to enter this unmatched user spike's score into the database is: ".$query_user_epi_score."</p>";
    
                    }
                }
            }
        }

        // Section to populate the epi html section:
        $padded_key_epi_array = [];
        $padded_user_epi_array = [];
        $padded_score_epi_array = [];
        
        $epi_html = "<div class='container'>";
        $epi_html_header = "<div class='row'><div class='col-sm'><h4>Your findings</h4></div><div class='col-sm'><h4>Correct findings</h4></div><div class='col-sm'><h4>Score</h4></div></div>";
        
        if ($user_spike_count === 0) {
            if ($key_spike_count === 0) {
                $padded_user_epi_array[1] = "<p>You did not enter any spikes/epileptiform findings for this EEG.</p>";
                $padded_key_epi_array[1] = "<p>There are no spikes/epileptiform findings for this EEG.</p>";
                $padded_score_epi_array[1] = "<p>Total score: 0</p>";
            }
            else for ($i = 1; $i <= $key_spike_count; $i++) {
                $padded_user_epi_array[$i] = "<p></p>";
                
                $padded_key_epi_array[$i] = "<p>Spike lateralization: ".$EEG_epi_text[$i]['spike_lateralization']."</p>";
                $padded_key_epi_array[$i] .= "<p>Spike localization: ".$EEG_epi_text[$i]['spike_localization']."</p>";
                $padded_key_epi_array[$i] .= "<p>Spike prevalence: ".$EEG_epi_text[$i]['spike_prevalence']."</p>";
                $padded_key_epi_array[$i] .= "<p>Spike modifier: ".$EEG_epi_text[$i]['spike_modifier']."</p>";
                
                $padded_score_epi_array[$i] = "<p>Score: 0/".$spike_scores[$i]['full_score']."</p>";
            }
        } else {
            if ($key_spike_count === 0) {
                for ($i = 1; $i <= $user_spike_count; $i++) {
                    $padded_user_epi_array[$i] = "<p style='color:red;'>Spike lateralization: ".$_POST['EEG_epi_s'][$i]['spike_lateralization']."</p>";
                    $padded_user_epi_array[$i] .= "<p style='color:red;'>Spike localization: ".$_POST['EEG_epi_s'][$i]['spike_localization']."</p>";
                    $padded_user_epi_array[$i] .= "<p style='color:red;'>Spike prevalence: ".$_POST['EEG_epi_s'][$i]['spike_prevalence']."</p>";
                    $padded_user_epi_array[$i] .= "<p style='color:red;'>Spike modifier: ".$_POST['EEG_epi_s'][$i]['spike_modifier']."</p>";
                    
                    $padded_key_epi_array[$i] = "<p></p>";
                    
                    $padded_score_epi_array[$i] = "<p>Score: The penalty for finding extraneous spikes is: ".array_sum($spike_scores[$i]['score'])."</p>";
                }
            } else {
                $unmatched_user_spikes = array_keys($copy_epi_user_int);
                $unmatched_key_spikes = array_keys($copy_EEG_epi_key);
                
                $i = 1;
                // First, iterate through $used_key_indexes because this contains all of the matched user/key spikes.
                foreach($used_key_indexes as $user_index => $key_index) {
                    if (in_array('spike_lateralization', $spike_scores[$user_index]['matched_parameters'])) {
                        $padded_user_epi_array[$i] = "<p style='color:green; font-weight: bold;'>Spike lateralization: ".$_POST['EEG_epi_s'][$user_index]['spike_lateralization']."</p>";
                        $padded_key_epi_array[$i] = "<p style='color:green; font-weight: bold;'>Spike lateralization: ".$EEG_epi_text[$key_index]['spike_lateralization']."</p>";
                    } else {
                        $padded_user_epi_array[$i] = "<p>Spike lateralization: ".$_POST['EEG_epi_s'][$user_index]['spike_lateralization']."</p>";
                        $padded_key_epi_array[$i] = "<p>Spike lateralization: ".$EEG_epi_text[$key_index]['spike_lateralization']."</p>";
                    }
                    
                    if (in_array('spike_localization', $spike_scores[$user_index]['matched_parameters'])) {
                        $padded_user_epi_array[$i] .= "<p style='color:green; font-weight: bold;'>Spike localization: ".$_POST['EEG_epi_s'][$user_index]['spike_localization']."</p>";
                        $padded_key_epi_array[$i] .= "<p style='color:green; font-weight: bold;'>Spike localization: ".$EEG_epi_text[$key_index]['spike_localization']."</p>";
                    } else {
                        $padded_user_epi_array[$i] .= "<p>Spike localization: ".$_POST['EEG_epi_s'][$user_index]['spike_localization']."</p>";
                        $padded_key_epi_array[$i] .= "<p>Spike localization: ".$EEG_epi_text[$key_index]['spike_localization']."</p>";
                    }
                    
                    if (in_array('spike_prevalence', $spike_scores[$user_index]['matched_parameters'])) {
                        $padded_user_epi_array[$i] .= "<p style='color:green; font-weight: bold;'>Spike prevalence: ".$_POST['EEG_epi_s'][$user_index]['spike_prevalence']."</p>";
                        $padded_key_epi_array[$i] .= "<p style='color:green; font-weight: bold;'>Spike prevalence: ".$EEG_epi_text[$key_index]['spike_prevalence']."</p>";
                    } else {
                        $padded_user_epi_array[$i] .= "<p>Spike prevalence: ".$_POST['EEG_epi_s'][$user_index]['spike_prevalence']."</p>";
                        $padded_key_epi_array[$i] .= "<p>Spike prevalence: ".$EEG_epi_text[$key_index]['spike_prevalence']."</p>";
                    }
                    
                    if (in_array('spike_modifier', $spike_scores[$user_index]['matched_parameters'])) {
                        $padded_user_epi_array[$i] .= "<p style='color:green; font-weight: bold;'>Spike modifier: ".$_POST['EEG_epi_s'][$user_index]['spike_modifier']."</p>";
                        $padded_key_epi_array[$i] .= "<p style='color:green; font-weight: bold;'>Spike modifier: ".$EEG_epi_text[$key_index]['spike_modifier']."</p>";
                    } else {
                        $padded_user_epi_array[$i] .= "<p>Spike modifier: ".$_POST['EEG_epi_s'][$user_index]['spike_modifier']."</p>";
                        $padded_key_epi_array[$i] .= "<p>Spike modifier: ".$EEG_epi_text[$key_index]['spike_modifier']."</p>";
                    }
                    
                    $padded_score_epi_array[$i] = "<p>Score: ".array_sum($spike_scores[$user_index]['score'])."/".$spike_scores[$user_index]['full_score']."</p>";
                    
                    $i++;
                }
                
                // Second, iterate through $unmatched_user_spikes to display the unmatched user spikes, and pad the key epi array with nothing.
                foreach($unmatched_user_spikes as $index => $unused_user_index) {
                    $padded_user_epi_array[$i] = "<p style='color:red;'>Spike lateralization: ".$_POST['EEG_epi_s'][$unused_user_index]['spike_lateralization']."</p>";
                    $padded_user_epi_array[$i] .= "<p style='color:red;'>Spike localization: ".$_POST['EEG_epi_s'][$unused_user_index]['spike_localization']."</p>";
                    $padded_user_epi_array[$i] .= "<p style='color:red;'>Spike prevalence: ".$_POST['EEG_epi_s'][$unused_user_index]['spike_prevalence']."</p>";
                    $padded_user_epi_array[$i] .= "<p style='color:red;'>Spike modifier: ".$_POST['EEG_epi_s'][$unused_user_index]['spike_modifier']."</p>";
                    
                    $padded_key_epi_array[$i] = "<p></p>";
                    
                    $padded_score_epi_array[$i] = "<p>Score: The penalty for finding extraneous spikes is: ".array_sum($spike_scores[$unused_user_index]['score'])."</p>";
                    
                    $i++;
                }
                
                // Third, iterate through $unmatched_key_spikes to display the unmatched key spikes, and pad the user epi array with nothing.
                foreach($unmatched_key_spikes as $index => $unused_key_index) {
                    $padded_user_epi_array[$i] = "<p></p>";
                    
                    $padded_key_epi_array[$i] = "<p>Spike lateralization: ".$EEG_epi_text[$unused_key_index]['spike_lateralization']."</p>";
                    $padded_key_epi_array[$i] .= "<p>Spike localization: ".$EEG_epi_text[$unused_key_index]['spike_localization']."</p>";
                    $padded_key_epi_array[$i] .= "<p>Spike prevalence: ".$EEG_epi_text[$unused_key_index]['spike_prevalence']."</p>";
                    $padded_key_epi_array[$i] .= "<p>Spike modifier: ".$EEG_epi_text[$unused_key_index]['spike_modifier']."</p>";
                    
                    $unused_key_spike_score = 0;
                    
                    foreach($relevant_epi_parameters_array as $index => $parameter_name) {
                        $unused_key_spike_score += $EEG_epi_scoring_template[$unused_key_index][$parameter_name];
                    }
                    
                    $padded_score_epi_array[$i] = "<p>Score: 0/".$unused_key_spike_score."</p>"; // here, you need to calculate the denominator and display it so the user knows how many points they missed out on.
                    
                    $i++;
                }
            }
        }
        
        $epi_html .= $epi_html_header;
        for ($i = 1; $i <= count($padded_key_epi_array); $i++) {
            $epi_html .= "<hr><div class='row'>";
            $epi_html .= "<div class='col-sm'>".$padded_user_epi_array[$i]."</div>";
            $epi_html .= "<div class='col-sm'>".$padded_key_epi_array[$i]."</div>";
            $epi_html .= "<div class='col-sm'>".$padded_score_epi_array[$i]."</div>";
            $epi_html .= "</div>";
        }
        $epi_html .= "</div><br>";
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
        <h1>EEG <?php echo $EEG_unique_id; ?></h1>
        <p>You successfully read a EEG record.</p>
    
        <div id="message"><?php 
        echo $message;
        ?></div>
        
        <div id="EEG findings">
            <h3>EEG findings</h3>
            <h5>PDR value</h5>
            <p>Your answer: <?php echo $_POST['EEG_interpretation_s']['pdr_value'] ?></p>
            <p>Correct answer: <?php echo $EEG_key_text['pdr_value'] ?></p>
            <p>Score: <?php echo $score_EEG_parameters['pdr_value'] ?>/<?php echo $EEG_scoring_template['pdr_value'] ?></p>
            
            <h5>Normal variants</h5>
            <p>Your answer: <?php echo implode(", ", $_POST['EEG_interpretation_s']['normal_variants']); ?></p>
            <p>Correct answer: <?php echo implode(", ", $EEG_key_text['normal_variants']); ?></p>
            <p>Score: <?php if (is_int($score_EEG_parameters['normal_variants'])) { echo $score_EEG_parameters['normal_variants']; } else { echo number_format($score_EEG_parameters['normal_variants'], 2); } ?>/<?php echo $EEG_scoring_template['normal_variants'] ?></p>
        </div>
        
        <div id="spikes">
            <h3>Spikes/Epileptiform findings</h3>
            <?php echo $epi_html; ?>
        </div>

        <div id="Overall assessment">
            <h3>Overall Assessment</h3>
            <br>
            <h5>Overall Assessment</h5>
            <p>Your answer: <?php echo $_POST['EEG_interpretation_s']['abn_summary'] ?></p>
            <p>Correct answer: <?php echo $EEG_key_text['abn_summary'] ?></p>
            <p>Score: <?php echo $score_EEG_parameters['abn_summary'] ?>/<?php echo $EEG_scoring_template['abn_summary'] ?></p>
            <br>
            <h5>Interpretation</h5>
            <p>Your answer: <?php echo $_POST['EEG_interpretation_s']['interpretation'] ?></p>
            <p>Correct answer: <?php echo $EEG_key_text['interpretation'] ?></p>
            <p>Score: <?php echo $score_EEG_parameters['interpretation'] ?>/<?php echo $EEG_scoring_template['interpretation'] ?></p>
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