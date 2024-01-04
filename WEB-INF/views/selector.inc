<?php $stylesheet_array = array('selector.css'); 
$scriptdir = dirname(__FILE__);
require $scriptdir . '/../includes/top.inc';
?>
<section>
    <h2>Choose Parameters</h2>
    <form id="filter-form" method="get">
        <div id="filter-form-panel">
            <fieldset>
                <legend>Elements</legend>
                <ul class="key-value-list">
                    <li>
                        <div class="li-key">
                            <label>CED Hostname</label>
                        </div>
                        <div class="li-value">
                            <input name="hostname" value="ced.acc.jlab.org" type="text"/>
                        </div>
                    </li>
                    <li>
                        <div class="li-key">
                            <label>Zone</label>
                        </div>
                        <div class="li-value">
                            <select name="zone">
                                <optgroup label="Common">
                                    <option>Injector</option>
                                    <option>HallA</option>
                                    <option>HallB</option>
                                    <option>HallC</option>
                                    <option>HallD</option>
                                    <option>BSY_DUMP</option>
                                </optgroup>
                                <optgroup label="Branches">
                                    <option>GUN2</option>
                                    <option>GUN3</option>
                                    <option>1D_SPECTROMETER</option>
                                    <option>2D_SPECTROMETER</option>
                                    <option>3D_MOTT</option>
                                    <option>4D_SPECTROMETER</option>
                                    <option>5D_SPECTROMETER</option>
                                    <option>HallA_COMPTON</option>
                                    <option>HALLA_MOELLER</option>
                                    <option>HALLA_ARC</option>
                                    <option>HallB_MOELLER</option>
                                    <option>HallC_COMPTON</option>
                                    <option>HallC_MOELLER</option>
                                    <option>HallC_ARC</option>
                                    <option>HallD_PHOTON</option>
                                    <option>HallD_ARC</option>
                                </optgroup>
                                <optgroup label="First Pass">
                                    <option>LINAC1</option>
                                    <option>LINAC2</option>
                                    <option>ARC1</option>
                                    <option>ARC2</option>
                                    <option>BSY_DUMP1</option>
                                    <option>BSY2</option>
                                </optgroup>
                                <optgroup label="Accumulating Passes">
                                    <option>PASS1</option> 
                                    <option>PASS2</option>
                                </optgroup>
                                <optgroup label="Songsheets">
                                    <option value="ACC-000-2845-0002">ACC-000-2845-0002 - Injector Multiline</option>
                                    <option value="ACC-000-2845-0023">ACC-000-2845-0023 - BSY Extraction Multiline</option>
                                </optgroup>
                                <optgroup label="Overarching">
                                    <option>Injector_Zones</option>
                                    <option>LINACS</option>
                                    <option>ARCS</option>
                                    <option>BSYS</option>
                                    <option>HALLA_ZONES</option>
                                    <option>Songsheet</option>
                                </optgroup>
                                <optgroup label="LERF">
                                    <option>GunTestStand</option>
                                    <option>DarkLight</option>
                                    <option>Dumps</option>
                                </optgroup>
                            </select>
                        </div>
                    </li>
                    <li>
                        <div class="li-key">
                            <label>Workspace</label>
                        </div>
                        <div class="li-value">
                            <input name="workspace" value="" type="text"/>
                        </div>
                    </li>
                </ul>
            </fieldset>
            <fieldset>
                <legend>Display Options</legend>
                <ul class="key-value-list">
                    <li>
                        <div class="li-key">
                            <label>Show Properties</label>
                        </div>
                        <div class="li-value">
                            <input name="properties" value="Y" type="checkbox"/>
                        </div>
                    </li>
                    <li>
                        <div class="li-key">
                            <label>Nomenclature Clusters</label>
                        </div>
                        <div class="li-value">
                            <input name="cluster" value="Y" type="checkbox"/>
                        </div>
                    </li>
                    <li>
                        <div class="li-key">
                            <label>Connect Lines</label>
                        </div>
                        <div class="li-value">
                            <input name="connect" value="Y" type="checkbox"/>
                        </div>
                    </li>
                    <li>
                        <div class="li-key">
                            <label>Link to CED/LED</label>
                        </div>
                        <div class="li-value">
                            <input name="link" value="Y" type="checkbox"/>
                        </div>
                    </li>
                </ul>
            </fieldset>
        </div>
        <button type="submit" formaction="diagram.svg">Generate SVG</button>
        <button type="submit" formaction="book.html">Generate Paginated HTML/SVG*</button>
    </form>
    <div class="footnote">* Assumes 96 DPI resolution on 8.5 X 11 inch paper with 0.5 inch margins and portrait orientation; zones with more than two parallel lines are truncated</div>
</section>
<?php require $scriptdir . '/../includes/bottom.inc'; ?>
