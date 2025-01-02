const DIAGRAM_TPL = `<div class='dd'>
        <a href='#' onclick='pgnLive(#pid#)' class='button'>PGNLive!</a>
        <a href='#' onclick='getPgn(#pid#)' class='button'>Get PGN</a>
        <h5 style='margin:5px;'>PID : #pid#</h5>#author#<br />#source#&nbsp;&nbsp;#date#
        <center><img src='./modules/diagram/?fen=#fen#&size=32' /></center>
        #stip#&nbsp;&nbsp;&nbsp;&nbsp;#pieces#
        <input type='text' value='#fen#' style='width:300px;' />
    </div>`;

const VIEWER_TPL = `<div class="titlebar">
<a href="javascript:close()" class="close-icon">X</a>
</div>
<div id='pgnlive'></div>`;

/**
 * shortcut for getting HTML Element by ID
 * @param {type} id
 * @returns {Element}
 */
function get(id) {
    return document.getElementById(id);
}

/**
 * POST request
 * @param {type} uri
 * @param {type} sendData
 * @returns {Promise}
 */
function request(uri, sendData) {
    return new Promise(function (resolve, reject) {
        let http = new XMLHttpRequest();
        http.onload = function () {
            if (http.status === 200) {
                resolve(JSON.parse(http.responseText));
            } else {
                let error = new Error(this.statusText);
                error.code = this.status;
                reject(error);
            }
        };
        http.onerror = function () {
            reject(new Error("Network error"));
        };

        http.open("post", uri, true);
        http.setRequestHeader('Content-type', 'application/json; charset=utf-8');
        http.send(JSON.stringify(sendData));
    });
}

/**
 * 
 * @param {string} template
 * @param {object} data
 * @returns {fill.result|String}
 */
function fill(template, data) {
    let result = template;
    for (const item in data) {
        result = result.replaceAll(item, data[item]);
    }
    return result;
}

async function getPosition(page) {
    get("advanced").className = "hidden";
    get("stat").innerHTML = "<h3>Loading... Please, wait!</h3>";
    let sendData = {
        "page": page,
        "author": get("author").value,
        "wmin": get("wmin").value,
        "wmax": get("wmax").value,
        "bmin": get("bmin").value,
        "bmax": get("bmax").value,
        "piece_pattern": patternMake(),
        "stipulation": get("stipulation").value,
        "theme": get("theme").value,
        "from_date": get("from_date").value,
        "to_date": get("to_date").value,
        "cook": get("cook").checked
    };

    const result = await request("api/?q=data", sendData);
    get("diag").innerHTML = "";
    for (const desc of result.html) {
        get("diag").innerHTML += fill(DIAGRAM_TPL, desc);
    }

    get("stat").innerHTML = "<h5>" + result.stat +
        " positions found.  <a href='javascript:getPdf();'>get pdf</a> (no more then 1000 diagrams per PDFfile)</h5>";
    if (page !== 0) {
        get("stat").innerHTML += "<a href='#' onclick='getPosition(" + (page - 1) +
            ")'><img src='images/left.gif' /></a> ";
    }
    if (page * 12 + 12 < result.stat) {
        get("stat").innerHTML += " <a href='#' onclick='getPosition(" + (page + 1) +
            ")'><img src='images/right.gif' /></a>";
    }
}

function patternMake() {
    let result = "";
    return result;
}

function clearAll() {
    get("stat").innerHTML = '';
    get("diag").innerHTML = '';
    get("author").value = '';
    get("wmin").value = '3';
    get("wmax").value = '3';
    get("bmin").value = '3';
    get("bmax").value = '3';
    get("from_date").value = '';
    get("to_date").value = '';
}

async function pgnLive(pid) {
    let pgn = await request('api/?q=pgn/' + pid);
    console.log(pgn.data);
    const config = {
        "pgn": pgn.data,
        "showMoves": "right",
        "showPlayers": "none",
        "menu": {
          "getPgn": { "enabled": true, "fileName": 'blah.pgn' },
        }
    };
    
    if(get("pgnlive")){
        LichessPgnViewer(get("pgnlive"), config);
    }
    get("pgnlive-wrapper").className = "showed";
    return false;
}

function getPgn(pid) {
    window.open('/api/?q=pgn/' + pid, 'getpgn', '');
    return false;
}

function getPdf() {
    const author = get("author").value;
    const wmin = get("wmin").value;
    const wmax = get("wmax").value;
    const bmin = get("bmin").value;
    const bmax = get("bmax").value;
    const piece_pattern = patternMake();
    const stipulation = get("stipulation").value;
    const theme = get("theme").value;
    const from_date = get("from_date").value;
    const to_date = get("to_date").value;
    window.open('getpdf.php?author=' + author +
        '&wmin=' + wmin +
        '&wmax=' + wmax +
        '&bmin=' + bmin +
        '&bmax=' + bmax +
        '&piece_pattern=' + piece_pattern +
        '&stipulation=' + stipulation +
        '&theme=' + theme +
        '&from_date=' + from_date +
        '&to_date=' + to_date,
        'file.pdf', '');
}

function showAdvanced() {
    get("advanced").className = "showed";
}

function hideAdvanced() {
    get("advanced").className = "hidden";
}

function close() {
    get("pgnlive-wrapper").className = "hidden";
    get("pgnlive-wrapper").innerHTML = VIEWER_TPL;
}