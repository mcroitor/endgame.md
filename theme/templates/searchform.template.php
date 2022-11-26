<div class="searchform">
    <h5>Search by:</h5>
    <div class="row">
        <div class="two columns">author: </div>
        <div class="ten columns">
            <input type="text" name="author" id="author" onfocus="showAdvanced();" />
        </div>
    </div>
    <div class="hidden" id="advanced">
        <div class="row">
            <div class="two columns">White pieces: </div>
            <div class="four columns">
                <select id="wsign">
                    <option value='&lt;'>&lt;</option>
                    <option value='='>=</option>
                    <option value='&gt;'>&gt;</option>
                </select>
                <input type="number" name="wpiece" min="0" id="wpiece" />
            </div>
            <div class="two columns">Black pieces: </div>
            <div class="four columns">
                <select id="bsign">
                    <option value='&lt;'>&lt;</option>
                    <option value='='>=</option>
                    <option value='&gt;'>&gt;</option>
                </select>
                <input type="number" name="bpiece" min="0" id="bpiece" />
            </div>
        </div>
        <div class="row">
            <div class="two columns">Stipulation: </div>
            <div class="four columns">
                <select id="stipulation">
                    <option value='-'>Any stipulation</option>
                    <option value='White wins'>White wins</option>
                    <option value='Draw'>Draw</option>
                    <option value='*'>Not specified</option>
                </select>
            </div>
            <div class="two columns">Theme: </div>
            <div class="four columns">
                <select id="theme">
                    <!-- themes -->
                </select>
            </div>
        </div>
        <div class="row">
            <div class="two columns">Date: </div>
            <div class="ten columns">
                <input type="number" min="0" max="3000" id="fromDate" /> -
                <input type="number" min="0" max="3000" id="toDate" />
            </div>
        </div>
        <div class="row">
            <div class="twelve columns">
                Is cooked: <input type="checkbox" id="cook" />
            </div>
        </div>
    </div>
</div>
<input type="button" value="Make Query" onclick="getPosition(0)" />
<input type="button" value="Clear Form" onclick="clearAll()" />
<hr class="line" />
<div id="stat"></div>
<div id="diag"></div>