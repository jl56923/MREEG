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
    
    <div class="container-fluid col-md-7" id="EEG-display">
        <h1>EEG Display: EEG #</h1>
        <img src="/EEG_image.jpg" alt="EEG Image" id="EEG-placeholder">
        <!-- This is a placeholder EEG image. The eventual plan is to get this area to use the D3 JS library to actively graph the EEG data, but in the meantime we'll use a placeholder EEG image. -->
    </div>
    
    <div class="container-fluid col-md-5" id="EEG-interpretation">
        <h1>EEG Interpretation</h1>
        <div class="form-group row">
            <label for="staticEEGID" class="col-sm-3 col-form-label">EEG number</label>
            <div class="col-sm-9">
                <input class="form-control" type="text" readonly id="staticEEGID" value="3">
            </div>
            <!--This will have to be read from the database at some point; basically, the page is going to have to keep track of which EEG the person is interpreting, and then fetch the EEG # and also the clinical history, etc. from the database which stores all th einfo about each EEG. -->
        </div>
        
        <div class="form-group row">
            <label for="staticEEGIndications" class="col-sm-3 col-form-label">EEG indications</label>
            <div class="col-sm-9">
                <textarea class="form-control" readonly id="staticEEGIndications" rows="3">34 yo man with a hx of possible convulsions in his youth, then recurring at age 51. Recently with TBI followed by memory problems and staring spells.</textarea>
            </div>
        </div>
        
        <div class="form-group row">
            <label for="staticCurrentMeds" class="col-sm-3 col-form-label">Current medications</label>
            <div class="col-sm-9">
                <input class="form-control" type="text" readonly id="staticCurrentMeds" value="levetiracetam, baclofen">
            </div>
        </div>
        
        <!-- Textarea for overall interpretation; not sure how this was being used in original MREEG. Would definitely have to clean the input from this textarea to avoid SQL injection or other hacks.
        <div class="form-group row">
            <label for="comments" class="col-sm-3 col-form-label">Comments/free text</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="comments" rows="5"></textarea>
            </div>
        </div>
        -->
        
        <div class="form-group row">
            <label for="backgroundOrganization" class="col-sm-3 col-form-label">Background organization</label>
                <select class="form-control col-sm-9" id="backgroundOrganization">
                    <option>good, with appropriate posterior dominance</option>
                    <option>fair, with anterior dominance and/or excess slower frequencies</option>
                    <option>poor, with mixed frequencies without organization</option>
                    <option>not applicable</option>
                </select>
        </div>
        
        <div class="form-group row">
            <label for="backgroundReactivity" class="col-sm-3 col-form-label">Background reactivity</label>
                <select class="form-control col-sm-9" id="backgroundReactivity">
                    <option>good (reactive to eye closure)</option>
                    <option>fair (some change with eye closure)</option>
                    <option>poor (changes seen with stimulation only)</option>
                    <option>absent (no reactivity)</option>
                </select>
        </div>
        
        <div class="form-group row">
            <label for="PDR" class="col-sm-3 col-form-label">PDR</label>
                <select class="form-control col-sm-9" id="PDR">
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
                <select class="form-control col-sm-9" id="PDRSymmetry">
                    <option>markedly asymmetric (right side more than 50% slower or attenuated relative to left)</option>
                    <option>mildly asymmetric (right side less than 50% slower or attenuated relative to left)</option>
                    <option>R=L symmetric</option>
                    <option>mildly asymmetric (left side less than 50% slower or attenuated relative to right)</option>
                    <option>markedly asymmetric (left side more than 50% slower or attenuated relative to right)</option>
                </select>
        </div>
        
        <div class="form-group row">
            <label for="PDRAmplitude" class="col-sm-3 col-form-label">PDR amplitude</label>
                <select class="form-control col-sm-9" id="PDRAmplitude">
                    <option>silent (&lt;=2 uV)</option>
                    <option>low (&lt;20 uV)</option>
                    <option>normal (20-100 uV)</option>
                    <option>high (100-150 uV)</option>
                    <option>excessively high (&gt;150 uV)</option>
                </select>
        </div>
        
        <div class="form-group row">
            <label for="beta" class="col-sm-3 col-form-label">Beta activity</label>
                <select class="form-control col-sm-9" id="beta">
                    <option>normal</option>
                    <option>absent</option>
                    <option>excessive</option>
                </select>
        </div>
        
        <!-- Could do radio buttons instead of select multiple from a list as well. -->
        <div class="form-group row">
            <label for="normalVariants" class="col-sm-3 col-form-label">Normal variants</label>
                <select multiple class="form-control col-sm-9" id="normalVariants">
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
                <select multiple class="form-control col-sm-9" id="artifactType">
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
                <select class="form-control col-sm-9" id="artifactSeverity">
                    <option>mild</option>
                    <option>moderate</option>
                    <option>severe</option>
                </select>
        </div>
        
        <!--For HV, photostim, and EKG, you would have to find some way to graph these as well. EKG I think comes in the data set, but HV and PS may not be marked in the EDF data.-->
        <div class="form-group row">
            <label for="hv" class="col-sm-3 col-form-label">Hyperventilation</label>
                <select class="form-control col-sm-9" id="hv">
                    <option>was not done</option>
                    <option>demonstrated normal symmetric buildup of slowing</option>
                    <option>demonstrated excessive focal left slowing</option>
                    <option>demonstrated excessive focal right slowing</option>
                    <option>demonstrated no changes</option>
                </select>
        </div>
        
        <div class="form-group row">
            <label for="photostim" class="col-sm-3 col-form-label">Photostimulation</label>
                <select class="form-control col-sm-9" id="photostim">
                    <option>was not done</option>
                    <option>demonstrated no/minimal driving</option>
                    <option>demonstrated moderate driving</option>
                    <option>demonstrated prominent driving</option>
                    <option>evoked epileptiform discharges</option>
                </select>
        </div>
        
        <!-- To fill in later: How to handle sleep (drowsy, stage 2, deep sleep) and epileptiform discharges (type, localization, lateralization) -->
        
        <div class="form-group row">
            <label for="eegAbnSummary" class="col-sm-3 col-form-label">Overall assessment</label>
                <select class="form-control col-sm-9" id="eegAbnSummary">
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
                <select class="form-control col-sm-9" id="interpretation">
                    <option>indicate diffuse encephalopathy</option>
                    <option>indicate cortical dysfunction</option>
                    <option>indicate cortical irritability</option>
                    <option>are abnormal but not definitively epileptic</option>
                    <option>suggest epilepsy</option>
                    <option>suggest NES</option>
                    <option>may indicate a sleep disorder</option>
                </select>
        </div>
        
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