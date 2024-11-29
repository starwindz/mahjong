<?php
// swos api for web interface of speedrun database
// rev.2
// define connection
$conn = mysqli_connect(
    'localhost',
    'starwindz',
    'aa1701',
    'starwindz'
);

// get parameters
$option = $_GET['option'];
$tmp = $_GET['username'];        $username = $tmp;
$tmp = $_GET['game_mode'];       if ($tmp == '') $game_mode  = 0; else $game_mode       = $tmp;
$tmp = $_GET['difficulty'];      if ($tmp == '') $difficulty = 0; else $difficulty      = $tmp;
$tmp = $_GET['played'];          if ($tmp == '') $played     = 0; else $played          = $tmp;
$tmp = $_GET['record_mode'];     if ($tmp == '') $record_mode     = 0; else $record_mode     = $tmp;
$tmp = $_GET['record'];          if ($tmp == '') $record          = 0; else $record          = $tmp;
$tmp = $_GET['replay_filename']; if ($tmp == '') $replay_filename = 0; else $replay_filename = $tmp;
$tmp = $_GET['best_record']; if ($tmp == '') $best_record = 0; else $best_record = $tmp;
$tmp = $_GET['current_record']; if ($tmp == '') $current_record = 0; else $current_record = $tmp;
$tmp = $_GET['best_replay_filename']; if ($tmp == '') $best_replay_filename = ''; else $best_replay_filename = $tmp;
$tmp = $_GET['current_replay_filename']; if ($tmp == '') $current_replay_filename = ''; else $current_replay_filename = $tmp;

function brk()
{
    //return "<br>\n";
    return "\n";
}

function upsert_row()
{
    global $conn;
    global $username, $game_mode, $difficulty, $played, $best_record, $current_record, $best_replay_filename, $current_replay_filename;
    // step 1
    $sql =
       'INSERT INTO speedrun_records(username, game_mode, difficulty, played, best_record, current_record, best_replay_filename, current_replay_filename)
        VALUES ("'.$username. '", '.$game_mode.', ' .$difficulty. ', ' .
        $played. ', ' .
        $best_record. ', ' .
        $current_record. ', ' .
        '"' . $best_replay_filename. '", ' .
        '"' . $current_replay_filename. '"' .
        ')
        ON DUPLICATE KEY UPDATE ' .
        'played = ' . $played. ', ' .
        'best_record = ' . $best_record. ', ' .
        'current_record = ' . $current_record. ', ' .
        'best_replay_filename = "' . $best_replay_filename. '", ' .
        'current_replay_filename = "' . $current_replay_filename. '"'.
        ';';
    //echo $sql . "\n";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo 'upsert_row: ok' . brk();
    }
    else {
        echo 'upsert_row: failed' . brk();
    }
}

function update_rank($difficulty)
{
    global $conn;
    global $username, $game_mode, $played, $best_record, $current_record, $best_replay_filename, $current_replay_filename;
    // phase 1
    $sql =
       'SELECT
            username, game_mode, difficulty, best_record, best_replay_filename, real_rank FROM(
        SELECT
            username, game_mode, difficulty, best_record, best_replay_filename,
            (@ranking := @ranking + 1) AS ranking,
            (@real_rank := IF(@last < best_record, @real_rank := @real_rank + 1, @real_rank)) AS real_rank,
            (@last := best_record)
        FROM
            speedrun_records AS a,
            (SELECT @ranking := 0, @last := 0, @real_rank := 0) AS b
        WHERE
            game_mode = ' .$game_mode. ' AND ' . 'difficulty = ' .$difficulty. '
        ORDER BY
            -best_record DESC
        ) AS cnt WHERE username = "' . $username. '"';
    //echo $sql . "\n";
    $rank_result = -1;
    $result = mysqli_query($conn, $sql);
    if ($result) {
        //echo 'update_rank (1): ok' . brk();
        while($row = mysqli_fetch_array($result)) {
            $rank_result = $row['real_rank'];
        }
    }
    else {
        //echo 'update_rank (1): failed' . brk();
    }
    // phase 2
    if ($rank_result != -1) {
        $sql =
           'UPDATE speedrun_records SET ranking = ' .$rank_result. '
            WHERE username = "' . $username. '"
            AND game_mode = ' .$game_mode. ' AND ' . 'difficulty = ' .$difficulty. ';';
        //echo $sql . "\n";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            //echo 'update_rank (2): ok' . brk();
        }
        else {
            //echo 'update_rank (2): failed' . brk();
        }
    }
    echo $rank_result . brk();
}

function delete_row($difficulty)
{
    global $conn;
    global $username, $game_mode, $played, $best_record, $current_record, $best_replay_filename, $current_replay_filename;
    $sql =
       'DELETE FROM speedrun_records
        WHERE username = "' . $username. '"
        AND game_mode = ' .$game_mode. ' AND ' . 'difficulty = ' .$difficulty. ';';
    //echo $sql . brk();
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo 'delete_row: ok' . brk();
    }
    else {
        echo 'delete_row: failed' . brk();
    }
}

function delete_rows()
{
    global $conn;
    global $username, $game_mode, $played, $best_record, $current_record, $best_replay_filename, $current_replay_filename;
    $sql =
       'DELETE FROM speedrun_records
        WHERE username = "' . $username. '"
        AND game_mode = ' .$game_mode. ';';
    //echo $sql . brk();
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo 'delete_rows: ok' . brk();
    }
    else {
        echo 'delete_rows: failed' . brk();
    }
}

function view_rows()
{
    global $conn;
    global $username, $game_mode, $difficulty, $played, $best_record, $current_record, $best_replay_filename, $current_replay_filename;
    $sql =
       'SELECT username, game_mode, difficulty, played,
        best_record, current_record, ranking,
        best_replay_filename, current_replay_filename
        FROM speedrun_records WHERE username = "' . $username. '"';
    //echo $sql . brk();
	//echo "attemping to connect sql server..." . brk();
    $result = mysqli_query($conn, $sql);
	//echo "done..." . brk();	
	if ($result) {
		//echo "printing..." . brk();	
		while($row = mysqli_fetch_array($result)) {
			echo $row['username'] . ', ' . $row['game_mode'] . ', ' . $row['difficulty'] . ', ' .
             $row['played'] . ', ' .
             $row['best_record'] . ", " .$row['current_record'] . ', ' .
             $row['ranking'] . ', ' .
             $row['best_replay_filename'] . ', ' .$row['current_replay_filename'] . brk();
		}
		//echo "done" . brk();
    }
	else {
		//echo "server connection error" . brk();
	}
}

function make_leaderboard($difficulty)
{
    global $conn;
    global $username, $game_mode, $played, $best_record, $current_record, $best_replay_filename, $current_replay_filename;
    $sql =
       'SELECT
            username, game_mode, difficulty, played, best_record, best_replay_filename, real_rank FROM(
        SELECT
            username, game_mode, difficulty, played, best_record, best_replay_filename,
            (@ranking := @ranking + 1) AS ranking,
            (@real_rank := IF(@last < best_record, @real_rank := @real_rank + 1, @real_rank)) AS real_rank,
            (@last := best_record)
        FROM
            speedrun_records AS a,
            (SELECT @ranking := 0, @last := 0, @real_rank := 0) AS b
        WHERE
            game_mode = ' .$game_mode. ' AND ' . 'difficulty = ' .$difficulty. '
        ORDER BY
            -best_record DESC
        LIMIT 100
        ) AS cnt;';
    //echo $sql . "\n";
    $result = mysqli_query($conn, $sql);
    $cnt = 0;
    while($row = mysqli_fetch_array($result)) {
        $cnt++;
		/*
        echo $row['username'] . ', ' . $row['game_mode'] . ', ' . $row['difficulty'] . ', ' .
             $row['played'] . ', ' .
             $row['best_record'] . ", " .
             $row['real_rank'] . ', ' .
             $row['best_replay_filename'] . brk();
		*/
        echo $row['username'] . ', ' . $row['game_mode'] . ', ' . $row['difficulty'] . ', ' .
             $row['best_record'] . ", " .
             $row['best_replay_filename'] . ', ' .
             $row['real_rank'] . brk();
    }
    
    for ($i = 0; $i <= 100 - $cnt - 1; $i++) {
        echo 'none, 0, 0, 10000, none, 0'. brk();
    }
}

/////
// add a row
if ($option == 'upsert_row') {
    upsert_row();
}

// update rank
else if ($option == 'update_rank') {
    global $difficulty; 
    update_rank($difficulty);
}
 
// update ranks
else if ($option == 'update_ranks') {
    for ($i = 0; $i <= 4; $i++) {
        update_rank($i);
    } 
}

// delete a row
else if ($option == 'delete_row') {
    global $difficulty;		
    delete_row($difficulty);
}

// delete rows
else if ($option == 'delete_rows') {
	/*
    for ($i = 0; $i <= 4; $i++) {
		delete_row($i);
	}
	*/
	delete_rows();
}

// sync a row
else if ($option == 'sync_row') {
    global $difficulty; 
    
    if ($played > 0) {
        upsert_row();
        update_rank($difficulty);
    }
    else {
        delete_row($difficulty);
    }
}

// view rows
else if ($option == 'view_rows') {
    view_rows();
}

// make leaderboard
else if ($option == 'make_leaderboard') {
    global $difficulty;
    make_leaderboard($difficulty);
}

// make leaderboards
else if ($option == 'make_leaderboards') {
    for ($i = 0; $i <= 4; $i++) {
        make_leaderboard($i);
    }
}

else {
    echo 'swos api' .brk();
}
?>