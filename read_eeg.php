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
                  <input class="form-control" type="text" placeholder="Username" aria-label="Username">
                  <input class="form-control" type="text" placeholder="Password" aria-label="Password">
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
        <section>
            <h3>Background information</h3>
            <div class="form-group row">
                <label for="staticEEGID" class="col-sm-3 col-form-label">EEG number</label>
                <div class="col-sm-8">
                    <input class="form-control" type="text" readonly id="staticEEGID" value="3">
                </div>
                <!--This will have to be read from the database at some point; basically, the page is going to have to keep track of which EEG the person is interpreting, and then fetch the EEG # and also the clinical history, etc. from the database which stores all th einfo about each EEG. -->
            </div>
            
            <div class="form-group row">
                <label for="staticEEGIndications" class="col-sm-3 col-form-label">EEG indications</label>
                <div class="col-sm-8">
                    <textarea class="form-control" readonly id="staticEEGIndications" rows="3">34 yo man with a hx of possible convulsions in his youth, then recurring at age 51. Recently with TBI followed by memory problems and staring spells.</textarea>
                </div>
            </div>
            
            <div class="form-group row">
                <label for="staticCurrentMeds" class="col-sm-3 col-form-label">Current medications</label>
                <div class="col-sm-8">
                    <input class="form-control" type="text" readonly id="staticCurrentMeds" value="levetiracetam, baclofen">
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
                <label for="backgroundOrganization" class="col-sm-3 col-form-label">Background organization</label>
                    <select class="form-control col-sm-8" id="backgroundOrganization">
                        <option>good, with appropriate posterior dominance</option>
                        <option>fair, with anterior dominance and/or excess slower frequencies</option>
                        <option>poor, with mixed frequencies without organization</option>
                        <option>not applicable</option>
                    </select>
            </div>
            
            <div class="form-group row">
                <label for="backgroundReactivity" class="col-sm-3 col-form-label">Background reactivity</label>
                    <select class="form-control col-sm-8" id="backgroundReactivity">
                        <option>good (reactive to eye closure)</option>
                        <option>fair (some change with eye closure)</option>
                        <option>poor (changes seen with stimulation only)</option>
                        <option>absent (no reactivity)</option>
                    </select>
            </div>
            
            <div class="form-group row">
                <label for="PDR" class="col-sm-3 col-form-label">PDR</label>
                    <select class="form-control col-sm-8" id="PDR">
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
            
            <div class="form-group row">
                <label for="PDRSymmetry" class="col-sm-3 col-form-label">PDR symmetry</label>
                    <select class="form-control col-sm-8" id="PDRSymmetry">
                        <option>markedly asymmetric (right side more than 50% slower or attenuated relative to left)</option>
                        <option>mildly asymmetric (right side less than 50% slower or attenuated relative to left)</option>
                        <option>R=L symmetric</option>
                        <option>mildly asymmetric (left side less than 50% slower or attenuated relative to right)</option>
                        <option>markedly asymmetric (left side more than 50% slower or attenuated relative to right)</option>
                    </select>
            </div>
            
            <div class="form-group row">
                <label for="PDRAmplitude" class="col-sm-3 col-form-label">PDR amplitude</label>
                    <select class="form-control col-sm-8" id="PDRAmplitude">
                        <option>silent (&lt;=2 uV)</option>
                        <option>low (&lt;20 uV)</option>
                        <option>normal (20-100 uV)</option>
                        <option>high (100-150 uV)</option>
                        <option>excessively high (&gt;150 uV)</option>
                    </select>
            </div>
            
            <div class="form-group row">
                <label for="beta" class="col-sm-3 col-form-label">Beta activity</label>
                    <select class="form-control col-sm-8" id="beta">
                        <option>normal</option>
                        <option>absent</option>
                        <option>excessive</option>
                    </select>
            </div>
                
            <!-- Could do radio buttons instead of select multiple from a list as well. -->
            <div class="form-group row">
                <label for="normalVariants" class="col-sm-3 col-form-label">Normal variants</label>
                    <select multiple class="form-control col-sm-8" id="normalVariants">
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
                
            <div class="form-group row">
                <label for="artifactType" class="col-sm-3 col-form-label">Artifact present</label>
                    <select multiple class="form-control col-sm-8" id="artifactType">
                        <option>eye</option>
                        <option>tongue</option>
                        <option>electrode</option>
                        <option>EKG</option>
                        <option>pulse</option>
                        <option>movement</option>
                        <option>sweat</option>
                        <option>breathing</option>
                        <option>power line&#47;60 Hz</option>
                    </select>
            </div>
                
            <div class="form-group row">
                <label for="artifactSeverity" class="col-sm-3 col-form-label">Artifact severity</label>
                    <select class="form-control col-sm-8" id="artifactSeverity">
                        <option>mild</option>
                        <option>moderate</option>
                        <option>severe</option>
                    </select>
            </div>
                
            <!--For HV, photostim, and EKG, you would have to find some way to graph these as well. EKG I think comes in the data set, but HV and PS may not be marked in the EDF data.-->
            <div class="form-group row">
                <label for="hv" class="col-sm-3 col-form-label">Hyperventilation</label>
                    <select class="form-control col-sm-8" id="hv">
                        <option>was not done</option>
                        <option>demonstrated normal symmetric buildup of slowing</option>
                        <option>demonstrated excessive focal left slowing</option>
                        <option>demonstrated excessive focal right slowing</option>
                        <option>demonstrated no changes</option>
                    </select>
            </div>
                
            <div class="form-group row">
                <label for="photostim" class="col-sm-3 col-form-label">Photostimulation</label>
                    <select class="form-control col-sm-8" id="photostim">
                        <option>was not done</option>
                        <option>demonstrated no/minimal driving</option>                            <option>demonstrated moderate driving</option>
                        <option>demonstrated prominent driving</option>
                        <option>evoked epileptiform discharges</option>
                    </select>
            </div>
        </section>
        
        <!-- This is the beginning of the 3 subtables that are part of the EEG interpretation: EEG_slow, EEG_sz, and EEG_epi.
        The user can enter as many entries as they like tof slow, sz or epi 'findings'; they can add another sub-form for
        each additional entry/finding, and they can also remove it if they decide that they actually don't want it.
        You are going to base this adding/deleting fields dynamically from this website: http://formvalidation.io/examples/adding-dynamic-field/-->
        
        <!-- Need to ask or figure out what the values are for some of these; slowing morphology? slowing rhythm?
        <section>
            <h2>EEG slowing</h2>
            slowing morphology
            rhythm
            modifier
            location
            lateralization
            duration
            frequency
            prevalance
        </section>
        -->
        
        <section>
            <h3>Spikes/epileptiform findings</h3>
            <h4>Spikes 1</h4>
            <div class="form-group row">
                <label for="spikeLateralization" class="col-sm-3 col-form-label">Spike lateralization</label>
                    <select class="form-control col-sm-8" id = "spikeLateralization">
                        <option>bilateral R>L</option>
                        <option>bilateral L>R</option>
                        <option>left</option>
                        <option>right</option>
                        <option>vertex</option>
                        <option>bilateral L=R</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="spikeLocalization" class="col-sm-3 col-form-label">Spike localization</label>
                    <select class="form-control col-sm-8" id = "spikeLocalization">
                        <option>generalized</option>
                        <option>frontal</option>
                        <option>temporal</option>
                        <option>parietal</option>
                        <option>occipital</option>
                        <option>central</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="spikePrevalence" class="col-sm-3 col-form-label">Spike prevalence</label>
                    <select class="form-control col-sm-8" id = "spikePrevalance">
                        <option>continuous</option>
                        <option>every few seconds</option>
                        <option>every few minutes</option>
                        <option>rare</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="spikeModifier" class="col-sm-3 col-form-label">Spike modifier</label>
                    <select class="form-control col-sm-8" id = "spikeModifier">
                        <option>with stimulation</option>
                        <option>periodic</option>
                        <option>low amplitude</option>
                        <option>high amplitude</option>
                        <option>polyspike</option>
                        <option>triphasic</option>
                        <option>sleep augmented</option>
                    </select>
            </div>
        </section>
        
        <section>
            <h3>Seizures</h3>
            <h4>Seizure 1</h4>
            <div class="form-group row">
                <label for="seizureLateralization" class="col-sm-3 col-form-label">Seizure lateralization</label>
                    <select class="form-control col-sm-8" id = "seizureLateralization">
                        <option>left</option>
                        <option>right</option>
                        <option>onset on left, spread to right</option>
                        <option>onset on right, spread to left</option>
                        <option>generalized</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="seizureLocalization" class="col-sm-3 col-form-label">Seizure localization</label>
                    <select class="form-control col-sm-8" id = "seizureLocalization">
                        <option>frontal</option>
                        <option>temporal</option>
                        <option>parietal</option>
                        <option>occipital</option>
                        <option>central</option>
                    </select>
            </div>
            <div class="form-group row">
                <label for="seizureDuration" class="col-sm-3 col-form-label">Seizure duration</label>
                    <select class="form-control col-sm-8" id = "seizureDuration">
                        <option>seconds</option>
                        <option>minutes</option>
                        <option>continuous</option>
                    </select>
            </div>
            <!--Need to clarify some of these other parameters: sz time, sz postictal slow, sz generalized, Recurrent.
            Not sure what these mean or what the values would be. -->
        </section>
        <section>
            <h3>Overall Assessment</h3>
        <div class="form-group row">
            <label for="eegAbnSummary" class="col-sm-3 col-form-label">Overall assessment</label>
                <select class="form-control col-sm-8" id="eegAbnSummary">
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
                <select class="form-control col-sm-8" id="interpretation">
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
        <input type="submit" class="btn btn-info" value="Submit">


    </div>
    <!-- You want to make these columns resizable, so that if the user wants to make the form bigger and shrink the EEG display, they can.
    Searching google for something along the lines of "css user resize column bootstrap" should point in the right direction.
    Figuring out how this codepen works should also help: https://codepen.io/ericfillipe/pen/RGNaRK-->

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    
  </body>
</html>