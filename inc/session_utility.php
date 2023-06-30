<?php

/**
 * \file
 * \brief All the files needed for a LWT session.
 * 
 * By requiring this file, you start a session, connect to the 
 * database and declare a lot of useful functions.
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/session__utility_8php.html
 * @since   2.0.3-fork
 */

require_once __DIR__ . '/database_connect.php';


/**
 * Return the list of all tags.
 * 
 * @param int $refresh If true, refresh all tags for session
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return array<string> All tags
 */
function get_tags($refresh = 0) 
{
    global $tbpref;
    if (isset($_SESSION['TAGS']) 
        && is_array($_SESSION['TAGS']) 
        && isset($_SESSION['TBPREF_TAGS']) 
        && $_SESSION['TBPREF_TAGS'] == $tbpref . url_base() 
        && $refresh == 0
    ) {
            return $_SESSION['TAGS'];
    }
    $tags = array();
    $sql = 'SELECT TgText FROM ' . $tbpref . 'tags ORDER BY TgText';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $tags[] = (string)$record["TgText"];
    }
    mysqli_free_result($res);
    $_SESSION['TAGS'] = $tags;
    $_SESSION['TBPREF_TAGS'] = $tbpref . url_base();
    return $_SESSION['TAGS'];
}

/**
 * Return the list of all text tags.
 * 
 * @param int $refresh If true, refresh all text tags for session
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return array<string> All text tags
 */
function get_texttags($refresh = 0) 
{
    global $tbpref;
    if (isset($_SESSION['TEXTTAGS']) 
        && is_array($_SESSION['TEXTTAGS']) 
        && isset($_SESSION['TBPREF_TEXTTAGS']) 
        && $refresh == 0
        && $_SESSION['TBPREF_TEXTTAGS'] == $tbpref . url_base()
    ) {
            return $_SESSION['TEXTTAGS']; 
    }
    $tags = array();
    $sql = 'SELECT T2Text FROM ' . $tbpref . 'tags2 ORDER BY T2Text';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $tags[] = (string)$record["T2Text"];
    }
    mysqli_free_result($res);
    $_SESSION['TEXTTAGS'] = $tags;
    $_SESSION['TBPREF_TEXTTAGS'] = $tbpref . url_base();
    return $_SESSION['TEXTTAGS'];
}

// -------------------------------------------------------------

function getTextTitle($textid): string 
{
    global $tbpref;
    $text = get_first_value(
        "SELECT TxTitle AS value 
        FROM " . $tbpref . "texts 
        WHERE TxID=" . $textid
    );
    if (!isset($text)) { 
        $text = "?"; 
    }
    return (string)$text;
}

// -------------------------------------------------------------

function get_tag_selectoptions($v,$l): string 
{
    global $tbpref;
    if (!isset($v)) { 
        $v = ''; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select TgID, TgText 
        from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags 
        where TgID = WtTgID and WtWoID = WoID 
        group by TgID 
        order by UPPER(TgText)"; 
    } else {
        $sql = "select TgID, TgText 
        from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags 
        where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $l . " 
        group by TgID 
        order by UPPER(TgText)"; 
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["TgText"];
        $cnt++;
        $r .= "<option value=\"" . $record["TgID"] . "\"" .
         get_selected($v, $record["TgID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_texttag_selectoptions($v,$l): string 
{
    global $tbpref;
    if (!isset($v) ) {
        $v = ''; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select T2ID, T2Text 
        from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags 
        where T2ID = TtT2ID and TtTxID = TxID 
        group by T2ID 
        order by UPPER(T2Text)"; 
    } else {
        $sql = "select T2ID, T2Text 
        from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags 
        where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $l . " 
        group by T2ID 
        order by UPPER(T2Text)"; 
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["T2Text"];
        $cnt++;
        $r .= "<option value=\"" . $record["T2ID"] . "\"" . 
        get_selected($v, $record["T2ID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_txtag_selectoptions($l,$v): string
{
    global $tbpref;
    if (!isset($v)) {
        $v = ''; 
    }
    $u ='';
    $r = "<option value=\"&amp;texttag\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    $sql = 'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID, GROUP_CONCAT(TxID 
    ORDER BY TxID) AS TextID 
    FROM ' . $tbpref . 'texts 
    LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
    LEFT JOIN ' . $tbpref . 'tags2 ON TtT2ID = T2ID';
    if ($l) {
        $sql .= ' WHERE TxLgID=' . $l; 
    }
    $sql .= ' GROUP BY UPPER(TagName)';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        if ($record['TagName']==1) {
            $u ="<option disabled=\"disabled\">--------</option><option value=\"" . 
            $record['TextID'] . "&amp;texttag=-1\"" . get_selected($v, "-1") . 
            ">UNTAGGED</option>";
        } else {
            $r .= "<option value=\"" .$record['TextID']."&amp;texttag=". 
            $record['TagID'] . "\"" . get_selected($v, $record['TagID']) . ">" . 
            $record['TagName'] . "</option>";
        }
    }
    mysqli_free_result($res);
    return $r.$u;
}

// -------------------------------------------------------------

function get_archivedtexttag_selectoptions($v,$l): string 
{
    global $tbpref;
    if (!isset($v)) { 
        $v = ''; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select T2ID, T2Text 
        from " . $tbpref . "archivedtexts, " . 
        $tbpref . "tags2, " . $tbpref . "archtexttags 
        where T2ID = AgT2ID and AgAtID = AtID 
        group by T2ID 
        order by UPPER(T2Text)"; 
    } else {
        $sql = "select T2ID, T2Text 
        from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . 
        $tbpref . "archtexttags 
        where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $l . " 
        group by T2ID 
        order by UPPER(T2Text)"; 
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["T2Text"];
        $cnt++;
        $r .= "<option value=\"" . $record["T2ID"] . "\"" . 
        get_selected($v, $record["T2ID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}


/**
 * Save the tags for words.
 * 
 * @return void
 */
function saveWordTags($wid) 
{
    global $tbpref;
    runsql("DELETE from " . $tbpref . "wordtags WHERE WtWoID =" . $wid, '');
    if (!isset($_REQUEST['TermTags'])  
        || !is_array($_REQUEST['TermTags'])  
        || !isset($_REQUEST['TermTags']['TagList'])  
        || !is_array($_REQUEST['TermTags']['TagList'])
    ) {
         return;
    }
    $cnt = count($_REQUEST['TermTags']['TagList']);
    getWordTags(1);

    for ($i = 0; $i < $cnt; $i++) {
        $tag = $_REQUEST['TermTags']['TagList'][$i];
        if(!in_array($tag, $_SESSION['TAGS'])) {
            runsql(
                "INSERT INTO {$tbpref}tags (TgText) 
                VALUES(" . convert_string_to_sqlsyntax($tag) . ")", 
                ""
            );
        }
        runsql(
            "INSERT INTO {$tbpref}wordtags (WtWoID, WtTgID) 
            SELECT $wid, TgID 
            FROM {$tbpref}tags 
            WHERE TgText = " . convert_string_to_sqlsyntax($tag), 
            ""
        );
    }
    // refresh tags cache
    get_tags(1);
}

/**
 * Save the tags for texts.
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix.
 */
function saveTextTags($tid): void 
{
    global $tbpref;
    runsql(
        "DELETE FROM " . $tbpref . "texttags WHERE TtTxID =" . $tid, 
        ''
    );
    if (!isset($_REQUEST['TextTags']) 
        || !is_array($_REQUEST['TextTags']) 
        || !isset($_REQUEST['TextTags']['TagList']) 
        || !is_array($_REQUEST['TextTags']['TagList'])
    ) {
        return;
    }
    $cnt = count($_REQUEST['TextTags']['TagList']);
    get_texttags(1);

    for ($i = 0; $i < $cnt; $i++) {
        $tag = $_REQUEST['TextTags']['TagList'][$i];
        if (!in_array($tag, $_SESSION['TEXTTAGS'])) {
            runsql(
                "INSERT INTO {$tbpref}tags2 (T2Text) 
                VALUES(" . convert_string_to_sqlsyntax($tag) . ")", 
                ""
            );
        }
        runsql(
            "INSERT INTO {$tbpref}texttags (TtTxID, TtT2ID) 
            SELECT $tid, T2ID 
            FROM {$tbpref}tags2 
            WHERE T2Text = " . convert_string_to_sqlsyntax($tag), 
            ""
        );
    }
    // refresh tags cache
    get_texttags(1);
}


/**
 * Save the tags for archived texts.
 * 
 * @return void
 * 
 * @global string $tbpref Databse table prefix. 
 */
function saveArchivedTextTags($tid): void 
{
    global $tbpref;
    runsql("DELETE from " . $tbpref . "archtexttags WHERE AgAtID =" . $tid, '');
    if (!isset($_REQUEST['TextTags']) 
        || !is_array($_REQUEST['TextTags']) 
        || !isset($_REQUEST['TextTags']['TagList']) 
        || !is_array($_REQUEST['TextTags']['TagList'])
    ) {
        return;
    }
    $cnt = count($_REQUEST['TextTags']['TagList']);
    get_texttags(1);
    for ($i = 0; $i < $cnt; $i++) {
        $tag = $_REQUEST['TextTags']['TagList'][$i];
        if (!in_array($tag, $_SESSION['TEXTTAGS'])) {
            runsql(
                'INSERT INTO {$tbpref}tags2 (T2Text) 
                VALUES(' . convert_string_to_sqlsyntax($tag) . ')', 
                ""
            );
        }
        runsql(
            "INSERT INTO {$tbpref}archtexttags (AgAtID, AgT2ID) 
            SELECT $tid, T2ID 
            FROM {$tbpref}tags2 
            WHERE T2Text = " . convert_string_to_sqlsyntax($tag), 
            ""
        );
        // refresh tags cache
        get_texttags(1);
    }
}

// -------------------------------------------------------------

function getWordTags($wid): string 
{
    global $tbpref;
    $r = '<ul id="termtags">';
    if ($wid > 0) {
        $sql = 'select TgText 
        from ' . $tbpref . 'wordtags, ' . $tbpref . 'tags 
        where TgID = WtTgID and WtWoID = ' . $wid . ' 
        order by TgText';
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["TgText"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}

/**
 * Return a HTML-formatted list of the text tags.
 *
 * @param int $tid Text ID. Can be below 1 to create an empty list.
 *
 * @return string UL list of text tags
 *
 * @global string $tbpref Database table prefix 
 */
function getTextTags($tid): string 
{
    global $tbpref;
    $r = '<ul id="texttags" class="respinput">';
    if ($tid > 0) {
        $sql = "SELECT T2Text 
        FROM {$tbpref}texttags, {$tbpref}tags2 
        WHERE T2ID = TtT2ID AND TtTxID = $tid 
        ORDER BY T2Text";
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}


/**
 * Return a HTML-formatted list of the text tags for an archived text.
 *
 * @param int $tid Text ID. Can be below 1 to create an empty list.
 *
 * @return string UL list of text tags
 *
 * @global string $tbpref Database table prefix 
 */
function getArchivedTextTags($tid): string 
{
    global $tbpref;
    $r = '<ul id="texttags">';
    if ($tid > 0) {
        $sql = 'SELECT T2Text 
        FROM ' . $tbpref . 'archtexttags, ' . $tbpref . 'tags2 
        WHERE T2ID = AgT2ID AND AgAtID = ' . $tid . ' 
        ORDER BY T2Text';
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}

// -------------------------------------------------------------

function addtaglist($item, $list): string 
{
    global $tbpref;
    $tagid = get_first_value(
        'select TgID as value 
        from ' . $tbpref . 'tags 
        where TgText = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        runsql(
            'insert into ' . $tbpref . 'tags (TgText) 
            values(' . convert_string_to_sqlsyntax($item) . ')', 
            ""
        );
        $tagid = get_first_value(
            'select TgID as value 
            from ' . $tbpref . 'tags 
            where TgText = ' . convert_string_to_sqlsyntax($item)
        );
    }
    $sql = 'select WoID 
    from ' . $tbpref . 'words 
    LEFT JOIN ' . $tbpref . 'wordtags 
    ON WoID = WtWoID AND WtTgID = ' . $tagid . ' 
    WHERE WtTgID IS NULL AND WoID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += (int) runsql(
            'insert ignore into ' . $tbpref . 'wordtags (WtWoID, WtTgID) 
            values(' . $record['WoID'] . ', ' . $tagid . ')', 
            ""
        );
    }
    mysqli_free_result($res);
    get_tags($refresh = 1);
    return "Tag added in $cnt Terms";
}

// -------------------------------------------------------------

function addarchtexttaglist($item, $list): string 
{
    global $tbpref;
    $tagid = get_first_value(
        'select T2ID as value from ' . $tbpref . 'tags2 
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        runsql(
            'insert into ' . $tbpref . 'tags2 (T2Text) 
            values(' . convert_string_to_sqlsyntax($item) . ')', 
            ""
        );
        $tagid = get_first_value(
            'select T2ID as value 
            from ' . $tbpref . 'tags2 
            where T2Text = ' . convert_string_to_sqlsyntax($item)
        );
    }
    $sql = 'select AtID from ' . $tbpref . 'archivedtexts 
    LEFT JOIN ' . $tbpref . 'archtexttags 
    ON AtID = AgAtID AND AgT2ID = ' . $tagid . ' 
    WHERE AgT2ID IS NULL AND AtID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += (int) runsql(
            'insert ignore into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) 
            values(' . $record['AtID'] . ', ' . $tagid . ')', 
            ""
        );
    }
    mysqli_free_result($res);
    get_texttags($refresh = 1);
    return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function addtexttaglist($item, $list): string 
{
    global $tbpref;
    $tagid = get_first_value(
        'select T2ID as value 
        from ' . $tbpref . 'tags2 
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        runsql(
            'insert into ' . $tbpref . 'tags2 (T2Text) 
            values(' . convert_string_to_sqlsyntax($item) . ')', 
            ""
        );
        $tagid = get_first_value(
            'select T2ID as value 
            from ' . $tbpref . 'tags2 
            where T2Text = ' . convert_string_to_sqlsyntax($item)
        );
    }
    $sql = 'select TxID from ' . $tbpref . 'texts
     LEFT JOIN ' . $tbpref . 'texttags 
     ON TxID = TtTxID AND TtT2ID = ' . $tagid . ' 
     WHERE TtT2ID IS NULL AND TxID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += (int) runsql(
            'insert ignore into ' . $tbpref . 'texttags (TtTxID, TtT2ID) 
            values(' . $record['TxID'] . ', ' . $tagid . ')', 
            ""
        );
    }
    mysqli_free_result($res);
    get_texttags($refresh = 1);
    return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function removetaglist($item, $list): string 
{
    global $tbpref;
    $tagid = get_first_value(
        'SELECT TgID AS value
        FROM ' . $tbpref . 'tags
        WHERE TgText = ' . convert_string_to_sqlsyntax($item)
    );
    if (! isset($tagid)) { 
        return "Tag " . $item . " not found"; 
    }
    $sql = 'select WoID from ' . $tbpref . 'words where WoID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql(
            'DELETE FROM ' . $tbpref . 'wordtags
            WHERE WtWoID = ' . $record['WoID'] . ' AND WtTgID = ' . $tagid, 
            ""
        );
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Terms";
}

// -------------------------------------------------------------

function removearchtexttaglist($item, $list): string 
{
    global $tbpref;
    $tagid = get_first_value(
        'select T2ID as value 
        from ' . $tbpref . 'tags2 
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) { 
        return "Tag " . $item . " not found"; 
    }
    $sql = 'select AtID from ' . $tbpref . 'archivedtexts where AtID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql(
            'delete from ' . $tbpref . 'archtexttags 
            where AgAtID = ' . $record['AtID'] . ' and AgT2ID = ' . $tagid, 
            ""
        );
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function removetexttaglist($item, $list): string 
{
    global $tbpref;
    $tagid = get_first_value(
        'select T2ID as value from ' . $tbpref . 'tags2 
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) { 
        return "Tag " . $item . " not found"; 
    }
    $sql = 'select TxID from ' . $tbpref . 'texts where TxID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql(
            'delete from ' . $tbpref . 'texttags 
            where TtTxID = ' . $record['TxID'] . ' and TtT2ID = ' . $tagid, 
            ""
        );
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function load_feeds($currentfeed): void
{
    global $tbpref;
    $cnt=0;
    $ajax=$feeds=array();
    echo '<script type="text/javascript">';
    if (isset($_REQUEST['check_autoupdate'])) {
        $result = do_mysqli_query(
            "SELECT * FROM " . $tbpref . "newsfeeds 
            where `NfOptions` like '%autoupdate=%'"
        );
        while($row = mysqli_fetch_assoc($result)){
            if ($autoupdate = get_nf_option($row['NfOptions'], 'autoupdate')) {
                if(strpos($autoupdate, 'h')!==false) {
                    $autoupdate = str_replace('h', '', $autoupdate);
                    $autoupdate = 60 * 60 * (int)$autoupdate;
                } elseif(strpos($autoupdate, 'd')!==false) {
                    $autoupdate=str_replace('d', '', $autoupdate);
                    $autoupdate=60 * 60 * 24 * (int)$autoupdate;
                } elseif(strpos($autoupdate, 'w')!==false) {
                    $autoupdate=str_replace('w', '', $autoupdate);
                    $autoupdate=60 * 60 * 24 * 7 * (int)$autoupdate;
                } else { 
                    continue; 
                }
                if(time()>($autoupdate + (int) $row['NfUpdate'])) {
                    $ajax[$cnt]=  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" . $row['NfID'] . "').replaceWith( '<div id=\"feed_" . $row['NfID'] . "\" class=\"msgblue\"><p>". addslashes($row['NfName']).": loading</p></div>' );},url:'inc/ajax_load_feed.php', data: { NfID: '".$row['NfID']."', NfSourceURI: '". $row['NfSourceURI']."', NfName: '". addslashes($row['NfName'])."', NfOptions: '". $row['NfOptions']."', cnt: '". $cnt."' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);$('#feed_" . $row['NfID'] . "').replaceWith( data );}})";
                    $cnt+=1;
                    $feeds[$row['NfID']]=$row['NfName'];
                }
            }
        }
        mysqli_free_result($result);
    } else {
        $sql="SELECT * FROM " . $tbpref . "newsfeeds WHERE NfID in ($currentfeed)";
        $result = do_mysqli_query($sql);
        while($row = mysqli_fetch_assoc($result)){
            $ajax[$cnt]=  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" . $row['NfID'] . "').replaceWith( '<div id=\"feed_" . $row['NfID'] . "\" class=\"msgblue\"><p>". addslashes($row['NfName']).": loading</p></div>' );},url:'inc/ajax_load_feed.php', data: { NfID: '".$row['NfID']."', NfSourceURI: '". $row['NfSourceURI']."', NfName: '". addslashes($row['NfName'])."', NfOptions: '". $row['NfOptions']."', cnt: '". $cnt."' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);$('#feed_" . $row['NfID'] . "').replaceWith( data );}})";
            $cnt+=1;
            $feeds[$row['NfID']]=$row['NfName'];
        }
        mysqli_free_result($result);
    }
    if(!empty($ajax)) {
        $z=array();
        for($i=1;$i<=$cnt;$i++){
            $z[]='a'.$i;
        }
        echo "feedcnt=0;\n";
        echo '$(document).ready(function(){ $.when(',implode(',', $ajax),").then(function(",implode(',', $z),"){window.location.replace(\"",$_SERVER['PHP_SELF'],"\");});});";
    } else { 
        echo "window.location.replace(\"",$_SERVER['PHP_SELF'],"\");"; 
    }
    echo "\n</script>\n";
    if($cnt!=1) { 
        echo "<div class=\"msgblue\"><p>UPDATING <span id=\"feedcount\">0</span>/",$cnt," FEEDS</p></div>"; 
    }
    foreach($feeds as $k=>$v){
        echo "<div id='feed_$k' class=\"msgblue\"><p>". $v.": waiting</p></div>";
    }
    echo "<div class=\"center\"><button onclick='window.location.replace(\"",$_SERVER['PHP_SELF'],"\");'>Continue</button></div>";
}

// -------------------------------------------------------------


function write_rss_to_db($texts): string
{
    global $tbpref;
    $texts=array_reverse($texts);
    $message1=$message2=$message3=$message4=0;
    $Nf_ID = null;
    foreach($texts as $text){
        $Nf_ID[]=$text['Nf_ID'];
    }
    $Nf_ID=array_unique($Nf_ID);
    $Nf_tag='';
    $text_item = null;
    $nf_max_texts = null;
    foreach($Nf_ID as $feed_ID){
        foreach($texts as $text){
            if($feed_ID==$text['Nf_ID']) {
                if($Nf_tag!='"'.implode('","', $text['TagList']).'"') {
                    $Nf_tag= '"'.implode('","', $text['TagList']).'"';
                    foreach($text['TagList'] as $tag){
                        if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
                            do_mysqli_query(
                                'insert into ' . $tbpref . 'tags2 (T2Text) 
                                values (' . convert_string_to_sqlsyntax($tag) . ')'
                            );
                        }
                    }
                    $nf_max_texts = $text['Nf_Max_Texts'];
                }
                echo '<div class="msgblue"><p class="hide_message">+++ "' . 
                $text['TxTitle']. '" added! +++</p></div>';
                do_mysqli_query(
                    'INSERT INTO ' . $tbpref . 'texts (
                        TxLgID,TxTitle,TxText,TxAudioURI,TxSourceURI
                    ) VALUES (
                        '.$text['TxLgID'].',' . 
                        convert_string_to_sqlsyntax($text['TxTitle']) .','. 
                        convert_string_to_sqlsyntax($text['TxText']) .','. 
                        convert_string_to_sqlsyntax($text['TxAudioURI']) .','.
                        convert_string_to_sqlsyntax($text['TxSourceURI']) .')'
                );
                $id = get_last_key();
                splitCheckText(
                    get_first_value(
                        'select TxText as value from ' . $tbpref . 'texts 
                        where TxID = ' . $id
                    ), 
                    get_first_value(
                        'select TxLgID as value from ' . $tbpref . 'texts 
                        where TxID = ' . $id
                    ), 
                    $id 
                );
                do_mysqli_query(
                    'insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID) 
                    select ' . $id . ', T2ID from ' . $tbpref . 'tags2 
                    where T2Text in (' . $Nf_tag .')'
                );        
            }
        }
        get_texttags(1);
        $result=do_mysqli_query(
            "SELECT TtTxID FROM " . $tbpref . "texttags 
            join " . $tbpref . "tags2 on TtT2ID=T2ID 
            WHERE T2Text in (". $Nf_tag .")"
        );
        $text_count=0;
        while($row = mysqli_fetch_assoc($result)){
            $text_item[$text_count++]=$row['TtTxID'];
        }
        mysqli_free_result($result);
        if($text_count>$nf_max_texts) {
            sort($text_item, SORT_NUMERIC);
            $text_item=array_slice($text_item, 0, $text_count-$nf_max_texts);
            foreach ($text_item as $text_ID){
                $message3 += (int) runsql(
                    'delete from ' . $tbpref . 'textitems2 
                    where Ti2TxID = ' . $text_ID, 
                    ""
                );
                $message2 += (int) runsql(
                    'delete from ' . $tbpref . 'sentences 
                    where SeTxID = ' . $text_ID, 
                    ""
                );
                $message4 += (int) runsql(
                    'insert into ' . $tbpref . 'archivedtexts (
                        AtLgID, AtTitle, AtText, AtAnnotatedText, 
                        AtAudioURI, AtSourceURI
                    ) select TxLgID, TxTitle, TxText, TxAnnotatedText, 
                    TxAudioURI, TxSourceURI 
                    from ' . $tbpref . 'texts 
                    where TxID = ' . $text_ID, 
                    ""
                );
                $id = get_last_key();
                runsql(
                    'insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) 
                    select ' . $id . ', TtT2ID from ' . $tbpref . 'texttags 
                    where TtTxID = ' . $text_ID, 
                    ""
                );    
                $message1 += (int) runsql(
                    'delete from ' . $tbpref . 'texts 
                    where TxID = ' . $text_ID, 
                    ""
                );
                // $message .= $message4 . " / " . $message1 . " / " . $message2 . " / " . $message3;
                adjust_autoincr('texts', 'TxID');
                adjust_autoincr('sentences', 'SeID');
                runsql(
                    "DELETE " . $tbpref . "texttags 
                    FROM (" 
                        . $tbpref . "texttags 
                        LEFT JOIN " . $tbpref . "texts on TtTxID = TxID
                    ) 
                    WHERE TxID IS NULL", 
                    ''
                );        
            }
        }
    }
    if ($message4>0 || $message1>0) { 
        return "Texts archived: " . $message1 . 
        " / Sentences deleted: " . $message2 . " / Text items deleted: " . $message3; 
    }
    else { 
        return ''; 
    }
}

// -------------------------------------------------------------

function print_last_feed_update($diff): void
{
    $periods = array(
    array(60 * 60 * 24 * 365 , 'year'),
    array(60 * 60 * 24 * 30 , 'month'),
    array(60 * 60 * 24 * 7, 'week'),
    array(60 * 60 * 24 , 'day'),
    array(60 * 60 , 'hour'),
    array(60 , 'minute'),
    array(1 , 'second'),
    );
    if($diff>=1) {
        for($key=0;$key<7;$key++){
            $x=intval($diff/$periods[$key][0]);
            if($x>=1) {
                echo " last update: $x ";
                print_r($periods[$key][1]);
                if($x>1) { echo 's'; 
                }echo ' ago';break;
            }
        }
    }
    else { 
        echo ' up to date'; 
    }
}

/**
 * @return null|string|string[]
 *
 * @psalm-return array<string, string>|null|string
 */
function get_nf_option($str,$option)
{
    $arr=explode(',', $str);
    $all = null;
    if($option=='all') { 
        $all = array();
    }
    foreach($arr as $value){
        $res=explode('=', $value);
        if(trim($res[0])==$option) { 
            return $res[1]; 
        }
        if($option=='all') { 
            $all[$res[0]]=$res[1]; 
        }
    }
    if($option=='all') { 
        return $all; 
    }
    return null;
}

// -------------------------------------------------------------

/**
 * @return ((false|mixed|null|string)[]|null|string)[]|false
 *
 * @psalm-return array<'feed_text'|'feed_title'|int, array{title: null|string, desc: null|string, link: string, encoded?: false|string, description?: false|string, content?: false|string, text?: mixed}|null|string>|false
 */
function get_links_from_new_feed($NfSourceURI): array|false
{
    $rss = new DOMDocument('1.0', 'utf-8');
    if (!$rss->load($NfSourceURI, LIBXML_NOCDATA | ENT_NOQUOTES)) { 
        return false; 
    }
    $rss_data = array();
    $desc_count=0;
    $desc_nocount=0;
    $enc_count=0;
    $enc_nocount=0;
    if ($rss->getElementsByTagName('rss')->length !== 0) {
        $feed_tags = array(
            'item' => 'item',
            'title' => 'title',
            'description' => 'description',
            'link' => 'link'
        );
    } elseif ($rss->getElementsByTagName('feed')->length !== 0) {
        $feed_tags = array(
            'item' => 'entry',
            'title' => 'title',
            'description' => 'summary',
            'link' => 'link'
        );
    } else { 
        return false; 
    }
    foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
        $item = array ( 
            'title' => preg_replace(
                array('/\s\s+/','/\ \&\ /','/\"/'), 
                array(' ',' &amp; ','\"'), 
                trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)
            ),
            'desc' => preg_replace(
                array('/\s\s+/','/\ \&\ /','/\<[^\>]*\>/','/\"/'), 
                array(' ',' &amp; ','','\"'), 
                trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)
            ),
            'link' => trim(
                ($feed_tags['item']=='entry') ? 
                ($node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')) : 
                ($node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)
            ),
        );
        if ($feed_tags['item']=='item') {
            foreach($node->getElementsByTagName('encoded') as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['encoded'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['encoded'] = mb_convert_encoding(
                        html_entity_decode($item['encoded'], ENT_NOQUOTES, "UTF-8"), 
                        "HTML-ENTITIES", 
                        "UTF-8"
                    );
                }
            }
            foreach($node->getElementsByTagName('description') as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['description'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['description'] = mb_convert_encoding(
                        html_entity_decode($item['description'], ENT_NOQUOTES, "UTF-8"), 
                        "HTML-ENTITIES", 
                        "UTF-8"
                    );
                }
            }
            if (isset($item['desc'])) {
                if(mb_strlen($item['desc'], "UTF-8")>900) { 
                    $desc_count++; 
                } else { 
                    $desc_nocount++; 
                }
            }
            if (isset($item['encoded'])) {
                if(mb_strlen($item['encoded'], "UTF-8")>900) { 
                    $enc_count++; 
                } else { 
                    $enc_nocount++; 
                }
            }
        }
        if ($feed_tags['item']=='entry') {
            foreach($node->getElementsByTagName('content') as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['content'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['content'] = mb_convert_encoding(
                        html_entity_decode($item['content'], ENT_NOQUOTES, "UTF-8"),
                        "HTML-ENTITIES", 
                        "UTF-8"
                    );
                }
            }
            if (isset($item['content'])) {
                if (mb_strlen($item['content'], "UTF-8")>900) { 
                    $desc_count++; 
                } else { 
                    $desc_nocount++; 
                }
            }
        }
        if ($item['title'] != "" && $item['link'] != "") { 
            array_push($rss_data, $item); 
        }
    }
    if ($desc_count > $desc_nocount) {
        $source = ($feed_tags['item']=='entry') ? 'content' : 'description';
        $rss_data['feed_text'] = $source;
        foreach ($rss_data as $i=>$val){
            if (is_array($val))
                $rss_data[$i]['text'] = $val[$source];
        }
    } else if ($enc_count > $enc_nocount) {
        $rss_data['feed_text'] = 'encoded';
        foreach ($rss_data as $i=>$val){
            if (is_array($val))
                $rss_data[$i]['text'] = $val['encoded'];
        }
    }
    $rss_data['feed_title'] = $rss->getElementsByTagName('title')->item(0)->nodeValue;
    return $rss_data;
}

// -------------------------------------------------------------

/**
 * @return (false|null|string)[][]|false
 *
 * @psalm-return false|list<array{title: null|string, desc: null|string, link: string, date: string, text?: false|string, audio: string}>
 */
function get_links_from_rss($NfSourceURI,$NfArticleSection): array|false
{
    $rss = new DOMDocument('1.0', 'utf-8');
    if(!$rss->load($NfSourceURI, LIBXML_NOCDATA | ENT_NOQUOTES)) { 
        return false; 
    }
    $rss_data = array();
    if($rss->getElementsByTagName('rss')->length !== 0) {
        $feed_tags=array(
            'item' => 'item','title' => 'title','description' => 'description',
            'link' => 'link','pubDate' => 'pubDate','enclosure' => 'enclosure',
            'url' => 'url'
        );
    }
    elseif($rss->getElementsByTagName('feed')->length !== 0) {
        $feed_tags=array(
            'item' => 'entry','title' => 'title','description' => 'summary',
            'link' => 'link','pubDate' => 'published','enclosure' => 'link',
            'url' => 'href'
        );
    }
    else { 
        return false; 
    }
    foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
        $item = array (
        'title' => preg_replace(
            array('/\s\s+/','/\ \&\ /'), array(' ',' &amp; '), 
            trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)
        ),
        'desc' => isset($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue) ? 
        preg_replace(
            array('/\ \&\ /','/<br(\s+)?\/?>/i','/<br [^>]*?>/i','/\<[^\>]*\>/','/(\n)[\s^\n]*\n[\s]*/'), 
            array(' &amp; ',"\n","\n",'','$1$1'), 
            trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)
        ) : '',
        'link' => trim(
            ($feed_tags['item']=='entry')?(
            $node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')
            ):(
                $node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)
        ),
        'date' => isset($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue)?
        trim($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue)
        : null,
        );
        $pubDate = date_parse_from_format('D, d M Y H:i:s T', $item['date']);
        if($pubDate['error_count']>0) {
            $item['date'] = date("Y-m-d H:i:s", time()-count($rss_data));
        }
        else{
            $item['date'] = date(
                "Y-m-d H:i:s", mktime(
                    $pubDate['hour'], 
                    $pubDate['minute'], $pubDate['second'], $pubDate['month'], 
                    $pubDate['day'], $pubDate['year']
                )
            );
        }
        if(strlen($item['desc'])>1000) { $item['desc']=mb_substr($item['desc'], 0, 995, "utf-8") . '...'; 
        }
        if ($NfArticleSection) {
            foreach ($node->getElementsByTagName($NfArticleSection) as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['text'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['text']=mb_convert_encoding(html_entity_decode($item['text'], ENT_NOQUOTES, "UTF-8"), "HTML-ENTITIES", "UTF-8");
                    //$item['text']=str_replace ('"','\"',$item['text']);///////////////
                }
            }
        }
        $item['audio'] = "";
        foreach($node->getElementsByTagName($feed_tags['enclosure']) as $enc){
            $type=$enc->getAttribute('type');
            if($type=="audio/mpeg") { $item['audio']=$enc->getAttribute($feed_tags['url']); 
            }
        }
        if($item['title']!="" && ($item['link']!="" || ($NfArticleSection!="" && !empty($item['text'])))) { array_push($rss_data, $item); 
        }
    }
    return $rss_data;
}


/**
 * @return (array|mixed|null|string)[][]|null|string
 *
 * @psalm-return array<array{TxTitle: mixed, TxAudioURI?: mixed|null, TxText?: string, TxSourceURI?: mixed|string, message?: string, link?: non-empty-list<mixed>}>|null|string
 */
function get_text_from_rsslink($feed_data, $NfArticleSection, $NfFilterTags, $NfCharset=null): array|string|null
{
    global $tbpref;
    $data = null;
    foreach ($feed_data as $key =>$val) {
        if (strncmp($NfArticleSection, 'redirect:', 9)==0) {    
            $dom = new DOMDocument;
            $HTMLString = file_get_contents(trim($feed_data[$key]['link']));
            $dom->loadHTML($HTMLString);
            $xPath = new DOMXPath($dom);
            $redirect = explode(" | ", $NfArticleSection, 2);
            $NfArticleSection = $redirect[1];
            $redirect = substr($redirect[0], 9);
            $feed_host = parse_url(trim($feed_data[$key]['link']));
            foreach ($xPath->query($redirect) as $node) {
                if (empty(trim($node->localName)) 
                    || $node->nodeType == XML_TEXT_NODE
                    || !$node->hasAttributes()
                ) {
                    continue;
                }
                /*
                May be better but yet untested*/
                /**
                 * @psalm-suppress NullIterator
                 */  
                foreach ($node->attributes as $attr) {
                    if ($attr->name=='href') {
                        $feed_data[$key]['link'] = $attr->value;
                        if (strncmp($feed_data[$key]['link'], '..', 2)==0) {
                            $feed_data[$key]['link'] = 'http://'.$feed_host['host'] . 
                            substr($feed_data[$key]['link'], 2);
                        }
                    }
                }
                /*
                $len = $node->attributes->length;
                for ($i=0; $i<$len; $i++){
                    if ($node->attributes->item($i)->name=='href') {
                        $feed_data[$key]['link'] = $node->attributes->item($i)->value;
                        if (strncmp($feed_data[$key]['link'], '..', 2)==0) {
                            $feed_data[$key]['link'] = 'http://'.$feed_host['host'] . substr($feed_data[$key]['link'], 2);
                        }
                    }
                } */   
            }
            unset($dom);
            unset($HTMLString);
            unset($xPath);
        }
        $data[$key]['TxTitle'] = $feed_data[$key]['title'];
        $data[$key]['TxAudioURI'] = isset($feed_data[$key]['audio'])? $feed_data[$key]['audio'] : null;
        $data[$key]['TxText'] = "";
        if(isset($feed_data[$key]['text'])) {
            if($feed_data[$key]['text']=="") {
                unset($feed_data[$key]['text']);
            }
        }
        if(isset($feed_data[$key]['text'])) {
            $link = trim($feed_data[$key]['link']);
            if(substr($link, 0, 1)=='#') {
                runsql(
                    'UPDATE ' . $tbpref . 'feedlinks 
                    SET FlLink=' . convert_string_to_sqlsyntax($link) . ' 
                    where FlID = ' .substr($link, 1), 
                    ""
                );
            }
            $data[$key]['TxSourceURI'] = $link;
            $HTMLString=str_replace(
                array('>','<'), 
                array('> ',' <'), 
                $feed_data[$key]['text']
            );//$HTMLString=str_replace (array('>','<'),array('> ',' <'),$HTMLString);
        } else {
            $data[$key]['TxSourceURI'] = $feed_data[$key]['link'];
            $context = stream_context_create(array('http' => array('follow_location' => true )));
            $HTMLString = file_get_contents(trim($data[$key]['TxSourceURI']), false, $context);
            if(!empty($HTMLString)) {
                $encod  = '';
                if(empty($NfCharset)) {
                    
                    $header=get_headers(trim($data[$key]['TxSourceURI']), 1);
                    foreach($header as $k=>$v){
                        if(strtolower($k)=='content-type') {
                            if(is_array($v)) {
                                $encod=$v[count($v)-1];
                            }
                            else{
                                $encod=$v;
                            }
                            $pos = strpos($encod, 'charset=');
                            if(($pos!==false) && (strpos($encod, 'text/html;')!==false)) {
                                $encod=substr($encod, $pos+8);    
                                break;
                            } else { 
                                $encod=''; 
                            }
                        }
                        
                    }
                } else {
                    if ($NfCharset!='meta') { 
                        $encod  = $NfCharset; 
                    }
                }
                
                if (empty($encod)) {
                    $doc = new DOMDocument;
                    $previous_value = libxml_use_internal_errors(true);
                    $doc->loadHTML($HTMLString);
                    /*
                    if (!$doc->loadHTML($HTMLString)) {
                    foreach (libxml_get_errors() as $error) {
                    // handle errors here
                    }*/
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous_value);
                    $nodes=$doc->getElementsByTagName('meta');
                    foreach($nodes as $node){
                        $len=$node->attributes->length;
                        for($i=0;$i<$len;$i++){
                            if($node->attributes->item($i)->name=='content') {
                                $pos = strpos($node->attributes->item($i)->value, 'charset=');
                                if($pos) {
                                    $encod=substr($node->attributes->item($i)->value, $pos+8);
                                    unset($doc);
                                    unset($nodes);
                                    break 2;    
                                }
                            }
                        }    
                    }
                    if(empty($encod)) {
                        foreach($nodes as $node){
                            $len=$node->attributes->length;
                            if($len=='1') {
                                if($node->attributes->item(0)->name=='charset') {

                                    $encod=$node->attributes->item(0)->value;
                                    break;    
                                }
                            }
                        }    
                    }
                }
                unset($doc);
                unset($nodes);
                if(empty($encod)) {
                    mb_detect_order("ASCII,UTF-8,ISO-8859-1,windows-1252,iso-8859-15");
                    $encod  = mb_detect_encoding($HTMLString);
                }
                $chset=$encod;
                switch($encod){
                case 'windows-1253':
                    $chset='el_GR.utf8';
                    break;
                case 'windows-1254':
                    $chset='tr_TR.utf8';
                    break;
                case 'windows-1255':
                    $chset='he.utf8';
                    break;
                case 'windows-1256':
                    $chset='ar_AE.utf8';
                    break;
                case 'windows-1258':
                    $chset='vi_VI.utf8';
                    break;
                case 'windows-874':
                    $chset='th_TH.utf8';
                    break;
                }
                $HTMLString = '<meta http-equiv="Content-Type" content="text/html; charset='. $chset .'">' .$HTMLString;
                if($encod!=$chset) { $HTMLString = iconv($encod, 'utf-8', $HTMLString); 
                }
                else { $HTMLString=mb_convert_encoding($HTMLString, 'HTML-ENTITIES', $encod); 
                }
            }
        }
        $HTMLString=str_replace(array('<br />','<br>','</br>','</h','</p'), array("\n","\n","","\n</h","\n</p"), $HTMLString);
        $dom = new DOMDocument();
        $previous_value = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8">' . $HTMLString);
        foreach ($dom->childNodes as $item){/////////////////////////////////
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item); // remove hack
            }
        }
        $dom->encoding = 'UTF-8'; // insert proper    //////////////////////////////

        /*
        if (!$dom->loadHTML($HTMLString)) {
        foreach (libxml_get_errors() as $error) {
        // handle errors here
        }*/
        libxml_clear_errors();
        libxml_use_internal_errors($previous_value);
        $filter_tags = explode("!?!", rtrim("//img | //script | //meta | //noscript | //link | //iframe!?!".$NfFilterTags, "!?!"));
        foreach (explode("!?!", $NfArticleSection) as $article_tag) {
            if($article_tag=='new') {
                foreach ($filter_tags as $filter_tag){
                    $nodes=$dom->getElementsByTagName($filter_tag);
                    $domElemsToRemove = array();
                    foreach ( $nodes as $domElement ) {
                        $domElemsToRemove[] = $domElement;
                    }
                    foreach ($domElemsToRemove as $node) {
                        $node->parentNode->removeChild($node);
                    }
                }
                $nodes=$dom->getElementsByTagName('*');
                foreach ( $nodes as $node ) {
                    $node->removeAttribute('onclick');
                }
                $str=$dom->saveHTML($dom);
                //$str=mb_convert_encoding(html_entity_decode($str, ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
                return preg_replace(
                    array('/\<html[^\>]*\>/','/\<body\>/'), array('',''), $str
                );
            }
        }
        $selector = new DOMXPath($dom);
        foreach ($filter_tags as $filter_tag){
            foreach ($selector->query($filter_tag) as $node) {
                $node->parentNode->removeChild($node);
            }
        }
        if(isset($feed_data[$key]['text'])) {
            foreach ($selector->query($NfArticleSection) as $text_temp) {
                if($text_temp->nodeValue != '') {
                    $data[$key]['TxText'] .= mb_convert_encoding(
                        $text_temp->nodeValue, "HTML-ENTITIES", "UTF-8"
                    );
                }
            }
            $data[$key]['TxText'] = html_entity_decode($data[$key]['TxText'], ENT_NOQUOTES, "UTF-8");
        }        
        else{
            $article_tags = explode("!?!", $NfArticleSection);if(strncmp($NfArticleSection, 'redirect:', 9)==0) { unset($article_tags[0]); 
            }
            foreach ($article_tags as $article_tag) {
                foreach ($selector->query($article_tag) as $text_temp) {
                    if($text_temp->nodeValue != '') {
                        $data[$key]['TxText'].= $text_temp->nodeValue;
                    }
                }
            }
        }        
                
        if($data[$key]['TxText']=="") {
            unset($data[$key]);
            if(!isset($data['error']['message'])) { $data['error']['message']=''; 
            }
            $data['error']['message'].= '"<a href=' . $feed_data[$key]['link'] .' onclick="window.open(this.href, \'child\'); return false">'  . $feed_data[$key]['title'] . '</a>" has no text section!<br />';
            $data['error']['link'][]=$feed_data[$key]['link'];
        }
        else{
            $data[$key]['TxText']=trim(preg_replace(array('/[\r\t]+/','/(\n)[\s^\n]*\n[\s]*/','/\ \ +/'), array(' ','$1$1',' '), $data[$key]['TxText']));
            //$data[$key]['TxText']=trim(preg_replace(array('/[\s^\n]+/','/(\n)[\s^\n]*\n[\s]*/','/\ +/','/[ ]*(\n)/'), array(' ','$1$1',' ','$1'), $data[$key]['TxText']));
        }
    }
    return $data;
}


/**
 * Return navigation arrows to previous and next texts.
 *
 * @param int    $textid  ID of the current text
 * @param string $url     Base URL to append before $textid
 * @param bool   $onlyann Restrict to annotated texts only
 * @param string $add     Some content to add before the output
 *
 * @return string Arrows to previous and next texts.
 */
function getPreviousAndNextTextLinks($textid, $url, $onlyann, $add): string 
{
    global $tbpref;
    $currentlang = validateLang(
        processDBParam("filterlang", 'currentlanguage', '', 0)
    );
    $wh_lang = '';
    if ($currentlang != '') {
        $wh_lang = ' AND TxLgID=' . $currentlang;
    }

    $currentquery = processSessParam("query", "currenttextquery", '', 0);
    $currentquerymode = processSessParam(
        "query_mode", "currenttextquerymode", 'title,text', 0
    );
    $currentregexmode = getSettingWithDefault("set-regex-mode");
    $wh_query = $currentregexmode . 'LIKE ';
    if ($currentregexmode == '') {
        $wh_query .= convert_string_to_sqlsyntax(
            str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))
        );
    } else {
        $wh_query .= convert_string_to_sqlsyntax($currentquery);
    }
    switch ($currentquerymode) {
    case 'title,text':
        $wh_query=' AND (TxTitle ' . $wh_query . ' OR TxText ' . $wh_query . ')';
        break;
    case 'title':
        $wh_query=' AND (TxTitle ' . $wh_query . ')';
        break;
    case 'text':
        $wh_query=' AND (TxText ' . $wh_query . ')';
        break;
    }
    if ($currentquery=='') { 
        $wh_query = ''; 
    }

    $currenttag1 = validateTextTag(
        processSessParam("tag1", "currenttexttag1", '', 0), 
        $currentlang
    );
    $currenttag2 = validateTextTag(
        processSessParam("tag2", "currenttexttag2", '', 0), 
        $currentlang
    );
    $currenttag12 = processSessParam("tag12", "currenttexttag12", '', 0);
    $wh_tag1 = null;
    $wh_tag2 = null;
    if ($currenttag1 == '' && $currenttag2 == '') {
        $wh_tag = ''; 
    } else {
        if ($currenttag1 != '') {
            if ($currenttag1 == -1) {
                $wh_tag1 = "group_concat(TtT2ID) IS NULL"; 
            } else {
                $wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'"; 
            }
        }
        if ($currenttag2 != '') {
            if ($currenttag2 == -1) {
                $wh_tag2 = "group_concat(TtT2ID) IS NULL"; 
            }
            else {
                $wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'"; 
            }
        }
        if ($currenttag1 != '' && $currenttag2 == '') {    
            $wh_tag = " having (" . $wh_tag1 . ') '; 
        }
        elseif ($currenttag2 != '' && $currenttag1 == '') {    
            $wh_tag = " having (" . $wh_tag2 . ') ';
        } else {
            $wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) '; 
        }
    }

    $currentsort = processDBParam("sort", 'currenttextsort', '1', 1);
    $sorts = array('TxTitle','TxID desc','TxID asc');
    $lsorts = count($sorts);
    if ($currentsort < 1) { 
        $currentsort = 1; 
    }
    if ($currentsort > $lsorts) { 
        $currentsort = $lsorts; 
    }

    if ($onlyann) { 
        $sql = 
        'SELECT TxID 
        FROM (
            (' . $tbpref . 'texts 
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            ) 
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages 
        WHERE LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 ' 
        . $wh_lang . $wh_query . ' 
        GROUP BY TxID ' . $wh_tag . ' 
        ORDER BY ' . $sorts[$currentsort-1]; 
    }
    else {
        $sql = 
        'SELECT TxID 
        FROM (
            (' . $tbpref . 'texts 
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            ) 
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages 
        WHERE LgID = TxLgID ' . $wh_lang . $wh_query . ' 
        GROUP BY TxID ' . $wh_tag . ' 
        ORDER BY ' . $sorts[$currentsort-1]; 
    }

    $list = array(0);
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        array_push($list, (int) $record['TxID']);
    }
    mysqli_free_result($res);
    array_push($list, 0);
    $listlen = count($list);
    for ($i=1; $i < $listlen-1; $i++) {
        if($list[$i] == $textid) {
            if ($list[$i-1] !== 0) {
                $title = tohtml(getTextTitle($list[$i-1]));
                $prev = '<a href="' . $url . $list[$i-1] . '" target="_top"><img src="icn/navigation-180-button.png" title="Previous Text: ' . $title . '" alt="Previous Text: ' . $title . '" /></a>';
            }
            else {
                $prev = '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />'; 
            }
            if ($list[$i+1] !== 0) {
                $title = tohtml(getTextTitle($list[$i+1]));
                $next = '<a href="' . $url . $list[$i+1] . 
                '" target="_top"><img src="icn/navigation-000-button.png" title="Next Text: ' . $title . '" alt="Next Text: ' . $title . '" /></a>';
            }
            else {
                $next = '<img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />'; 
            }
            return $add . $prev . ' ' . $next;
        }
    }
    return $add . '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" /> 
    <img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
}


/**
 * Return an HTML formatted logo of the application.
 *
 * @since 2.7.0 Do no longer indicate database prefix in logo
 */
function echo_lwt_logo(): void 
{
    echo '<img class="lwtlogo" src="' . get_file_path('img/lwt_icon.png') . '" title="LWT" alt="LWT logo" />';
}


/**
 * Return all different database prefixes that are in use.
 *
 * @return string[] A list of prefixes.
 *
 * @psalm-return list<string>
 */
function getprefixes(): array 
{
    $prefix = array();
    $res = do_mysqli_query(
        str_replace(
            '_', 
            "\\_", 
            "SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull('%_settings')
        )
    );
    while ($row = mysqli_fetch_row($res)) {
        $prefix[] = substr($row[0], 0, -9); 
    }
    mysqli_free_result($res);
    return $prefix;
}

/**
 * Select the path for a media (audio or video).
 *
 * @param string $f Previous media file URI
 *
 * @return string HTML-formatted string for media selection
 */
function selectmediapath($f): string 
{
    $exists = file_exists('media');
    if ($exists) {
        if (is_dir('media')) { 
            $msg = ''; 
        } else { 
            $msg = '<br />[Error: "../' . basename(getcwd()) . '/media" exists, but it is not a directory.]'; 
        }
    } else {
        $msg = '<br />[Directory "../' . basename(getcwd()) . '/media" does not yet exist.]';
    }
    $r = '<p>
    YouTube, Dailymotion, Vimeo or choose a file in "../' . basename(getcwd()) . '/media"
    <br />
    (only mp3, mp4, ogg, wav, webm files shown):
    </p> ' . $msg;
    if ($msg == '') {
        $r .= '
        <select name="Dir" onchange="{val=this.form.Dir.options[this.form.Dir.selectedIndex].value; if (val != \'\') this.form.' 
            . $f . '.value = val; this.form.Dir.value=\'\';}" style="width: 200px;">
            <option value="">[Choose...]</option>' . 
            selectmediapathoptions('media') . 
        '</select> ';
    }
    $r .= '<span class="click" onclick="do_ajax_update_media_select();" style="margin-left: 16px;">
        <img src="icn/arrow-circle-135.png" title="Refresh Media Selection" alt="Refresh Media Selection" /> Refresh</span>';
    return $r;
}

/**
 * Get the dirrent options to dsplay as acceptable media files.
 *
 * @param string $dir Directory containing files
 *
 * @return string HTML-formatted OPTION tags
 */
function selectmediapathoptions($dir): string 
{
    $is_windows = ("WIN" == strtoupper(substr(PHP_OS, 0, 3)));
    $mediadir = scandir($dir);
    $formats = array('mp3', 'mp4', 'ogg', 'wav', 'webm');
    $r = '<option disabled="disabled">-- Directory: ' . tohtml($dir) . ' --</option>';
    foreach ($mediadir as $entry) {
        if ($is_windows) { 
            $entry = mb_convert_encoding($entry, 'UTF-8', 'ISO-8859-1'); 
        }
        if (substr($entry, 0, 1) != '.' && !is_dir($dir . '/' . $entry)) {
            $ex = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ex, $formats)) {
                $r .= '<option value="' . tohtml($dir . '/' . $entry) . '">' . 
                tohtml($dir . '/' . $entry) . '</option>'; 
            }
        }
    }
    foreach ($mediadir as $entry) {
        if (substr($entry, 0, 1) != '.' && is_dir($dir . '/' . $entry)) {
            $r .= selectmediapathoptions($dir . '/' . $entry); 
        }
    }
    return $r;
}

// -------------------------------------------------------------

function get_seconds_selectoptions($v): string 
{
    if (!isset($v) ) { 
        $v = 5; 
    }
    $r = '';
    for ($i=1; $i <= 10; $i++) {
        $r .= "<option value=\"" . $i . "\"" . get_selected($v, $i);
        $r .= ">" . $i . " sec</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_playbackrate_selectoptions($v): string 
{
    if (! isset($v) ) { $v = '10'; 
    }
    $r = '';
    for ($i=5; $i <= 15; $i++) {
        $text = ($i<10 ? (' 0.' . $i . ' x ') : (' 1.' . ($i-10) . ' x ') ); 
        $r .= "<option value=\"" . $i . "\"" . get_selected($v, $i);
        $r .= ">&nbsp;" . $text . "&nbsp;</option>";
    }
    return $r;
}




// -------------------------------------------------------------

function remove_soft_hyphens($str): string 
{
    return str_replace('­', '', $str);  // first '..' contains Softhyphen 0xC2 0xAD
}


// -------------------------------------------------------------

function replace_supp_unicode_planes_char($s): ?string 
{
    return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $s); 
    /* U+2588 = UTF8: E2 96 88 = FULL BLOCK = ⬛︎  */ 
}

// -------------------------------------------------------------

function makeCounterWithTotal($max, $num): string 
{
    if ($max == 1) { 
        return ''; 
    }
    if ($max < 10) { 
        return $num . "/" . $max; 
    }
    return substr(
        str_repeat("0", strlen($max)) . $num,
        -strlen($max)
    ) . "/" . $max;
}

// -------------------------------------------------------------

function encodeURI($url): string 
{
    $reserved = array(
    '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', 
    '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
    );
    $unescaped = array(
    '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
    '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
    );
    $score = array(
    '%23'=>'#'
    );
    return strtr(rawurlencode($url), array_merge($reserved, $unescaped, $score));
}

/**
 * Echo the path of a file using the theme directory. Echo the base file name of 
 * file is not found
 *
 * @param string $filename Filename
 */
function print_file_path($filename): void
{
    echo get_file_path($filename);
}

/**
 * Get the path of a file using the theme directory
 * 
 * @param string $filename Filename
 * 
 * @return string File path if it exists, otherwise the filename
 */
function get_file_path($filename)
{
    $file = getSettingWithDefault('set-theme-dir').preg_replace('/.*\//', '', $filename);
    if (file_exists($file)) { 
        return $file; 
    }
    return $filename;
}


// -------------------------------------------------------------

function get_sepas() 
{
    static $sepa;
    if (!$sepa) {
        $sepa = preg_quote(getSettingWithDefault('set-term-translation-delimiters'), '/');
    }
    return $sepa;
}

// -------------------------------------------------------------

function get_first_sepa() 
{
    static $sepa;
    if (!$sepa) {
        $sepa = mb_substr(
            getSettingWithDefault('set-term-translation-delimiters'),
            0, 1, 'UTF-8'
        );
    }
    return $sepa;
}


/**
 * Prepare options for mobile.
 *
 * @param "0"|"1"|"2" $v Current mobile type
 */
function get_mobile_display_mode_selectoptions($v): string 
{
    if (!isset($v)) { 
        $v = "0"; 
    }
    $r  = "<option value=\"0\"" . get_selected($v, "0");
    $r .= ">Auto</option>";
    $r .= "<option value=\"1\"" . get_selected($v, "1");
    $r .= ">Force Non-Mobile</option>";
    $r .= "<option value=\"2\"" . get_selected($v, "2");
    $r .= ">Force Mobile</option>";
    return $r;
}

// -------------------------------------------------------------

function get_sentence_count_selectoptions($v): string 
{
    if (!isset($v)) {
        $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Just ONE</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">TWO (+previous)</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">THREE (+previous,+next)</option>";
    return $r;
}

// -------------------------------------------------------------

function get_words_to_do_buttons_selectoptions($v): string 
{
    if (!isset($v)) {
        $v = "1"; 
    }
    $r  = "<option value=\"0\"" . get_selected($v, "0");
    $r .= ">I Know All &amp; Ignore All</option>";
    $r .= "<option value=\"1\"" . get_selected($v, "1");
    $r .= ">I Know All</option>";
    $r .= "<option value=\"2\"" . get_selected($v, "2");
    $r .= ">Ignore All</option>";
    return $r;
}

// -------------------------------------------------------------

function get_regex_selectoptions($v): string 
{
    if (!isset($v)) {
        $v = ""; 
    }
    $r  = "<option value=\"\"" . get_selected($v, "");
    $r .= ">Default</option>";
    $r .= "<option value=\"r\"" . get_selected($v, "r");
    $r .= ">RegEx</option>";
    $r .= "<option value=\"COLLATE 'utf8_bin' r\"" . get_selected($v, "COLLATE 'utf8_bin' r");
    $r .= ">RegEx CaseSensitive</option>";
    return $r;
}

// -------------------------------------------------------------

function get_tooltip_selectoptions($v): string 
{
    if (!isset($v)) {
        $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Native</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">JqueryUI</option>";
    return $r;
}

// -------------------------------------------------------------

function get_themes_selectoptions($v): string
{
    $themes = glob('themes/*', GLOB_ONLYDIR);
    $r = '<option value="themes/Default/">Default</option>';
    foreach($themes as $theme){
        if($theme!='themes/Default') {
            $r.= '<option value="'.$theme.'/" '. get_selected($v, $theme.'/');
            $r .= ">". str_replace(array('themes/','_'), array('',' '), $theme) ."</option>";
        }
    }
    return $r;
}


/**
 * Get a SESSION value and update it if necessary.
 * 
 * @param string $reqkey  If in $_REQUEST, update the session with $_REQUEST[$reqkey]
 * @param string $sesskey Field of the session to get or update
 * @param string $default Default value to return
 * @param bool   $isnum   If true, convert the result to an int
 * 
 * @return string|int The string data unless $isnum is specified
 */
function processSessParam($reqkey, $sesskey, $default, $isnum) 
{
    if (isset($_REQUEST[$reqkey])) {
        $reqdata = trim($_REQUEST[$reqkey]);
        $_SESSION[$sesskey] = $reqdata;
        $result = $reqdata;
    } elseif(isset($_SESSION[$sesskey])) {
        $result = $_SESSION[$sesskey];
    } else {
        $result = $default;
    }
    if ($isnum) {
        $result = (int)$result; 
    }
    return $result;
}


/**
 * Get a database value and update it if necessary.
 * 
 * @param string $reqkey  If in $_REQUEST, update the database with $_REQUEST[$reqkey]
 * @param string $dbkey   Field of the database to get or update
 * @param string $default Default value to return
 * @param bool   $isnum   If true, convert the result to an int
 * 
 * @return string|int The string data unless $isnum is specified
 */
function processDBParam($reqkey, $dbkey, $default, $isnum) 
{
    $dbdata = getSetting($dbkey);
    if (isset($_REQUEST[$reqkey])) {
        $reqdata = trim($_REQUEST[$reqkey]);
        saveSetting($dbkey, $reqdata);
        $result = $reqdata;
    } elseif ($dbdata != '') {
        $result = $dbdata;
    } else {
        $result = $default;
    }
    if ($isnum) { 
        $result = (int)$result; 
    }
    return $result;
}


// -------------------------------------------------------------

function getWordTagList($wid, $before=' ', $brack=1, $tohtml=1): string 
{
    global $tbpref;
    $lbrack = $rbrack = '';
    if ($brack) {
        $lbrack = "[";
        $rbrack = "]";
    }
    $r = get_first_value(
        "SELECT IFNULL(
            GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', '),
            ''
        ) AS value 
        FROM (
            (
                {$tbpref}words 
                LEFT JOIN {$tbpref}wordtags 
                ON WoID = WtWoID
            ) 
            LEFT JOIN {$tbpref}tags 
            ON TgID = WtTgID
        ) 
        WHERE WoID = $wid"
    );
    if ($r != '') { 
        $r = $before . $lbrack . $r . $rbrack; 
    }
    if ($tohtml) {
        $r = tohtml($r); 
    }
    return $r;
}

/**
 * Return the last inserted ID in the database
 * 
 * @return int
 * 
 * @since 2.6.0-fork Officially returns a int in documentation, as it was the case
 */
function get_last_key() 
{
    return (int)get_first_value('SELECT LAST_INSERT_ID() AS value');        
}

/**
 * If $value is true, return an HTML-style checked attribute.
 *
 * @param mixed $value Some value that can be evaluated as a boolean
 *
 * @return ' checked="checked" '|'' if value is true
 */
function get_checked($value): string 
{
    if (!isset($value)) { 
        return ''; 
    }
    if ($value) { 
        return ' checked="checked" '; 
    }
    return '';
}

/**
 * Return an HTML attribute if $value is equal to $selval.
 * 
 * @return ''|' selected="selected" ' Depending if inputs are equal 
 */
function get_selected($value, $selval) 
{
    if (!isset($value)) { 
        return ''; 
    }
    if ($value == $selval) { 
        return ' selected="selected" '; 
    }
    return '';
}


/* Functions relative to word tests. */

/**
 * Create a projection operator do perform word test.
 * 
 * @param int $key Type of test. 
 *                 * 0: word selection
 *                 * 1: text item selection
 *                 * 2: from language
 *                 * 3: from text
 * @param array|int $value Object to select.
 * 
 * @return string Operator
 * 
 * @global string $tbpref;
 */
function do_test_test_get_projection($key, $value)
{
    global $tbpref;
    switch ($key)
    {
        case 0:
            $id_string = implode(",", $value);
            $testsql = " {$tbpref}words WHERE WoID IN ($id_string) ";
            $cntlang = get_first_value(
                "SELECT COUNT(DISTINCT WoLgID) AS value 
                FROM $testsql"
            );
            if ($cntlang > 1) {
                echo "<p>Sorry - The selected terms are in $cntlang languages," . 
                " but tests are only possible in one language at a time.</p>";
                exit();
            }
            break;
        case 1:
            $id_string = implode(",", $value);
            $testsql = " {$tbpref}words, {$tbpref}textitems2 
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ($id_string) ";
            $cntlang = get_first_value(
                "SELECT COUNT(DISTINCT WoLgID) AS value 
                FROM $testsql"
            );
            if ($cntlang > 1) {
                echo "<p>Sorry - The selected terms are in $cntlang languages," . 
                " but tests are only possible in one language at a time.</p>";
                exit();
            }
            break;
        case 2:
            $testsql = " {$tbpref}words WHERE WoLgID = $value ";
            break;
        case 3:
            $testsql = " {$tbpref}words, {$tbpref}textitems2 
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = $value ";
            break;
        default:
            my_die("do_test_test.php called with wrong parameters"); 
            break;
    }
    return $testsql;
}

/**
 * Prepare the SQL when the text is a selection.
 * 
 * @param int $selection_type. 2 is words selection and 3 is terms selection.
 * @param string $selection_data Comma separated ID of elements to test.
 * 
 * @return string SQL formatted string suitable to projection (inserted in a "FROM ")
 */
function do_test_test_from_selection($selection_type, $selection_data) {
    $data_string_array = explode(",", trim($selection_data, "()"));
    $data_int_array = array_map('intval', $data_string_array);
    switch ((int)$selection_type) {
        case 2:
            $test_sql = do_test_test_get_projection(0, $data_int_array);
            break;
        case 3:
            $test_sql = do_test_test_get_projection(1, $data_int_array);
            break;
        default:
            $test_sql = $selection_data;
            $cntlang = get_first_value(
                "SELECT COUNT(DISTINCT WoLgID) AS value 
                FROM $test_sql"
            );
            if ($cntlang > 1) {
                echo "<p>Sorry - The selected terms are in $cntlang languages," . 
                " but tests are only possible in one language at a time.</p>";
                exit();
            }
    }
    return $test_sql;
}


/**
 * Make the plus and minus controls in a test table for a word.
 * 
 * @param int $score  Score associated to this word
 * @param int $status Status for this word
 * @param int $wordid Word ID
 * 
 * @return string the HTML-formatted string to use
 */
function make_status_controls_test_table($score, $status, $wordid): string 
{
    if ($score < 0) { 
        $scoret = '<span class="red2">' . get_status_abbr($status) . '</span>'; 
    } else {
        $scoret = get_status_abbr($status); 
    }
        
    if ($status <= 5 || $status == 98) { 
        $plus = '<img src="icn/plus.png" class="click" title="+" alt="+" onclick="changeTableTestStatus(' . $wordid .',true);" />'; 
    } else {
        $plus = '<img src="'.get_file_path('icn/placeholder.png').'" title="" alt="" />'; 
    }
    if ($status >=1 ) { 
        $minus = '<img src="icn/minus.png" class="click" title="-" alt="-" onclick="changeTableTestStatus(' . $wordid .',false);" />'; 
    } else {
        $minus = '<img src="'.get_file_path('icn/placeholder.png').'" title="" alt="" />'; 
    }
    return ($status == 98 ? '' : $minus . ' ') . $scoret . ($status == 99 ? '' : ' ' . $plus);
}

/**
 * Return options as HTML code to insert in a language select.
 * 
 * @param string|int|null $v  Selected language ID
 * @param string          $dt Default value to display
 */
function get_languages_selectoptions($v, $dt): string 
{
    global $tbpref;
    $sql = "SELECT LgID, LgName FROM {$tbpref}languages 
    WHERE LgName<>'' ORDER BY LgName";
    $res = do_mysqli_query($sql);
    $r = '<option value="" ';
    if (!isset($v) || trim($v) == '') {
        $r .= 'selected="selected"';
    } 
    $r .= ">$dt</option>";
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["LgName"];
        if (strlen($d) > 30 ) { 
            $d = substr($d, 0, 30) . "..."; 
        }
        $r .= "<option value=\"" . $record["LgID"] . "\" " . 
        get_selected($v, $record["LgID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    return $r;
}

// -------------------------------------------------------------

function get_languagessize_selectoptions($v): string 
{
    if (!isset($v)) { 
        $v = 100; 
    }
    $r = "<option value=\"100\"" . get_selected($v, 100);
    $r .= ">100 %</option>";
    $r .= "<option value=\"150\"" . get_selected($v, 150);
    $r .= ">150 %</option>";
    $r .= "<option value=\"200\"" . get_selected($v, 200);
    $r .= ">200 %</option>";
    $r .= "<option value=\"250\"" . get_selected($v, 250);
    $r .= ">250 %</option>";
    return $r;
}

// -------------------------------------------------------------

function get_wordstatus_radiooptions($v): string 
{
    if (!isset($v)) { 
        $v = 1; 
    }
    $r = "";
    $statuses = get_statuses();
    foreach ($statuses as $n => $status) {
        $r .= '<span class="status' . $n . '" title="' . tohtml($status["name"]) . '">';
        $r .= '&nbsp;<input type="radio" name="WoStatus" value="' . $n . '"';
        if ($v == $n) { 
            $r .= ' checked="checked"'; 
        }
        $r .= ' />' . tohtml($status["abbr"]) . "&nbsp;</span> ";
    }
    return $r;
}

// -------------------------------------------------------------

function get_wordstatus_selectoptions($v, $all, $not9899, $off=true): string 
{
    if (!isset($v)) {
        if ($all) { 
            $v = ""; 
        } else { 
            $v = 1; 
        }
    }
    $r = "";
    if ($all && $off) {
        $r .= "<option value=\"\"" . get_selected($v, '');
        $r .= ">[Filter off]</option>";
    }
    $statuses = get_statuses();
    foreach ($statuses as $n => $status) {
        if ($not9899 && ($n == 98 || $n == 99)) { 
            continue; 
        }
        $r .= "<option value =\"" . $n . "\"" . get_selected($v, $n!=0?$n:'0');
        $r .= ">" . tohtml($status['name']) . " [" . 
        tohtml($status['abbr']) . "]</option>";
    }
    if ($all) {
        $r .= '<option disabled="disabled">--------</option>';
        $status_1_name = tohtml($statuses[1]["name"]);
        $status_1_abbr = tohtml($statuses[1]["abbr"]);
        $r .= "<option value=\"12\"" . get_selected($v, 12);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
        tohtml($statuses[2]["abbr"]) . "]</option>";
        $r .= "<option value=\"13\"" . get_selected($v, 13);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
        tohtml($statuses[3]["abbr"]) . "]</option>";
        $r .= "<option value=\"14\"" . get_selected($v, 14);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"15\"" . get_selected($v, 15);
        $r .= ">Learning/-ed [" . $status_1_abbr . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $status_2_name = tohtml($statuses[2]["name"]);
        $status_2_abbr = tohtml($statuses[2]["abbr"]);
        $r .= "<option value=\"23\"" . get_selected($v, 23);
        $r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
        tohtml($statuses[3]["abbr"]) . "]</option>";
        $r .= "<option value=\"24\"" . get_selected($v, 24);
        $r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"25\"" . get_selected($v, 25);
        $r .= ">Learning/-ed [" . $status_2_abbr . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $status_3_name = tohtml($statuses[3]["name"]);
        $status_3_abbr = tohtml($statuses[3]["abbr"]);
        $r .= "<option value=\"34\"" . get_selected($v, 34);
        $r .= ">" . $status_3_name . " [" . $status_3_abbr . ".." . 
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"35\"" . get_selected($v, 35);
        $r .= ">Learning/-ed [" . $status_3_abbr . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $r .= "<option value=\"45\"" . get_selected($v, 45);
        $r .= ">Learning/-ed [" .  tohtml($statuses[4]["abbr"]) . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $r .= "<option value=\"599\"" . get_selected($v, 599);
        $r .= ">All known [" . tohtml($statuses[5]["abbr"]) . "+" . 
        tohtml($statuses[99]["abbr"]) . "]</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_annotation_position_selectoptions($v): string
{
    if (! isset($v) ) { $v = 1; 
    }
    $r = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Behind</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">In Front Of</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Below</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Above</option>";
    return $r;
}

// -------------------------------------------------------------

function get_paging_selectoptions($currentpage, $pages): string 
{
    $r = "";
    for ($i=1; $i<=$pages; $i++) {
        $r .= "<option value=\"" . $i . "\"" . get_selected($i, $currentpage);
        $r .= ">$i</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_wordssort_selectoptions($v): string 
{
    if (! isset($v) ) { 
        $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Term A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Translation A-Z</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Newest first</option>";
    $r .= "<option value=\"7\"" . get_selected($v, 7);
    $r .= ">Oldest first</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Oldest first</option>"; 
    $r .= "<option value=\"5\"" . get_selected($v, 5);
    $r .= ">Status</option>";
    $r .= "<option value=\"6\"" . get_selected($v, 6);
    $r .= ">Score Value (%)</option>";
    $r .= "<option value=\"7\"" . get_selected($v, 7);
    $r .= ">Word Count Active Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_tagsort_selectoptions($v): string 
{
    if (! isset($v) ) { 
        $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Tag Text A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Tag Comment A-Z</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Newest first</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Oldest first</option>";
    return $r;
}

// -------------------------------------------------------------

function get_textssort_selectoptions($v): string 
{ 
    if (!isset($v)) { 
        $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Title A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Newest first</option>"; 
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Oldest first</option>"; 
    return $r;
}


// -------------------------------------------------------------

function get_andor_selectoptions($v): string 
{
    if (!isset($v)) { 
        $v = 0; 
    }
    $r  = "<option value=\"0\"" . get_selected($v, 0);
    $r .= ">... OR ...</option>";
    $r .= "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">... AND ...</option>";
    return $r;
}

// -------------------------------------------------------------

function get_set_status_option($n, $suffix = ""): string 
{
    return "<option value=\"s" . $n . $suffix . "\">Set Status to " .
    tohtml(get_status_name($n)) . " [" . tohtml(get_status_abbr($n)) .
    "]</option>";
}

// -------------------------------------------------------------

function get_status_name($n): string 
{
    $statuses = get_statuses();
    return $statuses[$n]["name"];
}

// -------------------------------------------------------------

function get_status_abbr($n): string 
{
    $statuses = get_statuses();
    return $statuses[$n]["abbr"];
}

// -------------------------------------------------------------

function get_colored_status_msg($n): string 
{
    return '<span class="status' . $n . '">&nbsp;' . tohtml(get_status_name($n)) . 
    '&nbsp;[' . tohtml(get_status_abbr($n)) . ']&nbsp;</span>';
}

// -------------------------------------------------------------

function get_multiplewordsactions_selectoptions(): string 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"test\">Test Marked Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"spl1\">Increase Status by 1 [+1]</option>";
    $r .= "<option value=\"smi1\">Reduce Status by 1 [-1]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= get_set_status_option(1);
    $r .= get_set_status_option(5);
    $r .= get_set_status_option(99);
    $r .= get_set_status_option(98);
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"today\">Set Status Date to Today</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"lower\">Set Marked Terms to Lowercase</option>";
    $r .= "<option value=\"cap\">Capitalize Marked Terms</option>";
    $r .= "<option value=\"delsent\">Delete Sentences of Marked Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"exp\">Export Marked Terms (Anki)</option>";
    $r .= "<option value=\"exp2\">Export Marked Terms (TSV)</option>";
    $r .= "<option value=\"exp3\">Export Marked Terms (Flexible)</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Terms</option>";
    return $r;
}

// -------------------------------------------------------------

function get_multipletagsactions_selectoptions(): string 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option value=\"del\">Delete Marked Tags</option>";
    return $r;
}

// -------------------------------------------------------------

function get_allwordsactions_selectoptions(): string 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"testall\">Test ALL Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"spl1all\">Increase Status by 1 [+1]</option>";
    $r .= "<option value=\"smi1all\">Reduce Status by 1 [-1]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= get_set_status_option(1, "all");
    $r .= get_set_status_option(5, "all");
    $r .= get_set_status_option(99, "all");
    $r .= get_set_status_option(98, "all");
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"todayall\">Set Status Date to Today</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"lowerall\">Set ALL Terms to Lowercase</option>";
    $r .= "<option value=\"capall\">Capitalize ALL Terms</option>";
    $r .= "<option value=\"delsentall\">Delete Sentences of ALL Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtagall\">Add Tag</option>";
    $r .= "<option value=\"deltagall\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"expall\">Export ALL Terms (Anki)</option>";
    $r .= "<option value=\"expall2\">Export ALL Terms (TSV)</option>";
    $r .= "<option value=\"expall3\">Export ALL Terms (Flexible)</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"delall\">Delete ALL Terms</option>";
    return $r;
}

// -------------------------------------------------------------

function get_alltagsactions_selectoptions(): string 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option value=\"delall\">Delete ALL Tags</option>";
    return $r;
}

/// Returns options for an HTML dropdown to choose a text along a criterion
function get_multipletextactions_selectoptions(): string 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"test\">Test Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"rebuild\">Reparse Texts</option>";
    $r .= "<option value=\"setsent\">Set Term Sentences</option>";
    $r .= "<option value=\"setactsent\">Set Active Term Sentences</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"arch\">Archive Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_multiplearchivedtextactions_selectoptions(): string 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"unarch\">Unarchive Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_texts_selectoptions($lang, $v): string 
{
    global $tbpref;
    if (! isset($v) ) { $v = ''; 
    }
    if (! isset($lang) ) { $lang = ''; 
    }    
    if ($lang=="" ) { 
        $l = ""; 
    }    
    else { 
        $l = "and TxLgID=" . $lang; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    $sql = "select TxID, TxTitle, LgName 
    from " . $tbpref . "languages, " . $tbpref . "texts 
    where LgID = TxLgID " . $l . " 
    order by LgName, TxTitle";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["TxTitle"];
        if (mb_strlen($d, 'UTF-8') > 30 ) { 
            $d = mb_substr($d, 0, 30, 'UTF-8') . "..."; 
        }
        $r .= "<option value=\"" . $record["TxID"] . "\"" . 
        get_selected($v, $record["TxID"]) . ">" . tohtml(($lang!="" ? "" : ($record["LgName"] . ": ")) . $d) . "</option>";
    }
    mysqli_free_result($res);
    return $r;
}


/**
 * Makes HTML content for a text of style "Page 1 of 3".
 * 
 * @return void
 */
function makePager($currentpage, $pages, $script, $formname): void 
{
    $marger = 'style="margin-left: 4px; margin-right: 4px;"';
    if ($currentpage > 1) { 
        ?>
<a href="<?php echo $script; ?>?page=1" <?php echo $marger; ?>>
    <img src="icn/control-stop-180.png" title="First Page" alt="First Page" />
</a>
<a href="<?php echo $script; ?>?page=<?php echo $currentpage-1; ?>" <?php echo $marger; ?>>
    <img src="icn/control-180.png" title="Previous Page" alt="Previous Page" />
</a>
        <?php
    }
    ?>
Page
    <?php
    if ($pages == 1) { 
        echo '1'; 
    }
    else {
        ?>
<select name="page" onchange="{val=document.<?php echo $formname; ?>.page.options[document.<?php echo $formname; ?>.page.selectedIndex].value; location.href='<?php echo $script; ?>?page=' + val;}">
    <?php echo get_paging_selectoptions($currentpage, $pages); ?>
</select>
        <?php
    }
    echo ' of ' . $pages . ' ';
    if ($currentpage < $pages) { 
        ?>
<a href="<?php echo $script; ?>?page=<?php echo $currentpage+1; ?>" <?php echo $marger; ?>>
    <img src="icn/control.png" title="Next Page" alt="Next Page" />
</a>
<a href="<?php echo $script; ?>?page=<?php echo $pages; ?>" <?php echo $marger; ?>>
    <img src="icn/control-stop.png" title="Last Page" alt="Last Page" />
</a>
        <?php 
    }
}

// -------------------------------------------------------------

function makeStatusCondition($fieldname, $statusrange): string 
{
    if ($statusrange >= 12 && $statusrange <= 15) {
        return '(' . $fieldname . ' between 1 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange >= 23 && $statusrange <= 25) {
        return '(' . $fieldname . ' between 2 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange >= 34 && $statusrange <= 35) {
        return '(' . $fieldname . ' between 3 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange == 45) {
        return '(' . $fieldname . ' between 4 and 5)';
    } elseif ($statusrange == 599) {
        return $fieldname . ' in (5,99)';
    } else {
        return $fieldname . ' = ' . $statusrange;
    }
}

// -------------------------------------------------------------

function checkStatusRange($currstatus, $statusrange): bool 
{
    if ($statusrange >= 12 && $statusrange <= 15) {
        return ($currstatus >= 1 && $currstatus <= ($statusrange % 10));
    } 
    if ($statusrange >= 23 && $statusrange <= 25) {
        return ($currstatus >= 2 && $currstatus <= ($statusrange % 10));
    } 
    if ($statusrange >= 34 && $statusrange <= 35) {
        return ($currstatus >= 3 && $currstatus <= ($statusrange % 10));
    } 
    if ($statusrange == 45) {
        return ($currstatus == 4 || $currstatus == 5);
    } if ($statusrange == 599) {
        return ($currstatus == 5 || $currstatus == 99);
    }
    return ($currstatus == $statusrange);
}

/**
 * Adds HTML attributes to create a filter over words learning status.
 *
 * @param  int<0, 5>|98|99|599 $status Word learning status 
 *                                     599 is a special status 
 *                                     combining 5 and 99 statuses.
 *                                     0 return an empty string 
 * @return string CSS class filter to exclude $status
 */
function makeStatusClassFilter($status) 
{
    if ($status == 0) { 
        return ''; 
    }
    $liste = array(1,2,3,4,5,98,99);
    if ($status == 599) {
        makeStatusClassFilterHelper(5, $liste);
        makeStatusClassFilterHelper(99, $liste);
    } elseif ($status < 6 || $status > 97) { 
        makeStatusClassFilterHelper($status, $liste);
    } else {
        $from = (int) ($status / 10);
        $to = $status - ($from*10);
        for ($i = $from; $i <= $to; $i++) {
            makeStatusClassFilterHelper($i, $liste); 
        }
    }
    // Set all statuses that are not -1
    $r = '';
    foreach ($liste as $v) {
        if ($v != -1) { 
            $r .= ':not(.status' . $v . ')'; 
        }
    }
    return $r;
}

/**
 * Replace $status in $array by -1
 *
 * @param int   $status A value in $array
 * @param int[] $array  Any array of values
 */
function makeStatusClassFilterHelper($status, &$array): void 
{
    $pos = array_search($status, $array);
    if ($pos !== false) {
        $array[$pos] = -1; 
    }
}

/**
 * Create and verify a dictionary URL link
 *
 * Case 1: url without any ### or lwt_term: append UTF-8-term
 * Case 2: url with one ### or lwt_term: substitute UTF-8-term
 * Case 3: url with two (###|lwt_term)enc###: unsupported encoding changed, 
 *         abandonned since 2.6.0-fork
 * 
 * @param string $u Dictionary URL. It may contain 'lwt_term' that will get parsed
 * @param string $t Text that substite the 'lwt_term'
 * 
 * @return string Dictionary link formatted
 * 
 * @since 2.7.0-fork It is recommended to use "lwt_term" instead of "###"
 */

function createTheDictLink($u, $t) 
{
    $url = trim($u);
    $trm = trim($t);
    // No ###|lwt_term found
    if (preg_match("/lwt_term|###/", $url, $matches) == false) {
        $r = $url . urlencode($trm);
        return $r;
    }
    $pos = stripos($url, $matches[0]);
    // ###|lwt_term found
    $pos2 = stripos($url, '###', $pos + 1);
    if ($pos2 === false) {
        // 1 ###|lwt_term found
        return str_replace($matches[0], ($trm == '' ? '+' : urlencode($trm)), $url);
    }
    // 2 ### found
    // Get encoding
    $enc = trim(substr(
        $url, $pos + mb_strlen($matches[0]), $pos2 - $pos - mb_strlen($matches[0])
    ));
    $r = substr($url, 0, $pos);
    $r .= urlencode(mb_convert_encoding($trm, $enc, 'UTF-8'));
    if ($pos2+3 < strlen($url)) { 
        $r .= substr($url, $pos2 + 3); 
    }
    return $r;
}


/**
 * Returns dictionnary links formatted as HTML.
 * 
 * @param int    $lang      Language ID
 * @param string $word  
 * @param string $sentctljs 
 * @param bool   $openfirst True if we should open right frames with translation
 *                          first
 * 
 * @return string HTML-formatted interface
 * 
 * @global string $tbpref Database table prefix
 */
function createDictLinksInEditWin($lang, $word, $sentctljs, $openfirst): string 
{
    global $tbpref;
    $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI 
    FROM ' . $tbpref . 'languages 
    WHERE LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI']) ? 
    $record['LgGoogleTranslateURI'] : "";
    mysqli_free_result($res);
    $r ='';
    if ($openfirst) {
        $r .= '<script type="text/javascript">';
        $r .= "\n//<![CDATA[\n";
        $r .= makeOpenDictStrJS(createTheDictLink($wb1, $word));
        $r .= "//]]>\n</script>\n";
    }
    $r .= 'Lookup Term: ';
    $r .= makeOpenDictStr(createTheDictLink($wb1, $word), "Dict1"); 
    if ($wb2 != "") { 
        $r .= makeOpenDictStr(createTheDictLink($wb2, $word), "Dict2"); 
    } 
    if ($wb3 != "") { 
        $r .= makeOpenDictStr(createTheDictLink($wb3, $word), "Translator") . 
        ' | ' . 
        makeOpenDictStrDynSent($wb3, $sentctljs, "Translate sentence"); 
    } 
    return $r;
}

/**
 * Create a dictionnary open URL from an pseudo-URL
 * 
 * @param string $url An URL, starting with a "*" is deprecated.
 *                    * If it contains a "popup" query, open in new window 
 *                    * Otherwise open in iframe
 * @param string $txt Clickable text to display
 * 
 * @return string HTML-formatted string
 */
function makeOpenDictStr($url, $txt): string 
{
    $r = '';
    if ($url == '' || $txt == '') {
        return $r;
    }
    $popup = false;
    if (str_starts_with($url, '*')) {
        $url = substr($url, 1);
        $popup = true;
    }
    if (!$popup) {
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query !== false && $query !== null) {
            parse_str($query, $url_query);
            $popup |= array_key_exists('lwt_popup', $url_query);
        }
    }
    if ($popup) {
        $r = ' <span class="click" onclick="owin(' . 
        prepare_textdata_js($url) . ');">' . 
        tohtml($txt) . 
        '</span> ';
    } else {
        $r = ' <a href="' . $url . 
        '" target="ru" onclick="showRightFrames();">' . 
        tohtml($txt) . '</a> ';
    }
    return $r;
}

// -------------------------------------------------------------

function makeOpenDictStrJS($url): string 
{
    $r = '';
    if ($url != '') {
        $popup = false;
        if (str_starts_with($url, "*")) {
            $url = substr($url, 1);
            $popup = true;
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query !== false && $query !== null) {
            parse_str($query, $url_query);
            $popup |= array_key_exists('lwt_popup', $url_query);
        }
        if ($popup) {
            $r = "owin(" . prepare_textdata_js($url) . ");\n";
        } else {
            $r = "top.frames['ru'].location.href=" . prepare_textdata_js($url) . ";\n";
        } 
    }
    return $r;
}

/**
 * Create a dictionnary open URL from an pseudo-URL
 * 
 * @param string $url       A string containing at least a URL
 *                          * If it contains the query "lwt_popup", open in Popup
 *                          * Starts with a '*': open in pop-up window (deprecated)
 *                          * Otherwise open in iframe
 * @param string $sentctljs Clickable text to display
 * @param string $txt       Clickable text to display
 * 
 * @return string HTML-formatted string
 * 
 * @since 2.7.0-fork Supports LibreTranslate, using other string that proper URL is 
 *                   deprecated. 
 */
function makeOpenDictStrDynSent($url, $sentctljs, $txt): string 
{
    $r = '';
    if ($url == '') {
        return $r;
    }
    $popup = false;
    if (str_starts_with($url, "*")) {
        $url = substr($url, 1);
        $popup = true;
    }
    $parsed_url = parse_url($url);
    $prefix = '';
    if ($parsed_url === false) {
        $prefix = 'http://';
        $parsed_url = parse_url($prefix . $url);
    }
    parse_str($parsed_url['query'], $url_query);
    $popup |= array_key_exists('lwt_popup', $url_query);
    if (
        str_starts_with($url, "ggl.php") || 
        str_ends_with($parsed_url['path'], "/ggl.php")
        ) {
        $url = str_replace('?', '?sent=1&', $url);
    }
    return '<span class="click" onclick="translateSentence'.($popup ? '2' : '').'(' . 
    prepare_textdata_js($url) . ',' . $sentctljs . ');">' . 
    tohtml($txt) . '</span>';
}

/**
 * Returns dictionnary links formatted as HTML.
 * 
 * @param int    $lang      Language ID
 * @param string $sentctljs 
 * @param string $wordctljs  
 * 
 * @return string HTM+-formatted interface
 * 
 * @global string $tbpref Database table prefix
 */
function createDictLinksInEditWin2($lang, $sentctljs, $wordctljs): string 
{
    global $tbpref;
    $sql = "SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI 
    FROM {$tbpref}languages WHERE LgID = $lang";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    if (substr($wb1, 0, 1) == '*') { 
        $wb1 = substr($wb1, 1); 
    }
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    if (substr($wb2, 0, 1) == '*') {
        $wb2 = substr($wb2, 1); 
    }
    $wb3 = isset($record['LgGoogleTranslateURI']) ? 
    $record['LgGoogleTranslateURI'] : "";
    if (substr($wb3, 0, 1) == '*') {
        $wb3 = substr($wb3, 1); 
    }
    mysqli_free_result($res);
    $r = 'Lookup Term: 
    <span class="click" onclick="translateWord2(' . prepare_textdata_js($wb1) .
    ',' . $wordctljs . ');">Dict1</span> ';
    if ($wb2 != "") { 
        $r .= '<span class="click" onclick="translateWord2(' . 
        prepare_textdata_js($wb2) . ',' . $wordctljs . ');">Dict2</span> '; 
    }
    if ($wb3 != "") {
        $sent_mode = substr($wb3, 0, 7) == 'ggl.php';
        $sent_mode |= str_ends_with(parse_url($wb3, PHP_URL_PATH), '/ggl.php');
        $r .= '<span class="click" onclick="translateWord2(' . 
        prepare_textdata_js($wb3) . ',' . $wordctljs . ');">Translator</span>
         | <span class="click" onclick="translateSentence2(' . 
         prepare_textdata_js($sent_mode ? 
         str_replace('?', '?sent=1&', $wb3) : $wb3) . ',' . $sentctljs . 
         ');">Translate sentence</span>'; 
    }
    return $r;
}

// -------------------------------------------------------------

function makeDictLinks($lang, $wordctljs): string 
{
    global $tbpref;
    $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI 
    FROM ' . $tbpref . 'languages WHERE LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    if (substr($wb1, 0, 1) == '*') { 
        $wb1 = substr($wb1, 1); 
    }
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    if (substr($wb2, 0, 1) == '*') { 
        $wb2 = substr($wb2, 1); 
    }
    $wb3 = isset($record['LgGoogleTranslateURI']) ? 
    $record['LgGoogleTranslateURI'] : "";
    if (substr($wb3, 0, 1) == '*') { 
        $wb3 = substr($wb3, 1); 
    }
    mysqli_free_result($res);
    $r ='<span class="smaller">';
    $r .= '<span class="click" onclick="translateWord3(' . 
    prepare_textdata_js($wb1) . ',' . $wordctljs . ');">[1]</span> ';
    if ($wb2 != "") { 
        $r .= '<span class="click" onclick="translateWord3(' . 
        prepare_textdata_js($wb2) . ',' . $wordctljs . ');">[2]</span> '; 
    }
    if ($wb3 != "") { 
        $r .= '<span class="click" onclick="translateWord3(' . 
        prepare_textdata_js($wb3) . ',' . $wordctljs . ');">[G]</span>'; 
    } 
    $r .= '</span>';
    return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin3($lang, $sentctljs, $wordctljs): string 
{
    global $tbpref;
    $sql = "SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI 
    FROM {$tbpref}languages WHERE LgID = $lang";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $popup = false;
    if (substr($wb1, 0, 1) == '*') {
        $wb1 = substr($wb1, 0, 1);
        $popup = true;
    }
    $popup |= str_contains($wb1, "lwt_popup=");
    if ($popup) {
        $f1 = 'translateWord2(' . prepare_textdata_js($wb1); 
    } else { 
        $f1 = 'translateWord(' . prepare_textdata_js($wb1); 
    }
        
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $popup = false;
    if (substr($wb2, 0, 1) == '*') {
        $wb2 = substr($wb2, 0, 1);
        $popup = true;
    }
    $popup |= str_contains($wb2, "lwt_popup=");
    if ($popup) {
        $f2 = 'translateWord2(' . prepare_textdata_js($wb2); 
    } else { 
        $f2 = 'translateWord(' . prepare_textdata_js($wb2); 
    }

    $wb3 = isset($record['LgGoogleTranslateURI']) ? 
    $record['LgGoogleTranslateURI'] : "";
    $popup = false;
    if (substr($wb3, 0, 1) == '*') {
        $wb3 = substr($wb3, 0, 1);
        $popup = true;
    }
    $parsed_url = parse_url($wb3);
    $prefix = '';
    if ($wb3 != '' && $parsed_url === false) {
        $prefix = 'http://';
        $parsed_url = parse_url($prefix . $wb3);
    }
    if (array_key_exists('query', $parsed_url)) {
        parse_str($parsed_url['query'], $url_query);
        $popup |= array_key_exists('lwt_popup', $url_query);
    }
    if ($popup) {
        $f3 = 'translateWord2(' . prepare_textdata_js($wb3);
        $f4 = 'translateSentence2(' . prepare_textdata_js($wb3);
    } else {
        $f3 = 'translateWord(' . prepare_textdata_js($wb3);
        $f4 = 'translateSentence(' . prepare_textdata_js(
            (str_ends_with($parsed_url['path'], "/ggl.php")) ? 
            str_replace('?', '?sent=1&', $wb3) : $wb3
        );
    }

    mysqli_free_result($res);
    $r ='';
    $r .= 'Lookup Term: ';
    $r .= '<span class="click" onclick="' . $f1 . ',' . $wordctljs . ');">
    Dict1</span> ';
    if ($wb2 != "") { 
        $r .= '<span class="click" onclick="' . $f2 . ',' . $wordctljs . ');">
        Dict2</span> '; 
    }
    if ($wb3 != "") { 
        $r .= '<span class="click" onclick="' . $f3 . ',' . $wordctljs . ');">
        Translator</span> | 
        <span class="click" onclick="' . $f4 . ',' . $sentctljs . ');">
        Translate sentence</span>'; 
    } 
    return $r;
}

/**
 * Return checked attribute if $val is in array $_REQUEST[$name]
 *
 * @param mixed  $val Value to look for, needle
 * @param string $name Key of request haystack.
 *
 * @return string ' ' of ' checked="checked" ' if the qttribute should be checked.
 *
 * @psalm-return ' '|' checked="checked" '
 */
function checkTest($val, $name): string 
{
    if (!isset($_REQUEST[$name])) { 
        return ' '; 
    }
    if (!is_array($_REQUEST[$name])) { 
        return ' '; 
    }
    if (in_array($val, $_REQUEST[$name])) { 
        return ' checked="checked" '; 
    }
    return ' ';
}

// -------------------------------------------------------------

function strToHex($string): string
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $h = dechex(ord($string[$i]));
        if (strlen($h) == 1 ) { 
            $hex .= "0" . $h; 
        }
        else {
            $hex .= $h; 
        }
    }
    return strtoupper($hex);
}

/**
 * Escapes everything to "¤xx" but not 0-9, a-z, A-Z, and unicode >= (hex 00A5, dec 165)
 *
 * @param string $string String to escape
 */
function strToClassName($string): string
{
    $length = mb_strlen($string, 'UTF-8');
    $r = '';
    for ($i=0; $i < $length; $i++)
    {
        $c = mb_substr($string, $i, 1, 'UTF-8');
        $o = ord($c);
        if (($o < 48)  
            || ($o > 57 && $o < 65)  
            || ($o > 90 && $o < 97)  
            || ($o > 122 && $o < 165)
        ) {
            $r .= '¤' . strToHex($c); 
        } else { 
            $r .= $c; 
        }
    }
    return $r;
}

// -------------------------------------------------------------

/**
 * @return never
 */
function anki_export($sql) 
{
    // WoID, LgRightToLeft, LgRegexpWordCharacters, LgName, WoText, WoTranslation, WoRomanization, WoSentence, taglist
    $res = do_mysqli_query($sql);
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if ('MECAB'== strtoupper(trim($record['LgRegexpWordCharacters']))) {
            $termchar = '一-龥ぁ-ヾ';
        } else {
            $termchar = $record['LgRegexpWordCharacters'];
        }
        $rtlScript = $record['LgRightToLeft'];
        $span1 = ($rtlScript ? '<span dir="rtl">' : '');
        $span2 = ($rtlScript ? '</span>' : '');
        $lpar = ($rtlScript ? ']' : '[');
        $rpar = ($rtlScript ? '[' : ']');
        $sent = tohtml(repl_tab_nl($record["WoSentence"]));
        $sent1 = str_replace(
            "{", '<span style="font-weight:600; color:#0000ff;">' . $lpar, str_replace(
                "}", $rpar . '</span>', 
                mask_term_in_sentence($sent, $termchar)
            )
        );
        $sent2 = str_replace("{", '<span style="font-weight:600; color:#0000ff;">', str_replace("}", '</span>', $sent));
        $x .= $span1 . tohtml(repl_tab_nl($record["WoText"])) . $span2 . "\t" . 
        tohtml(repl_tab_nl($record["WoTranslation"])) . "\t" . 
        tohtml(repl_tab_nl($record["WoRomanization"])) . "\t" . 
        $span1 . $sent1 . $span2 . "\t" . 
        $span1 . $sent2 . $span2 . "\t" . 
        tohtml(repl_tab_nl($record["LgName"])) . "\t" . 
        tohtml($record["WoID"]) . "\t" . 
        tohtml($record["taglist"]) .  
        "\r\n";
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header("Content-disposition: attachment; filename=lwt_anki_export_" . date('Y-m-d-H-i-s') . ".txt");
    echo $x;
    exit();
}

// -------------------------------------------------------------

/**
 * @return never
 */
function tsv_export($sql) 
{
    // WoID, LgName, WoText, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
    $res = do_mysqli_query($sql);
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        $x .= repl_tab_nl($record["WoText"]) . "\t" . 
        repl_tab_nl($record["WoTranslation"]) . "\t" . 
        repl_tab_nl($record["WoSentence"]) . "\t" . 
        repl_tab_nl($record["WoRomanization"]) . "\t" . 
        $record["WoStatus"] . "\t" . 
        repl_tab_nl($record["LgName"]) . "\t" . 
        $record["WoID"] . "\t" . 
        $record["taglist"] . "\r\n";
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header(
        "Content-disposition: attachment; filename=lwt_tsv_export_" . 
        date('Y-m-d-H-i-s') . ".txt"
    );
    echo $x;
    exit();
}

// -------------------------------------------------------------

/**
 * @return never
 */
function flexible_export($sql) 
{
    // WoID, LgName, LgExportTemplate, LgRightToLeft, WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
    $res = do_mysqli_query($sql);
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if (isset($record['LgExportTemplate'])) {
            $woid = $record['WoID'];
            $langname = repl_tab_nl($record['LgName']);
            $rtlScript = $record['LgRightToLeft'];
            $span1 = ($rtlScript ? '<span dir="rtl">' : '');
            $span2 = ($rtlScript ? '</span>' : '');
            $term = repl_tab_nl($record['WoText']);
            $term_lc = repl_tab_nl($record['WoTextLC']);
            $transl = repl_tab_nl($record['WoTranslation']);
            $rom = repl_tab_nl($record['WoRomanization']);
            $sent_raw = repl_tab_nl($record['WoSentence']);
            $sent = str_replace('{', '', str_replace('}', '', $sent_raw));
            $sent_c = mask_term_in_sentence_v2($sent_raw);
            $sent_d = str_replace('{', '[', str_replace('}', ']', $sent_raw));
            $sent_x = str_replace('{', '{{c1::', str_replace('}', '}}', $sent_raw));
            $sent_y = str_replace(
                '{', '{{c1::', str_replace('}', '::' . $transl . '}}', $sent_raw)
            );
            $status = $record['WoStatus'];
            $taglist = trim($record['taglist']);
            $xx = repl_tab_nl($record['LgExportTemplate']);    
            $xx = str_replace('%w', $term, $xx);        
            $xx = str_replace('%t', $transl, $xx);        
            $xx = str_replace('%s', $sent, $xx);        
            $xx = str_replace('%c', $sent_c, $xx);        
            $xx = str_replace('%d', $sent_d, $xx);        
            $xx = str_replace('%r', $rom, $xx);        
            $xx = str_replace('%a', $status, $xx);        
            $xx = str_replace('%k', $term_lc, $xx);        
            $xx = str_replace('%z', $taglist, $xx);        
            $xx = str_replace('%l', $langname, $xx);        
            $xx = str_replace('%n', $woid, $xx);        
            $xx = str_replace('%%', '%', $xx);        
            $xx = str_replace('$w', $span1 . tohtml($term) . $span2, $xx);        
            $xx = str_replace('$t', tohtml($transl), $xx);        
            $xx = str_replace('$s', $span1 . tohtml($sent) . $span2, $xx);        
            $xx = str_replace('$c', $span1 . tohtml($sent_c) . $span2, $xx);        
            $xx = str_replace('$d', $span1 . tohtml($sent_d) . $span2, $xx);        
            $xx = str_replace('$x', $span1 . tohtml($sent_x) . $span2, $xx);        
            $xx = str_replace('$y', $span1 . tohtml($sent_y) . $span2, $xx);        
            $xx = str_replace('$r', tohtml($rom), $xx);        
            $xx = str_replace('$k', $span1 . tohtml($term_lc) . $span2, $xx);        
            $xx = str_replace('$z', tohtml($taglist), $xx);        
            $xx = str_replace('$l', tohtml($langname), $xx);        
            $xx = str_replace('$$', '$', $xx);        
            $xx = str_replace('\\t', "\t", $xx);        
            $xx = str_replace('\\n', "\n", $xx);        
            $xx = str_replace('\\r', "\r", $xx);        
            $xx = str_replace('\\\\', '\\', $xx);        
            $x .= $xx;
        }
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header(
        "Content-disposition: attachment; filename=lwt_flexible_export_" . 
        date('Y-m-d-H-i-s') . ".txt"
    );
    echo $x;
    exit();
}

// -------------------------------------------------------------

function mask_term_in_sentence_v2($s): string 
{
    $l = mb_strlen($s, 'utf-8');
    $r = '';
    $on = 0;
    for ($i=0; $i < $l; $i++) {
        $c = mb_substr($s, $i, 1, 'UTF-8');
        if ($c == '}') { 
            $on = 0;
            continue;
        }
        if ($c == '{') {
            $on = 1;
            $r .= '[...]';
            continue;
        }
        if ($on == 0) {
            $r .= $c;
        }
    }
    return $r;
}

/**
 * Replace all white space characters by a simple space ' '.
 * The output string is also trimmed.
 * 
 * @param  string $s String to parse
 * @return string String with only simple whitespaces.
 */
function repl_tab_nl($s) 
{
    $s = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $s);
    $s = preg_replace('/\s/u', ' ', $s);
    $s = preg_replace('/\s{2,}/u', ' ', $s);
    return trim($s);
}

// -------------------------------------------------------------

function mask_term_in_sentence($s,$regexword): string 
{
    $l = mb_strlen($s, 'utf-8');
    $r = '';
    $on = 0;
    for ($i=0; $i < $l; $i++) {
        $c = mb_substr($s, $i, 1, 'UTF-8');
        if ($c == '}') { $on = 0; 
        }
        if ($on) {
            if (preg_match('/[' . $regexword . ']/u', $c)) {
                $r .= '•';
            } else {
                $r .= $c;
            }    
        }
        else {
            $r .= $c;
        }
        if ($c == '{') { 
            $on = 1; 
        }
    }
    return $r;
}

/**
 * Compute and echo word statistics about a list of text ID.
 *
 * It is useful for unknown percent with this fork.
 *
 * The echo is an output array{0: int, 1: int, 2: int, 
 * 3: int, 4: int, 5: int} 
 * Total number of words, number of expression, statistics, total unique, 
 * number of unique expressions, unique statistics
 *
 * @param string $textID Text IDs separated by comma
 *
 * @global string $tbpref Table name prefix
 */
function textwordcount($textID): void 
{
    global $tbpref;
    $total = $total_unique = $expr = $expr_unique = $stat = $stat_unique = array();
    $res = do_mysqli_query(
        "SELECT Ti2TxID AS text, COUNT(DISTINCT LOWER(Ti2Text)) AS value, 
        COUNT(LOWER(Ti2Text)) AS total
		FROM {$tbpref}textitems2
		WHERE Ti2WordCount = 1 AND Ti2TxID IN($textID)
		GROUP BY Ti2TxID"
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $total[$record['text']] = $record['total'];
        $total_unique[$record['text']] = $record['value'];
    }
    mysqli_free_result($res);
    $res = do_mysqli_query(
        'SELECT Ti2TxID as text, count(distinct Ti2WoID) as value, 
        count(Ti2WoID) as total
		from ' . $tbpref . 'textitems2
		where Ti2WordCount > 1 and Ti2TxID in(' . $textID . ')
		group by Ti2TxID'
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $expr[$record['text']] = $record['total'];
        $expr_unique[$record['text']] = $record['value'];
    }
    mysqli_free_result($res);
    $res = do_mysqli_query(
        'SELECT Ti2TxID as text, count(distinct Ti2WoID) as value, 
        count(Ti2WoID) as total, WoStatus as status
		from ' . $tbpref . 'textitems2, ' . $tbpref . 'words
		where Ti2WoID!=0 and Ti2TxID in(' . $textID . ') and Ti2WoID=WoID
		group by Ti2TxID, WoStatus'
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $stat[$record['text']][$record['status']]=$record['total'];
        $stat_unique[$record['text']][$record['status']]=$record['value'];
    }
    mysqli_free_result($res);
    $r = array(
        'total'=>$total, 'expr'=>$expr, 'stat'=>$stat, 'totalu'=>$total_unique,
        'expru'=>$expr_unique, 'statu'=>$stat_unique
    );
    echo json_encode($r);
}

// -------------------------------------------------------------

function texttodocount($text): string 
{
    global $tbpref;
    return '<span title="To Do" class="status0">&nbsp;' . 
    (get_first_value(
        'SELECT count(DISTINCT LOWER(Ti2Text)) as value 
        FROM ' . $tbpref . 'textitems2 
        WHERE Ti2WordCount=1 and Ti2WoID=0 and Ti2TxID=' . $text
    )
    ) . '&nbsp;</span>';
}

/**
 * Return the number of words left to do in this text.
 *
 * @param string|int $textid Text ID
 *
 * @return string HTML result
 *
 * @global string $tbpref Database table prefix
 * 
 * @since 2.7.0-fork Adapted to use LibreTranslate dictionary as well.
 */
function texttodocount2($textid): string
{
    global $tbpref;
    if (is_string($textid)) {
        $textid = (int) $textid;
    }
    $c = get_first_value(
        "SELECT COUNT(DISTINCT LOWER(Ti2Text)) AS value 
        FROM {$tbpref}textitems2 
        WHERE Ti2WordCount=1 AND Ti2WoID=0 AND Ti2TxID=$textid"
    );
    if ($c <= 0) {
        return '<span title="No unknown word remaining" class="status0" ' . 
        'style="padding: 0 5px; margin: 0 5px;">' . $c . '</span>'; 
    }
    $show_buttons = getSettingWithDefault('set-words-to-do-buttons');
    
    $dict = (string) get_first_value(
        "SELECT LgGoogleTranslateURI AS value 
        FROM {$tbpref}languages, {$tbpref}texts 
        WHERE LgID = TxLgID and TxID = $textid"
    );
    $tl = $sl = "";
    if ($dict) {
        // (2.5.2-fork) For future version of LWT: do not use translator uri 
        // to find language code
        if (str_starts_with($dict, '*')) {
            $dict = substr($dict, 1);
        }
        if (str_starts_with($dict, 'ggl.php')) {
            // We just need to form a valid URL
            $dict = "http://" . $dict;
        }
        parse_str(parse_url($dict, PHP_URL_QUERY), $url_query);
        if (array_key_exists('lwt_translator', $url_query) && 
        $url_query['lwt_translator'] == "libretranslate") {
            $tl = $url_query['target'];
            $sl = $url_query['source'];
        } else {
            // Defaulting to Google Translate query style
            $tl = $url_query['tl'];
            $sl = $url_query['sl'];
        }
    }
    
    $res = '<span title="Number of unknown words" class="status0" ' . 
    'style="padding: 0 5px; margin: 0 5px;">' . $c . '</span>' .
    '<img src="icn/script-import.png" ' . 
    'onclick="showRightFrames(\'bulk_translate_words.php?tid=' . $textid . 
    '&offset=0&sl=' . $sl . '&tl=' . $tl . '\');" ' . 
    'style="cursor: pointer; vertical-align:middle" title="Lookup New Words" ' .
    'alt="Lookup New Words" />';
    if ($show_buttons != 2) {
        $res .= '<input type="button" onclick="iknowall(' . $textid . 
        ');" value="Set All to Known" />'; 
    }
    if ($show_buttons != 1) { 
        $res .= '<input type="button" onclick="ignoreall(' . $textid . 
        ');" value="Ignore All" />'; 
    }
    return $res;
}

/**
 * Format the sentence(s) $seid containing $wordlc highlighting $wordlc.
 * 
 * @param int    $seid   Sentence ID
 * @param string $wordlc Term text in lower case
 * @param int    $mode   * Up to 1: return only the current sentence
 *                       * Above 1: return previous sentence and current sentence 
 *                       * Above 2: return previous, current and next sentence
 *  
 * @return array{0: string, 1: string} [0]=html, word in bold, [1]=text, word in {}
 * 
 * @global string $tbpref Database table prefix.
 */
function getSentence($seid, $wordlc, $mode): array 
{
    global $tbpref;
    $res = do_mysqli_query(
        "SELECT 
        CONCAT(
            '​', group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR '​'), '​'
        ) AS SeText, Ti2TxID AS SeTxID, LgRegexpWordCharacters, 
        LgRemoveSpaces, LgSplitEachChar 
        FROM {$tbpref}textitems2, {$tbpref}languages 
        WHERE Ti2LgID = LgID AND Ti2WordCount < 2 AND Ti2SeID = $seid" 
    );
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = (int)$record["LgRemoveSpaces"] == 1;
    $splitEachChar = (int)$record['LgSplitEachChar'] != 0;
    $txtid = $record["SeTxID"];
    if (($removeSpaces && !$splitEachChar) 
        || 'MECAB'== strtoupper(trim($record["LgRegexpWordCharacters"]))
    ) {
        $text = $record["SeText"];
        $wordlc = '[​]*' . preg_replace('/(.)/u', "$1[​]*", $wordlc);
        $pattern = "/(?<=[​])($wordlc)(?=[​])/ui";
    } else {
        $text = str_replace(array('​​','​','\r'), array('\r','','​'), $record["SeText"]);
        if ($splitEachChar) {
            $pattern = "/($wordlc)/ui";
        } else {
            $pattern = '/(?<![' . $record["LgRegexpWordCharacters"] . '])(' . 
            remove_spaces($wordlc, $removeSpaces) . ')(?![' . 
            $record["LgRegexpWordCharacters"] . '])/ui';
        }
    }
    $se = str_replace('​', '', preg_replace($pattern, '<b>$0</b>', $text));
    $sejs = str_replace('​', '', preg_replace($pattern, '{$0}', $text));
    if ($mode > 1) {
        if ($removeSpaces && !$splitEachChar) {
            $prevseSent = get_first_value(
                "SELECT concat(
                    '​', 
                    group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                    '​'
                ) AS value 
                from {$tbpref}sentences, {$tbpref}textitems2 
                where Ti2SeID = SeID and SeID < $seid and SeTxID = $txtid 
                and trim(SeText) not in ('¶', '') 
                group by SeID 
                order by SeID desc"
            );
        } else {
            $prevseSent = get_first_value(
                "SELECT SeText as value from {$tbpref}sentences 
                where SeID < $seid and SeTxID = $txtid
                and trim(SeText) not in ('¶', '') 
                order by SeID desc"
            );
        }
        if (isset($prevseSent)) {
            $se = preg_replace($pattern, '<b>$0</b>', $prevseSent) . $se;
            $sejs = preg_replace($pattern, '{$0}', $prevseSent) . $sejs;
        }
    }
    if ($mode > 2) {
        if ($removeSpaces && !$splitEachChar) {
            $nextSent = get_first_value(
                "SELECT concat(
                    '​', 
                    group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                    '​'
                ) as value 
                from {$tbpref}sentences, {$tbpref}textitems2 
                where Ti2SeID = SeID and SeID > $seid
                and SeTxID = $txtid and trim(SeText) not in ('¶','') 
                group by SeID 
                order by SeID asc"
            );
        } else {
            $nextSent = get_first_value(
                "SELECT SeText as value 
                FROM {$tbpref}sentences 
                where SeID > $seid AND SeTxID = $txtid 
                and trim(SeText) not in ('¶','') 
                order by SeID asc"
            );
        }
        if (isset($nextSent)) {
            $se .= preg_replace($pattern, '<b>$0</b>', $nextSent);
            $sejs .= preg_replace($pattern, '{$0}', $nextSent);
        }
    }
    mysqli_free_result($res);
    if ($removeSpaces) {
        $se = str_replace('​', '', $se);
        $sejs = str_replace('​', '', $sejs);
    }
     // [0]=html, word in bold, [1]=text, word in {} 
    return array($se, $sejs);
}

/**
 * Show 20 sentences containg $wordlc.
 *
 * @param int      $lang      Language ID
 * @param string   $wordlc    Term in lower case.
 * @param int|null $wid       Word ID
 * @param string   $jsctlname Path for the textarea of the sentence of the word being 
 *                            edited.
 * @param int      $mode      * Up to 1: return only the current sentence
 *                            * Above 1: return previous and current sentence 
 *                            * Above 2: return previous, current and next sentence
 *
 * @return string HTML-formatted string of which elements are candidate sentences to use.
 *
 * @global string $tbpref Database table prefix
 */
function get20Sentences($lang, $wordlc, $wid, $jsctlname, $mode): string 
{
    global $tbpref;
    $r = '<p><b>Sentences in active texts with <i>' . tohtml($wordlc) . '</i></b></p>
    <p>(Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> 
    to copy sentence into above term)</p>';
    $mecab_str = null;
    if (empty($wid)) {
        $sql = "SELECT DISTINCT SeID, SeText 
        FROM {$tbpref}sentences, {$tbpref}textitems2 
        WHERE LOWER(Ti2Text) = " . convert_string_to_sqlsyntax($wordlc) . " 
        AND Ti2WoID = 0 AND SeID = Ti2SeID AND SeLgID = $lang 
        ORDER BY CHAR_LENGTH(SeText), SeText 
        LIMIT 0,20";
    } else if ($wid==-1) {
        $res = do_mysqli_query(
            'SELECT LgRegexpWordCharacters, LgRemoveSpaces 
            FROM ' . $tbpref . 'languages 
            WHERE LgID = ' . $lang
        );
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        $removeSpaces = $record["LgRemoveSpaces"];
        if ('MECAB'== strtoupper(trim($record["LgRegexpWordCharacters"]))) {
            $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
            //$mecab_args = ' -F {%m%t\\t -U {%m%t\\t -E \\n ';
            // For instance, "このラーメン" becomes "この    6    68\nラーメン    7    38"
            $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n ';
            if (file_exists($mecab_file)) { 
                unlink($mecab_file); 
            }
            $fp = fopen($mecab_file, 'w');
            fwrite($fp, $wordlc . "\n");
            fclose($fp);
            $mecab = get_mecab_path($mecab_args);
            $handle = popen($mecab . $mecab_file, "r");
            if (!feof($handle)) {
                $row = fgets($handle, 256);
                // Format string removing numbers. 
                // MeCab tip: 2 = hiragana, 6 = kanji, 7 = katakana
                $mecab_str = "\t" . preg_replace_callback(
                    '([267]?)\t[0-9]+$', 
                    function ($matches) {
                        return isset($matches[1]) ? "\t" : "";
                    }, 
                    $row
                ); 
            }
            pclose($handle);
            unlink($mecab_file);
            $sql 
            = 'SELECT SeID, SeText, 
            concat(
                "\\t",
                group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR "\\t"),
                "\\t"
            ) val
             FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2
             WHERE lower(SeText)
             LIKE ' . convert_string_to_sqlsyntax("%$wordlc%") . '
             AND SeID = Ti2SeID AND SeLgID = ' . $lang . ' AND Ti2WordCount<2
             GROUP BY SeID HAVING val 
             LIKE ' . convert_string_to_sqlsyntax_notrim_nonull("%$mecab_str%") . '
             ORDER BY CHAR_LENGTH(SeText), SeText 
             LIMIT 0,20';
        } else {
            if (!($removeSpaces==1)) {
                $pattern = convert_regexp_to_sqlsyntax(
                    '(^|[^' . $record["LgRegexpWordCharacters"] . '])'
                     . remove_spaces($wordlc, $removeSpaces)
                     . '([^' . $record["LgRegexpWordCharacters"] . ']|$)'
                );
            } else {
                $pattern = convert_string_to_sqlsyntax($wordlc);
            }
            $sql 
            = "SELECT DISTINCT SeID, SeText
             FROM {$tbpref}sentences
             WHERE SeText RLIKE $pattern AND SeLgID = $lang
             ORDER BY CHAR_LENGTH(SeText), SeText 
             LIMIT 0,20";
        }
    } else {
        $sql 
        = "SELECT DISTINCT SeID, SeText
         FROM {$tbpref}sentences, {$tbpref}textitems2
         WHERE Ti2WoID = $wid AND SeID = Ti2SeID AND SeLgID = $lang
         ORDER BY CHAR_LENGTH(SeText), SeText
         LIMIT 0,20";
    }
    $res = do_mysqli_query($sql);
    $r .= '<p>';
    $last = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if ($last != $record['SeText']) {
            $sent = getSentence($record['SeID'], $wordlc, $mode);
            if (mb_strstr($sent[1], '}', false, 'UTF-8')) {
                $r .= '<span class="click" onclick="{' . $jsctlname . '.value=' . 
                    prepare_textdata_js($sent[1]) . '; makeDirty();}">
                <img src="icn/tick-button.png" title="Choose" alt="Choose" />
                </span> &nbsp;' . $sent[0] . '<br />';
            }
        }
        $last = $record['SeText'];
    }
    mysqli_free_result($res);
    $r .= '</p>';
    return $r;
}


/**
 * Return a dictionary of languages name - id
 * 
 * @return array<string, int>
 */
function get_languages(): array 
{
    global $tbpref;
    $langs = array();
    $sql = "SELECT LgID, LgName FROM " . $tbpref . "languages WHERE LgName<>''";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $langs[(string)$record['LgName']] = (int)$record['LgID'];
    }
    mysqli_free_result($res);
    return $langs;
}


/**
 * Get language name from its ID 
 * 
 * @param  string $lid Language ID
 * @return string Language name
 * @global string $tbpref Table name prefix
 */ 
function getLanguage($lid) 
{
    global $tbpref;
    if (!isset($lid) || trim($lid) == '' || !is_numeric($lid)) { 
        return ''; 
    }
    $r = get_first_value(
        "SELECT LgName AS value 
        FROM " . $tbpref . "languages 
        WHERE LgID='" . $lid . "'"
    );
    if (isset($r)) { 
        return (string)$r; 
    }
    return '';
}

// -------------------------------------------------------------

function getScriptDirectionTag($lid): string 
{
    global $tbpref;
    if (!isset($lid) ) { 
        return ''; 
    }
    if (trim($lid) == '' ) { 
        return ''; 
    }
    if (!is_numeric($lid) ) {
        return ''; 
    }
    $r = get_first_value(
        "select LgRightToLeft as value 
        from " . $tbpref . "languages 
        where LgID='" . $lid . "'"
    );
    if (isset($r) ) {
        if ($r) { 
            return ' dir="rtl" '; 
        } 
    }
    return '';
}

/**
 * Insert an expression to the database using MeCab.
 *
 * @param string $text Text to insert
 * @param string $lid  Language ID
 * @param string $wid  Word ID
 * @param int    $len  Number of words in the expression
 *
 * @return string[][] Append text and values to insert to the database
 *
 * @since 2.5.0-fork Function added.
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return array{0: array<int, string>, 1: list<string>}
 */
function insert_expression_from_mecab($text, $lid, $wid, $len): array
{
    global $tbpref;

    $db_to_mecab = tempnam(sys_get_temp_dir(), "{$tbpref}db_to_mecab");
    $mecab_args = " -F %m\\t%t\\t\\n -U %m\\t%t\\t\\n -E \\t\\n ";

    $mecab = get_mecab_path($mecab_args);
    $sql = "SELECT SeID, SeTxID, SeFirstPos, SeText FROM {$tbpref}sentences 
    WHERE SeLgID = $lid AND 
    SeText LIKE " . convert_string_to_sqlsyntax_notrim_nonull("%$text%");
    $res = do_mysqli_query($sql);

    $parsed_text = '';
    $fp = fopen($db_to_mecab, 'w');
    fwrite($fp, $text);
    fclose($fp);
    $handle = popen($mecab . $db_to_mecab, "r");
    while (!feof($handle)) {
        $row = fgets($handle, 16132);
        $arr = explode("\t", $row, 4);
        // Not a word (punctuation)
        if (!empty($arr[0]) && $arr[0] != "EOP" 
            && strpos("2 6 7", $arr[1]) !== false
        ) {
            $parsed_text .= $arr[0] . ' ';
        }
    }


    $appendtext = array();
    $sqlarray = array();
    // For each sentence in database containing $text
    while ($record = mysqli_fetch_assoc($res)) {
        $sent = trim($record['SeText']);
        $fp = fopen($db_to_mecab, 'w');
        fwrite($fp, $sent . "\n");
        fclose($fp);

        $handle = popen($mecab . $db_to_mecab, "r");
        $parsed_sentence = '';
        // For each word in sentence
        while (!feof($handle)) {
            $row = fgets($handle, 16132);
            $arr = explode("\t", $row, 4);
            // Not a word (punctuation)
            if (!empty($arr[0]) && $arr[0] != "EOP" 
                && strpos("2 6 7", $arr[1]) !== false
            ) {
                $parsed_sentence .= $arr[0] . ' ';
            }
        }

        // Finally we check if parsed text is in parsed sentence
        $seek = mb_strpos($parsed_sentence, $parsed_text);
        // For each occurence of multi-word in sentence 
        while ($seek !== false) {
            // pos = Number of words * 2 + initial position
            $pos = preg_match_all('/ /', mb_substr($parsed_sentence, 0, $seek)) * 2 + 
            (int) $record['SeFirstPos'];
            // Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text
            $sqlarray[] = "($wid, $lid, {$record['SeTxID']}, {$record['SeID']}, 
            $pos, $len, " . convert_string_to_sqlsyntax_notrim_nonull($text) . ")";
            if (getSettingZeroOrOne('showallwords', 1)) {
                $appendtext[$pos] = "&nbsp;$len&nbsp";
            } else { 
                $appendtext[$pos] = $text;
            }
            $seek = mb_strpos($parsed_sentence, $parsed_text, $seek + 1);
        }
        pclose($handle);
    }
    mysqli_free_result($res);
    unlink($db_to_mecab);

    return array($appendtext, $sqlarray);
}

/**
 * Insert an expression to the database using MeCab.
 *
 * @param string $textlc Text to insert in lower case
 * @param string $lid    Language ID
 * @param string $wid    Word ID
 * @param int    $len    Number of words in the expression
 * @param int    $mode   If equal to 0, add data in the output
 *
 * @return array{string[], string[]} Append text and SQL array.
 * 
 * @since 2.5.0-fork Function deprecated. 
 *                   $mode is unnused, data are always returned.
 *                   The second return argument is always empty array.
 *
 * @deprecated Use insert_expression_from_mecab instead.
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return array{0: array<int, string>, 1: list<string>}
 */
function insertExpressionFromMeCab($textlc, $lid, $wid, $len, $mode): array
{
    return insert_expression_from_mecab($textlc, $lid, $wid, $len);
}

/**
 * Insert an expression without using a tool like MeCab.
 *
 * @param string $textlc Text to insert in lower case
 * @param string $lid    Language ID
 * @param string $wid    Word ID
 * @param int    $len    Number of words in the expression
 * @param mixed  $mode   Unnused
 *
 * @return (null|string)[][] Append text, empty and sentence id
 *
 * @since 2.5.0-fork Mode is unnused and data are always added to the output.
 * @since 2.5.2-fork Fixed multi-words insertion for languages using no space
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return array{0: array<int, null|string>, 1: array<empty, empty>, 2: list<string>}
 */
function insert_standard_expression($textlc, $lid, $wid, $len, $mode): array
{
    global $tbpref;
    $appendtext = array();
    $sqlarr = array();
    $res = do_mysqli_query("SELECT * FROM {$tbpref}languages WHERE LgID=$lid");
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = $record["LgRemoveSpaces"] == 1;
    $splitEachChar = $record['LgSplitEachChar'] != 0;
    $termchar = $record['LgRegexpWordCharacters'];
    mysqli_free_result($res);
    if ($removeSpaces && !$splitEachChar) {
        $sql = "SELECT 
        GROUP_CONCAT(Ti2Text ORDER BY Ti2Order SEPARATOR ' ') AS SeText, SeID, 
        SeTxID, SeFirstPos 
        FROM {$tbpref}textitems2
        JOIN {$tbpref}sentences 
        ON SeID=Ti2SeID AND SeLgID = Ti2LgID
        WHERE Ti2LgID = $lid 
        AND SeText LIKE " . convert_string_to_sqlsyntax_notrim_nonull("%$textlc%") . " 
        AND Ti2WordCount < 2 
        GROUP BY SeID";
    } else {
        $sql = "SELECT * FROM {$tbpref}sentences 
        WHERE SeLgID = $lid AND SeText LIKE " . 
        convert_string_to_sqlsyntax_notrim_nonull("%$textlc%");
    }
    $wis = $textlc;
    $res = do_mysqli_query($sql);
    $notermchar = "/[^$termchar]($textlc)[^$termchar]/ui";
    // For each sentence in the language containing the query
    $matches = null;
    while ($record = mysqli_fetch_assoc($res)){
        $string = ' ' . $record['SeText'] . ' ';
        if ($splitEachChar) {
            $string = preg_replace('/([^\s])/u', "$1 ", $string);
        } else if ($removeSpaces && empty($rSflag)) {
            $rSflag = preg_match(
                '/(?<=[ ])(' . preg_replace('/(.)/ui', "$1[ ]*", $textlc) . 
                ')(?=[ ])/ui', 
                $string, $ma
            );
            if (!empty($ma[1])) {
                $textlc = trim($ma[1]);
                $notermchar = "/[^$termchar]($textlc)[^$termchar]/ui";
            }
        }
        $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
        // For each occurence of query in sentence
        while ($last_pos !== false) {
            if ($splitEachChar || $removeSpaces  
                || preg_match($notermchar, " $string ", $matches, 0, $last_pos - 1)
            ) {
                // Number of terms before group
                $cnt = preg_match_all(
                    "/([$termchar]+)/u",
                    mb_substr($string, 0, $last_pos, 'UTF-8'),
                    $_
                );
                $pos = 2 * $cnt + (int) $record['SeFirstPos'];
                $txt = '';
                if ($matches[1] != $textlc) {
                    $txt = $splitEachChar ? $wis : $matches[1]; 
                }
                $sqlarr[] = "($wid, $lid, {$record['SeTxID']},
                {$record['SeID']}, $pos, $len, " . 
                convert_string_to_sqlsyntax_notrim_nonull($txt) . ')';
                if (getSettingZeroOrOne('showallwords', 1)) {
                    $appendtext[$pos] = "&nbsp;$len&nbsp";
                } else if ($splitEachChar || $removeSpaces) {
                    $appendtext[$pos] = $wis;
                } else {
                    $appendtext[$pos] = $matches[1];
                }
            }
            // Cut the sentence to before the right-most term starts
            $string = mb_substr($string, 0, $last_pos, 'UTF-8');
            $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
        }
    }
    mysqli_free_result($res);
    return array($appendtext, array(), $sqlarr);
}


/**
 * Prepare a JavaScript dialog to insert a new expression. Use elements in
 * global JavaScript scope.
 * 
 * @deprecated Use new_expression_interactable2 instead. The new function does not
 * use global JS variables.
 * 
 * @return void 
 */
function new_expression_interactable($hex, $appendtext, $sid, $len): void 
{
    $showAll = getSettingZeroOrOne('showallwords', 1) ? "m" : "";

    ?>
<script type="text/javascript">
    newExpressionInteractable(
        <?php echo json_encode($appendtext); ?>, 
        ' class="click mword <?php echo $showAll; ?>wsty TERM<?php echo $hex; ?> word' + 
    woid + ' status' + status + '" data_trans="' + trans + '" data_rom="' + 
    roman + '" data_code="<?php echo $len; ?>" data_status="' + 
    status + '" data_wid="' + woid + 
    '" title="' + title + '"' ,
        <?php echo json_encode($len); ?>, 
        <?php echo json_encode($hex); ?>,
        <?php echo json_encode(!$showAll); ?>
    );
 </script>
    <?php
    flush();
}


/**
 * Prepare a JavaScript dialog to insert a new expression.
 * 
 * @param string   $hex        Lowercase text, formatted version of the text.
 * @param string[] $appendtext Text to append
 * @param int      $wid        Term ID
 * @param int      $len        Words count.
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix.
 */
function new_expression_interactable2($hex, $appendtext, $wid, $len): void 
{
    global $tbpref;
    $showAll = (bool)getSettingZeroOrOne('showallwords', 1) ? "m" : "";
    
    $sql = "SELECT * FROM {$tbpref}words WHERE WoID=$wid";
    $res = do_mysqli_query($sql);

    $record = mysqli_fetch_assoc($res);

    $attrs = array(
        "class" => "click mword {$showAll}wsty TERM$hex word$wid status" . 
        $record["WoStatus"],
        "data_trans" => $record["WoTranslation"],
        "data_rom" => $record["WoRomanization"],
        "data_code" => $len,
        "data_status" => $record["WoStatus"],
        "data_wid" => $wid
    ); 
    mysqli_free_result($res);

    ?>
<script type="text/javascript">
    let term = <?php echo json_encode($attrs); ?>;

    let title = '';
    if (window.parent.JQ_TOOLTIP) 
        title = make_tooltip(
            <?php echo json_encode($appendtext); ?>, term.data_trans, term.data_rom, 
            parseInt(term.data_status, 10)
        );
    term['title'] = title;
    let attrs = ""; 
    Object.entries(term).forEach(([k, v]) => attrs += " " + k + '="' + v + '"');
    // keys(term).map((k) => k + '="' + term[k] + '"').join(" ");
    
    newExpressionInteractable(
        <?php echo json_encode($appendtext); ?>, 
        attrs,
        <?php echo json_encode($len); ?>, 
        <?php echo json_encode($hex); ?>,
        <?php echo json_encode(!$showAll); ?>
    );
 </script>
    <?php
    flush();
}

/**
 * Alter the database to add a new word
 *
 * @param string $textlc Text in lower case
 * @param string $lid    Language ID
 * @param int    $len    Number of words in the expression
 * @param int    $mode   Function mode
 *                       - 0: Default mode, do nothing special
 *                       - 1: Runs an expresion inserter interactable 
 *                       - 2: Return the sql output
 *
 * @return null|string If $mode == 2 return values to insert in textitems2, nothing otherwise.
 *
 * @global string $tbpref Table name prefix
 */
function insertExpressions($textlc, $lid, $wid, $len, $mode): string|null 
{
    global $tbpref;
    $sql = "SELECT * FROM {$tbpref}languages WHERE LgID=$lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $mecab = 'MECAB' == strtoupper(trim($record['LgRegexpWordCharacters']));
    $splitEachChar = !$mecab && $record['LgSplitEachChar'];
    mysqli_free_result($res);
    if ($splitEachChar) {
        $textlc = preg_replace('/([^\s])/u', "$1 ", $textlc);
    }

    /*
    * TODO:
    * $appendtext: text to append to the current text
    * $sqlarr: Expression to append to the database (text independent)
    * Should separate the two
    */ 
    if ($mecab) {
        list($appendtext, $sqlarr) = insert_expression_from_mecab(
            $textlc, $lid, $wid, $len
        );
    } else {
        list($appendtext, $_, $sqlarr) = insert_standard_expression(
            $textlc, $lid, $wid, $len, null
        );
    }
    $sqltext = null;
    if (!empty($sqlarr)) {
        $sqltext = '';
        if ($mode != 2) {
            $sqltext .= 
            "INSERT INTO {$tbpref}textitems2
             (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text)
             VALUES ";
        }
        $sqltext .= implode(',', $sqlarr);
        unset($sqlarr);
    }

    if ($mode == 0) {
        $hex = strToClassName(prepare_textdata($textlc)); 
        //new_expression_interactable($hex, $appendtext, $sid, $len);
        new_expression_interactable2($hex, $appendtext, $wid, $len);
    }
    if ($mode == 2) { 
        return $sqltext; 
    }
    if (isset($sqltext)) {
        do_mysqli_query($sqltext);
    }
    return null;
}


/**
 * Restore the database from a file.
 *
 * @param resource $handle Backup file handle
 * @param string   $title  File title
 *
 * @return string Human-readable status message
 *
 * @global string $trbpref Database table prefix
 * @global int    $debug   Debug status
 * @global string $dbname  Database name
 *
 * @since 2.0.3-fork Function was broken
 * @since 2.5.3-fork Function repaired
 * @since 2.7.0-fork $handle should be for an *uncompressed* file.
 */
function restore_file($handle, $title): string 
{
    global $tbpref;
    global $debug;
    global $dbname;
    $message = "";
    $lines = 0;
    $ok = 0;
    $errors = 0;
    $drops = 0;
    $inserts = 0;
    $creates = 0;
    $start = 1;
    while (!feof($handle)) {
        $sql_line = trim(
            str_replace("\r", "", str_replace("\n", "", fgets($handle, 99999)))
        );
        if ($sql_line != "") {
            if ($start) {
                if (strpos($sql_line, "-- lwt-backup-") === false  
                    && strpos($sql_line, "-- lwt-exp_version-backup-") === false
                ) {
                    $message = "Error: Invalid $title Restore file " .
                    "(possibly not created by LWT backup)";
                    $errors = 1;
                    break;
                }
                $start = 0;
                continue;
            }
            if (substr($sql_line, 0, 3) !== '-- ' ) {
                $res = mysqli_query(
                    $GLOBALS['DBCONNECTION'], insert_prefix_in_sql($sql_line)
                );
                $lines++;
                if ($res == false) { 
                    $errors++; 
                } else {
                    $ok++;
                    if (substr($sql_line, 0, 11) == "INSERT INTO") { 
                        $inserts++; 
                    } else if (substr($sql_line, 0, 10) == "DROP TABLE") { 
                        $drops++;
                    } else if (substr($sql_line, 0, 12) == "CREATE TABLE") { 
                        $creates++;
                    }
                }
            }
        }
    } // while (! feof($handle))
    fclose($handle);
    if ($errors == 0) {
        runsql("DROP TABLE IF EXISTS {$tbpref}textitems", '');
        check_update_db($debug, $tbpref, $dbname);
        reparse_all_texts();
        optimizedb();
        get_tags(1);
        get_texttags(1);
        $message = "Success: $title restored - $lines queries - $ok successful (" . 
        "$drops/$creates tables dropped/created, $inserts records added), " . 
        "$errors failed.";
    } else if ($message == "") {
        $message = "Error: $title NOT restored - $lines queries - $ok successful (" .
        "$drops/$creates tables dropped/created, $inserts records added), ". 
        "$errors failed.";
    }
    return $message;
}



// -------------------------------------------------------------

function recreate_save_ann($textid, $oldann): string 
{
    global $tbpref;
    $newann = create_ann($textid);
    // Get the translations from $oldann:
    $oldtrans = array();
    $olditems = preg_split('/[\n]/u', $oldann);
    foreach ($olditems as $olditem) {
        $oldvals = preg_split('/[\t]/u', $olditem);
        if ($oldvals[0] > -1) {
            $trans = '';
            if (count($oldvals) > 3) { 
                $trans = $oldvals[3]; 
            }
            $oldtrans[$oldvals[0] . "\t" . $oldvals[1]] = $trans;
        }
    }
    // Reset the translations from $oldann in $newann and rebuild in $ann:
    $newitems = preg_split('/[\n]/u', $newann);
    $ann = '';
    foreach ($newitems as $newitem) {
        $newvals = preg_split('/[\t]/u', $newitem);
        if ($newvals[0] > -1) {
            $key = $newvals[0] . "\t";
            if (isset($newvals[1])) { 
                $key .= $newvals[1]; 
            }
            if (isset($oldtrans[$key])) {
                $newvals[3] = $oldtrans[$key];
            }
            $item = implode("\t", $newvals);
        } else {
            $item = $newitem;
        }
        $ann .= $item . "\n";
    }
    runsql(
        'update ' . $tbpref . 'texts set ' .
        'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' 
        where TxID = ' . $textid, 
        ""
    );
    return (string)get_first_value(
        "select TxAnnotatedText as value 
        from " . $tbpref . "texts 
        where TxID = " . $textid
    );
}

// -------------------------------------------------------------

function create_ann($textid): string 
{
    global $tbpref;
    $ann = '';
    $sql = 
    'SELECT 
    CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END AS Code, 
    CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END AS TiText, 
    Ti2Order, 
    CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END AS TiIsNotWord, 
    WoID, WoTranslation 
    FROM (
        ' . $tbpref . 'textitems2 
        LEFT JOIN ' . $tbpref . 'words ON (Ti2WoID = WoID) AND (Ti2LgID = WoLgID)
    ) 
    WHERE Ti2TxID = ' . $textid . ' 
    ORDER BY Ti2Order asc, Ti2WordCount desc';
    $savenonterm = '';
    $saveterm = '';
    $savetrans = '';
    $savewordid = '';
    $until = 0;
    $res = do_mysqli_query($sql);
    $order = null;
    while ($record = mysqli_fetch_assoc($res)) {
        $actcode = (int)$record['Code'];
        $order = (int)$record['Ti2Order'];
        if ($order <= $until ) {
            continue;
        }
        if ($order > $until ) {
            $ann = $ann . process_term(
                $savenonterm, $saveterm, $savetrans, $savewordid, $order
            );
            $savenonterm = '';
            $saveterm = '';
            $savetrans = '';
            $savewordid = '';
            $until = $order;
        }
        if ($record['TiIsNotWord'] != 0) {
            $savenonterm = $savenonterm . $record['TiText'];
        }
        else {
            $until = $order + 2 * ($actcode-1);
            $saveterm = $record['TiText'];
            $savetrans = '';
            if(isset($record['WoID'])) {
                $savetrans = $record['WoTranslation'];
                $savewordid = $record['WoID'];
            }
        }
    } // while
    mysqli_free_result($res);
    $ann .= process_term($savenonterm, $saveterm, $savetrans, $savewordid, $order);
    return $ann;
}


// -------------------------------------------------------------

function insert_prefix_in_sql($sql_line) 
{
    global $tbpref;
    //                                 123456789012345678901
    if (substr($sql_line, 0, 12) == "INSERT INTO ") {
        return substr($sql_line, 0, 12) . $tbpref . substr($sql_line, 12); 
    }
    if (substr($sql_line, 0, 21) == "DROP TABLE IF EXISTS ") {
        return substr($sql_line, 0, 21) . $tbpref . substr($sql_line, 21);
    } if (substr($sql_line, 0, 14) == "CREATE TABLE `") {
        return substr($sql_line, 0, 14) . $tbpref . substr($sql_line, 14);
    } if (substr($sql_line, 0, 13) == "CREATE TABLE ") {
        return substr($sql_line, 0, 13) . $tbpref . substr($sql_line, 13);
    } 
    return $sql_line; 
}

// -------------------------------------------------------------

function create_save_ann($textid): string 
{
    global $tbpref;
    $ann = create_ann($textid);
    runsql(
        'update ' . $tbpref . 'texts set ' .
        'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' 
        where TxID = ' . $textid, ""
    );
    return (string)get_first_value(
        "select TxAnnotatedText as value 
        from " . $tbpref . "texts 
        where TxID = " . $textid
    );
}

// -------------------------------------------------------------

function process_term($nonterm, $term, $trans, $wordid, $line): string 
{
    $r = '';
    if ($nonterm != '') { 
        $r = $r . "-1\t" . $nonterm . "\n"; 
    }
    if ($term != '') { 
        $r = $r . $line . "\t" . $term . "\t" . trim($wordid) . "\t" . 
        get_first_translation($trans) . "\n"; 
    }
    return $r;
}

// -------------------------------------------------------------

function get_first_translation($trans): string 
{
    $arr = preg_split('/[' . get_sepas()  . ']/u', $trans);
    if (count($arr) < 1) { return ''; 
    }
    $r = trim($arr[0]);
    if ($r == '*') { $r =""; 
    }
    return $r;
}

// -------------------------------------------------------------

function get_annotation_link($textid): string 
{
    global $tbpref;
    if (get_first_value('select length(TxAnnotatedText) as value from ' . $tbpref . 'texts where TxID=' . $textid) > 0) { 
        return ' &nbsp;<a href="print_impr_text.php?text=' . $textid . 
        '" target="_top"><img src="icn/tick.png" title="Annotated Text" alt="Annotated Text" /></a>'; 
    }
    else { 
        return ''; 
    }
}

/**
 * Like trim, but in place (modify variable)
 *
 * @param string $value Value to be trimmed
 */
function trim_value(&$value): void 
{ 
    $value = trim($value); 
}

/** 
 * Parses text be read by an automatic audio player.
 * 
 * Some non-phonetic alphabet will need this, currently only Japanese
 * is supported, using MeCab.
 *
 * @param  string $text Text to be converted
 * @param  string $lang Language code (usually BCP 47 or ISO 639-1)
 * @return string Parsed text in a phonetic format.
 */
function phonetic_reading($text, $lang) 
{
    global $tbpref;
    // Many languages are already phonetic
    if ($lang != 'ja' && $lang != 'jp-JP' && $lang != 'jp' ) {
        return $text;
    }

    // Japanese is an exception
    $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
    $mecab_args = ' -O yomi ';
    if (file_exists($mecab_file)) { 
        unlink($mecab_file); 
    }
    $fp = fopen($mecab_file, 'w');
    fwrite($fp, $text . "\n");
    fclose($fp);
    $mecab = get_mecab_path($mecab_args);
    $handle = popen($mecab . $mecab_file, "r");
    /**
     * @var string $mecab_str Output string 
     */
    $mecab_str = '';
    while (($line = fgets($handle, 4096)) !== false) {
        $mecab_str .= $line; 
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    pclose($handle);
    unlink($mecab_file);
    return $mecab_str;
}


/**
 * Refresh a text.
 * 
 * @deprecated No longer used, incompatible with new database system.
 * @since      1.6.25-fork Not compatible with the database
 */
function refreshText($word,$tid): string 
{
    global $tbpref;
    // $word : only sentences with $word
    // $tid : textid
    // only to be used when $showAll = 0 !
    $out = '';
    $wordlc = trim(mb_strtolower($word, 'UTF-8'));
    if ($wordlc == '') { 
        return ''; 
    }
    $sql = 
    'SELECT distinct TiSeID FROM ' . $tbpref . 'textitems 
    WHERE TiIsNotWord = 0 AND TiTextLC = ' . convert_string_to_sqlsyntax($wordlc) . ' 
    AND TiTxID = ' . $tid . ' 
    ORDER BY TiSeID';
    $res = do_mysqli_query($sql);
    $inlist = '(';
    while ($record = mysqli_fetch_assoc($res)) { 
        if ($inlist == '(') { 
            $inlist .= $record['TiSeID']; 
        } else {
            $inlist .= ',' . $record['TiSeID']; 
        }
    }
    mysqli_free_result($res);
    if ($inlist == '(') { 
        return ''; 
    } else {
        $inlist =  ' WHERE TiSeID in ' . $inlist . ') '; 
    }
    $sql = 
    'SELECT TiWordCount AS Code, TiOrder, TiIsNotWord, WoID 
    FROM (' . $tbpref . 'textitems 
        LEFT JOIN ' . $tbpref . 'words ON (TiTextLC = WoTextLC) AND (TiLgID = WoLgID)
    ) ' . $inlist . ' 
    ORDER BY TiOrder asc, TiWordCount desc';

    $res = do_mysqli_query($sql);        

    $hideuntil = -1;
    $hidetag = "removeClass('hide');";

    while ($record = mysqli_fetch_assoc($res)) {  // MAIN LOOP
        $actcode = (int)$record['Code'];
        $order = (int)$record['TiOrder'];
        $notword = (int)$record['TiIsNotWord'];
        $termex = isset($record['WoID']);
        $spanid = 'ID-' . $order . '-' . $actcode;

        if ($hideuntil > 0 ) {
            if ($order <= $hideuntil ) {
                $hidetag = "addClass('hide');"; 
            } else {
                $hideuntil = -1;
                $hidetag = "removeClass('hide');";
            }
        }

        if ($notword != 0) {  // NOT A TERM
            $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
        } else {   // A TERM
            if ($actcode > 1) {   // A MULTIWORD FOUND
                if ($termex) {  // MULTIWORD FOUND - DISPLAY 
                    if ($hideuntil == -1) { $hideuntil = $order + ($actcode - 1) * 2; 
                    }
                    $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
                } else {  // MULTIWORD PLACEHOLDER - NO DISPLAY 
                    $out .= "$('#" . $spanid . "',context).addClass('hide');\n";
                }  
            } // ($actcode > 1) -- A MULTIWORD FOUND
            else {  // ($actcode == 1)  -- A WORD FOUND
                $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
            }  
        }
    } //  MAIN LOOP
    mysqli_free_result($res);
    return $out;
}

/**
 * Create an HTML media player, audio or video.
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 */
function makeMediaPlayer($path, $offset=0) 
{
    if ($path == '') {
        return;
    }
    /**
    * @var string $extension 
    * File extension (if exists) 
    */
    $extension = substr($path, -4);
    if ($extension == '.mp3' || $extension == '.wav' || $extension == '.ogg') {
        makeAudioPlayer($path, $offset);
    } else {
        makeVideoPlayer($path, $offset);
    }
}


/**
 * Create an embed video player
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 */
function makeVideoPlayer($path, $offset=0): void 
{
    $online = false;
    $url = null;
    if (preg_match(
        "/(?:https:\/\/)?www\.youtube\.com\/watch\?v=([\d\w]+)/iu", 
        $path, $matches
    )
    ) {
        // Youtube video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
        $online = true;
    } else if (preg_match(
        "/(?:https:\/\/)?youtu\.be\/([\d\w]+)/iu", 
        $path, $matches
    )
    ) {
        // Youtube video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
        $online = true;
    } else if (preg_match(
        "/(?:https:\/\/)?dai\.ly\/([^\?]+)/iu", 
        $path, $matches
    )
    ) {
        // Dailymotion
        $domain = "https://www.dailymotion.com/embed/video/";
        $id = $matches[1];
        $url = $domain . $id;
        $online = true;
    } else if (preg_match(
        "/(?:https:\/\/)?vimeo\.com\/(\d+)/iu",
        // Vimeo 
        $path, $matches
    )
    ) {
        $domain = "https://player.vimeo.com/video/";
        $id = $matches[1];
        $url = $domain . $id . "#t=" . $offset . "s";
        $online = true;
    } 

    if ($online) {
        // Online video player in iframe
        ?> 
<iframe style="width: 100%; height: 30%;" 
src="<?php echo $url ?>" 
title="Video player"
frameborder="0" 
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
allowfullscreen type="text/html">
</iframe>
        <?php
    } else {
        // Local video player
        // makeAudioPlayer($path, $offset);
        $type = "video/" . pathinfo($path, PATHINFO_EXTENSION);
        $title = pathinfo($path, PATHINFO_FILENAME);
        ?>
<video preload="auto" controls title="<?php echo $title ?>" 
style="width: 100%; height: 300px; display: block; margin-left: auto; margin-right: auto;">
    <source src="<?php echo $path; ?>" type="<?php echo $type; ?>">
    <p>Your browser does not support video tags.</p>
</video>
        <?php
    }
}


/**
 * Create an HTML audio player.
 *
 * @param string $audio  Audio URL
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 */
function makeAudioPlayer($audio, $offset=0) 
{
    if ($audio == '') {
        return;
    }
    $audio = trim($audio);
    $repeatMode = getSettingZeroOrOne('currentplayerrepeatmode', 0);
    $currentplayerseconds = getSetting('currentplayerseconds');
    if ($currentplayerseconds == '') { 
        $currentplayerseconds = 5; 
    }
    $currentplaybackrate = getSetting('currentplaybackrate');
    if ($currentplaybackrate == '') { 
        $currentplaybackrate = 10; 
    }
    ?>
<link type="text/css" href="<?php print_file_path('css/jplayer.css');?>" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.jplayer.js"></script>
<table style="margin-top: 5px; margin-left: auto; margin-right: auto;" cellspacing="0" cellpadding="0">
    <tr>
        <td class="center borderleft" style="padding-left:10px;">
            <span id="do-single" class="click<?php echo ($repeatMode ? '' : ' hide'); ?>" 
                style="color:#09F;font-weight: bold;" title="Toggle Repeat (Now ON)">↻
            </span>
            <span id="do-repeat" class="click<?php echo ($repeatMode ? ' hide' : ''); ?>"
                style="color:grey;font-weight: bold;" title="Toggle Repeat (Now OFF)">↻</span>
            <div id="playbackrateContainer" 
                style="font-size: 80%;position:relative;-webkit-touch-callout: none;-webkit-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;">
            </div>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="bordermiddle">
            <div id="jquery_jplayer_1" class="jp-jplayer"></div>
            <div class="jp-audio-container">
                <div id="jp_container_1" class="jp-audio">
                    <div class="jp-type-single">
                        <div id="jp_interface_1" class="jp-interface">
                            <ul class="jp-controls">
                                <li><a href="#" class="jp-play">play</a></li>
                                <li><a href="#" class="jp-pause">pause</a></li>
                                <li><a href="#" class="jp-stop">stop</a></li>
                                <li><a href="#" class="jp-mute">mute</a></li>
                                <li><a href="#" class="jp-unmute">unmute</a></li>
                            </ul>
                            <div class="jp-progress-container">
                                <div class="jp-progress">
                                    <div class="jp-seek-bar">
                                        <div class="jp-play-bar">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="jp-volume-bar-container">
                                <div class="jp-volume-bar">
                                    <div class="jp-volume-bar-value">
                                    </div>
                                </div>
                            </div>
                            <div class="jp-current-time">
                            </div>
                            <div class="jp-duration">
                            </div>
                        </div>
                        <div id="jp_playlist_1" class="jp-playlist">
                        </div>
                    </div>
                </div>
            </div>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center bordermiddle">
            <select id="backtime" name="backtime" onchange="{do_ajax_save_setting('currentplayerseconds',document.getElementById('backtime').options[document.getElementById('backtime').selectedIndex].value);}">
                <?php echo get_seconds_selectoptions($currentplayerseconds); ?>
            </select>
            <br />
            <span id="backbutt" class="click">
                <img src="icn/arrow-circle-225-left.png" alt="Rewind n seconds" title="Rewind n seconds" />
            </span>&nbsp;&nbsp;
            <span id="forwbutt" class="click">
                <img src="icn/arrow-circle-315.png" alt="Forward n seconds" title="Forward n seconds" />
            </span>
            <span id="playTime" class="hide"></span>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center borderright" style="padding-right:10px;">
            <select id="playbackrate" name="playbackrate">
                <?php echo get_playbackrate_selectoptions($currentplaybackrate); ?>
            </select>
            <br />
            <span id="slower" class="click">
                <img src="icn/minus.png" alt="Slower" title="Slower" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="stdspeed" class="click">
                <img src="icn/status-away.png" alt="Normal" title="Normal" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="faster" class="click">
                <img src="icn/plus.png" alt="Faster" title="Faster" style="margin-top:3px" />
            </span>
        </td>
    </tr>
</table>
<!-- Audio controls once that page was loaded -->
<script type="text/javascript">
    //<![CDATA[

    const MEDIA = <?php echo prepare_textdata_js(encodeURI($audio)); ?>;
    const MEDIA_OFFSET = <?php echo $offset; ?>;

    /**
     * Get the extension of a file.
     * 
     * @param {string} file File path
     * 
     * @returns {string} File extension
     */
    function get_extension(file) {
        return file.split('.').pop();
    }

    /**
     * Import audio data when jPlayer is ready.
     * 
     * @returns {undefined}
     */
    function addjPlayerMedia () {
        const ext = get_extension(MEDIA);
        let media_obj = {};
        if (ext == 'mp3') {
            media_obj['mp3'] = MEDIA;
        } else if (ext == 'ogg') {
            media_obj['oga'] = media_obj['ogv'] = media_obj['mp3'] = MEDIA;
        } else if (ext == 'wav') {
            media_obj['wav'] = media_obj['mp3'] = MEDIA;
        } else if (ext == 'mp4') {
            media_obj['mp4'] = MEDIA;
        } else if (ext == 'webm') {
            media_obj['webma'] = media_obj['webmv'] = MEDIA;
        } else {
            media_obj['mp3'] = MEDIA;
        }
        $(this)
        .jPlayer("setMedia", media_obj)
        .jPlayer("pause", MEDIA_OFFSET);
        if ($('#jquery_jplayer_1').data().jPlayer.status.playbackRateEnabled) {
            $("#playbackrateContainer").css("margin-top",".2em")
            .html(
                `<span id="pbSlower" style="position:absolute;top: 0; left: 0; bottom: 0; right: 50%;" title="Slower" onclick="click_slower();">
                    &nbsp;
                </span>
                <span id="pbFaster" style="position:absolute;top: 0; left: 50%; bottom: 0; right: 0;" title="Faster" onclick="click_faster();">
                    &nbsp;
                </span>
                <span class="ui-widget ui-state-default ui-corner-all" style="padding-left: 0.2em;padding-right: 0.2em;color:grey">
                    <span id="playbackSlower" style="padding-right: 0.15em;">≪</span>
                    <span id="pbvalue">1.0</span>
                    <span id="playbackFaster" style="padding-left: 0.15em;">≫</span>
                </span>`
            )
            .css("cursor", "pointer");
        }
    }

    /**
     * Prepare media interactions with jPlayer.
     * 
     * @returns void 
     */
    function prepareMediaInteractions() {

        $("#jquery_jplayer_1").jPlayer({
            ready: addjPlayerMedia,
            swfPath: "js",
            noVolume: {
                ipad: /^no$/, iphone: /^no$/, ipod: /^no$/,
                android_pad: /^no$/, android_phone: /^no$/,
                blackberry: /^no$/, windows_ce: /^no$/, iemobile: /^no$/, webos: /^no$/,
                playbook: /^no$/
            }
        });

        $("#jquery_jplayer_1")
        .on('bind', $.jPlayer.event.timeupdate, function(event) { 
            $("#playTime").text(Math.floor(event.jPlayer.status.currentTime));
        });
        
        $("#jquery_jplayer_1")
        .on('bind', $.jPlayer.event.play, function(event) { 
            set_current_playbackrate();
        });
        
        $("#slower").on('click', click_slower);
        $("#faster").on('click', click_faster);
        $("#stdspeed").on('click', click_stdspeed);
        $("#backbutt").on('click', click_back);
        $("#forwbutt").on('click', click_forw);
        $("#do-single").on('click', click_single);
        $("#do-repeat").on('click', click_repeat);
        $("#playbackrate").on('change', set_new_playbackrate);
        $("#backtime").on('change', set_new_playerseconds);
        
        <?php echo ($repeatMode ? "click_repeat();\n" : ''); ?>
    }

    $(document).ready(prepareMediaInteractions);
    //]]>
</script>
    <?php
}



/** 
 * Echo a HEAD tag for using with frames
 * 
 * @param string $title Title to use
 * 
 * @return void
 */
function framesetheader($title): void 
{
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    ?><!DOCTYPE html>
    <?php echo '<html lang="en">'; ?>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/styles.css');?>" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <!-- 
        <?php echo file_get_contents("UNLICENSE.md");?> 
    -->
    <title>LWT :: <?php echo tohtml($title); ?></title>
</head>
    <?php
}

/**
 * Write a page header and start writing its body.
 *
 * @param string $title Title of the page
 * @param bool   $close Set to true if you are closing the header
 * 
 * @since 2.7.0 Show no text near the logo, page title enclosed in H1
 *
 * @global bool $debug Show a DEBUG span if true
 */
function pagestart($title, $close): void 
{
    global $debug;
    pagestart_nobody($title);
    echo '<div>';
    if ($close) {
        echo '<a href="index.php" target="_top">'; 
    }
    echo_lwt_logo();
    if ($close) {
        echo '</a>';
        quickMenu();
    }
    echo '</div>
    <h1>' . tohtml($title) . ($debug ? ' <span class="red">DEBUG</span>' : '') . '</h1>';
} 

/**
 * Start a standard page with a complete header and a non-closed body.
 *
 * @param string $title  Title of the page
 * @param string $addcss Some CSS to be embed in a style tag
 *
 * @global string $tbpref The database table prefix if true
 * @global int    $debug  Show the requests if true
 */
function pagestart_nobody($title, $addcss=''): void 
{
    global $tbpref, $debug;
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    ?><!DOCTYPE html>
    <?php 
    echo '<html lang="en">';
    ?>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <!-- 
        <?php echo file_get_contents("UNLICENSE.md");?> 
    -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <link rel="apple-touch-icon" href="<?php print_file_path('img/apple-touch-icon-57x57.png');?>" />
    <link rel="apple-touch-icon" sizes="72x72" href="<?php print_file_path('img/apple-touch-icon-72x72.png');?>" />
    <link rel="apple-touch-icon" sizes="114x114" href="<?php print_file_path('img/apple-touch-icon-114x114.png');?>" />
    <link rel="apple-touch-startup-image" href="img/apple-touch-startup.png" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/jquery-ui.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/jquery.tagit.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/styles.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/feed_wizard.css');?>" />
    <style type="text/css">
        <?php echo $addcss . "\n"; ?>
    </style>
    
    <script type="text/javascript" src="js/jquery.js" charset="utf-8"></script>
    <script type="text/javascript" src="js/jquery.scrollTo.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="js/jquery-ui.min.js"  charset="utf-8"></script>
    <script type="text/javascript" src="js/jquery.jeditable.mini.js" charset="utf-8"></script>
    <script type="text/javascript" src="js/tag-it.js" charset="utf-8"></script>
    <script type="text/javascript" src="js/overlib/overlib_mini.js" charset="utf-8"></script>
    <!-- URLBASE : "<?php echo tohtml(url_base()); ?>" -->
    <!-- TBPREF  : "<?php echo tohtml($tbpref);  ?>" -->
    <script type="text/javascript">
        //<![CDATA[
        var STATUSES = <?php echo json_encode(get_statuses()); ?>;
        var TAGS = <?php echo json_encode(get_tags()); ?>;
        var TEXTTAGS = <?php echo json_encode(get_texttags()); ?>;
        //]]>
    </script>
    <script type="text/javascript" src="js/pgm.js" charset="utf-8"></script>
    
    <title>LWT :: <?php echo tohtml($title); ?></title>
</head>
    <?php
    echo '<body>';
    ?>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
    <?php
    flush();
    if ($debug) { 
        showRequest(); 
    }
}

?>