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

            <div class="filter-group">
                <label for="piece_pattern" class="filter-label">Piece pattern</label>
                <input type="text" id="piece_pattern" class="u-full-width" placeholder="e.g. Rp, Qp, P" title="Material without kings: uppercase = White (QRBNP), lowercase = Black (qrbnp)" />
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