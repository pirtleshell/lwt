<?php

/**
 * \file
 * \brief Connects to the database and check its state.
 * 
 * @author https://github.com/HugoFara/ HugoFara
 */

require_once __DIR__ . "/kernel_utility.php";
require __DIR__ . "/../connect.inc.php";

/**
 * Do a SQL query to the database. 
 * It is a wrapper for mysqli_query function.
 *
 * @param string $sql Query using SQL syntax
 *
 * @global mysqli $DBCONNECTION Connection to the database
 *
 * @return mysqli_result|true
 */
function do_mysqli_query($sql)
{
    global $DBCONNECTION;
    $res = mysqli_query($DBCONNECTION, $sql);
    if ($res != false) {
        return $res;
    }
    echo '</select></p></div>
    <div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
    '<p><b>Fatal Error in SQL Query:</b> ' . 
    tohtml($sql) . 
    '</p>' . 
    '<p><b>Error Code &amp; Message:</b> [' . 
    mysqli_errno($DBCONNECTION) . 
    '] ' . 
    tohtml(mysqli_error($DBCONNECTION)) . 
    "</p></div><hr /><pre>Backtrace:\n\n";
    debug_print_backtrace();
    echo '</pre><hr />';
    die('</body></html>');
}

/**
 * Run a SQL query, you can specify its behavior and error message.
 *
 * @param string $sql       MySQL query
 * @param string $m         Success phrase to prepend to the number of affected rows
 * @param bool   $sqlerrdie To die on errors (default = TRUE)
 *
 * @return string Error message if failure, or the number of affected rows
 */
function runsql($sql, $m, $sqlerrdie = true): string 
{
    if ($sqlerrdie) {
        $res = do_mysqli_query($sql); 
    } else {
        $res = mysqli_query($GLOBALS['DBCONNECTION'], $sql); 
    }        
    if ($res == false) {
        $message = "Error: " . mysqli_error($GLOBALS['DBCONNECTION']);
    } else {
        $num = mysqli_affected_rows($GLOBALS['DBCONNECTION']);
        $message = ($m == '') ? (string)$num : $m . ": " . $num;
    }
    return $message;
}


/**
 * Return the record "value" in the first line of the database if found.
 *
 * @param string $sql MySQL query
 * 
 * @return float|int|string|null Any returned value from the database.
 * 
 * @since 2.6.0-fork Officially return numeric types.
 */
function get_first_value($sql) 
{
    $res = do_mysqli_query($sql);        
    $record = mysqli_fetch_assoc($res);
    if ($record) { 
        $d = $record["value"]; 
    } else {
        $d = null; 
    }
    mysqli_free_result($res);
    return $d;
}


/**
 * Replace Windows line return ("\r\n") by Linux ones ("\n").
 * 
 * @param string $s Input string
 * 
 * @return string Adapted string. 
 */
function prepare_textdata($s): string 
{
    return str_replace("\r\n", "\n", $s);
}

// -------------------------------------------------------------

function prepare_textdata_js($s): string 
{
    $s = convert_string_to_sqlsyntax($s);
    if ($s == "NULL") { 
        return "''"; 
    }
    return str_replace("''", "\\'", $s);
}


/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string. "NULL" if the input string is empty.
 * 
 * @global $DBDONNECTION
 */
function convert_string_to_sqlsyntax($data): string 
{
    global $DBCONNECTION;
    $result = "NULL";
    $data = trim(prepare_textdata($data));
    if ($data != "") { 
        $result = "'".mysqli_real_escape_string($DBCONNECTION, $data)."'"; 
    }
    return $result;
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string
 */
function convert_string_to_sqlsyntax_nonull($data): string 
{
    $data = trim(prepare_textdata($data));
    return  "'" . mysqli_real_escape_string($GLOBALS['DBCONNECTION'], $data) . "'";
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped string
 */
function convert_string_to_sqlsyntax_notrim_nonull($data): string 
{
    return "'" . 
    mysqli_real_escape_string($GLOBALS['DBCONNECTION'], prepare_textdata($data)) . 
    "'";
}

// -------------------------------------------------------------

function convert_regexp_to_sqlsyntax($input): string 
{
    $output = preg_replace_callback(
        "/\\\\x\{([\da-z]+)\}/ui", 
        function ($a) {
            $num = $a[1];
            $dec = hexdec($num);
            return "&#$dec;";
        }, 
        preg_replace(
            array('/\\\\(?![-xtfrnvup])/u','/(?<=[[^])[\\\\]-/u'), 
            array('','-'), 
            $input
        )
    );
    return convert_string_to_sqlsyntax_nonull(
        html_entity_decode($output, ENT_NOQUOTES, 'UTF-8')
    );
}

/**
 * Validate a language ID
 *
 * @param string $currentlang Language ID to validate
 *
 * @return string '' if the language is not valid, $currentlang otherwise
 * 
 * @global string $tbpref Table name prefix
 */
function validateLang($currentlang): string 
{
    global $tbpref;
    if ($currentlang == '') {
        return '';
    }
    $sql_string = 'SELECT count(LgID) AS value 
    FROM ' . $tbpref . 'languages 
    WHERE LgID=' . $currentlang;
    if (get_first_value($sql_string) == 0) {  
        return ''; 
    } 
    return $currentlang;
}

/**
 * Validate a text ID
 *
 * @param string $currenttext Text ID to validate
 *
 * @global string '' if the text is not valid, $currenttext otherwise
 * 
 * @global string $tbpref Table name prefix
 */
function validateText($currenttext): string 
{
    global $tbpref;
    if ($currenttext == '') {
        return '';
    }
    $sql_string = 'SELECT count(TxID) AS value 
    FROM ' . $tbpref . 'texts WHERE TxID=' . 
    $currenttext;
    if (get_first_value($sql_string) == 0) {  
        return ''; 
    }
    return $currenttext;
}

// -------------------------------------------------------------

function validateTag($currenttag,$currentlang) 
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        $sql = "SELECT (
            " . $currenttag . " IN (
                SELECT TgID 
                FROM {$tbpref}words, {$tbpref}tags, {$tbpref}wordtags 
                WHERE TgID = WtTgID AND WtWoID = WoID" . 
                ($currentlang != '' ? " AND WoLgID = " . $currentlang : '') .
                " group by TgID order by TgText
            )
        ) AS value";
        /*if ($currentlang == '') {
            $sql = "SELECT (
                $currenttag in (
                    select TgID from {$tbpref}words, 
                    {$tbpref}tags, 
                    {$tbpref}wordtags 
                    where TgID = WtTgID and WtWoID = WoID 
                    group by TgID 
                    order by TgText
                    )
                ) as value"; 
        } else {
            $sql = "SELECT (
                $currenttag in (
                    select TgID 
                    from {$tbpref}words, {$tbpref}tags, 
                    {$tbpref}wordtags 
                    where TgID = WtTgID and WtWoID = WoID and WoLgID = $currentlang 
                    group by TgID order by TgText
                )
                ) as value"; 
        }*/
        $r = get_first_value($sql);
        if ($r == 0) { 
            $currenttag = ''; 
        } 
    }
    return $currenttag;
}

// -------------------------------------------------------------

function validateArchTextTag($currenttag,$currentlang) 
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        if ($currentlang == '') {
            $sql = "select (
                " . $currenttag . " in (
                    select T2ID 
                    from {$tbpref}archivedtexts, 
                    {$tbpref}tags2, 
                    {$tbpref}archtexttags 
                    where T2ID = AgT2ID and AgAtID = AtID 
                    group by T2ID order by T2Text
                )
            ) as value"; 
        }
        else {
            $sql = "select (
                " . $currenttag . " in (
                    select T2ID 
                    from {$tbpref}archivedtexts, 
                    {$tbpref}tags2, 
                    {$tbpref}archtexttags 
                    where T2ID = AgT2ID and AgAtID = AtID and AtLgID = $currentlang 
                    group by T2ID order by T2Text
                )
            ) as value"; 
        }
        $r = get_first_value($sql);
        if ($r == 0 ) { 
            $currenttag = ''; 
        } 
    }
    return $currenttag;
}

// -------------------------------------------------------------

function validateTextTag($currenttag,$currentlang) 
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        if ($currentlang == '') {
            $sql = "select (
                $currenttag in (
                    select T2ID 
                    from {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags 
                    where T2ID = TtT2ID and TtTxID = TxID 
                    group by T2ID 
                    order by T2Text
                )
            ) as value"; 
        } else {
            $sql = "select (
                $currenttag in (
                    select T2ID 
                    from {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags 
                    where T2ID = TtT2ID and TtTxID = TxID and TxLgID = $currentlang 
                    group by T2ID order by T2Text
                )
            ) as value"; 
        }
        $r = get_first_value($sql);
        if ($r == 0 ) { 
            $currenttag = ''; 
        } 
    }
    return $currenttag;
}

/** 
 * Convert a setting to 0 or 1
 *
 * @param string     $key The input value
 * @param string|int $dft Default value to use, should be convertible to string
 * 
 * @return int
 * 
 * @psalm-return 0|1
 */
function getSettingZeroOrOne($key, $dft): int
{
    $r = getSetting($key);
    $r = ($r == '' ? $dft : (((int)$r !== 0) ? 1 : 0));
    return (int)$r;
}

/**
 * Get a setting from the database. It can also check for its validity.
 * 
 * @param  string $key Setting key. If $key is 'currentlanguage' or 
 *                     'currenttext', we validate language/text.
 * @return string $val Value in the database if found, or an empty string
 * @global string $tbpref Table name prefix
 */
function getSetting($key) 
{
    global $tbpref;
    $val = get_first_value(
        'SELECT StValue AS value 
        FROM ' . $tbpref . 'settings 
        WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if (isset($val)) {
        $val = trim($val);
        if ($key == 'currentlanguage' ) { 
            $val = validateLang($val); 
        }
        if ($key == 'currenttext' ) { 
            $val = validateText($val); 
        }
        return $val;
    }
    else { 
        return ''; 
    }
}

/**
 * Get the settings value for a specific key. Return a default value when possible
 * 
 * @param string $key Settings key
 * 
 * @return string Requested setting, or default value, or ''
 * 
 * @global string $tbpref Table name prefix
 */
function getSettingWithDefault($key) 
{
    global $tbpref;
    $dft = get_setting_data();
    $val = get_first_value(
        'SELECT StValue AS value
         FROM ' . $tbpref . 'settings
         WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if (isset($val) && $val != '') {
        return trim($val); 
    }
    if (isset($dft[$key])) { 
        return $dft[$key]['dft']; 
    }
    return '';
    
}

/**
 * Save the setting identified by a key with a specific value.
 * 
 * @param string $k Setting key
 * @param mixed  $v Setting value, will get converted to string
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string Error or success message
 */
function saveSetting($k, $v) 
{
    global $tbpref;
    $dft = get_setting_data();
    if (!isset($v)) {
        return ''; 
    }
    if ($v === '') {
        return '';
    }
    runsql(
        'DELETE FROM ' . $tbpref . 'settings 
        WHERE StKey = ' . convert_string_to_sqlsyntax($k), 
        ''
    );
    if (isset($dft[$k]) && $dft[$k]['num']) {
        $v = (int)$v;
        if ($v < $dft[$k]['min']) { 
            $v = $dft[$k]['dft']; 
        }
        if ($v > $dft[$k]['max']) { 
            $v = $dft[$k]['dft']; 
        }
    }
    $dum = runsql(
        'INSERT INTO ' . $tbpref . 'settings (StKey, StValue) values(' .
        convert_string_to_sqlsyntax($k) . ', ' . 
        convert_string_to_sqlsyntax($v) . ')', 
        ''
    );
    return $dum;
}

/**
 * Check if the _lwtgeneral table exists, create it if not.
 */
function LWTTableCheck(): void
{
    if (mysqli_num_rows(do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")) == 0) {
        runsql(
            "CREATE TABLE IF NOT EXISTS _lwtgeneral ( 
                LWTKey varchar(40) NOT NULL, 
                LWTValue varchar(40) DEFAULT NULL, 
                PRIMARY KEY (LWTKey)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
        if (mysqli_num_rows(
            do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")
        ) == 0
        ) { 
            my_die("Unable to create table '_lwtgeneral'!"); 
        }
    }
}

// -------------------------------------------------------------

function LWTTableSet($key, $val): void
{
    LWTTableCheck();
    runsql(
        "INSERT INTO _lwtgeneral (LWTKey, LWTValue) VALUES (
            " . convert_string_to_sqlsyntax($key) . ", 
            " . convert_string_to_sqlsyntax($val) . "
        ) ON DUPLICATE KEY UPDATE LWTValue = " . convert_string_to_sqlsyntax($val), 
        ''
    );
}

// -------------------------------------------------------------

function LWTTableGet($key): string
{
    LWTTableCheck();
    return (string)get_first_value(
        "SELECT LWTValue as value 
        FROM _lwtgeneral 
        WHERE LWTKey = " . convert_string_to_sqlsyntax($key)
    );
}

/**
 * Adjust the auto-incrementation in the database.
 *
 * @global string $tbpref Database table prefix
 */
function adjust_autoincr($table, $key): void 
{
    global $tbpref;
    $val = get_first_value(
        'SELECT max(' . $key .')+1 AS value FROM ' . $tbpref . $table
    );
    if (!isset($val)) { 
        $val = 1; 
    }
    $sql = 'ALTER TABLE ' . $tbpref . $table . ' AUTO_INCREMENT = ' . $val;
    do_mysqli_query($sql);
}

/**
 * Optimize the database.
 *
 * @global string $trbpref Table prefix
 */
function optimizedb(): void 
{
    global $tbpref;
    adjust_autoincr('archivedtexts', 'AtID');
    adjust_autoincr('languages', 'LgID');
    adjust_autoincr('sentences', 'SeID');
    adjust_autoincr('texts', 'TxID');
    adjust_autoincr('words', 'WoID');
    adjust_autoincr('tags', 'TgID');
    adjust_autoincr('tags2', 'T2ID');
    adjust_autoincr('newsfeeds', 'NfID');
    adjust_autoincr('feedlinks', 'FlID');
    $sql = 
    'SHOW TABLE STATUS 
    WHERE Engine IN ("MyISAM","Aria") AND (
        (Data_free / Data_length > 0.1 AND Data_free > 102400) OR Data_free > 1048576
    ) AND Name';
    if(empty($tbpref)) { 
        $sql.= " NOT LIKE '\_%'"; 
    }
    else { 
        $sql.= " LIKE " . convert_string_to_sqlsyntax(rtrim($tbpref, '_')) . "'\_%'"; 
    }
    $res = do_mysqli_query($sql);
    while($row = mysqli_fetch_assoc($res)) {
        runsql('OPTIMIZE TABLE ' . $row['Name'], '');
    }
    mysqli_free_result($res);
}

/**
 * Update the word count for Japanese language (using MeCab only).
 * 
 * @param int $japid Japanese language ID
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix.
 */
function update_japanese_word_count($japid)
{
    global $tbpref;
        
    // STEP 1: write the useful info to a file
    $db_to_mecab = tempnam(sys_get_temp_dir(), "{$tbpref}db_to_mecab");
    $mecab_args = ' -F %m%t\\t -U %m%t\\t -E \\n ';
    $mecab = get_mecab_path($mecab_args);

    $sql = "SELECT WoID, WoTextLC FROM {$tbpref}words 
    WHERE WoLgID = $japid AND WoWordCount = 0";
    $res = do_mysqli_query($sql);
    $fp = fopen($db_to_mecab, 'w');
    while ($record = mysqli_fetch_assoc($res)) {
        fwrite($fp, $record['WoID'] . "\t" . $record['WoTextLC'] . "\n");
    }
    mysqli_free_result($res);
    fclose($fp);

    // STEP 2: process the data with MeCab and refine the output
    $handle = popen($mecab . $db_to_mecab, "r");
    if (feof($handle)) {
        pclose($handle);
        unlink($db_to_mecab);
        return;
    }
    $sql = "INSERT INTO {$tbpref}mecab (MID, MWordCount) values";
    $values = array();
    while (!feof($handle)) {
        $row = fgets($handle, 1024);
        $arr = explode("4\t", $row, 2);
        if (!empty($arr[1])) {
            $cnt = substr_count(
                preg_replace('$[^267]\t$u', '', $arr[1]), 
                "\t"
            );
            if (empty($cnt)) {
                $cnt = 1;
            }
            $values[] = "(" . convert_string_to_sqlsyntax($arr[0]) . ", $cnt)";
        }
    }
    pclose($handle);
    if (empty($values)) {
        // Nothing to update, quit
        return;
    }
    $sql .= join(",", $values);


    // STEP 3: edit the database
    do_mysqli_query(
        "CREATE TEMPORARY TABLE {$tbpref}mecab ( 
            MID mediumint(8) unsigned NOT NULL, 
            MWordCount tinyint(3) unsigned NOT NULL, 
            PRIMARY KEY (MID)
        ) CHARSET=utf8"
    );
    
    do_mysqli_query($sql);
    do_mysqli_query(
        "UPDATE {$tbpref}words 
        JOIN {$tbpref}mecab ON MID = WoID 
        SET WoWordCount = MWordCount"
    );
    do_mysqli_query("DROP TABLE {$tbpref}mecab");

    unlink($db_to_mecab);
}

/**
 * Initiate the number of words in terms for all languages.
 * 
 * Only terms with a word count set to 0 are changed.
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix
 */
function init_word_count(): void 
{
    global $tbpref;
    $sqlarr = array();
    $i = 0;
    $min = 0;
    /**
     * @var string|null ID for the Japanese language using MeCab
     */
    $japid = get_first_value(
        "SELECT group_concat(LgID) value 
        FROM {$tbpref}languages 
        WHERE UPPER(LgRegexpWordCharacters)='MECAB'"
    );

    if ($japid) {
        update_japanese_word_count((int)$japid);
    }
    $sql = "SELECT WoID, WoTextLC, LgRegexpWordCharacters, LgSplitEachChar 
    FROM {$tbpref}words, {$tbpref}languages 
    WHERE WoWordCount = 0 AND WoLgID = LgID 
    ORDER BY WoID";
    $result = do_mysqli_query($sql);
    while ($rec = mysqli_fetch_assoc($result)){
        if ($rec['LgSplitEachChar']) {
            $textlc = preg_replace('/([^\s])/u', "$1 ", $rec['WoTextLC']);
        } else {
            $textlc = $rec['WoTextLC'];
        }
        $sqlarr[]= ' WHEN ' . $rec['WoID'] . ' 
        THEN ' . preg_match_all(
            '/([' . $rec['LgRegexpWordCharacters'] . ']+)/u', $textlc, $ma
        );
        if (++$i % 1000 == 0) {
            $max = $rec['WoID'];
            $sqltext = "UPDATE  {$tbpref}words 
            SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . "
            END 
            WHERE WoWordCount=0 AND WoID BETWEEN $min AND $max";
            do_mysqli_query($sqltext);
            $min = $max;
            $sqlarr = array();
        }
    }
    mysqli_free_result($result);
    if (!empty($sqlarr)) {
        $sqltext = "UPDATE {$tbpref}words 
        SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . ' 
        END where WoWordCount=0';
        do_mysqli_query($sqltext);
    }
}

/**
 * Initiate the number of words in terms for all languages
 * 
 * Only terms with a word count set to 0 are changed.
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix
 * 
 * @deprecated Use init_word_count: same effect, but more logical name. Will be 
 * removed in version 3.0.0.
 */
function set_word_count()
{
    init_word_count();
}

/**
 * Parse a Japanese text using MeCab and add it to the database.
 *
 * @param string $text Text to parse.
 * @param int    $id   Text ID. If $id = -1 print results, 
 *                     if $id = -2 return splitted texts
 *
 * @return null|string[] Splitted sentence if $id = -2
 *
 * @since 2.5.1-fork Works even if LOAD DATA LOCAL INFILE operator is disabled.
 * @since 2.6.0-fork Use PHP instead of SQL, slower but works better.
 *
 * @global string $tbpref Database table prefix
 *
 * @psalm-return non-empty-list<string>|null
 */
function parse_japanese_text($text, $id): ?array
{
    global $tbpref;
    $text = preg_replace('/[ \t]+/u', ' ', $text);
    $text = trim($text);
    if ($id == -1) {
        echo '<div id="check_text" style="margin-right:50px;">
        <h2>Text</h2>
        <p>' . str_replace("\n", "<br /><br />", tohtml($text)). '</p>'; 
    } else if ($id == -2) {
        $text = preg_replace("/[\n]+/u", "\n¶", $text);
        return explode("\n", $text);
    }

    $file_name = tempnam(sys_get_temp_dir(), $tbpref . "tmpti");
    // We use the format "word  num num" for all nodes
    $mecab_args = " -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n";
    $mecab_args .= " -o $file_name ";
    $mecab = get_mecab_path($mecab_args);
    
    // WARNING: \n is converted to PHP_EOL here!
    $handle = popen($mecab, 'w');
    fwrite($handle, $text);
    pclose($handle);

    runsql(
        "CREATE TEMPORARY TABLE IF NOT EXISTS temptextitems2 (
            TiCount smallint(5) unsigned NOT NULL,
            TiSeID mediumint(8) unsigned NOT NULL,
            TiOrder smallint(5) unsigned NOT NULL,
            TiWordCount tinyint(3) unsigned NOT NULL,
            TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
        ) DEFAULT CHARSET=utf8", 
        ''
    );
    $handle = fopen($file_name, 'r');
    $mecabed = fread($handle, filesize($file_name));
    fclose($handle);
    $values = array();
    $order = 0;
    $sid = 1;
    if ($id > 0) {
        $sid = (int)get_first_value(
            "SELECT IFNULL(MAX(`SeID`)+1,1) as value 
            FROM {$tbpref}sentences"
        );
    }
    $term_type = 0;
    $count = 0;
    $row = array(0, 0, 0, "", 0);
    foreach (explode(PHP_EOL, $mecabed) as $line) {
        list($term, $node_type, $third) = explode(mb_chr(9), $line);
        if ($term_type == 2 || $term == 'EOP' && $third == '7') {
            $sid += 1;
        }
        $row[0] = $sid; // TiSeID
        $row[1] = $count + 1; // TiCount
        $count += mb_strlen($term);
        $last_term_type = $term_type;
        if ($third == '7') {
            if ($term == 'EOP') {
                $term = '¶';
            }
            $term_type = 2;
        } else if (str_contains('267', $node_type)) {
            $term_type = 0;
        } else {
            $term_type = 1;
        }
        $order += (int)(($term_type == 0) && ($last_term_type == 0)) + 
        (int)!(($term_type == 1) && ($last_term_type == 1));
        $row[2] = $order; // TiOrder
        $row[3] = convert_string_to_sqlsyntax_notrim_nonull($term); // TiText
        $row[4] = $term_type == 0 ? 1 : 0; // TiWordCount
        $values[] = "(" . implode(",", $row) . ")";
    }
    do_mysqli_query(
        "INSERT INTO temptextitems2 (
            TiSeID, TiCount, TiOrder, TiText, TiWordCount
        ) VALUES " . implode(',', $values)
    );
    // Delete elements TiOrder=@order
    do_mysqli_query("DELETE FROM temptextitems2 WHERE TiOrder=$order");
    do_mysqli_query(
        "INSERT INTO {$tbpref}temptextitems (
            TiCount, TiSeID, TiOrder, TiWordCount, TiText
        ) 
        SELECT MIN(TiCount) s, TiSeID, TiOrder, TiWordCount, 
        group_concat(TiText ORDER BY TiCount SEPARATOR '')
        FROM temptextitems2
        GROUP BY TiOrder"
    );
    do_mysqli_query("DROP TABLE temptextitems2");
    unlink($file_name);
    return null;
}

/**
 * Insert a processed text in the data in pure SQL way.
 * 
 * @param string $text Preprocessed text to insert
 * @param int    $id   Text ID
 * 
 * @return null
 * 
 * @global string $tbpref Database table prefix
 */
function save_processed_text_with_sql($text, $id)
{
    global $tbpref;
    $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tbpref . "tmpti.txt";
    $fp = fopen($file_name, 'w');
    fwrite($fp, $text);
    fclose($fp);
    do_mysqli_query("SET @order=0, @sid=1, @count = 0;");
    if ($id > 0) {
        do_mysqli_query(
            "SET @sid=(SELECT ifnull(max(`SeID`)+1,1) FROM `{$tbpref}sentences`);"
        );
    }
    $sql = "LOAD DATA LOCAL INFILE " . convert_string_to_sqlsyntax($file_name) ."
    INTO TABLE {$tbpref}temptextitems 
    FIELDS TERMINATED BY '\\t' LINES TERMINATED BY '\\n' (@word_count, @term)
    SET 
        TiSeID = @sid, 
        TiCount = (@count:=@count+CHAR_LENGTH(@term))+1-CHAR_LENGTH(@term),
        TiOrder = IF(
            @term LIKE '%\\r',
            CASE 
                WHEN (@term:=REPLACE(@term,'\\r','')) IS NULL THEN NULL 
                WHEN (@sid:=@sid+1) IS NULL THEN NULL 
                WHEN @count:= 0 IS NULL THEN NULL 
                ELSE @order := @order+1 
            END, 
            @order := @order+1
        ), 
        TiText = @term,
        TiWordCount = @word_count";
    do_mysqli_query($sql);
    unlink($file_name);
}

/**
 * Parse a text using the default tools. It is a not-japanese text.
 *
 * @param string $text Text to parse
 * @param int    $id   Text ID. If $id == -2, only split the text.
 * @param int    $lid  Language ID.
 *
 * @return null|string[] If $id == -2 return a splitted version of the text.
 *
 * @since 2.5.1-fork Works even if LOAD DATA LOCAL INFILE operator is disabled.
 *
 * @global string $tbpref Database table prefix
 *
 * @psalm-return non-empty-list<string>|null
 */
function parse_standard_text($text, $id, $lid): ?array
{
    global $tbpref;
    $sql = "SELECT * FROM {$tbpref}languages WHERE LgID=$lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = (string)$record['LgRemoveSpaces'];
    $splitSentence = (string)$record['LgRegexpSplitSentences'];
    $noSentenceEnd = (string)$record['LgExceptionsSplitSentences'];
    $termchar = (string)$record['LgRegexpWordCharacters'];
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);
    // Split text paragraphs using " ¶" symbol 
    $text = str_replace("\n", " ¶", $text);
    $text = trim($text);
    if ($record['LgSplitEachChar']) {
        $text = preg_replace('/([^\s])/u', "$1\t", $text);
    }
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($id == -1) { 
        echo "<div id=\"check_text\" style=\"margin-right:50px;\">
        <h4>Text</h4>
        <p " .  ($rtlScript ? 'dir="rtl"' : '') . ">" . 
        str_replace("¶", "<br /><br />", tohtml($text)) . 
        "</p>"; 
    }
    // "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter
    $text = preg_replace_callback(
        "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u",
        // Arrow functions were introduced in PHP 7.4 
        //fn ($matches) => find_latin_sentence_end($matches, $noSentenceEnd)
        function ($matches) use ($noSentenceEnd) {
            return find_latin_sentence_end($matches, $noSentenceEnd); 
        },
        $text
    );
    // Paragraph delimiters become a combination of ¶ and carriage return \r
    $text = str_replace(array("¶"," ¶"), array("¶\r","\r¶"), $text);
    $text = preg_replace(
        array(
            '/([^' . $termchar . '])/u',
            '/\n([' . $splitSentence . '][\'`"”)\]‘’‹›“„«»』」]*)\n\t/u',
            '/([0-9])[\n]([:.,])[\n]([0-9])/u'
        ), 
        array("\n$1\n", "$1", "$1$2$3"), 
        $text
    );
    if ($id == -2) {
        $text = remove_spaces(
            str_replace(
                array("\r\r","\t","\n"), array("\r","",""), $text
            ), 
            $removeSpaces
        );
        return explode("\r", $text);
    }

    
    $text = trim(
        preg_replace(
            array(
                "/\r(?=[]'`\"”)‘’‹›“„«»』」 ]*\r)/u",
                '/[\n]+\r/u',
                '/\r([^\n])/u',
                "/\n[.](?![]'`\"”)‘’‹›“„«»』」]*\r)/u",
                "/(\n|^)(?=.?[$termchar][^\n]*\n)/u"
            ), 
            array(
                "",
                "\r",
                "\r\n$1",
                ".\n",
                "\n1\t"
            ), 
            str_replace(array("\t","\n\n"), array("\n",""), $text)
        )
    );
    $text = remove_spaces(
        preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", $text), $removeSpaces
    );
    // It is faster to write to a file and let SQL do its magic, but may run into
    // security restrictions
    $use_local_infile = false;
    if (
        !in_array(
        get_first_value("SELECT @@GLOBAL.local_infile as value"), 
        array(1, '1', 'ON')
        )
    ) {
        $use_local_infile = false;
    }
    if ($use_local_infile) {
        save_processed_text_with_sql($text, $id);
    } else {
        $values = array();
        $order = 0;
        $sid = 1;
        if ($id > 0) {
            $sid = (int)get_first_value(
                "SELECT IFNULL(MAX(`SeID`)+1,1) as value 
                FROM {$tbpref}sentences"
            );
        }
        $count = 0;
        $row = array(0, 0, 0, "", 0);
        foreach (explode("\n", $text) as $line) {
            list($word_count, $term) = explode("\t", $line);
            $row[0] = $sid; // TiSeID
            $row[1] = $count + 1; // TiCount
            $count += mb_strlen($term);
            if (str_ends_with($term, "\r")) {
                $term = str_replace("\r", '', $term);
                $sid++;
                $count = 0;
            }
            $row[2] = ++$order; // TiOrder
            $row[3] = convert_string_to_sqlsyntax_notrim_nonull($term); // TiText
            $row[4] = (int)$word_count; // TiWordCount
            $values[] = "(" . implode(",", $row) . ")";
        }
        do_mysqli_query(
            "INSERT INTO {$tbpref}temptextitems (
                TiSeID, TiCount, TiOrder, TiText, TiWordCount
            ) VALUES " . implode(',', $values)
        );
    }
    return null;
}


/**
 * Pre-parse the input text before a definitive parsing by a specialized parser.
 *
 * @param string $text Text to parse
 * @param int    $id   Text ID
 * @param int    $lid  Language ID
 *
 * @return null|string[] If $id = -2 return a splitted version of the text
 *
 * @global string $tbpref Database table prefix
 *
 * @psalm-return non-empty-list<string>|null
 */
function prepare_text_parsing($text, $id, $lid): ?array
{
    global $tbpref;
    $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $termchar = (string)$record['LgRegexpWordCharacters'];
    $replace = explode("|", $record['LgCharacterSubstitutions']);
    mysqli_free_result($res);
    $text = prepare_textdata($text);
    //if(is_callable('normalizer_normalize')) $s = normalizer_normalize($s);
    do_mysqli_query('TRUNCATE TABLE ' . $tbpref . 'temptextitems');

    // because of sentence special characters
    $text = str_replace(array('}','{'), array(']','['), $text);    
    foreach ($replace as $value) {
        $fromto = explode("=", trim($value));
        if (count($fromto) >= 2) {
            $text = str_replace(trim($fromto[0]), trim($fromto[1]), $text);
        }
    }

    if ('MECAB' == strtoupper(trim($termchar))) {
        return parse_japanese_text($text, $id);
    } 
    return parse_standard_text($text, $id, $lid);
}

/**
 * Echo the sentences in a text. Prepare JS data for words and word count.
 * 
 * @param int $lid Language ID
 * 
 * @global string $tbpref Database table prefix
 * 
 * @return void
 */
function check_text_valid($lid)
{
    global $tbpref;
    $wo = $nw = array();
    $res = do_mysqli_query(
        'SELECT GROUP_CONCAT(TiText order by TiOrder SEPARATOR "") 
        Sent FROM ' . $tbpref . 'temptextitems group by TiSeID'
    );
    echo '<h4>Sentences</h4><ol>';
    while($record = mysqli_fetch_assoc($res)){
        echo "<li>" . tohtml($record['Sent']) . "</li>";
    }
    mysqli_free_result($res);
    echo '</ol>';
    $res = do_mysqli_query(
        "SELECT count(`TiOrder`) cnt, if(0=TiWordCount,0,1) as len, 
        LOWER(TiText) as word, WoTranslation 
        FROM {$tbpref}temptextitems 
        LEFT JOIN {$tbpref}words ON lower(TiText)=WoTextLC AND WoLgID=$lid 
        GROUP BY lower(TiText)"
    );
    while ($record = mysqli_fetch_assoc($res)) {
        if ($record['len']==1) {
            $wo[]= array(
                tohtml($record['word']),
                $record['cnt'],
                tohtml($record['WoTranslation'])
            );
        } else{
            $nw[] = array(
                tohtml((string)$record['word']), 
                tohtml((string)$record['cnt'])
            );
        }
    }
    mysqli_free_result($res);
    echo '<script type="text/javascript">
    WORDS = ', json_encode($wo), ';
    NOWORDS = ', json_encode($nw), ';
    </script>';
}


/**
 * Change the default values for default language, default text, etc...
 * 
 * @param int    $id  New default text ID
 * @param int    $lid New default language ID
 * @param string $sql 
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix
 */
function update_default_values($id, $lid, $sql)
{
    global $tbpref;
    do_mysqli_query(
        'ALTER TABLE ' . $tbpref . 'textitems2 
        ALTER Ti2LgID SET DEFAULT ' . $lid . ', 
        ALTER Ti2TxID SET DEFAULT ' . $id
    );
    do_mysqli_query(
        "INSERT INTO {$tbpref}textitems2 (
            Ti2WoID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
        ) $sql
        SELECT  WoID, TiSeID, TiOrder, TiWordCount, TiText 
        FROM {$tbpref}temptextitems 
        LEFT JOIN {$tbpref}words 
        ON LOWER(TiText) = WoTextLC AND TiWordCount=1 AND WoLgID = $lid 
        ORDER BY TiOrder, TiWordCount"
    );
    do_mysqli_query(
        'ALTER TABLE ' . $tbpref . 'sentences 
        ALTER SeLgID SET DEFAULT ' . $lid . ', 
        ALTER SeTxID SET DEFAULT ' . $id
    );
    do_mysqli_query('set @a=0;');
    do_mysqli_query(
        'INSERT INTO ' . $tbpref . 'sentences (
            SeOrder, SeFirstPos, SeText
        ) SELECT 
        @a:=@a+1, 
        min(if(TiWordCount=0,TiOrder+1,TiOrder)),
        GROUP_CONCAT(TiText order by TiOrder SEPARATOR "") 
        FROM ' . $tbpref . 'temptextitems 
        group by TiSeID'
    );
    do_mysqli_query(
        'ALTER TABLE ' . $tbpref . 'textitems2 
        ALTER Ti2LgID DROP DEFAULT, 
        ALTER Ti2TxID DROP DEFAULT'
    );
    do_mysqli_query(
        'ALTER TABLE ' . $tbpref . 'sentences 
        ALTER SeLgID DROP DEFAULT, 
        ALTER SeTxID DROP DEFAULT'
    );
}

/**
 * Check a text and display statistics about it.
 * 
 * @param string   $sql
 * @param bool     $rtlScript true if language is right-to-left
 * @param string[] $wl        Words lengths
 * 
 * @return void
 */
function check_text($sql, $rtlScript, $wl)
{
    $mw = array();
    if(!empty($wl)) {
        $res = do_mysqli_query($sql);
        while($record = mysqli_fetch_assoc($res)){
            $mw[]= array(
                tohtml((string)$record['word']),
                $record['cnt'],
                tohtml((string)$record['WoTranslation'])
            );
        }
        mysqli_free_result($res);
    }
    ?>
<script type="text/javascript">
    MWORDS = <?php echo json_encode($mw) ?>;
    if (<?php echo json_encode($rtlScript); ?>) {
        $(function() {
            $("li").attr("dir", "rtl");
        });
    }
    let h='<h4>Word List <span class="red2">(red = already saved)</span></h4>' + 
    '<ul class="wordlist">';
    $.each(
        WORDS, 
        function (k,v) {
            h += '<li><span' + (v[2]==""?"":' class="red2"') + '>[' + v[0] + '] — ' 
            + v[1] + (v[2]==""?"":' — ' + v[2]) + '</span></li>';
        }
        );
    h += '</ul><p>TOTAL: ' + WORDS.length 
    + '</p><h4>Expression List</span></h4><ul class="expressionlist">';
    $.each(MWORDS, function (k,v) {
        h+= '<li><span>[' + v[0] + '] — ' + v[1] + 
        (v[2]==""?"":' — ' + v[2]) + '</span></li>';
    });
    h += '</ul><p>TOTAL: ' + MWORDS.length + 
    '</p><h4>Non-Word List</span></h4><ul class="nonwordlist">';
    $.each(NOWORDS, function(k,v) {
        h+= '<li>[' + v[0] + '] — ' + v[1] + '</li>';
    });
    h += '</ul><p>TOTAL: ' + NOWORDS.length + '</p>'
    $('#check_text').append(h);
</script>

    <?php
}

/**
 * Check a text that contains expressions.
 *
 * @param int    $id     Text ID
 * @param int    $lid    Language ID
 * @param int[]  $wl     Word length
 * @param int    $wl_max Maximum word length
 * @param string $mw_sql SQL-formatted string
 *
 * @return string SQL-formatted query string
 *
 * @global string $tbpref Database table prefix
 */
function check_text_with_expressions($id, $lid, $wl, $wl_max, $mw_sql): string
{
    global $tbpref;

    $set_wo_sql = $set_wo_sql_2 = $del_wo_sql = $init_var = '';
    do_mysqli_query('SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2');
    do_mysqli_query('SET GLOBAL tmp_table_size = 1024 * 1024 * 1024 * 2');
    // For all possible multi-words length,  
    for ($i=$wl_max*2 -1; $i>1; $i--) {
        $set_wo_sql .= "WHEN (@a$i := @a".($i-1) . ") IS NULL THEN NULL ";
        $set_wo_sql_2 .= "WHEN (@a$i := @a".($i-2) .") IS NULL THEN NULL ";
        $del_wo_sql .= "WHEN (@a$i := @a0) IS NULL THEN NULL ";
        $init_var .= "@a$i=0,";
    }
    // 2.8.1-fork: @a0 is always 0? @f always '' but necessary to force code execution
    do_mysqli_query(
        "SET $init_var@a1=0, @a0=0, @se_id=0, @c='', @d=0, @f='', @ti_or=0;"
    );
    // Create a table to store length of each terms
    do_mysqli_query(
        "CREATE TEMPORARY TABLE IF NOT EXISTS {$tbpref}numbers( 
            n  tinyint(3) unsigned NOT NULL
        );"
    );
    do_mysqli_query("TRUNCATE TABLE {$tbpref}numbers");
    do_mysqli_query(
        "INSERT IGNORE INTO {$tbpref}numbers(n) VALUES (" . 
        implode('),(', $wl) . 
        ');'
    );
    if ($id>0) {
        $sql = 'SELECT straight_join WoID, sent, TiOrder - (2*(n-1)) TiOrder, 
        n TiWordCount,word';
    } else {
        $sql = 'SELECT straight_join count(WoID) cnt, n as len, 
        lower(WoText) as word, WoTranslation';
    }
    $sql .= 
    " FROM (
        SELECT straight_join 
        if(@se_id=TiSeID and @ti_or=TiOrder,
            if((@ti_or:=TiOrder+@a0) is null,TiSeID,TiSeID),
            if(
                @se_id=TiSeID, 
                IF(
                    (@d=1) and (0<>TiWordCount), 
                    CASE $set_wo_sql_2  
                        WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL 
                        WHEN (@se_id:=TiSeID+@a0) IS NULL THEN NULL 
                        WHEN (@ti_or:=TiOrder+@a0) IS NULL THEN NULL 
                        WHEN (@c:=concat(@c,TiText)) IS NULL THEN NULL 
                        WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL 
                        ELSE TiSeID 
                    END, 
                    CASE $set_wo_sql
                        WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL 
                        WHEN (@se_id:=TiSeID+@a0) IS NULL THEN NULL 
                        WHEN (@ti_or:=TiOrder+@a0) IS NULL THEN NULL 
                        WHEN (@c:=concat(@c,TiText)) IS NULL THEN NULL 
                        WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL 
                        ELSE TiSeID 
                    END
                ), 
                CASE $del_wo_sql 
                    WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL 
                    WHEN (@se_id:=TiSeID+@a0) IS NULL THEN NULL 
                    WHEN (@ti_or:=TiOrder+@a0) IS NULL THEN NULL 
                    WHEN (@c:=concat(TiText,@f)) IS NULL THEN NULL 
                    WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL 
                    ELSE TiSeID 
                END
            )
        ) sent, 
        if(
            @d=0, 
            NULL, 
            if(
                CRC32(@z:=substr(@c,CASE n$mw_sql END))<>CRC32(LOWER(@z)),
                @z,
                ''
            )
        ) word,
        if(@d=0 or ''=@z, NULL, lower(@z)) lword, 
        TiOrder,
        n FROM {$tbpref}numbers , {$tbpref}temptextitems
    ) ti, 
    {$tbpref}words 
    WHERE lword IS NOT NULL AND WoLgID=$lid AND 
    WoTextLC=lword AND WoWordCount=n";
    $sql .= ($id>0) ? ' UNION ALL ' : ' GROUP BY WoID ORDER BY WoTextLC';
    return $sql;
}

/**
 * Parse the input text.
 *
 * @param string     $text Text to parse
 * @param string|int $lid  Language ID (LgID from languages table)
 * @param int        $id   References whether the text is new to the database
 *                     $id = -1     => Check, return protocol
 *                     $id = -2     => Only return sentence array
 *                     $id = TextID => Split: insert sentences/textitems entries in DB
 *
 * @global string $tbpref Database table prefix
 *
 * @return null|string[] The sentence array if $id = -2
 *
 * @psalm-return non-empty-list<string>|null
 */
function splitCheckText($text, $lid, $id) 
{
    global $tbpref;
    $wl = array();
    $wl_max = 0;
    $mw_sql = '';
    $lid = (int) $lid;
    $sql = "SELECT LgRightToLeft FROM {$tbpref}languages WHERE LgID = $lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    // Just checking if LgID exists with ID should be enough
    if ($record == false) {
        my_die("Language data not found: $sql"); 
    }
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);

    if ($id == -2) {
        /*
        Replacement code not created yet 

        trigger_error(
            "Using splitCheckText with \$id == -2 is deprectad and won't work in 
            LWT 3.0.0. Use format_text instead.", 
            E_USER_WARNING
        );*/
        return prepare_text_parsing($text, -2, $lid);
    }
    prepare_text_parsing($text, $id, $lid);

    // Check text
    if ($id == -1) {
        check_text_valid($lid);
    }

    $res = do_mysqli_query(
        "SELECT WoWordCount AS word_count, count(WoWordCount) AS cnt 
        FROM {$tbpref}words 
        WHERE WoLgID = $lid AND WoWordCount > 1 
        GROUP BY WoWordCount"
    );
    while ($record = mysqli_fetch_assoc($res)){
        if ($wl_max < (int)$record['word_count']) { 
            $wl_max = (int)$record['word_count'];
        }
        $wl[] = (string)$record['word_count'];
        $mw_sql .= ' WHEN ' . $record['word_count'] . 
        ' THEN @a' . (intval($record['word_count']) * 2 - 1);
    }
    mysqli_free_result($res);
    $sql = '';
    // Text has multi-words
    if (!empty($wl)) {
        $sql = check_text_with_expressions($id, $lid, $wl, $wl_max, $mw_sql);
    }
    if ($id > 0) {
        update_default_values($id, $lid, $sql);
    }
    
    // Check text
    if ($id == -1) {
        check_text($sql, (bool)$rtlScript, $wl);
    }
    
    do_mysqli_query("TRUNCATE TABLE {$tbpref}temptextitems");
}


/**
 * Reparse all texts in order.
 *
 * @global string $tbpref Database table prefix
 */
function reparse_all_texts(): void 
{
    global $tbpref;
    runsql('TRUNCATE ' . $tbpref . 'sentences', '');
    runsql('TRUNCATE ' . $tbpref . 'textitems2', '');
    adjust_autoincr('sentences', 'SeID');
    init_word_count();
    $sql = "select TxID, TxLgID from {$tbpref}texts";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $id = (int) $record['TxID'];
        splitCheckText(
            (string)get_first_value(
                "SELECT TxText as value 
                from {$tbpref}texts 
                where TxID = $id"
            ), 
            (string)$record['TxLgID'], $id 
        );
    }
    mysqli_free_result($res);
}

/**
 * Update the database if it is using an outdate version.
 *
 * @param string $dbname Name of the database
 *
 * @global string $tbpref Database table prefix
 * @global 0|1    $debug  Output debug messages.
 * 
 * @return void
 */
function update_database($dbname)
{
    global $tbpref, $debug;

    // DB Version
    $currversion = get_version_number();
    
    $res = mysqli_query(
        $GLOBALS['DBCONNECTION'], 
        "SELECT StValue AS value 
        FROM {$tbpref}settings 
        WHERE StKey = 'dbversion'"
    );
    if (mysqli_errno($GLOBALS['DBCONNECTION']) != 0) { 
        my_die(
            'There is something wrong with your database ' . $dbname . 
            '. Please reinstall.'
        ); 
    }
    $record = mysqli_fetch_assoc($res);
    if ($record) {
        $dbversion = $record["value"];
    } else {
        $dbversion = 'v001000000';
    }
    mysqli_free_result($res);
    
    // Do DB Updates if tables seem to be old versions
    
    if ($dbversion < $currversion) {

        if ($debug) {
            echo "<p>DEBUG: check DB collation: "; 
        }
        if ('utf8utf8_general_ci' != get_first_value(
            'SELECT concat(default_character_set_name, default_collation_name) as value 
            FROM information_schema.SCHEMATA 
            WHERE schema_name = "' . $dbname . '"'
        )
        ) {
            runsql("SET collation_connection = 'utf8_general_ci'", '');
            runsql(
                'ALTER DATABASE `' . $dbname . 
                '` CHARACTER SET utf8 COLLATE utf8_general_ci', 
                ''
            );
            if ($debug) { 
                echo 'changed to utf8_general_ci</p>'; 
            }
        } else if ($debug) { 
            echo 'OK</p>'; 
        }

        if ($debug) { 
            echo "<p>DEBUG: do DB updates: $dbversion --&gt; $currversion</p>"; 
        }
        runsql(
            "ALTER TABLE {$tbpref}words 
            ADD WoTodayScore DOUBLE NOT NULL DEFAULT 0, 
            ADD WoTomorrowScore DOUBLE NOT NULL DEFAULT 0, 
            ADD WoRandom DOUBLE NOT NULL DEFAULT 0", 
            '', false);
        runsql(
            "ALTER TABLE {$tbpref}words 
            ADD WoWordCount tinyint(3) unsigned NOT NULL DEFAULT 0 AFTER WoSentence", 
            '', 
            false);
        runsql(
            "ALTER TABLE {$tbpref}words 
            ADD INDEX WoTodayScore (WoTodayScore), 
            ADD INDEX WoTomorrowScore (WoTomorrowScore), 
            ADD INDEX WoRandom (WoRandom)", 
            '', 
            false
        );
        runsql(
            "ALTER TABLE {$tbpref}languages 
            ADD LgRightToLeft tinyint(1) UNSIGNED NOT NULL DEFAULT 0", 
            '', 
            false
        );
        runsql(
            "ALTER TABLE {$tbpref}texts 
            ADD TxAnnotatedText LONGTEXT NOT NULL AFTER TxText", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}archivedtexts 
            ADD AtAnnotatedText LONGTEXT NOT NULL AFTER AtText", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}tags 
            CHANGE TgComment TgComment VARCHAR(200) 
            CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}tags2 
            CHANGE T2Comment T2Comment VARCHAR(200) 
            CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}languages 
            CHANGE LgGoogleTTSURI LgExportTemplate VARCHAR(1000) 
            CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}texts 
            ADD TxSourceURI VARCHAR(1000) 
            CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}archivedtexts 
            ADD AtSourceURI VARCHAR(1000) 
            CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}texts ADD TxPosition smallint(5) NOT NULL DEFAULT 0", 
            '', false
        );
        runsql(
            "ALTER TABLE {$tbpref}texts ADD TxAudioPosition float NOT NULL DEFAULT 0", 
            '', false
        );
        runsql(
            "ALTER TABLE `{$tbpref}wordtags` DROP INDEX WtWoID", '', false
        );
        runsql(
            "ALTER TABLE `{$tbpref}texttags` DROP INDEX TtTxID", '', false
        );
        runsql(
            "ALTER TABLE `{$tbpref}archtexttags` DROP INDEX AgAtID", '', false
        );

        // Database manipulations to upgrade from the official LWT to the community 
        // fork
        runsql(
            "ALTER TABLE `{$tbpref}archivedtexts` 
            MODIFY COLUMN `AtLgID` tinyint(3) unsigned NOT NULL, 
            MODIFY COLUMN `AtID` smallint(5) unsigned NOT NULL,
            ADD INDEX AtLgIDSourceURI (AtSourceURI(20),AtLgID)", 
            '', false
        );
        runsql(
            "ALTER TABLE `{$tbpref}languages` 
            MODIFY COLUMN `LgID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT, 
            MODIFY COLUMN `LgRemoveSpaces` tinyint(1) unsigned NOT NULL, 
            MODIFY COLUMN `LgSplitEachChar` tinyint(1) unsigned NOT NULL, 
            MODIFY COLUMN `LgRightToLeft` tinyint(1) unsigned NOT NULL",
            '', false
        );
        runsql(
            "ALTER TABLE `{$tbpref}sentences` 
            MODIFY COLUMN `SeID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
            MODIFY COLUMN `SeLgID` tinyint(3) unsigned NOT NULL, 
            MODIFY COLUMN `SeTxID` smallint(5) unsigned NOT NULL, 
            MODIFY COLUMN `SeOrder` smallint(5) unsigned NOT NULL",
            '', false
        );
        runsql(
            "ALTER TABLE `{$tbpref}texts` 
            MODIFY COLUMN `TxID` smallint(5) unsigned NOT NULL AUTO_INCREMENT, 
            MODIFY COLUMN `TxLgID` tinyint(3) unsigned NOT NULL, 
            ADD INDEX TxLgIDSourceURI (TxSourceURI(20),TxLgID)",
            '', false
        );
        runsql(
            "ALTER TABLE `{$tbpref}words` 
            MODIFY COLUMN `WoID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
            MODIFY COLUMN `WoLgID` tinyint(3) unsigned NOT NULL, 
            MODIFY COLUMN `WoStatus` tinyint(4) NOT NULL", 
            '', false
        ); 
        runsql(
            "ALTER TABLE `{$tbpref}words` 
            DROP INDEX WoTextLC",
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'words` 
            DROP INDEX WoLgIDTextLC, 
            ADD UNIQUE INDEX WoTextLCLgID (WoTextLC, WoLgID)', 
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'words` 
            ADD INDEX WoWordCount (WoWordCount)', 
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'archtexttags` 
            MODIFY COLUMN `AgAtID` smallint(5) unsigned NOT NULL, 
            MODIFY COLUMN `AgT2ID` smallint(5) unsigned NOT NULL', 
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'tags` 
            MODIFY COLUMN `TgID` smallint(5) unsigned NOT NULL AUTO_INCREMENT', 
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'tags2` 
            MODIFY COLUMN `T2ID` smallint(5) unsigned NOT NULL AUTO_INCREMENT', 
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'wordtags` 
            MODIFY COLUMN `WtTgID` smallint(5) unsigned NOT NULL AUTO_INCREMENT', 
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'texttags` 
            MODIFY COLUMN `TtTxID` smallint(5) unsigned NOT NULL, 
            MODIFY COLUMN `TtT2ID` smallint(5) unsigned NOT NULL', 
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'temptextitems` 
            ADD TiCount smallint(5) unsigned NOT NULL, 
            DROP TiLgID, 
            DROP TiTxID',
            '', false
        );
        runsql(
            'ALTER TABLE `' . $tbpref . 'temptextitems` 
            ADD TiCount smallint(5) unsigned NOT NULL', 
            '', false
        );
        runsql(
            "UPDATE {$tbpref}sentences 
            JOIN {$tbpref}textitems2 
            ON Ti2SeID = SeID AND Ti2Order=SeFirstPos AND Ti2WordCount=0 
            SET SeFirstPos = SeFirstPos+1", 
            '', false
        );
        if ($debug) { 
            echo '<p>DEBUG: rebuilding tts</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS tts (
                TtsID mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
                TtsTxt varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                TtsLc varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                PRIMARY KEY (TtsID), 
                UNIQUE KEY TtsTxtLC (TtsTxt,TtsLc)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1", 
            ''
        );

        // Since 2.9.0, fixes the missing auto incrementation of texts
        runsql(
            "ALTER TABLE `{$tbpref}archivedtexts` 
            MODIFY COLUMN `AtID` smallint(5) unsigned NOT NULL AUTO_INCREMENT", 
            '', false
        );
        
        // set to current.
        saveSetting('dbversion', $currversion);
        saveSetting('lastscorecalc', '');  // do next section, too
    }
}

/**
 * Check and/or update the database.
 *
 * @global mysqli $DBCONNECTION Connection to the database
 */
function check_update_db($debug, $tbpref, $dbname): void 
{
    $tables = array();
    
    $res = do_mysqli_query(
        str_replace(
            '_', 
            "\\_", 
            "SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull($tbpref . '%')
        )
    );
    while ($row = mysqli_fetch_row($res)) {
        $tables[] = $row[0]; 
    }
    mysqli_free_result($res);
    
    $count = 0;  /// counter for cache rebuild
    
    // Rebuild Tables if missing (current versions!)
    
    if (!in_array("{$tbpref}archivedtexts", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding archivedtexts</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}archivedtexts ( 
                AtID smallint(5) unsigned NOT NULL AUTO_INCREMENT, 
                AtLgID tinyint(3) unsigned NOT NULL, 
                AtTitle varchar(200) NOT NULL, 
                AtText text NOT NULL, 
                AtAnnotatedText longtext NOT NULL, 
                AtAudioURI varchar(200) DEFAULT NULL, 
                AtSourceURI varchar(1000) DEFAULT NULL, 
                PRIMARY KEY (AtID), 
                KEY AtLgID (AtLgID), 
                KEY AtLgIDSourceURI (AtSourceURI(20),AtLgID) 
            ) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}languages", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding languages</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}languages ( 
                LgID tinyint(3) unsigned NOT NULL AUTO_INCREMENT, 
                LgName varchar(40) NOT NULL, 
                LgDict1URI varchar(200) NOT NULL, 
                LgDict2URI varchar(200) DEFAULT NULL, 
                LgGoogleTranslateURI varchar(200) DEFAULT NULL, 
                LgExportTemplate varchar(1000) DEFAULT NULL, 
                LgTextSize smallint(5) unsigned NOT NULL DEFAULT '100', 
                LgCharacterSubstitutions varchar(500) NOT NULL, 
                LgRegexpSplitSentences varchar(500) NOT NULL, 
                LgExceptionsSplitSentences varchar(500) NOT NULL, 
                LgRegexpWordCharacters varchar(500) NOT NULL, 
                LgRemoveSpaces tinyint(1) unsigned NOT NULL DEFAULT '0', 
                LgSplitEachChar tinyint(1) unsigned NOT NULL DEFAULT '0', 
                LgRightToLeft tinyint(1) unsigned NOT NULL DEFAULT '0', 
                PRIMARY KEY (LgID), 
                UNIQUE KEY LgName (LgName) 
            ) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}sentences", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding sentences</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}sentences ( 
                SeID mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
                SeLgID tinyint(3) unsigned NOT NULL, 
                SeTxID smallint(5) unsigned NOT NULL, 
                SeOrder smallint(5) unsigned NOT NULL, 
                SeText text, SeFirstPos smallint(5) unsigned NOT NULL, 
                PRIMARY KEY (SeID), 
                KEY SeLgID (SeLgID), 
                KEY SeTxID (SeTxID), 
                KEY SeOrder (SeOrder) 
            ) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
        $count++;
    }
    
    if (!in_array("{$tbpref}settings", $tables)) {
        if ($debug) {
             echo '<p>DEBUG: rebuilding settings</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}settings ( 
                StKey varchar(40) NOT NULL, 
                StValue varchar(40) DEFAULT NULL, 
                PRIMARY KEY (StKey)
            ) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}textitems2", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding textitems2</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}textitems2 (
                Ti2WoID mediumint(8) unsigned NOT NULL, 
                Ti2LgID tinyint(3) unsigned NOT NULL, 
                Ti2TxID smallint(5) unsigned NOT NULL, 
                Ti2SeID mediumint(8) unsigned NOT NULL, 
                Ti2Order smallint(5) unsigned NOT NULL, 
                Ti2WordCount tinyint(3) unsigned NOT NULL, 
                Ti2Text varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                PRIMARY KEY (Ti2TxID,Ti2Order,Ti2WordCount), KEY Ti2WoID (Ti2WoID)
            ) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
        // Add data from the old database system
        if (in_array("{$tbpref}textitems", $tables)) {
            runsql(
                "INSERT INTO {$tbpref}textitems2 (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, 
                    Ti2Text
                ) 
                SELECT IFNULL(WoID,0), TiLgID, TiTxID, TiSeID, TiOrder, 
                CASE WHEN TiIsNotWord = 1 THEN 0 ELSE TiWordCount END as WordCount, 
                CASE 
                    WHEN STRCMP(TiText COLLATE utf8_bin,TiTextLC)!=0 OR TiWordCount=1 
                    THEN TiText 
                    ELSE '' 
                END as Text 
                FROM {$tbpref}textitems 
                LEFT JOIN {$tbpref}words ON TiTextLC=WoTextLC AND TiLgID=WoLgID 
                WHERE TiWordCount<2 OR WoID IS NOT NULL",
                ''
            );
            runsql("TRUNCATE {$tbpref}textitems", '');
        }
        $count++;
    }


    if (!in_array("{$tbpref}temptextitems", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding temptextitems</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}temptextitems ( 
                TiCount smallint(5) unsigned NOT NULL, 
                TiSeID mediumint(8) unsigned NOT NULL, 
                TiOrder smallint(5) unsigned NOT NULL, 
                TiWordCount tinyint(3) unsigned NOT NULL, 
                TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
            ) ENGINE=MEMORY DEFAULT CHARSET=utf8", 
            ''
        );
    }

    if (!in_array("{$tbpref}tempwords", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding tempwords</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}tempwords (
                WoText varchar(250) DEFAULT NULL, 
                WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                WoTranslation varchar(500) NOT NULL DEFAULT '*', 
                WoRomanization varchar(100) DEFAULT NULL, 
                WoSentence varchar(1000) DEFAULT NULL, 
                WoTaglist varchar(255) DEFAULT NULL, 
                PRIMARY KEY(WoTextLC) 
            ) ENGINE=MEMORY DEFAULT CHARSET=utf8", 
            ''
        );
    }

    if (!in_array("{$tbpref}texts", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding texts</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}texts ( 
                TxID smallint(5) unsigned NOT NULL AUTO_INCREMENT, 
                TxLgID tinyint(3) unsigned NOT NULL, 
                TxTitle varchar(200) NOT NULL, 
                TxText text NOT NULL, 
                TxAnnotatedText longtext NOT NULL, 
                TxAudioURI varchar(200) DEFAULT NULL, 
                TxSourceURI varchar(1000) DEFAULT NULL, 
                TxPosition smallint(5) DEFAULT 0, 
                TxAudioPosition float DEFAULT 0, 
                PRIMARY KEY (TxID), 
                KEY TxLgID (TxLgID), 
                KEY TxLgIDSourceURI (TxSourceURI(20),TxLgID) 
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}words", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding words</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}words ( 
                WoID mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
                WoLgID tinyint(3) unsigned NOT NULL, 
                WoText varchar(250) NOT NULL, 
                WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                WoStatus tinyint(4) NOT NULL, 
                WoTranslation varchar(500) NOT NULL DEFAULT '*', 
                WoRomanization varchar(100) DEFAULT NULL, 
                WoSentence varchar(1000) DEFAULT NULL, 
                WoWordCount tinyint(3) unsigned NOT NULL DEFAULT 0, 
                WoCreated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                WoStatusChanged timestamp NOT NULL DEFAULT '1970-01-01 12:00:00', 
                WoTodayScore double NOT NULL DEFAULT '0', 
                WoTomorrowScore double NOT NULL DEFAULT '0', 
                WoRandom double NOT NULL DEFAULT '0', 
                PRIMARY KEY (WoID), 
                UNIQUE KEY WoTextLCLgID (WoTextLC,WoLgID), 
                KEY WoLgID (WoLgID), 
                KEY WoStatus (WoStatus), 
                KEY WoTranslation (WoTranslation(20)), 
                KEY WoCreated (WoCreated), 
                KEY WoStatusChanged (WoStatusChanged), 
                KEY WoWordCount(WoWordCount), 
                KEY WoTodayScore (WoTodayScore), 
                KEY WoTomorrowScore (WoTomorrowScore), 
                KEY WoRandom (WoRandom) 
            ) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}tags", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding tags</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}tags ( 
                TgID smallint(5) unsigned NOT NULL AUTO_INCREMENT, 
                TgText varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                TgComment varchar(200) NOT NULL DEFAULT '', 
                PRIMARY KEY (TgID), 
                UNIQUE KEY TgText (TgText) 
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}wordtags", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding wordtags</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}wordtags ( 
                WtWoID mediumint(8) unsigned NOT NULL, 
                WtTgID smallint(5) unsigned NOT NULL, 
                PRIMARY KEY (WtWoID,WtTgID), 
                KEY WtTgID (WtTgID) 
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}tags2", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding tags2</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}tags2 ( 
                T2ID smallint(5) unsigned NOT NULL AUTO_INCREMENT, 
                T2Text varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                T2Comment varchar(200) NOT NULL DEFAULT '', 
                PRIMARY KEY (T2ID), 
                UNIQUE KEY T2Text (T2Text) 
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}texttags", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding texttags</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}texttags ( 
                TtTxID smallint(5) unsigned NOT NULL, 
                TtT2ID smallint(5) unsigned NOT NULL, 
                PRIMARY KEY (TtTxID,TtT2ID), KEY TtT2ID (TtT2ID) 
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}newsfeeds", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding newsfeeds</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}newsfeeds (
                NfID tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
                NfLgID tinyint(3) unsigned NOT NULL,
                NfName varchar(40) NOT NULL,
                NfSourceURI varchar(200) NOT NULL,
                NfArticleSectionTags text NOT NULL,
                NfFilterTags text NOT NULL,
                NfUpdate int(12) unsigned NOT NULL,
                NfOptions varchar(200) NOT NULL,
                PRIMARY KEY (NfID), 
                KEY NfLgID (NfLgID), 
                KEY NfUpdate (NfUpdate)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}feedlinks", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding feedlinks</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}feedlinks (
                FlID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                FlTitle varchar(200) NOT NULL,
                FlLink varchar(400) NOT NULL,
                FlDescription text NOT NULL,
                FlDate datetime NOT NULL,
                FlAudio varchar(200) NOT NULL,
                FlText longtext NOT NULL,
                FlNfID tinyint(3) unsigned NOT NULL,
                PRIMARY KEY (FlID), 
                KEY FlLink (FlLink), 
                KEY FlDate (FlDate), 
                UNIQUE KEY FlTitle (FlNfID,FlTitle)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    
    if (!in_array("{$tbpref}archtexttags", $tables)) {
        if ($debug) { 
            echo '<p>DEBUG: rebuilding archtexttags</p>'; 
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS {$tbpref}archtexttags ( 
                AgAtID smallint(5) unsigned NOT NULL, 
                AgT2ID smallint(5) unsigned NOT NULL, 
                PRIMARY KEY (AgAtID,AgT2ID), 
                KEY AgT2ID (AgT2ID) 
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8", 
            ''
        );
    }
    runsql(
        "ALTER TABLE `{$tbpref}sentences` ADD SeFirstPos smallint(5) NOT NULL", 
        '', 
        $sqlerrdie = false
    );
    
    if ($count > 0) {        
        // Rebuild Text Cache if cache tables new
        if ($debug) { 
            echo '<p>DEBUG: rebuilding cache tables</p>'; 
        }
        reparse_all_texts();
    }
    
    // Update the database
    update_database($dbname);

    // Do Scoring once per day, clean Word/Texttags, and optimize db
    $lastscorecalc = getSetting('lastscorecalc');
    $today = date('Y-m-d');
    if ($lastscorecalc != $today) {
        if ($debug) { 
            echo '<p>DEBUG: Doing score recalc. Today: ' . $today . 
            ' / Last: ' . $lastscorecalc . '</p>'; 
        }
        runsql(
            "UPDATE {$tbpref}words 
            SET " . make_score_random_insert_update('u') ." 
            WHERE WoTodayScore>=-100 AND WoStatus<98", 
            ''
        );
        runsql(
            "DELETE {$tbpref}wordtags 
            FROM ({$tbpref}wordtags LEFT JOIN {$tbpref}tags on WtTgID = TgID) 
            WHERE TgID IS NULL", 
            ''
        );
        runsql(
            "DELETE {$tbpref}wordtags 
            FROM ({$tbpref}wordtags LEFT JOIN {$tbpref}words ON WtWoID = WoID) 
            WHERE WoID IS NULL", 
            ''
        );
        runsql(
            "DELETE {$tbpref}texttags 
            FROM ({$tbpref}texttags LEFT JOIN {$tbpref}tags2 ON TtT2ID = T2ID) 
            WHERE T2ID IS NULL", 
            ''
        );
        runsql(
            "DELETE {$tbpref}texttags 
            FROM ({$tbpref}texttags LEFT JOIN {$tbpref}texts ON TtTxID = TxID) 
            WHERE TxID IS NULL", 
            ''
        );
        runsql(
            "DELETE {$tbpref}archtexttags 
            FROM (
                {$tbpref}archtexttags 
                LEFT JOIN {$tbpref}tags2 ON AgT2ID = T2ID
            ) 
            WHERE T2ID IS NULL", 
            ''
        );
        runsql(
            "DELETE {$tbpref}archtexttags 
            FROM (
                {$tbpref}archtexttags 
                LEFT JOIN {$tbpref}archivedtexts ON AgAtID = AtID
            ) 
            WHERE AtID IS NULL", 
            ''
        );
        optimizedb();
        saveSetting('lastscorecalc', $today);
    }
}


/**
 * Make the connection to the database.
 * 
 * @return mysqli Connection to the database
 * 
 * @psalm-suppress UndefinedDocblockClass
 * 
 * @since 2.6.0-fork Use mysqli_init and mysql_real_connect instead of deprecated mysql_connect
 * @since 2.6.0-fork Tries to allow local infiles for the connection.
 */
function connect_to_database($server, $userid, $passwd, $dbname) 
{
    // @ suppresses error messages
    
    // Necessary since mysqli_report default setting in PHP 8.1+ has changed
    @mysqli_report(MYSQLI_REPORT_OFF);

    $dbconnection = mysqli_init();

    if ($dbconnection === false) {
        my_die(
            'Database connection error. Is MySQL running? 
            You can refer to the documentation: 
            https://hugofara.github.io/lwt/docs/install.html 
            [Error Code: ' . mysqli_connect_errno() . 
            ' / Error Message: ' . mysqli_connect_error() . ']'
        );
    }

    @mysqli_options($dbconnection, MYSQLI_OPT_LOCAL_INFILE, 1);

    $success = @mysqli_real_connect(
        $dbconnection, $server, $userid, $passwd, $dbname
    );

    if (!$success && mysqli_connect_errno() == 1049) {
        // Database unknown, try with generic database
        $success = @mysqli_real_connect(
            $dbconnection, $server, $userid, $passwd
        );

        if (!$success || !$dbconnection) { 
            my_die(
                'DB connect error, connection parameters may be wrong, 
                please check file "connect.inc.php". 
                You can refer to the documentation:  
                https://hugofara.github.io/lwt/docs/install.html 
                [Error Code: ' . mysqli_connect_errno() . 
                ' / Error Message: ' . mysqli_connect_error() . ']'
            );
        }
        $result = mysqli_query(
            $dbconnection, 
            "CREATE DATABASE `$dbname` 
            DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
        );
        if (!$result) {
            my_die("Failed to create database! " . $result);
        }
        mysqli_close($dbconnection);
        $success = @mysqli_real_connect(
            $dbconnection, $server, $userid, $passwd, $dbname
        );
    }

    if (!$success) { 
        my_die(
            'DB connect error, connection parameters may be wrong, 
            please check file "connect.inc.php"
            You can refer to the documentation:
            https://hugofara.github.io/lwt/docs/install.html 
            [Error Code: ' . mysqli_connect_errno() . 
            ' / Error Message: ' . mysqli_connect_error() . ']'
        ); 
    }

    @mysqli_query($dbconnection, "SET NAMES 'utf8'");

    // @mysqli_query($DBCONNECTION, "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    @mysqli_query($dbconnection, "SET SESSION sql_mode = ''");
    return $dbconnection;
}

/**
 * Get the prefixes for the database.
 * 
 * Is $tbpref set in connect.inc.php? Take it and $fixed_tbpref=1.
 * If not: $fixed_tbpref=0. Is it set in table "_lwtgeneral"? Take it.
 * If not: Use $tbpref = '' (no prefix, old/standard behaviour).
 * 
 * @param string|null $tbpref Temporary database table prefix
 * 
 * @return 0|1 Table Prefix is fixed, no changes possible
 */
function get_database_prefixes(&$tbpref) 
{
    // *** GLOBAL VARIABLES ***

    if (!isset($tbpref)) {
        $fixed_tbpref = 0;
        $p = LWTTableGet("current_table_prefix");
        $tbpref = isset($p) ? $p : '';
    } else {
        $fixed_tbpref = 1; 
    }

    $len_tbpref = strlen($tbpref); 
    if ($len_tbpref > 0) {
        if ($len_tbpref > 20) { 
            my_die(
                'Table prefix/set "' . $tbpref .
                '" longer than 20 digits or characters.' . 
                ' Please fix in "connect.inc.php".'
            );
        }
        for ($i=0; $i < $len_tbpref; $i++) { 
            if (strpos(
                "_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 
                substr($tbpref, $i, 1)
            ) === false
            ) {
                my_die(
                    'Table prefix/set "' . $tbpref . 
                    '" contains characters or digits other than 0-9, a-z, A-Z ' .
                    'or _. Please fix in "connect.inc.php".'
                ); 
            } 
        } 
    }

    if (!$fixed_tbpref) { 
        LWTTableSet("current_table_prefix", $tbpref); 
    }

    // *******************************************************************
    // IF PREFIX IS NOT '', THEN ADD A '_', TO ENSURE NO IDENTICAL NAMES
    if ($tbpref !== '') { 
        $tbpref .= "_"; 
    }
    return $fixed_tbpref;
}

// --------------------  S T A R T  --------------------------- //

// Start Timer
if (!empty($dspltime)) {
    get_execution_time(); 
}

/**
 * @var mysqli $DBCONNECTION Connection to the database
 */
$DBCONNECTION = connect_to_database($server, $userid, $passwd, $dbname);
/** 
 * @var string $tbpref Database table prefix 
 */
$tbpref = null;
/** 
 * @var int $fixed_tbpref Database prefix is fixed (1) or not (0)
 */
$fixed_tbpref = get_database_prefixes($tbpref);
// check/update db
check_update_db($debug, $tbpref, $dbname);

?>