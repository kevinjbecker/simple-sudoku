<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="icon" type="image/x-icon" href="/assets/brand/favicon/favicon.ico">
        <link rel="stylesheet" href="/_style/style.css" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"/>
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
        <script defer src="https://use.fontawesome.com/releases/v5.2.0/js/all.js" integrity="sha384-4oV5EgaV02iISL2ban6c/RmotsABqE4yZxZLcYMAdG7FAPsyHYAPpywE9PJo+Khy" crossorigin="anonymous"></script>
        <title>SimpleSudoku&mdash;Kevin J. Becker</title>
        <style>
            .sudoku-thick-right {
                border-right: 3px solid #dee2e6!important;
            }
            .sudoku-thick-bottom {
                border-bottom: 3px solid #dee2e6!important;
            }
            .table-bordered-td td {
                border: 1px solid #dee2e6;
                padding: 0;
            }
            .sudoku-input {
                width: 35px !important;
                text-align: center;
                background-color: transparent;
                border: none;
                color: #dee2e6;
                margin-top: 2px;
            }
            .sudoku-input::placeholder {
                color: #515151;
            }
            .sudoku-entered {
                color: #ff6868;
                font-weight: 900;
            }
            .sudoku-no-solution {
                color: #ff6868;
            }
            .sudoku-solution {
                color: #59c16a;
            }
        </style>
    </head>
    <body>
        <div class="container" style="margin-top:30px;">
            <div class="row" style="margin-bottom:15px;">
                <div class="col-sm-9 content">
                    <h4 class="display-3">Sudoku Solver</h4>
                    <p class="lead custom-lead">
                        A simple backtracking Sudoku solver built as a way to challenge myself into creating
                        more optimized solving for a Sudoku game.  Any game (if a solution is possible) can
                        be solved in just a few seconds or less.
                    </p>
                </div>
            </div>
            <div class="row" style="margin-bottom: 50px;">
                <div class = "col content">
                    <center>
                        <p>
                            Enter the game and then click "Solve!"
                            <br/>
                            Numbers like <span class = "sudoku-entered">this</span> were entered before solving.
                        </p>
                        <table class = "table table-dark table-bordered-td"
                               style = "width: 315px;
                                        height:315px;
                                        border: 3px solid #dee2e6;
                                        background-color: transparent !important;">
                            <tbody>
                                <?php
                                function genSpace($row, $col) {
                                    // general space items which all spaces have
                                    $ret = "<td class = '";

                                    // if our row is a "barrier" we need to add it
                                    if($row == 2 || $row == 5) {
                                        $ret .= "sudoku-thick-bottom ";
                                    }

                                    // see above ^^
                                    if($col == 2 || $col == 5) {
                                        $ret .= "sudoku-thick-right ";
                                    }

                                    $ret .= "'><input type = 'text' id = '$row-$col' name = '$row-$col' pattern = '\d*' placeholder = '#' maxlength='1' class = 'sudoku-input' onkeyup='moveCursor(event, $row, $col)'></td>";

                                    return $ret;
                                }

                                // this drops in all of our sudoku squares
                                for($row = 0; $row < 9; ++$row) {
                                    echo '<tr>';
                                    for($col = 0; $col < 9; ++$col) {
                                        // sets in place a table entry (with id of <row>-<col> for easy access)
                                        echo genSpace($row, $col);
                                    }
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                        <button class = "btn btn-graphite" onclick = "solve();">Solve!</button>
                        <button class = "btn btn-graphite" onclick = "clearBoard();">Clear board</button>
                        <p id = "nosol" class = "sudoku-no-solution d-none">No solution possible.</p>
                        <p id = "sol" class = "sudoku-solution d-none">Solution found.</p>
                    </center>
                </div>
            </div>
        </div>
        <script>
            const ES = -1;

            /// Board class holds the sudoku "game" board
            class Board {
                constructor(toCopy, row, col, val) {
                    // ES is an _E_MPTY _S_PACE, used to very obviously mark empty spaces
                    this.ES = -1;

                    // default constructor path
                    if(toCopy === undefined) {
                        this.board =
                        [
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES],
                            [ES,ES,ES,ES,ES,ES,ES,ES,ES]
                        ];
                    }
                    // copy constructor path (board provided to copy)
                    else {
                        this.board = $.extend(true, {}, toCopy.board);
                        this.board[row][col] = val;
                    }
                }

                /// sets the value at a location in the board
                setLocation(row, col, value) {
                    this.board[row][col] = value;
                }

                /// determines if the board is "solved" (aka no duplicates and no
                /// zeroes in the board remain)
                isSolved() {
                    // we determine if we are solved now
                    for(let row = 0; row < 9; ++row)
                        for(let col = 0; col < 9; ++col)
                            // if we have an empty space we are not solved
                            if(this.board[row][col] == ES) return false;
                    // lastly we check if this board is valid and return that value
                    return this.isValidConfig();
                }

                // determines if the board is "valid" (aka no duplicates)
                isValidConfig() {
                    for(let row = 0; row < 9; ++row) {
                        for(let col = 0; col < 9; ++col) {
                            let cur = this.board[row][col];
                            // goes to next loop if cur = 0
                            if(cur == ES) continue;
                            // checks to make sure the row is free of duplicate
                            for(let c = 0; c < 9; ++c) {
                                if(c != col && this.board[row][c] == cur) {
                                    return false;
                                }
                            }

                            // checks to make sure the column is free of ducplicate
                            for(let r = 0; r < 9; ++r) {
                                if(r != row && this.board[r][col] == cur) {
                                    return false;
                                }
                            }

                            // check the square we're in for duplicates
                            let squareR = (row < 3) ? 0 : (row < 6) ? 3 : 6;
                            let squareC = (col < 3) ? 0 : (col < 6) ? 3 : 6;
                            // goes through each square in the square
                            for(let r = squareR; r < squareR + 3; ++r) {
                                for(let c = squareC; c < squareC + 3; ++c) {
                                    // skip ourselves
                                    if(r == row && c == col) continue;
                                    // if we find a second one equal to ourselve
                                    if(this.board[r][c] == cur)
                                        return false;
                                }
                            }
                        }
                    }
                    return true;
                }

                /// gets the children of this configuration (next empty square
                /// filled with every number 1..9)
                getChildrenConfigs() {
                    // gets the next empty
                    let nextEmptyR = 0;
                    let nextEmptyC = 0;

                    outer_loop:
                    for(let row = 0; row < 9; ++row) {
                        for(let col = 0; col < 9; ++col) {
                            if(this.board[row][col] == ES) {
                                nextEmptyR = row;
                                nextEmptyC = col;
                                // breaks the outer loop as we've successfully found our next square
                                break outer_loop;
                            }
                        }
                    }

                    // used to store the children
                    let children = [];

                    for(let i = 1; i <= 9; ++i) {
                        // pushes new child into the array
                        children.push(new Board(this, nextEmptyR, nextEmptyC, i));
                    }

                    // returns our children
                    return children;
                }


                /// draws the board on the DOM version (for viewer to see)
                draw() {
                    for(let row = 0; row < 9; ++row) {
                        for(let col = 0; col < 9; ++col) {
                            $('#'+row+'-'+col).val((this.board[row][col] != ES) ? this.board[row][col] : "");
                        }
                    }
                }
            }

            /// used to solve the given input (if one exists)
            function solve() {
                /* hides the results at the bottom below the buttons if they
                   were shown */
                hideResults();

                // creates a new board
                let board = new Board();

                // fills our board with the values of each spce (if one exists)
                for(let row = 0; row < 9; ++row) {
                    for(let col = 0; col < 9; ++col) {
                        let spotVal = $("#"+row+"-"+col).val();
                        if(spotVal != "") {
                            board.setLocation(row, col, parseInt(spotVal));
                            // makes the values that were "entered" bolder so
                            // user knows what they entered
                            $("#"+row+"-"+col).addClass("sudoku-entered")
                        }
                    }
                }

                /* now things are setup we need to start the backtrack
                   we store it in result to fill in the spaces later */
                result = backtrack(board);

                // draws the solutioun if one exists
                if(result != null) {
                    result.draw();
                }

                // shows a message if a solution exists or otherwise
                $((result == null) ? "#nosol" : "#sol").removeClass("d-none");
            }


            /// non-intelligent check to make sure all characters are legal


            function backtrack(board) {
                // if the board is solved, we don't need to go any further
                if(board.isSolved()) {
                    return board;
                }

                /* if we're not solved, we need to go through each of the chidlren
                   of this generation */
                else {
                    /* gets the children configurations and backtracks if they
                       are valid */
                    let children = board.getChildrenConfigs();

                    for(let i = 0; i < children.length; ++i) {
                        if(children[i].isValidConfig()) {
                            sol = backtrack(children[i]);
                            /* in order to not return undefined, we need to
                               confirm it isn't before returning */
                            if(sol != undefined) {
                                return sol;
                            }
                        }
                    }
                }
                // if we get here, we have hit an issue... we cannot solve
                return null;
            }

            /// clears the board of any input values
            function clearBoard() {
                // only clears the board on user confirmation
                if(confirm("Press \"OK\" to clear the board.")) {
                    hideResults();
                    clearEntries();
                    removeEntered();
                }
            }


            /// clears the entries in the board
            function clearEntries() {
                for(let row = 0; row < 9; ++row) {
                    for(let col = 0; col < 9; ++col) {
                        $("#"+row+"-"+col).val("");
                    }
                }
            }


            /// removes entered class from the board
            function removeEntered() {
                for(let row = 0; row < 9; ++row) {
                    for(let col = 0; col < 9; ++col) {
                        $("#"+row+"-"+col).removeClass("sudoku-entered");
                    }
                }
            }


            function hideResults() {
                // resets our board
                $("#nosol").addClass("d-none");
                $("#sol").addClass("d-none");
            }

            // moves the cursor, onkeyup we highlight the text
            function moveCursor(e, row, col) {
                let key = e.keyCode || e.charCode;
                switch(key) {
                    case 13: // enter
                        solve();
                        break;
                    case 37: // left
                        if(col > 0) {
                            $("#"+row+"-"+(col-1)).focus();
                        }
                        break;
                    case 38: // up
                        if(row > 0) {
                            $("#"+(row-1)+"-"+col).focus();
                        }
                        break;
                    case 39: // right
                        if(col < 8) {
                            $("#"+row+"-"+(col+1)).focus();
                        }
                        break;
                    case 40: // down
                        if(row < 8) {
                            $("#"+(row+1)+"-"+col).focus();
                        }
                        break;
                    default: // anything else
                        removeEntered();
                }
            }
        </script>
    </body>
</html>
