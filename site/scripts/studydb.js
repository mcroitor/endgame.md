const PAGE_SIZE = 12;
const APP_BASE = (document.querySelector('meta[name="app-base"]')?.getAttribute("content") || "").replace(/\/+$/, "");
const currentStudiesMap = new Map();
let lastFocusedElement = null;

/**
 * Shortcut for getting HTML Element by ID.
 * @param {string} id
 * @returns {HTMLElement|null}
 */
function get(id) {
    return document.getElementById(id);
}

/**
 * Escape HTML special characters to prevent XSS in templates.
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

/**
 * POST JSON request.
 * @param {string} uri
 * @param {object} [sendData]
 * @returns {Promise<any>}
 */
function request(uri, sendData) {
    return new Promise(function (resolve, reject) {
        const http = new XMLHttpRequest();
        http.onload = function () {
            if (http.status === 200) {
                try {
                    resolve(JSON.parse(http.responseText));
                } catch (e) {
                    reject(new Error("Invalid server response"));
                }
            } else {
                const error = new Error(this.statusText || "Request failed");
                error.code = this.status;
                reject(error);
            }
        };
        http.onerror = function () {
            reject(new Error("Network error"));
        };

        http.open("POST", uri, true);
        http.setRequestHeader("Content-type", "application/json; charset=utf-8");
        http.send(sendData !== undefined ? JSON.stringify(sendData) : null);
    });
}

function patternMake() {
    const el = get("piece_pattern");
    return el ? el.value.trim() : "";
}

function getSelectedCook() {
    const checked = document.querySelector('input[name="cook"]:checked');
    return checked ? checked.value : "all";
}

function setSelectedCook(val) {
    const target = document.querySelector(`input[name="cook"][value="${val}"]`);
    if (target) {
        target.checked = true;
    }
}

function getDefaultToDate() {
    return String(new Date().getFullYear());
}

function countActiveFilters() {
    let count = 0;
    const wmin = get("wmin");
    const wmax = get("wmax");
    const bmin = get("bmin");
    const bmax = get("bmax");
    const stip = get("stipulation");
    const theme = get("theme");
    const fromDate = get("from_date");
    const toDate = get("to_date");
    const cook = getSelectedCook();

    if (wmin && wmin.value !== "2") count++;
    if (wmax && wmax.value !== "7") count++;
    if (bmin && bmin.value !== "2") count++;
    if (bmax && bmax.value !== "7") count++;
    if (stip && stip.value !== "-") count++;
    if (theme && theme.value !== "-") count++;
    if (patternMake() !== "") count++;
    if (fromDate && fromDate.value !== "1900") count++;
    if (toDate && toDate.value !== getDefaultToDate()) count++;
    if (cook !== "all") count++;

    return count;
}

function updateFiltersBadge() {
    const badge = get("active-filters-count");
    if (!badge) return;
    const count = countActiveFilters();
    badge.textContent = `(filters: ${count})`;
}

function toggleAdvanced(forceState) {
    const panel = get("advanced");
    const btn = get("toggle-advanced");
    if (!panel || !btn) return;
    const isHidden = panel.classList.contains("hidden");
    const shouldShow = forceState !== undefined ? forceState : isHidden;

    if (shouldShow) {
        panel.classList.remove("hidden");
        btn.setAttribute("aria-expanded", "true");
    } else {
        panel.classList.add("hidden");
        btn.setAttribute("aria-expanded", "false");
    }
}

function countFenPieces(fen) {
    const boardPart = String(fen || "").trim().split(/\s+/)[0] || "";
    const matches = boardPart.match(/[kqrbnpKQRBNP]/g);
    return matches ? matches.length : 0;
}

function formatSyzygyCategory(cat) {
    const map = {
        win: "Win",
        draw: "Draw",
        loss: "Loss",
        "cursed-win": "Cursed Win",
        "blessed-loss": "Blessed Loss",
        "maybe-win": "Maybe Win",
        "maybe-loss": "Maybe Loss",
        unknown: "Unknown"
    };
    return map[cat] || String(cat || "Unknown");
}

async function lookupSyzygy(fen) {
    const box = get("pgnlive-syzygy");
    if (!box || !fen) return;

    box.classList.remove("hidden");
    box.innerHTML = '<div class="status-message">Querying Syzygy tablebase&hellip;</div>';

    try {
        const res = await fetch(`https://tablebase.lichess.ovh/standard?fen=${encodeURIComponent(fen)}`);
        if (res.status === 429) {
            box.innerHTML = '<div class="status-message status-error">Syzygy rate limit reached. Please try again in a moment.</div>';
            return;
        }
        if (!res.ok) {
            box.innerHTML = `<div class="status-message status-error">Syzygy lookup unavailable (HTTP ${res.status}).</div>`;
            return;
        }
        const data = await res.json();
        const verdict = formatSyzygyCategory(data.category);
        const dtz = data.dtz !== null && data.dtz !== undefined ? `DTZ ${data.dtz}` : "";
        const dtm = data.dtm !== null && data.dtm !== undefined ? `DTM ${data.dtm}` : "";
        const metrics = [dtz, dtm].filter(Boolean).join(" &middot; ");

        const moves = Array.isArray(data.moves) ? data.moves.slice(0, 6) : [];
        const movesHtml = moves.length
            ? `<div class="syzygy-moves">${moves.map((m) => {
                const moveCat = formatSyzygyCategory(m.category);
                const moveDtz = m.dtz !== null && m.dtz !== undefined ? ` (DTZ ${m.dtz})` : "";
                return `<span class="syzygy-move-chip"><strong>${escapeHtml(m.san)}</strong>: ${escapeHtml(moveCat)}${escapeHtml(moveDtz)}</span>`;
            }).join("")}</div>`
            : "";

        box.innerHTML = `<div class="syzygy-header">
                <span class="syzygy-title">Syzygy Tablebase</span>
                <span class="syzygy-verdict syzygy-cat-${escapeHtml(data.category || "unknown")}">${escapeHtml(verdict)}</span>
                ${metrics ? `<span class="syzygy-metrics">${metrics}</span>` : ""}
            </div>
            ${movesHtml}`;
    } catch (err) {
        box.innerHTML = `<div class="status-message status-error">Failed to reach Syzygy tablebase (${escapeHtml(err.message)}).</div>`;
    }
}

function parseSideToMove(fen) {
    const parts = String(fen || "").trim().split(/\s+/);
    if (parts[1] === "b") return "Black to move";
    if (parts[1] === "w") return "White to move";
    return "";
}

function formatDate(rawDate) {
    const text = String(rawDate || "").trim();
    if (!text) return "";
    return text.replace(/(\.\?\?)+$/, "");
}

function formatPieces(rawPieces) {
    const m = String(rawPieces || "").match(/(\d+)\s*\+\s*(\d+)/);
    if (!m) return { label: String(rawPieces || ""), title: "White + Black pieces" };
    return {
        label: `${m[1]}W + ${m[2]}B`,
        title: `${m[1]} white pieces and ${m[2]} black pieces`
    };
}

function formatStipulation(rawStip, rawSource) {
    const text = String(rawStip || "").trim();
    const sourceText = String(rawSource || "").trim();
    const mateMatch = text.match(/#0*(\d+)/) || sourceText.match(/#0*(\d+)\s*\{m\}/i);
    if (mateMatch) {
        return { label: `Mate in ${mateMatch[1]}`, raw: text || sourceText, cls: "study-badge-mate" };
    }
    if (!text || text === "*" || text === "-") {
        return { label: "", raw: text, cls: "" };
    }
    if (/white\s+wins/i.test(text)) {
        return { label: text, raw: text, cls: "study-badge-win" };
    }
    if (/draw/i.test(text)) {
        return { label: text, raw: text, cls: "study-badge-draw" };
    }
    return { label: text, raw: text, cls: "" };
}

function buildBadgesHtml(desc) {
    const rawFen = String(desc["#fen#"] || "");
    const sideLabel = parseSideToMove(rawFen);
    const stip = formatStipulation(desc["#stip#"], desc["#source#"]);
    const pieces = formatPieces(desc["#pieces#"]);

    const badges = [];
    if (sideLabel) {
        badges.push(`<span class="study-badge study-badge-side">${escapeHtml(sideLabel)}</span>`);
    }
    if (stip.label) {
        badges.push(
            `<span class="study-badge ${stip.cls}" title="${escapeHtml(stip.raw)}">${escapeHtml(stip.label)}</span>`
        );
    }
    if (pieces.label) {
        badges.push(
            `<span class="study-badge study-badge-side" title="${escapeHtml(pieces.title)}">${escapeHtml(pieces.label)}</span>`
        );
    }
    return badges.join("");
}

function renderCard(desc) {
    const pid = String(desc["#pid#"] ?? "");
    currentStudiesMap.set(pid, desc);

    const pidEsc = escapeHtml(pid);
    const author = escapeHtml(desc["#author#"] || "Unknown author");
    const rawSource = String(desc["#source#"] || "").trim();
    const sourceClean = escapeHtml(rawSource.replace(/\s*\{m\}\s*/gi, "").trim());
    const date = escapeHtml(formatDate(desc["#date#"]));
    const rawFen = String(desc["#fen#"] || "");
    const fenEscaped = escapeHtml(rawFen);
    const fenUrl = encodeURIComponent(rawFen);

    const sourceLine = [sourceClean, date].filter(Boolean).join(", ");
    const sourceTitle = [escapeHtml(rawSource), escapeHtml(desc["#date#"] || "")].filter(Boolean).join(", ");

    return `<article class="study-card">
        <div class="study-card-header">
            <h5 class="study-author" title="${author}">${author}</h5>
            <p class="study-source" title="${sourceTitle}">${sourceLine || "&nbsp;"}</p>
        </div>
        <div class="study-board js-open-solution" data-pid="${pidEsc}" role="button" tabindex="0" title="Open solution" aria-label="Open solution for study ${pidEsc}">
            <img src="${APP_BASE}/modules/diagram/?fen=${fenUrl}&size=32" alt="Chess position FEN: ${fenEscaped}" loading="lazy" />
        </div>
        <div class="study-badges">${buildBadgesHtml(desc)}</div>
        <div class="study-card-footer">
            <div class="study-actions">
                <button type="button" class="button js-pgnlive" data-pid="${pidEsc}">Solution</button>
                <a href="${APP_BASE}/?q=pgn/${pidEsc}" class="button" download="endgame_${pidEsc}.pgn">PGN</a>
                <button type="button" class="button js-copy-fen" data-fen="${fenEscaped}">Copy FEN</button>
            </div>
            <span class="study-pid">PID ${pidEsc}</span>
        </div>
    </article>`;
}

function buildPaginationHtml(currentPage, totalItems) {
    const totalPages = Math.ceil(totalItems / PAGE_SIZE);
    if (totalPages <= 1) return "";

    const pages = [];
    pages.push(
        `<button type="button" class="page-btn" data-page="${currentPage - 1}" ${currentPage === 0 ? "disabled" : ""}>&larr; Prev</button>`
    );

    const visible = new Set([0, totalPages - 1]);
    for (let i = Math.max(0, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
        visible.add(i);
    }
    const sorted = Array.from(visible).sort((a, b) => a - b);

    let prev = -1;
    for (const p of sorted) {
        if (prev !== -1 && p - prev > 1) {
            pages.push('<span class="page-ellipsis">&hellip;</span>');
        }
        pages.push(
            `<button type="button" class="page-btn${p === currentPage ? " active" : ""}" data-page="${p}" aria-current="${p === currentPage ? "page" : "false"}">${p + 1}</button>`
        );
        prev = p;
    }

    pages.push(
        `<button type="button" class="page-btn" data-page="${currentPage + 1}" ${currentPage >= totalPages - 1 ? "disabled" : ""}>Next &rarr;</button>`
    );
    return pages.join("");
}

function syncUrlWithQuery(sendData) {
    if (!window.history || !window.history.pushState) return;
    const params = new URLSearchParams();
    if (sendData.author) params.set("author", sendData.author);
    if (sendData.wmin !== "2") params.set("wmin", sendData.wmin);
    if (sendData.wmax !== "7") params.set("wmax", sendData.wmax);
    if (sendData.bmin !== "2") params.set("bmin", sendData.bmin);
    if (sendData.bmax !== "7") params.set("bmax", sendData.bmax);
    if (sendData.stipulation !== "-") params.set("stipulation", sendData.stipulation);
    if (sendData.theme !== "-") params.set("theme", sendData.theme);
    if (sendData.piece_pattern) params.set("piece_pattern", sendData.piece_pattern);
    if (sendData.from_date !== "1900") params.set("from_date", sendData.from_date);
    if (sendData.to_date !== getDefaultToDate()) params.set("to_date", sendData.to_date);
    if (sendData.cook !== "all") params.set("cook", sendData.cook);
    if (sendData.sort && sendData.sort !== "pid_asc") params.set("sort", sendData.sort);
    if (sendData.page > 0) params.set("page", String(sendData.page));

    const qs = params.toString();
    const basePath = window.location.pathname.replace(/^\/+/, "/") || "/";
    const nextUrl = qs ? `${basePath}?${qs}` : basePath;
    try {
        window.history.pushState(null, "", nextUrl);
    } catch (_) {
        // ignore if history state cannot be updated
    }
}

function restoreQueryFromUrl() {
    const params = new URLSearchParams(window.location.search);
    if (params.has("q")) return false;

    const keys = ["author", "wmin", "wmax", "bmin", "bmax", "stipulation", "theme", "piece_pattern", "from_date", "to_date", "cook", "sort", "page"];
    const hasSearchParam = keys.some((k) => params.has(k));
    if (!hasSearchParam) return false;

    if (params.has("author") && get("author")) get("author").value = params.get("author");
    if (params.has("wmin") && get("wmin")) get("wmin").value = params.get("wmin");
    if (params.has("wmax") && get("wmax")) get("wmax").value = params.get("wmax");
    if (params.has("bmin") && get("bmin")) get("bmin").value = params.get("bmin");
    if (params.has("bmax") && get("bmax")) get("bmax").value = params.get("bmax");
    if (params.has("stipulation") && get("stipulation")) get("stipulation").value = params.get("stipulation");
    if (params.has("theme") && get("theme")) get("theme").value = params.get("theme");
    if (params.has("piece_pattern") && get("piece_pattern")) get("piece_pattern").value = params.get("piece_pattern");
    if (params.has("from_date") && get("from_date")) get("from_date").value = params.get("from_date");
    if (params.has("to_date") && get("to_date")) get("to_date").value = params.get("to_date");
    if (params.has("cook")) setSelectedCook(params.get("cook"));
    if (params.has("sort") && get("sort")) get("sort").value = params.get("sort");

    updateFiltersBadge();
    if (countActiveFilters() > 0) {
        toggleAdvanced(true);
    }

    const page = Math.max(0, parseInt(params.get("page") || "0", 10) || 0);
    getPosition(page, false);
    return true;
}

async function getPosition(page, pushUrl = true) {
    const diag = get("diag");
    const stat = get("stat");
    const resultsHeader = get("results-header");
    const pagTop = get("pagination-top");
    const pagBottom = get("pagination-bottom");
    if (!diag || !stat) return;

    const sendData = {
        page: page,
        author: get("author").value.trim(),
        wmin: get("wmin").value,
        wmax: get("wmax").value,
        bmin: get("bmin").value,
        bmax: get("bmax").value,
        piece_pattern: patternMake(),
        stipulation: get("stipulation").value,
        theme: get("theme").value,
        from_date: get("from_date").value,
        to_date: get("to_date").value,
        cook: getSelectedCook(),
        sort: get("sort") ? get("sort").value : "pid_asc"
    };

    if (pushUrl) {
        syncUrlWithQuery(sendData);
    }
    updateFiltersBadge();

    resultsHeader.classList.remove("hidden");
    stat.innerHTML = "<h5>Searching&hellip;</h5>";
    diag.innerHTML = '<div class="status-message">Loading positions&hellip; Please wait.</div>';
    if (pagTop) pagTop.innerHTML = "";
    if (pagBottom) pagBottom.innerHTML = "";

    try {
        const result = await request(`${APP_BASE}/api/?q=data`, sendData);
        const total = Number(result.stat || 0);
        const items = Array.isArray(result.html) ? result.html : [];

        stat.innerHTML = `<h5>${total.toLocaleString("en-US")} positions found</h5>
            <p class="pdf-note">PDF export includes up to 1,000 diagrams per file.</p>`;

        if (items.length === 0) {
            diag.innerHTML = '<div class="status-message">No positions found. Try adjusting or clearing your filters.</div>';
            return;
        }

        diag.innerHTML = items.map(renderCard).join("");

        const pagHtml = buildPaginationHtml(page, total);
        if (pagTop) pagTop.innerHTML = pagHtml;
        if (pagBottom) pagBottom.innerHTML = pagHtml;
    } catch (err) {
        stat.innerHTML = "<h5>Search error</h5>";
        diag.innerHTML = `<div class="status-message status-error">Failed to load positions (${escapeHtml(err.message)}). Please try again.</div>`;
    }
}

function clearAll() {
    if (get("author")) get("author").value = "";
    if (get("wmin")) get("wmin").value = "2";
    if (get("wmax")) get("wmax").value = "7";
    if (get("bmin")) get("bmin").value = "2";
    if (get("bmax")) get("bmax").value = "7";
    if (get("stipulation")) get("stipulation").value = "-";
    if (get("theme")) get("theme").value = "-";
    if (get("piece_pattern")) get("piece_pattern").value = "";
    setSelectedCook("all");
    if (get("from_date")) get("from_date").value = "1900";
    if (get("to_date")) get("to_date").value = getDefaultToDate();
    if (get("sort")) get("sort").value = "pid_asc";

    if (get("results-header")) get("results-header").classList.add("hidden");
    if (get("stat")) get("stat").innerHTML = "";
    if (get("diag")) get("diag").innerHTML = "";
    if (get("pagination-top")) get("pagination-top").innerHTML = "";
    if (get("pagination-bottom")) get("pagination-bottom").innerHTML = "";

    updateFiltersBadge();
    if (window.history && window.history.pushState) {
        const basePath = window.location.pathname.replace(/^\/+/, "/") || "/";
        try {
            window.history.pushState(null, "", basePath);
        } catch (_) {
            // ignore
        }
    }
}

function copyToClipboard(text, btn, feedbackText = "Copied \u2713") {
    const showDone = () => {
        if (!btn) return;
        const prev = btn.textContent;
        btn.textContent = feedbackText;
        setTimeout(() => { btn.textContent = prev; }, 1500);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(showDone).catch(() => fallbackCopy(text, showDone));
    } else {
        fallbackCopy(text, showDone);
    }
}

function fallbackCopy(text, onDone) {
    const ta = document.createElement("textarea");
    ta.value = text;
    ta.style.position = "fixed";
    ta.style.opacity = "0";
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand("copy");
        onDone();
    } catch (_) {
        // ignore
    }
    document.body.removeChild(ta);
}

async function pgnLive(pid, triggerEl = null) {
    const backdrop = get("pgnlive-backdrop");
    const viewer = get("pgnlive");
    const metaBox = get("pgnlive-meta");
    const actionsBox = get("pgnlive-actions");
    const closeBtn = get("pgnlive-close");
    if (!backdrop || !viewer) return false;

    lastFocusedElement = triggerEl || document.activeElement;

    const study = currentStudiesMap.get(String(pid));
    if (metaBox) {
        if (study) {
            const author = escapeHtml(study["#author#"] || "Unknown author");
            const source = escapeHtml(String(study["#source#"] || "").replace(/\s*\{m\}\s*/gi, "").trim());
            const date = escapeHtml(formatDate(study["#date#"]));
            const sourceLine = [source, date].filter(Boolean).join(", ");
            metaBox.innerHTML = `<div class="pgnlive-meta-main">
                <span class="pgnlive-meta-author">${author}</span>
                ${sourceLine ? `<span class="pgnlive-meta-source">${sourceLine}</span>` : ""}
                <span class="study-pid">PID ${escapeHtml(pid)}</span>
            </div>
            <div class="study-badges" style="margin:0;">${buildBadgesHtml(study)}</div>`;
            metaBox.classList.remove("hidden");
        } else {
            metaBox.innerHTML = `<div class="pgnlive-meta-main"><span class="study-pid">PID ${escapeHtml(pid)}</span></div>`;
            metaBox.classList.remove("hidden");
        }
    }

    if (actionsBox) {
        const fen = study ? String(study["#fen#"] || "") : "";
        const pieceCount = fen ? countFenPieces(fen) : 0;
        const lichessUrl = fen ? `https://lichess.org/analysis/standard/${encodeURI(fen.replaceAll(" ", "_"))}` : "";
        const studyUrl = `${window.location.origin}${APP_BASE}/?pid=${encodeURIComponent(pid)}`;
        actionsBox.innerHTML = `
            ${fen ? `<button type="button" class="button js-modal-copy-fen" data-fen="${escapeHtml(fen)}">Copy FEN</button>` : ""}
            <a href="${APP_BASE}/?q=pgn/${escapeHtml(pid)}" class="button" download="endgame_${escapeHtml(pid)}.pgn">Download PGN</a>
            ${lichessUrl ? `<a href="${escapeHtml(lichessUrl)}" class="button" target="_blank" rel="noopener">Open in Lichess</a>` : ""}
            ${fen && pieceCount > 0 && pieceCount <= 7 ? `<button type="button" class="button js-check-syzygy" data-fen="${escapeHtml(fen)}">Check Syzygy</button>` : ""}
            <button type="button" class="button js-modal-copy-link" data-url="${escapeHtml(studyUrl)}">Copy link</button>
        `;
        actionsBox.classList.remove("hidden");
    }

    const syzygyBox = get("pgnlive-syzygy");
    if (syzygyBox) {
        syzygyBox.innerHTML = "";
        syzygyBox.classList.add("hidden");
    }

    backdrop.classList.remove("hidden");
    if (closeBtn) closeBtn.focus();
    viewer.innerHTML = '<div class="status-message">Loading solution&hellip;</div>';

    try {
        const pgn = await request(`${APP_BASE}/api/?q=pgn/` + encodeURIComponent(pid));
        if (!pgn || !pgn.data) {
            viewer.innerHTML = '<div class="status-message status-error">PGN data is not available for this study.</div>';
            return false;
        }
        viewer.innerHTML = "";
        const mount = document.createElement("div");
        viewer.appendChild(mount);
        const config = {
            pgn: pgn.data,
            showMoves: "right",
            showPlayers: "none",
            menu: {
                getPgn: { enabled: true, fileName: `endgame_${pid}.pgn` }
            }
        };
        LichessPgnViewer(mount, config);
    } catch (err) {
        viewer.innerHTML = `<div class="status-message status-error">Failed to load solution (${escapeHtml(err.message)}).</div>`;
    }
    return false;
}

function closePgnModal() {
    const backdrop = get("pgnlive-backdrop");
    const viewer = get("pgnlive");
    const metaBox = get("pgnlive-meta");
    const actionsBox = get("pgnlive-actions");
    if (backdrop) backdrop.classList.add("hidden");
    if (viewer) viewer.innerHTML = "";
    if (metaBox) {
        metaBox.innerHTML = "";
        metaBox.classList.add("hidden");
    }
    if (actionsBox) {
        actionsBox.innerHTML = "";
        actionsBox.classList.add("hidden");
    }
    const syzygyBox = get("pgnlive-syzygy");
    if (syzygyBox) {
        syzygyBox.innerHTML = "";
        syzygyBox.classList.add("hidden");
    }
    if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
        lastFocusedElement.focus();
        lastFocusedElement = null;
    }
}

function getPdf() {
    const params = new URLSearchParams({
        q: "pdf/get",
        author: get("author").value.trim(),
        wmin: get("wmin").value,
        wmax: get("wmax").value,
        bmin: get("bmin").value,
        bmax: get("bmax").value,
        piece_pattern: patternMake(),
        stipulation: get("stipulation").value,
        theme: get("theme").value,
        from_date: get("from_date").value,
        to_date: get("to_date").value,
        cook: getSelectedCook()
    });
    window.open(`${APP_BASE}/?` + params.toString(), "_blank", "noopener");
}

function initNavbarActiveState() {
    const params = new URLSearchParams(window.location.search);
    const currentQ = params.get("q") || "/";
    document.querySelectorAll(".navbar-link[data-route]").forEach((link) => {
        const route = link.getAttribute("data-route");
        if (route === currentQ || (route === "/" && !params.has("q"))) {
            link.classList.add("active");
        }
    });
}

function initAuthDropdown() {
    const menu = get("auth-menu");
    if (!menu) return;
    const toggle = menu.querySelector(".auth-toggle");
    const dropdown = menu.querySelector(".auth-dropdown");
    if (!toggle || !dropdown) return;

    function setOpen(open) {
        dropdown.classList.toggle("hidden", !open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
    }

    toggle.addEventListener("click", (e) => {
        e.stopPropagation();
        setOpen(dropdown.classList.contains("hidden"));
    });

    document.addEventListener("click", (e) => {
        if (!menu.contains(e.target)) {
            setOpen(false);
        }
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            setOpen(false);
        }
    });
}

function initFenCreatePreview() {
    const fenInput = document.querySelector('.endgame-create-form input[name="fen"]');
    const wrap = get("fen-preview-wrap");
    const img = get("fen-preview-img");
    if (!fenInput || !wrap || !img) return;

    let timer = null;
    fenInput.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const val = fenInput.value.trim();
            if (!val) {
                wrap.classList.add("hidden");
                img.src = "";
                return;
            }
            img.src = `${APP_BASE}/modules/diagram/?fen=${encodeURIComponent(val)}&size=32`;
            wrap.classList.remove("hidden");
        }, 250);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initNavbarActiveState();
    initAuthDropdown();
    initFenCreatePreview();

    const searchForm = get("search-form");
    if (searchForm) {
        searchForm.addEventListener("submit", (e) => {
            e.preventDefault();
            getPosition(0);
        });
        searchForm.addEventListener("change", updateFiltersBadge);
        updateFiltersBadge();
    }

    const toggleAdvBtn = get("toggle-advanced");
    if (toggleAdvBtn) {
        toggleAdvBtn.addEventListener("click", () => toggleAdvanced());
    }

    const clearBtn = get("clear-btn");
    if (clearBtn) {
        clearBtn.addEventListener("click", clearAll);
    }

    const sortSelect = get("sort");
    if (sortSelect) {
        sortSelect.addEventListener("change", () => getPosition(0));
    }

    const pdfBtn = get("pdf-btn");
    if (pdfBtn) {
        pdfBtn.addEventListener("click", getPdf);
    }

    const diag = get("diag");
    if (diag) {
        diag.addEventListener("click", (e) => {
            const openTrigger = e.target.closest(".js-pgnlive, .js-open-solution");
            if (openTrigger) {
                pgnLive(openTrigger.getAttribute("data-pid"), openTrigger);
                return;
            }
            const copyBtn = e.target.closest(".js-copy-fen");
            if (copyBtn) {
                copyToClipboard(copyBtn.getAttribute("data-fen") || "", copyBtn);
            }
        });
        diag.addEventListener("keydown", (e) => {
            if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains("js-open-solution")) {
                e.preventDefault();
                pgnLive(e.target.getAttribute("data-pid"), e.target);
            }
        });
    }

    const modalActions = get("pgnlive-actions");
    if (modalActions) {
        modalActions.addEventListener("click", (e) => {
            const copyFen = e.target.closest(".js-modal-copy-fen");
            if (copyFen) {
                copyToClipboard(copyFen.getAttribute("data-fen") || "", copyFen);
                return;
            }
            const copyLink = e.target.closest(".js-modal-copy-link");
            if (copyLink) {
                copyToClipboard(copyLink.getAttribute("data-url") || "", copyLink, "Link copied \u2713");
                return;
            }
            const syzygyBtn = e.target.closest(".js-check-syzygy");
            if (syzygyBtn) {
                lookupSyzygy(syzygyBtn.getAttribute("data-fen") || "");
            }
        });
    }

    document.addEventListener("click", (e) => {
        const pageBtn = e.target.closest(".page-btn[data-page]");
        if (pageBtn && !pageBtn.disabled) {
            const page = parseInt(pageBtn.getAttribute("data-page"), 10);
            if (!Number.isNaN(page) && page >= 0) {
                getPosition(page);
                window.scrollTo({ top: 0, behavior: "smooth" });
            }
        }
    });

    const backdrop = get("pgnlive-backdrop");
    const closeBtn = get("pgnlive-close");
    if (backdrop) {
        backdrop.addEventListener("click", (e) => {
            if (e.target === backdrop) closePgnModal();
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener("click", closePgnModal);
    }
    document.addEventListener("keydown", (e) => {
        if (!backdrop || backdrop.classList.contains("hidden")) return;
        if (e.key === "Escape") {
            closePgnModal();
            return;
        }
        if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
            const controls = document.querySelectorAll("#pgnlive .lpv__controls button");
            if (controls.length >= 4) {
                const targetBtn = e.key === "ArrowLeft" ? controls[1] : controls[2];
                if (targetBtn && !targetBtn.disabled) {
                    e.preventDefault();
                    targetBtn.click();
                }
            }
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("pid")) {
        pgnLive(urlParams.get("pid"));
    }
    restoreQueryFromUrl();
});
