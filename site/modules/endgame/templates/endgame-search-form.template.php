<div class="searchform">
    <h5>Search by:</h5>
    <div class="row">
        <div class="two columns">author: </div>
        <div class="ten columns">
            <input type="text" name="author" class="u-full-width" id="author" onfocus="showAdvanced();" />
        </div>
    </div>
    <div class="hidden" id="advanced">
        <div class="row">
            <div class="two columns">White pieces: </div>
            <div class="two columns">
                <label for="wmin" class="six columns">from</label>
                <input type="number" class="six columns" name="wmin" min="1" max="32" value="2" id="wmin" />
            </div>
            <div class="two columns">
                <label for="wmax" class="six columns">to</label>
                <input type="number" class="six columns" name="wmax" min="1" max="32" value="7" id="wmax" />
            </div>
            <div class="two columns">Black pieces: </div>
            <div class="two columns">
                <label for="bmin" class="six columns">from</label>
                <input type="number" class="six columns" name="bmin" min="1" max="32" value="2" id="bmin" />
            </div>
            <div class="two columns">
                <label for="bmax" class="six columns">to</label>
                <input type="number" class="six columns" name="bmax" min="1" max="32" value="7" id="bmax" />
            </div>
        </div>
        <div class="row">
            <div class="two columns">Stipulation: </div>
            <div class="four columns">
                <select id="stipulation" class="u-full-width">
                    <option value='-'>Any stipulation</option>
                    <option value='White wins'>White wins</option>
                    <option value='Draw'>Draw</option>
                    <option value='*'>Not specified</option>
                </select>
            </div>
            <div class="two columns">Theme: </div>
            <div class="four columns">
                <select id="theme" class="u-full-width">
                    <!-- themes -->
                </select>
            </div>
        </div>
        <div class="row">
            <div class="two columns">Date: </div>
            <div class="ten columns">
                <input type="number" min="0" max="3000" value="1900" id="from_date" /> -
                <input type="number" min="0" max="3000" value="<!-- last-date -->" id="to_date" />
            </div>
        </div>
        <div class="row">
            <div class="twelve columns">
                <label for="cook" class="two columns">Is cooked:</label>
                <input type="checkbox" class="one columns" name="cook" id="cook" />
            </div>
        </div>
    </div>
</div>
<input type="button" value="Make Query" onclick="getPosition(0)" />
<input type="button" value="Clear Form" onclick="clearAll()" />
<hr class="line" />
<div id="stat"></div>
<div id="diag"></div>