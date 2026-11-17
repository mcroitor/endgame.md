<form id="search-form" class="searchform" novalidate>
    <div class="search-main-row">
        <div class="search-author-field">
            <label for="author">Author</label>
            <input type="text" name="author" class="u-full-width" id="author" placeholder="e.g. Kubbel, Troitzky, Rinck..." />
        </div>
        <div class="search-actions">
            <button type="submit" class="button-primary search-submit-btn">Search</button>
            <button type="button" id="clear-btn" class="search-clear-btn">Clear</button>
        </div>
    </div>

    <div class="advanced-toggle-row">
        <button type="button" id="toggle-advanced" class="advanced-toggle" aria-expanded="false" aria-controls="advanced">
            Advanced search &#9662; <span id="active-filters-count" class="filters-badge"></span>
        </button>
    </div>

    <div class="hidden advanced-panel" id="advanced">
        <div class="advanced-grid">
            <div class="filter-group">
                <span class="filter-label">White pieces</span>
                <div class="range-inputs">
                    <label for="wmin" class="range-sub">from</label>
                    <input type="number" name="wmin" min="1" max="32" value="2" id="wmin" />
                    <label for="wmax" class="range-sub">to</label>
                    <input type="number" name="wmax" min="1" max="32" value="7" id="wmax" />
                </div>
            </div>

            <div class="filter-group">
                <span class="filter-label">Black pieces</span>
                <div class="range-inputs">
                    <label for="bmin" class="range-sub">from</label>
                    <input type="number" name="bmin" min="1" max="32" value="2" id="bmin" />
                    <label for="bmax" class="range-sub">to</label>
                    <input type="number" name="bmax" min="1" max="32" value="7" id="bmax" />
                </div>
            </div>

            <div class="filter-group">
                <label for="stipulation" class="filter-label">Stipulation</label>
                <select id="stipulation" class="u-full-width">
                    <option value="-">Any stipulation</option>
                    <option value="White wins">White wins</option>
                    <option value="Draw">Draw</option>
                    <option value="*">Not specified</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="theme" class="filter-label">Theme</label>
                <select id="theme" class="u-full-width">
                    <!-- themes -->
                </select>
            </div>

            <div class="filter-group filter-group-wide">
                <div class="piece-matrix-header">
                    <span class="filter-label">Pieces (material without kings)</span>
                    <select id="piece_mode" class="piece-mode-select" aria-label="Piece pattern match mode">
                        <option value="exact">Exact material</option>
                        <option value="contains">Includes pieces</option>
                    </select>
                </div>
                <table class="piece-matrix" aria-label="Piece counts">
                    <thead>
                        <tr>
                            <th></th>
                            <th title="Queen">&#9813; Q</th>
                            <th title="Rook">&#9814; R</th>
                            <th title="Bishop">&#9815; B</th>
                            <th title="Knight">&#9816; N</th>
                            <th title="Pawn">&#9817; P</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>White</th>
                            <td><input type="number" class="piece-count-input" data-piece="Q" min="0" max="9" value="0" aria-label="White Queens" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="R" min="0" max="9" value="0" aria-label="White Rooks" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="B" min="0" max="9" value="0" aria-label="White Bishops" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="N" min="0" max="9" value="0" aria-label="White Knights" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="P" min="0" max="9" value="0" aria-label="White Pawns" /></td>
                        </tr>
                        <tr>
                            <th>Black</th>
                            <td><input type="number" class="piece-count-input" data-piece="q" min="0" max="9" value="0" aria-label="Black Queens" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="r" min="0" max="9" value="0" aria-label="Black Rooks" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="b" min="0" max="9" value="0" aria-label="Black Bishops" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="n" min="0" max="9" value="0" aria-label="Black Knights" /></td>
                            <td><input type="number" class="piece-count-input" data-piece="p" min="0" max="9" value="0" aria-label="Black Pawns" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="filter-group">
                <span class="filter-label">Year</span>
                <div class="range-inputs">
                    <label for="from_date" class="range-sub">from</label>
                    <input type="number" min="0" max="3000" value="1900" id="from_date" />
                    <label for="to_date" class="range-sub">to</label>
                    <input type="number" min="0" max="3000" value="<!-- last-date -->" id="to_date" />
                </div>
            </div>

            <div class="filter-group filter-group-wide">
                <span class="filter-label">Cooked studies</span>
                <div class="segmented-control" role="radiogroup" aria-label="Cooked studies filter">
                    <label class="segmented-option">
                        <input type="radio" name="cook" value="all" checked />
                        <span>All</span>
                    </label>
                    <label class="segmented-option">
                        <input type="radio" name="cook" value="0" />
                        <span>Sound only</span>
                    </label>
                    <label class="segmented-option">
                        <input type="radio" name="cook" value="1" />
                        <span>Cooked only</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</form>

<div id="results-header" class="results-header hidden">
    <div id="stat" class="results-stat"></div>
    <div class="results-controls">
        <label for="sort" class="sort-label">Sort by</label>
        <select id="sort" class="sort-select">
            <option value="pid_asc">Default (PID)</option>
            <option value="date_asc">Year (oldest first)</option>
            <option value="date_desc">Year (newest first)</option>
            <option value="author_asc">Author (A–Z)</option>
            <option value="pieces_asc">Pieces (fewest first)</option>
        </select>
        <button type="button" id="pdf-btn" class="button-primary pdf-btn">Download PDF</button>
    </div>
</div>

<div id="pagination-top" class="pagination"></div>
<div id="diag" class="study-grid"></div>
<div id="pagination-bottom" class="pagination"></div>