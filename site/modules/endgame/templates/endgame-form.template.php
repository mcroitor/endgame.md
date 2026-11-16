<form method="post" action="<!-- www -->/?q=endgame/create" class="endgame-create-form">
    <div class="row">
        <div class="twelve columns">
            <label for="fen">FEN</label>
            <input type="text" name="fen" id="fen" class="u-full-width" placeholder="e.g. 8/8/8/8/8/8/4K3/4k2R w - - 0 1" />
        </div>
    </div>
    <div id="fen-preview-wrap" class="fen-preview-wrap hidden">
        <img id="fen-preview-img" src="" alt="FEN board preview" />
    </div>
    <div class="row">
        <div class="six columns">
            <label for="author">Author</label>
            <input type="text" name="author" id="author" class="u-full-width" />
        </div>
        <div class="six columns">
            <label for="date">Date</label>
            <input type="text" name="date" id="date" class="u-full-width" />
        </div>
    </div>
    <div class="row">
        <div class="six columns">
            <label for="source">Source</label>
            <input type="text" name="source" id="source" class="u-full-width" />
        </div>
        <div class="six columns">
            <label for="stipulation">Stipulation</label>
            <select name="stipulation" id="stipulation" class="u-full-width">
                <option value="White wins">White wins</option>
                <option value="Draw">Draw</option>
                <option value="*">Not specified</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="twelve columns">
            <input type="submit" value="Save" class="button-primary" />
        </div>
    </div>
</form>