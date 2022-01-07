function pieceCount(fen)
{
    var white = 0;
    var black = 0;
    var len = fen.length;
    for (i = 0; i < len && fen.charAt(i); i++)
    {
        switch (fen.charAt(i))
        {
            case 'P':
            case 'N':
            case 'B':
            case 'R':
            case 'Q':
            case 'K':
                white++;
                break;
            case 'p':
            case 'n':
            case 'b':
            case 'r':
            case 'q':
            case 'k':
                black++;
                break;
        }
    }
    result = white + " + " + black;
    return result;
}

function fen2diag(fen)
{
    var result = '<table border="1" cellspacing="0" style="width:0px; line-height:0px;"><tr><td nowrap>';
    var LEN = fen.length;
    var c = 0;
    var count = 0;
    var currChar;

    var control = 0, aux = "";

    for (var i = 0; i < LEN && fen.charAt(i) !== ' '; i++)
    {
        aux += currChar;
    }
    fen = aux;
    LEN = fen.length;

    for (i = 0; i < LEN; i++)
    {
        currChar = fen.charAt(i);
        if (currChar > '0' && currChar < '9')
            control += parseInt(currChar);
        else
        {
            switch (currChar) {
                case 'K':
                case 'Q':
                case 'R':
                case 'B':
                case 'N':
                case 'P':
                case 'k':
                case 'q':
                case 'r':
                case 'b':
                case 'n':
                case 'p':
                    control++;
                    break;
                case '/':
                    continue;
                default:
                    control += 100;
            }

        }
    }
    if (control !== 64)
    {
        fen = "8/8/8/8/8/8/8/8";
        LEN = fen.length;
    }

    for (i = 0; i < LEN; i++)
    {
        currChar = fen.charAt(i);
        if (currChar > '0' && currChar < '9')
        {
            for (t = 0; t < parseInt(currChar); t++)
            {
                result += '<IMG SRC="img/';
                count++;
                if ((count % 2) === 0)
                {
                    result += 1;
                } else
                {
                    result += 0;
                }
                result += '.GIF" width="25" />';
            }
        } else if (currChar === '/')
        {
            count++;
            result += "<br />";
        } else if (currChar === ' ')
        {
            break;
        } else
        {
            count++;
            if (count % 2 === 0)
            {
                c = 1;
            } else
            {
                c = 0;
            }
            result += '<IMG SRC="img/';
            switch (currChar) {
                case 'K':
                case 'Q':
                case 'R':
                case 'B':
                case 'N':
                case 'P':
                    result += currChar + c;
                    break;
                case 'k':
                case 'q':
                case 'r':
                case 'b':
                case 'n':
                case 'p':
                    result += currChar.toUpperCase() + (c + 2);
                    break;
            }
            result += '.GIF" width="25" />';
        }

    }
    result += "</td></tr></table>\r\n";
    return result;
}
