<h2>Edit Study (PID <!-- pid -->)</h2>
<!-- error-banner -->
<form method="post" action="<!-- www -->/?q=endgame/update/<!-- pid -->" class="endgame-edit-form">
    <!-- csrf-input -->
    <div class="row">
        <div class="twelve columns">
            <label for="fen">FEN</label>
            <input type="text" name="fen" id="fen" class="u-full-width" value="<!-- fen -->" required />
        </div>
    </div>
    <div id="fen-preview-wrap" class="fen-preview-wrap">
        <img id="fen-preview-img" src="<!-- www -->/modules/diagram/?fen=<!-- fen-encoded -->&amp;size=32" alt="FEN board preview" />
    </div>
    <div class="row">
        <div class="six columns">
            <label for="author">Author</label>
            <input type="text" name="author" id="author" class="u-full-width" value="<!-- author -->" required />
        </div>
        <div class="six columns">
            <label for="date">Date</label>
            <input type="text" name="date" id="date" class="u-full-width" value="<!-- date -->" placeholder="e.g. 1935" />
        </div>
    </div>
    <div class="row">
        <div class="six columns">
            <label for="source">Source</label>
            <input type="text" name="source" id="source" class="u-full-width" value="<!-- source -->" />
        </div>
        <div class="six columns">
            <label for="award">Award</label>
            <input type="text" name="award" id="award" class="u-full-width" value="<!-- award -->" />
        </div>
    </div>
    <div class="row">
        <div class="four columns">
            <label for="stipulation">Stipulation</label>
            <select name="stipulation" id="stipulation" class="u-full-width">
                <!-- stipulation-options -->
            </select>
        </div>
        <div class="four columns">
            <label for="theme">Theme</label>
            <input type="text" name="theme" id="theme" class="u-full-width" value="<!-- theme -->" placeholder="e.g. Stalemate" />
        </div>
        <div class="four columns">
            <label for="cook">Cooked</label>
            <label style="display:flex;align-items:center;gap:0.6rem;font-weight:normal;margin-top:0.6rem;">
                <input type="checkbox" name="cook" id="cook" value="1" <!-- cook-checked --> />
                <span>Mark study as cooked (has duals/refutation)</span>
            </label>
        </div>
    </div>
    <div class="row">
        <div class="twelve columns">
            <label for="commentary">Commentary</label>
            <textarea name="commentary" id="commentary" class="u-full-width" rows="2"><!-- commentary --></textarea>
        </div>
    </div>
    <div class="row">
        <div class="twelve columns">
            <label for="raw_pgn">PGN (raw record)</label>
            <textarea name="raw_pgn" id="raw_pgn" class="u-full-width" rows="6"><!-- raw-pgn --></textarea>
        </div>
    </div>
    <div class="row">
        <div class="twelve columns" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
            <input type="submit" value="Save changes" class="button-primary" />
            <a href="<!-- www -->/?pid=<!-- pid -->" class="button">Cancel</a>
        </div>
    </div>
</form>
<!-- delete-section -->
