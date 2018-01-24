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
        
        .radio-inline {
            margin-left: 20px;
        }
        
        .btn {
            margin:10px auto;
            display:block;
        }
        
        #read_eeg {
            margin-bottom: 20px;
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
        
        <form action="report_eeg_s.php" method="post" id="EEG_interpretation_form">
        <section>
            <h3>Background information</h3>
            
            <div class="form-group row">
                <label for="EEG_indications" class="col-sm-2 col-form-label">EEG indications</label>
                <div class="col-sm-10">
                    <textarea class="form-control" id="EEG_indications" name="EEG_interpretation_s[EEG_indications]" rows="3"></textarea>
                </div>
            </div>
            
            <div class="form-group row">
                <label for="medications" class="col-sm-2 col-form-label">Current medications</label>
                <div class="col-sm-10">
                    <input class="form-control" type="text" id="medications" name="EEG_interpretation_s[medications]">
                </div>
            </div>
        </section>
        
        <section>
            <h3>EEG findings</h3>
            
            <div class="form-group row">
                <label for="pdr_value" class="col-sm-2 col-form-label">PDR</label>
                    <select class="form-control col-sm-7" id="pdr_value" name="EEG_interpretation_s[pdr_value]">
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
                <label for="pdr_value_score" class="col-sm-2 col-form-label">PDR score</label>
                <div class="col-sm-1">
                    <input class="form-control" type="number" min=0 name="EEG_interpretation_score[pdr_value]">
                </div>
            </div>
                
            <!-- Could do radio buttons instead of select multiple from a list as well. If you are going to allow the user to select multiple values, will have to figure out how to enter these as a multivalued field. Will plan to use implode/explode to store multiple values as a comma delimited string. -->
            <div class="form-group row">
                <label for="normal_variants" class="col-sm-2 col-form-label">Normal variants</label>
                    <select class="form-control col-sm-7" id="normal_variants" name="EEG_interpretation_s[normal_variants]" required>
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
                <label for="normal_variants_score" class="col-sm-2 col-form-label">Normal variants score</label>
                <div class="col-sm-1">
                    <input class="form-control" type="number" min=0 name="EEG_interpretation_score[normal_variants]">
                </div>
            </div>
        
        <!-- This is the beginning of the 3 subtables that are part of the EEG interpretation: EEG_slow, EEG_sz, and EEG_epi.
        The user can enter as many entries as they like tof slow, sz or epi 'findings'; they can add another sub-form for
        each additional entry/finding, and they can also remove it if they decide that they actually don't want it.
        You are going to base this adding/deleting fields dynamically from this website: http://formvalidation.io/examples/adding-dynamic-field/-->
        
        <section>
            <h3>Spikes/epileptiform findings</h3>
            <br>
            <label class="radio-inline"><input type="radio" name="spike_present" value="spike_absent" id="spike_absent" onclick="check_spike()" checked required> No spikes or epileptiform discharges present </label>
            <label class="radio-inline"><input type="radio" name="spike_present" value="spike_present" id="spike_present" onclick="check_spike()" required> Spike(s) or epileptiform discharge(s) present </label>
            <br>
            <fieldset id = "spike1" class="spike" style="display:none">
            <h4 class="spike_title">Spike 1</h4>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Spike lateralization</label>
                    <select class="form-control col-sm-7 spike_lateralization" name="EEG_epi_s[1][spike_lateralization]">
                        <option>bilateral R>L</option>
                        <option>bilateral L>R</option>
                        <option>left</option>
                        <option>right</option>
                        <option>vertex</option>
                        <option>bilateral L=R</option>
                    </select>
                <label class="col-sm-2 col-form-label">Spike lateralization score</label>
                <div class="col-sm-1">
                    <input class="form-control spike_lateralization_score" type="number" min=0 name="EEG_epi_score[1][spike_lateralization]">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Spike localization</label>
                    <select class="form-control col-sm-7 spike_localization" name="EEG_epi_s[1][spike_localization]">
                        <option>generalized</option>
                        <option>frontal</option>
                        <option>temporal</option>
                        <option>parietal</option>
                        <option>occipital</option>
                        <option>central</option>
                    </select>
                <label class="col-sm-2 col-form-label">Spike localization score</label>
                <div class="col-sm-1">
                    <input class="form-control spike_localization_score" type="number" min=0 name="EEG_epi_score[1][spike_localization]">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Spike prevalence</label>
                    <select class="form-control col-sm-7 spike_prevalence" name="EEG_epi_s[1][spike_prevalence]">
                        <option>continuous</option>
                        <option>every few seconds</option>
                        <option>every few minutes</option>
                        <option>rare</option>
                    </select>
                <label class="col-sm-2 col-form-label">Spike prevalence score</label>
                <div class="col-sm-1">
                    <input class="form-control spike_prevalence_score" type="number" min=0 name="EEG_epi_score[1][spike_prevalence]">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Spike modifier</label>
                    <select class="form-control col-sm-7 spike_modifier" name="EEG_epi_s[1][spike_modifier]">
                        <option>with stimulation</option>
                        <option>periodic</option>
                        <option>low amplitude</option>
                        <option>high amplitude</option>
                        <option>polyspike</option>
                        <option>triphasic</option>
                        <option>sleep augmented</option>
                    </select>
                <label class="col-sm-2 col-form-label">Spike modifier score</label>
                <div class="col-sm-1">
                    <input class="form-control spike_modifier_score" type="number" min=0 name="EEG_epi_score[1][spike_modifier]">
                </div>
            </div>
            </fieldset>
            <div class="flex-center spike">
                <button id="addMoreSpike" class="btn btn-info spike" style="display:none">Add another spike</button>
            </div>
        </section>
        <br>
        <section>
            <h3>Overall Assessment</h3>
        <div class="form-group row">
            <label for="abn_summary" class="col-sm-2 col-form-label">Overall assessment</label>
                <select class="form-control col-sm-7" id="abn_summary" name="EEG_interpretation_s[abn_summary]">
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
                <label for="abn_summary_score" class="col-sm-2 col-form-label">Overall assessment score</label>
                <div class="col-sm-1">
                    <input class="form-control" type="number" min=0 name="EEG_interpretation_score[abn_summary]">
                </div>
        </div>
        
        <div class="form-group row">
            <label for="interpretation" class="col-sm-2 col-form-label">Interpretation</label>
                <select class="form-control col-sm-7" id="interpretation" name="EEG_interpretation_s[interpretation]">
                    <option>indicate diffuse encephalopathy</option>
                    <option>indicate cortical dysfunction</option>
                    <option>indicate cortical irritability</option>
                    <option>are abnormal but not definitively epileptic</option>
                    <option>suggest epilepsy</option>
                    <option>suggest NES</option>
                    <option>may indicate a sleep disorder</option>
                </select>
                <label for="interpretation_score" class="col-sm-2 col-form-label">Interpretation score</label>
                <div class="col-sm-1">
                    <input class="form-control" type="number" min=0 name="EEG_interpretation_score[interpretation]">
                </div>
        </div>
        </section>
        <input type="submit" class="btn btn-info" name="create_eeg" value="Create EEG" id="create_eeg">
        </form>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    
    <script>
        function check_spike() {
            if (document.getElementById('spike_present').checked) {
                $(".spike").css('display', 'block');
            }
            else $(".spike").css('display', 'none');
        }
    </script>
    
    <script>
        $(function() {
            var spike_count = 1;
            $("#addMoreSpike").click(function(e) {
                e.preventDefault();
                spike_count++;
                console.log(spike_count);
                $("fieldset:last").after("<fieldset id = 'spike"+spike_count+"' class='spike' style='display:block'> <h4 class='spike_title'>Spike "+spike_count+"</h4> <div class='flex-center'> <button class='removeSpike btn btn-danger'>Remove spike</button> </div> <div class='form-group row'> <label class='col-sm-2 col-form-label'>Spike lateralization</label> <select class='form-control col-sm-7 spike_lateralization' name='EEG_epi_s["+ spike_count +"][spike_lateralization]'> <option>bilateral R>L</option> <option>bilateral L>R</option> <option>left</option> <option>right</option> <option>vertex</option> <option>bilateral L=R</option> </select> <label class='col-sm-2 col-form-label'>Spike lateralization score</label> <div class='col-sm-1'> <input class='form-control spike_lateralization_score' type='number' min=0 name='EEG_epi_score["+ spike_count +"][spike_lateralization]'> </div> </div> <div class='form-group row'> <label class='col-sm-2 col-form-label'>Spike localization</label> <select class='form-control col-sm-7 spike_localization' name='EEG_epi_s["+ spike_count +"][spike_localization]'> <option>generalized</option> <option>frontal</option> <option>temporal</option> <option>parietal</option> <option>occipital</option> <option>central</option> </select> <label class='col-sm-2 col-form-label'>Spike localization score</label> <div class='col-sm-1'> <input class='form-control spike_localization_score' type='number' min=0 name='EEG_epi_score["+ spike_count +"][spike_localization]'> </div> </div> <div class='form-group row'> <label class='col-sm-2 col-form-label'>Spike prevalence</label> <select class='form-control col-sm-7 spike_prevalence' name='EEG_epi_s["+ spike_count +"][spike_prevalence]'> <option>continuous</option> <option>every few seconds</option> <option>every few minutes</option> <option>rare</option> </select> <label class='col-sm-2 col-form-label'>Spike prevalence score</label> <div class='col-sm-1'> <input class='form-control spike_prevalence_score' type='number' min=0 name='EEG_epi_score["+ spike_count +"][spike_prevalence]'> </div> </div> <div class='form-group row'> <label class='col-sm-2 col-form-label'>Spike modifier</label> <select class='form-control col-sm-7 spike_modifier' name='EEG_epi_s["+ spike_count +"][spike_modifier]'> <option>with stimulation</option> <option>periodic</option> <option>low amplitude</option> <option>high amplitude</option> <option>polyspike</option> <option>triphasic</option> <option>sleep augmented</option> </select> <label class='col-sm-2 col-form-label'>Spike modifier score</label> <div class='col-sm-1'> <input class='form-control spike_modifier_score' type='number' min=0 name='EEG_epi_score["+ spike_count +"][spike_modifier]'> </div> </div> </fieldset>");
            });
            
            $(document).on("click", ".removeSpike", function(e) {
               e.preventDefault();
               spike_count--;
               
               //$(this).closest("fieldset").remove();
               var remove_spike_id = $(this).closest("fieldset").prop("id");
               $("#"+remove_spike_id).remove();
               
               $("fieldset").each(function(index) {
                   $(this).attr("id", "spike"+(index+1));
               });
               
               $(".spike_title").each(function(index) {
                  $(this).text("Spike " + (index+1)); 
               });
               
               $.each(["spike_lateralization", "spike_localization", "spike_prevalence", "spike_modifier"], function(index, parameter_name) {
                   $("."+parameter_name).each(function(i) {
                       $(this).attr("name", "EEG_epi_s["+(i+1)+"]["+parameter_name+"]"); 
                   });
                   $("."+parameter_name+"_score").each(function(i) {
                      $(this).attr("name", "EEG_epi_score["+(i+1)+"]["+parameter_name+"]"); 
                   });
               });
               
            });
            
        });
    </script>
    
  </body>
</html>