(function () {
    const PIECE_SYMBOLS = {
        K: "\u2654", Q: "\u2655", R: "\u2656", B: "\u2657", N: "\u2658", P: "\u2659",
        k: "\u265A", q: "\u265B", r: "\u265C", b: "\u265D", n: "\u265E", p: "\u265F"
    };

    const FILES = "abcdefgh";

    function stripPgnToMainLine(pgnText) {
        const fenMatch = String(pgnText || "").match(/\[FEN\s+"([^"]+)"\]/i);
        const fen = fenMatch ? fenMatch[1].trim() : "";

        // Remove header tags
        let body = String(pgnText || "").replace(/\[[^\]]*\]/g, " ");
        // Remove line comments
        body = body.replace(/;[^\r\n]*/g, " ");
        // Remove brace comments
        body = body.replace(/\{[^}]*\}/g, " ");

        // Remove nested (...) variations
        let clean = "";
        let depth = 0;
        for (let i = 0; i < body.length; i++) {
            const ch = body[i];
            if (ch === "(") {
                depth++;
            } else if (ch === ")") {
                if (depth > 0) depth--;
            } else if (depth === 0) {
                clean += ch;
            }
        }

        // Tokenize and filter move numbers, NAGs, results
        const tokens = clean.trim().split(/\s+/).filter(Boolean);
        const moves = [];
        for (const tok of tokens) {
            if (/^\d+\.+$/.test(tok)) continue;
            if (/^\$\d+$/.test(tok)) continue;
            if (/^(1-0|0-1|1\/2-1\/2|\*)$/.test(tok)) continue;
            const stripped = tok.replace(/^\d+\.+/, "");
            if (stripped) moves.push(stripped);
        }

        return { fen, moves };
    }

    function parseFen(fen) {
        const parts = String(fen || "8/8/8/8/8/8/8/8 w - - 0 1").trim().split(/\s+/);
        const ranks = (parts[0] || "8/8/8/8/8/8/8/8").split("/");
        const board = Array.from({ length: 8 }, () => Array(8).fill(""));

        for (let r = 0; r < 8 && r < ranks.length; r++) {
            let c = 0;
            for (const ch of ranks[r]) {
                if (c >= 8) break;
                if (/[1-8]/.test(ch)) {
                    c += parseInt(ch, 10);
                } else {
                    board[r][c] = ch;
                    c++;
                }
            }
        }

        return {
            board,
            turn: parts[1] === "b" ? "b" : "w",
            ep: parts[3] && parts[3] !== "-" ? parts[3] : null
        };
    }

    function cloneBoard(board) {
        return board.map((row) => row.slice());
    }

    function isWhitePiece(p) {
        return p !== "" && p === p.toUpperCase();
    }

    function isPieceColor(p, turn) {
        if (!p) return false;
        return turn === "w" ? isWhitePiece(p) : !isWhitePiece(p);
    }

    function canReach(board, r1, c1, r2, c2, pieceType, turn) {
        const dr = r2 - r1;
        const dc = c2 - c1;
        const adr = Math.abs(dr);
        const adc = Math.abs(dc);

        if (r1 === r2 && c1 === c2) return false;

        if (pieceType === "N") {
            return (adr === 2 && adc === 1) || (adr === 1 && adc === 2);
        }
        if (pieceType === "K") {
            return adr <= 1 && adc <= 1;
        }
        if (pieceType === "P") {
            const dir = turn === "w" ? -1 : 1;
            const startRow = turn === "w" ? 6 : 1;
            // Forward
            if (dc === 0 && board[r2][c2] === "") {
                if (dr === dir) return true;
                if (r1 === startRow && dr === 2 * dir && board[r1 + dir][c1] === "") return true;
            }
            // Diagonal capture (or en passant)
            if (adc === 1 && dr === dir) {
                return true;
            }
            return false;
        }

        const isRookMove = dr === 0 || dc === 0;
        const isBishopMove = adr === adc;

        if (pieceType === "R" && !isRookMove) return false;
        if (pieceType === "B" && !isBishopMove) return false;
        if (pieceType === "Q" && !isRookMove && !isBishopMove) return false;

        const stepR = dr === 0 ? 0 : (dr > 0 ? 1 : -1);
        const stepC = dc === 0 ? 0 : (dc > 0 ? 1 : -1);
        let cr = r1 + stepR;
        let cc = c1 + stepC;
        while (cr !== r2 || cc !== c2) {
            if (board[cr][cc] !== "") return false;
            cr += stepR;
            cc += stepC;
        }
        return true;
    }

    function resolveSanStep(state, sanRaw) {
        const san = sanRaw.replace(/[+#!?]+$/g, "");
        const board = cloneBoard(state.board);
        const turn = state.turn;
        let nextEp = null;

        // Castling
        if (san === "O-O" || san === "0-0") {
            const row = turn === "w" ? 7 : 0;
            board[row][6] = board[row][4];
            board[row][4] = "";
            board[row][5] = board[row][7];
            board[row][7] = "";
            return {
                from: [row, 4],
                to: [row, 6],
                promo: null,
                san: sanRaw,
                nextState: { board, turn: turn === "w" ? "b" : "w", ep: null }
            };
        }
        if (san === "O-O-O" || san === "0-0-0") {
            const row = turn === "w" ? 7 : 0;
            board[row][2] = board[row][4];
            board[row][4] = "";
            board[row][3] = board[row][0];
            board[row][0] = "";
            return {
                from: [row, 4],
                to: [row, 2],
                promo: null,
                san: sanRaw,
                nextState: { board, turn: turn === "w" ? "b" : "w", ep: null }
            };
        }

        // Promotion
        let promo = null;
        let core = san;
        const promoMatch = core.match(/=([QRBN])$/i);
        if (promoMatch) {
            promo = promoMatch[1].toUpperCase();
            core = core.slice(0, -2);
        }

        // Destination square
        const destMatch = core.match(/([a-h])([1-8])$/);
        if (!destMatch) return null;
        const toCol = FILES.indexOf(destMatch[1]);
        const toRow = 8 - parseInt(destMatch[2], 10);
        const rest = core.slice(0, -2).replace("x", "");

        let pieceType = "P";
        let disambig = rest;
        if (rest.length > 0 && /[KQRBN]/.test(rest[0])) {
            pieceType = rest[0];
            disambig = rest.slice(1);
        }

        let fromFileHint = null;
        let fromRankHint = null;
        for (const ch of disambig) {
            if (/[a-h]/.test(ch)) fromFileHint = FILES.indexOf(ch);
            if (/[1-8]/.test(ch)) fromRankHint = 8 - parseInt(ch, 10);
        }

        const expectedChar = turn === "w" ? pieceType : pieceType.toLowerCase();
        let found = null;

        for (let r = 0; r < 8; r++) {
            if (fromRankHint !== null && r !== fromRankHint) continue;
            for (let c = 0; c < 8; c++) {
                if (fromFileHint !== null && c !== fromFileHint) continue;
                if (board[r][c] !== expectedChar) continue;
                if (canReach(board, r, c, toRow, toCol, pieceType, turn)) {
                    // For pawn non-captures, ensure target is empty; for captures, target or ep
                    if (pieceType === "P" && c !== toCol) {
                        const isCapture = board[toRow][toCol] !== "" || (state.ep === destMatch[1] + destMatch[2]);
                        if (!isCapture) continue;
                    }
                    found = [r, c];
                    break;
                }
            }
            if (found) break;
        }

        if (!found) return null;

        const [fromRow, fromCol] = found;
        const movingPiece = board[fromRow][fromCol];

        // En passant capture cleanup
        if (pieceType === "P" && fromCol !== toCol && board[toRow][toCol] === "") {
            board[fromRow][toCol] = "";
        }

        // Double pawn push sets ep square
        if (pieceType === "P" && Math.abs(toRow - fromRow) === 2) {
            const epRow = (fromRow + toRow) / 2;
            nextEp = FILES[fromCol] + (8 - epRow);
        }

        board[toRow][toCol] = promo ? (turn === "w" ? promo : promo.toLowerCase()) : movingPiece;
        board[fromRow][fromCol] = "";

        return {
            from: [fromRow, fromCol],
            to: [toRow, toCol],
            promo,
            san: sanRaw,
            nextState: { board, turn: turn === "w" ? "b" : "w", ep: nextEp }
        };
    }

    function buildTimeline(fen, sanMoves) {
        const initialState = parseFen(fen);
        const steps = [];
        let state = initialState;

        for (const san of sanMoves) {
            const step = resolveSanStep(state, san);
            if (!step) break;
            steps.push(step);
            state = step.nextState;
        }
        return { initialState, steps };
    }

    class SolveController {
        constructor(container, pgnText, fallbackFen, onRevealSolution) {
            this.container = container;
            this.onRevealSolution = onRevealSolution;

            const parsed = stripPgnToMainLine(pgnText);
            this.fen = parsed.fen || fallbackFen || "8/8/8/8/8/8/8/8 w - - 0 1";
            const timeline = buildTimeline(this.fen, parsed.moves);

            this.initialState = timeline.initialState;
            this.steps = timeline.steps;
            this.reset();
        }

        reset() {
            this.stepIndex = 0;
            this.state = {
                board: cloneBoard(this.initialState.board),
                turn: this.initialState.turn,
                ep: this.initialState.ep
            };
            this.selected = null;
            this.hintLevel = 0;
            this.lastMove = null;
            this.wrongSquare = null;
            this.pendingPromo = null;
            this.busy = false;
            this.statusText = this.steps.length === 0
                ? "No playable moves found in PGN."
                : (this.state.turn === "w" ? "White to move — find the winning line." : "Black to move — find the best line.");
            this.statusKind = "info";
            this.render();
        }

        handleSquareClick(r, c) {
            if (this.busy || this.stepIndex >= this.steps.length || this.pendingPromo) return;

            const piece = this.state.board[r][c];
            if (this.selected === null) {
                if (isPieceColor(piece, this.state.turn)) {
                    this.selected = [r, c];
                    this.wrongSquare = null;
                    this.render();
                }
                return;
            }

            const [sr, sc] = this.selected;
            if (sr === r && sc === c) {
                this.selected = null;
                this.render();
                return;
            }

            if (isPieceColor(piece, this.state.turn)) {
                this.selected = [r, c];
                this.wrongSquare = null;
                this.render();
                return;
            }

            // Check if pawn reaches promotion rank
            const moving = this.state.board[sr][sc];
            const isPawnPromo = (moving === "P" && r === 0) || (moving === "p" && r === 7);
            if (isPawnPromo && Math.abs(sc - c) <= 1 && Math.abs(sr - r) === 1) {
                this.pendingPromo = { from: [sr, sc], to: [r, c] };
                this.render();
                return;
            }

            this.tryMove([sr, sc], [r, c], null);
        }

        choosePromotion(promoPiece) {
            if (!this.pendingPromo) return;
            const { from, to } = this.pendingPromo;
            this.pendingPromo = null;
            this.tryMove(from, to, promoPiece);
        }

        tryMove(from, to, promo) {
            const expected = this.steps[this.stepIndex];
            if (!expected) return;

            const matchFrom = expected.from[0] === from[0] && expected.from[1] === from[1];
            const matchTo = expected.to[0] === to[0] && expected.to[1] === to[1];
            const matchPromo = !expected.promo || expected.promo === promo;

            if (matchFrom && matchTo && matchPromo) {
                this.selected = null;
                this.hintLevel = 0;
                this.wrongSquare = null;
                this.state = {
                    board: cloneBoard(expected.nextState.board),
                    turn: expected.nextState.turn,
                    ep: expected.nextState.ep
                };
                this.lastMove = { from: expected.from, to: expected.to };
                this.stepIndex++;

                if (this.stepIndex >= this.steps.length) {
                    this.statusText = "Solved! Main line completed.";
                    this.statusKind = "success";
                    this.render();
                    return;
                }

                this.statusText = "Correct! Opponent replying\u2026";
                this.statusKind = "success";
                this.busy = true;
                this.render();

                setTimeout(() => {
                    const reply = this.steps[this.stepIndex];
                    if (reply) {
                        this.state = {
                            board: cloneBoard(reply.nextState.board),
                            turn: reply.nextState.turn,
                            ep: reply.nextState.ep
                        };
                        this.lastMove = { from: reply.from, to: reply.to };
                        this.stepIndex++;
                    }
                    this.busy = false;
                    if (this.stepIndex >= this.steps.length) {
                        this.statusText = "Solved! Main line completed.";
                        this.statusKind = "success";
                    } else {
                        this.statusText = "Your move!";
                        this.statusKind = "info";
                    }
                    this.render();
                }, 320);
            } else {
                this.selected = null;
                this.wrongSquare = to;
                this.statusText = "Incorrect, try again.";
                this.statusKind = "error";
                this.render();
                setTimeout(() => {
                    this.wrongSquare = null;
                    this.render();
                }, 650);
            }
        }

        triggerHint() {
            if (this.busy || this.stepIndex >= this.steps.length) return;
            this.hintLevel = Math.min(2, this.hintLevel + 1);
            this.statusText = this.hintLevel === 1
                ? "Hint: move the highlighted piece."
                : `Hint: ${this.steps[this.stepIndex].san}`;
            this.statusKind = "info";
            this.render();
        }

        buildPlayedMovesHtml() {
            if (this.stepIndex === 0) {
                return '<span class="solve-empty-moves">Moves you find will appear here.</span>';
            }
            const items = [];
            for (let i = 0; i < this.stepIndex; i++) {
                const prefix = i % 2 === 0 ? `${Math.floor(i / 2) + 1}. ` : "";
                items.push(`<span class="solve-move-chip">${prefix}${this.escape(this.steps[i].san)}</span>`);
            }
            return items.join(" ");
        }

        escape(str) {
            return String(str ?? "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;");
        }

        render() {
            const expected = this.steps[this.stepIndex];
            let boardHtml = '<div class="solve-board" role="grid" aria-label="Interactive chessboard">';

            for (let r = 0; r < 8; r++) {
                for (let c = 0; c < 8; c++) {
                    const isLight = (r + c) % 2 === 0;
                    const classes = ["solve-sq", isLight ? "solve-sq-light" : "solve-sq-dark"];

                    if (this.selected && this.selected[0] === r && this.selected[1] === c) {
                        classes.push("solve-sq-selected");
                    }
                    if (this.lastMove) {
                        const isLast = (this.lastMove.from[0] === r && this.lastMove.from[1] === c)
                            || (this.lastMove.to[0] === r && this.lastMove.to[1] === c);
                        if (isLast) classes.push("solve-sq-correct");
                    }
                    if (this.wrongSquare && this.wrongSquare[0] === r && this.wrongSquare[1] === c) {
                        classes.push("solve-sq-wrong");
                    }
                    if (this.hintLevel >= 1 && expected) {
                        if (expected.from[0] === r && expected.from[1] === c) {
                            classes.push("solve-sq-hint");
                        }
                        if (this.hintLevel >= 2 && expected.to[0] === r && expected.to[1] === c) {
                            classes.push("solve-sq-hint");
                        }
                    }

                    const piece = this.state.board[r][c];
                    const symbol = piece ? (PIECE_SYMBOLS[piece] || "") : "";
                    const pieceClass = piece ? (isWhitePiece(piece) ? "solve-piece-w" : "solve-piece-b") : "";
                    const coordRank = c === 0 ? `<span class="solve-coord-rank">${8 - r}</span>` : "";
                    const coordFile = r === 7 ? `<span class="solve-coord-file">${FILES[c]}</span>` : "";

                    boardHtml += `<button type="button" class="${classes.join(" ")}" data-r="${r}" data-c="${c}" aria-label="${FILES[c]}${8 - r}">
                        ${coordRank}${coordFile}
                        ${symbol ? `<span class="solve-piece ${pieceClass}">${symbol}</span>` : ""}
                    </button>`;
                }
            }
            boardHtml += "</div>";

            const promoHtml = this.pendingPromo
                ? `<div class="solve-promo-bar">
                    <span>Promote to:</span>
                    ${["Q", "R", "B", "N"].map((p) => {
                        const sym = PIECE_SYMBOLS[this.state.turn === "w" ? p : p.toLowerCase()];
                        return `<button type="button" class="button solve-promo-btn" data-promo="${p}">${sym} ${p}</button>`;
                    }).join("")}
                   </div>`
                : "";

            const solved = this.steps.length > 0 && this.stepIndex >= this.steps.length;

            this.container.innerHTML = `<div class="solve-layout">
                <div class="solve-board-col">
                    ${boardHtml}
                    ${promoHtml}
                </div>
                <div class="solve-side-col">
                    <div class="solve-status solve-status-${this.statusKind}">${this.escape(this.statusText)}</div>
                    <div class="solve-progress">${this.buildPlayedMovesHtml()}</div>
                    <div class="solve-controls">
                        <button type="button" class="button js-solve-hint" ${solved || this.steps.length === 0 ? "disabled" : ""}>Hint</button>
                        <button type="button" class="button js-solve-reset" ${this.stepIndex === 0 ? "disabled" : ""}>Reset</button>
                        <button type="button" class="button button-primary js-solve-reveal">Show solution</button>
                    </div>
                </div>
            </div>`;

            this.container.querySelectorAll(".solve-sq").forEach((sq) => {
                sq.addEventListener("click", () => {
                    this.handleSquareClick(
                        parseInt(sq.getAttribute("data-r"), 10),
                        parseInt(sq.getAttribute("data-c"), 10)
                    );
                });
            });
            this.container.querySelectorAll(".solve-promo-btn").forEach((btn) => {
                btn.addEventListener("click", () => {
                    this.choosePromotion(btn.getAttribute("data-promo"));
                });
            });
            const hintBtn = this.container.querySelector(".js-solve-hint");
            if (hintBtn) hintBtn.addEventListener("click", () => this.triggerHint());
            const resetBtn = this.container.querySelector(".js-solve-reset");
            if (resetBtn) resetBtn.addEventListener("click", () => this.reset());
            const revealBtn = this.container.querySelector(".js-solve-reveal");
            if (revealBtn) revealBtn.addEventListener("click", () => this.onRevealSolution());
        }
    }

    window.EndgameSolver = {
        mount(container, pgnText, fallbackFen, onRevealSolution) {
            return new SolveController(container, pgnText, fallbackFen, onRevealSolution);
        }
    };
})();
