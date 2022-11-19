const DIAGRAM_TPL = `<div class='dd'><a href='#' onclick='pgnLive(#pid#)' class='button'>PGNLive!</a>
<a href='#' onclick='getPgn(#pid#)' class='button'>Get PGN</a>
<h5 style='margin:5px;'>PID : #pid#</h5>#author#<br />#source#&nbsp;&nbsp;#date#
<center><img src='./modules/diagram/?fen=#fen#&size=32' /></center>
#stip#&nbsp;&nbsp;&nbsp;&nbsp;#pieces#
<input type='text' value='#fen#' style='width:300px;' /></div>`;

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
    for (let item in data) {
        result = result.replaceAll(item, data[item]);
    }
    return result;
}

async function getPosition(page) {
    get("advanced").className = "hidden";
    showItem("diag");
    get("stat").innerHTML = "<h3>Loading... Please, wait!</h3>";
    let sendData = {
        "page": page,
        "author": get("author").value,
        "wpiece": get("wpiece").value,
        "wsign": get("wsign").value,
        "bpiece": get("bpiece").value,
        "bsign": get("bsign").value,
        "piece_pattern": patternMake(),
        "stipulation": get("stipulation").value,
        "theme": get("theme").value,
        "fromDate": get("fromDate").value,
        "toDate": get("toDate").value,
        "cook": get("cook").checked
    };

    const result = await request("data.php", sendData);
    const p = result["html"];
    get("diag").innerHTML = "";
    for (const desc of p) {
        get("diag").innerHTML += fill(DIAGRAM_TPL, desc);
    }

    get("stat").innerHTML = "<h5>" + result["stat"] +
        " positions found.  <a href='javascript:getPdf();'>get pdf</a> (no more then 1000 diagrams per PDFfile)</h5>";
    if (page !== 0) {
        get("stat").innerHTML += "<a href='#' onclick='getPosition(" + (page - 1) +
            ")'><img src='images/left.gif' /></a> ";
    }
    if (page * 12 + 12 < result["stat"]) {
        get("stat").innerHTML += " <a href='#' onclick='getPosition(" + (page + 1) +
            ")'><img src='images/right.gif' /></a>";
    }
}

function patternMake() {
    let result = "";
    /*
     i=0;
     while(i<get("q0").value)
     {
     result += "a";
     i++;
     }
     i=0;
     while(i<get("r0").value)
     {
     result += "b";
     i++;
     }
     i=0;
     while(i<get("b0").value)
     {
     result += "c";
     i++;
     }
     i=0;
     while(i<get("n0").value)
     {
     result += "d";
     i++;
     }
     i=0;
     while(i<get("p0").value)
     {
     result += "e";
     i++;
     }
     i=0;
     while(i<get("q2").value)
     {
     result += "f";
     i++;
     }
     i=0;
     while(i<get("r2").value)
     {
     result += "g";
     i++;
     }
     i=0;
     while(i<get("b2").value)
     {
     result += "h";
     i++;
     }
     i=0;
     while(i<get("n2").value)
     {
     result += "i";
     i++;
     }
     i=0;
     while(i<get("p2").value)
     {
     result += "j";
     i++;
     }
     */
    return result;
}

function clearAll() {
    //    get("q0").value = '';
    //    get("r0").value = '';
    //    get("b0").value = '';
    //    get("n0").value = '';
    //    get("p0").value = '';
    //    get("q2").value = '';
    //    get("r2").value = '';
    //    get("b2").value = '';
    //    get("n2").value = '';
    //    get("p2").value = '';
    get("debug").innerHTML = '';
    get("stat").innerHTML = '';
    get("diag").innerHTML = '';
    get("author").value = '';
    get("wpiece").value = '';
    get("bpiece").value = '';
    get("fromDate").value = '';
    get("toDate").value = '';
}

async function pgnLive(pid) {
    /* window.open('pgnlive/pgnlive.php?pid=' + pid, 'pgnlive', 'location=0,toolbar=0,scrollbar=auto'); */
    let pgn = await request('api/?q=pgn/' + pid);
    console.log(pgn);
    let config = {
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
    window.open('getpgn.php?pid=' + pid, 'getpgn', '');
    return false;
}

function showItem(item) {
    get("diag").className = "hidden";
    get("about").className = "hidden";
    get("linx").className = "hidden";
    get(item).className = "showed";
}

function getPdf() {
    const author = get("author").value;
    const wpiece = get("wpiece").value;
    const wsign = get("wsign").value;
    const bpiece = get("bpiece").value;
    const bsign = get("bsign").value;
    const piece_pattern = patternMake();
    const stipulation = get("stipulation").value;
    const theme = get("theme").value;
    const fromDate = get("fromDate").value;
    const toDate = get("toDate").value;
    window.open('getpdf.php?author=' + author +
        '&wpiece=' + wpiece +
        '&wsign=' + wsign +
        '&bpiece=' + bpiece +
        '&bsign=' + bsign +
        '&piece_pattern=' + piece_pattern +
        '&stipulation=' + stipulation +
        '&theme=' + theme +
        '&fromDate=' + fromDate +
        '&toDate=' + toDate,
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