<?php

/**
 * Save a Setting (k/v) and redirect to URI u
 * 
 * Call: save_setting_redirect.php?k=[key]&v=[value]&u=[RedirURI]
 * 
 * @since 2.5.4-fork You can omit either u, or (k, v).
 */

namespace SaveSetting;

require_once __DIR__ . '/session_utility.php';

/*
 * Return the parameters from the URL.
 * 
 * @return {string, string, string} Setting key, setting value and target URL
 */
function get_parameters() 
{
    $k = getreq('k');
    $v = getreq('v');
    $u = getreq('u');
    return array($k, $v, $u);
}

/*
 * Unset all session settings, and set current text to default.
 * 
 * @return undefined 
 */
function unset_settings()
{
    unset($_SESSION['currenttextpage']);
    unset($_SESSION['currenttextquery']);
    unset($_SESSION['currenttextquerymode']);
    unset($_SESSION['currenttexttag1']);
    unset($_SESSION['currenttexttag2']);
    unset($_SESSION['currenttexttag12']);
    
    unset($_SESSION['currentwordpage']);
    unset($_SESSION['currentwordquery']);
    unset($_SESSION['currentwordquerymode']);
    unset($_SESSION['currentwordstatus']);
    unset($_SESSION['currentwordtext']);
    unset($_SESSION['currentwordtag1']);
    unset($_SESSION['currentwordtag2']);
    unset($_SESSION['currentwordtag12']);
    unset($_SESSION['currentwordtextmode']);
    unset($_SESSION['currentwordtexttag']);
    
    unset($_SESSION['currentarchivepage']);
    unset($_SESSION['currentarchivequery']);
    unset($_SESSION['currentarchivequerymode']);
    unset($_SESSION['currentarchivetexttag1']);
    unset($_SESSION['currentarchivetexttag2']);
    unset($_SESSION['currentarchivetexttag12']);
    
    unset($_SESSION['currentrsspage']);
    unset($_SESSION['currentrssfeed']);
    unset($_SESSION['currentrssquery']);
    unset($_SESSION['currentrssquerymode']);
    
    unset($_SESSION['currentfeedspage']);
    unset($_SESSION['currentmanagefeedsquery']);
    
    
    saveSetting('currenttext', '');

}

/*
 * Save settings and go to a page.
 * 
 * @param string k Setting key
 * @param string v Setting value
 * @param string u URL to go to
 * 
 * @return undefined
 */
function save($k, $v)
{
    if ($k == 'currentlanguage') {
        unset_settings();
    }
    
    saveSetting($k, $v);

}

list($k, $v, $u) = get_parameters();
if ($k != '') {
    save($k, $v);
}
if ($u != '') {
    if (isset(parse_url($url)['host'])) {
        // Absolute URL, go to header
        header("Location: " . $u);
    } else {
        // Relative, change current path
        header("Location: ../" . $u);
    }
    exit();
}
?>