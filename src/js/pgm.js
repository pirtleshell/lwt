/**
 * \file
 * \brief LWT Javascript functions
 * 
 * @package Lwt
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   1.6.16-fork
 * 
 * "Learning with Texts" (LWT) is free and unencumbered software
 * released into the PUBLIC DOMAIN.
 * 
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a
 * compiled binary, for any purpose, commercial or non-commercial,
 * and by any means.
 * 
 * In jurisdictions that recognize copyright laws, the author or
 * authors of this software dedicate any and all copyright
 * interest in the software to the public domain. We make this
 * dedication for the benefit of the public at large and to the
 * detriment of our heirs and successors. We intend this
 * dedication to be an overt act of relinquishment in perpetuity
 * of all present and future rights to this software under
 * copyright law.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
 * AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * For more information, please refer to [http://unlicense.org/].
 */

/**************************************************************
Global variables for OVERLIB
***************************************************************/

/**
 * OVERLIB text font
 */
var ol_textfont = '"Lucida Grande",Arial,sans-serif,STHeiti,"Arial Unicode MS",MingLiu';
var ol_textsize = 3;
var ol_sticky = 1;
var ol_captionfont = '"Lucida Grande",Arial,sans-serif,STHeiti,"Arial Unicode MS",MingLiu';
var ol_captionsize = 3;
var ol_width = 260;
var ol_close = 'Close';
var ol_offsety = 30;
var ol_offsetx = 3;
var ol_fgcolor = '#FFFFE8';
var ol_closecolor = '#FFFFFF';

/**************************************************************
 * Helper functions for overlib
 ***************************************************************/

/**
 * Handle click event on ignored words
 * 
 * @param {string}    wblink1     First dictionary URL
 * @param {string}    wblink2     Second dictionary URL
 * @param {string}    wblink3     Google Translate dictionary URL
 * @param {string}    hints       Hint for the word
 * @param {int}       txid        Text ID
 * @param {*}         torder 
 * @param {string}    txt         Text
 * @param {int}       wid         Word ID 
 * @param {*}         multi_words 
 * @param {boolean}   rtl         Right-to-left text indicator
 * @param {*}         ann 
 * @returns {boolean}
 */
function run_overlib_status_98(
  wblink1, wblink2, wblink3, hints, txid, torder, txt, wid, multi_words, rtl, ann
) {
  const lang = getLangFromDict(WBLINK3);
  return overlib(
    make_overlib_audio(txt, lang) + 
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/>' +
    make_overlib_link_new_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION,
    'Word'
  );
}

/**
 * Handle click event on well-known words
 * 
 * @param {string}    wblink1     First dictionary URL
 * @param {string}    wblink2     Second dictionary URL
 * @param {string}    wblink3     Google Translate dictionary URL
 * @param {string}    hints       Hint for the word
 * @param {int}       txid        Text ID
 * @param {*}         torder 
 * @param {string}    txt         Text
 * @param {int}       wid         Word ID 
 * @param {*} multi_words 
 * @param {boolean}   rtl         Right-to-left text indicator
 * @param {*} ann 
 * @returns {boolean}
 */
function run_overlib_status_99 (
  wblink1, wblink2, wblink3, hints, txid, torder, txt, wid, multi_words, rtl, ann
  ) {
  const lang = getLangFromDict(WBLINK3);
  return overlib(
    make_overlib_audio(txt, lang) + 
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/> ' +
		make_overlib_link_new_word(txid, torder, wid) + ' | ' +
		make_overlib_link_delete_word(txid, wid) +
		make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    'Word'
  );
}

/**
 * Handle click event on learning words (levels 1 to 5)
 * 
 * @param {string}    wblink1     First dictionary URL
 * @param {string}    wblink2     Second dictionary URL
 * @param {string}    wblink3     Google Translate dictionary URL
 * @param {string}    hints       Hint for the word
 * @param {int}       txid        Text ID
 * @param {*}         torder 
 * @param {string}    txt         Text
 * @param {int}       wid         Word ID 
 * @param {int}       stat 
 * @param {*} multi_words 
 * @param {boolean}   rtl         Right-to-left text indicator
 * @param {*}         ann         Unused
 * @returns {boolean}
 */
function run_overlib_status_1_to_5 (
  wblink1, wblink2, wblink3, hints, txid, 
  torder, txt, wid, stat, multi_words, rtl, ann
  ) {
  const lang = getLangFromDict(WBLINK3);
  return overlib(
    '<div>' + make_overlib_audio(txt, lang) + '<span>(Read)</span></div>' +
    make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
		make_overlib_link_edit_word(txid, torder, wid) + ' | ' +
		make_overlib_link_delete_word(txid, wid) +
		make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    make_overlib_link_edit_word_title(
      'Word &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;', 
      txid, torder, wid
    )
  );
}

/**
 * Handle click event on unknown words.
 * 
 * @param {string}        wblink1     First dictionary URL
 * @param {string}        wblink2     Second dictionary URL
 * @param {string}        wblink3     Google Translate dictionary URL
 * @param {string}        hints       Hint for the word
 * @param {int}           txid        Text ID
 * @param {*}             torder 
 * @param {string}        txt         Text
 * @param {array<string>} multi_words 
 * @param {int}           rtl         1 if right-to-left language
 * @returns {boolean}
 */
function run_overlib_status_unknown (
  wblink1, wblink2, wblink3, hints, txid, torder, txt, multi_words, rtl
  ) {
  const lang = getLangFromDict(WBLINK3);
  return overlib(
    make_overlib_audio(txt, lang) + '<b>' + escape_html_chars(hints) + '</b><br /> ' +
		make_overlib_link_wellknown_word(txid, torder) + ' <br /> ' +
		make_overlib_link_ignore_word(txid, torder) +
		make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    'New Word'
  );
}

/**
 * Handle click event on a multi-word.
 * 
 * @param {string}        wblink1     First dictionary URL
 * @param {string}        wblink2     Second dictionary URL
 * @param {string}        wblink3     Google Translate dictionary URL
 * @param {string}        hints       Hint for the word
 * @param {int}           txid        Text ID
 * @param {*}             torder 
 * @param {string}        txt         Text
 * @param {int}           wid         Word ID 
 * @param {int}           rtl         1 if right-to-left language
 * @returns {boolean}
 */
function run_overlib_multiword (
  wblink1, wblink2, wblink3, hints, txid, torder, txt, wid, stat, wcnt, ann
  ) {
  const lang = getLangFromDict(WBLINK3);
  return overlib(
    make_overlib_audio(txt, lang) + '<b>' + escape_html_chars_2(hints, ann) + '</b><br /> ' +
		make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
		make_overlib_link_edit_multiword(txid, torder, wid) + ' | ' +
		make_overlib_link_delete_multiword(txid, wid) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    make_overlib_link_edit_multiword_title(
      wcnt.trim() + '-Word-Expression', txid, torder, wid
    )
  );
}

/**
 * Make an overlib dialog so that the user can say if he knows the word or not.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {int}    wid     Word ID
 * @param {string} txt     Word text
 * @param {string} trans   Word translation 
 * @param {string} roman   Word romanization 
 * @param {string} stat    Word learning status
 * @param {string} sent    Lookup sentence in Google Translate
 * @param {int}    todo    If 1, the user should say if he knows the word.
 * @param {*}      oldstat Old status, unused
 * @returns 
 */
function run_overlib_test(
  wblink1, wblink2, wblink3, wid, txt, trans, roman, stat, sent, todo, oldstat
  ) {
  const s = parseInt(stat, 10);
  let c = s + 1;
  if (c > 5) c = 5;
  let w = s - 1;
  if (w < 1) w = 1;
  let cc = stat + ' ▶ ' + c; 
  if (c == s) cc = c;
  let ww = stat + ' ▶ ' + w; 
  if (w == s) ww = w;
  let overlib_string = '';
  if (todo == 1) {
    overlib_string += '<center><hr noshade size=1 /><b>';
    if (stat >= 1 && stat <= 5) {
      overlib_string += 
		  make_overlib_link_change_status_test(
        wid, 
        1, 
        '<img src="icn/thumb-up.png" title="Got it!" alt="Got it!" /> Got it! [' + 
        cc + ']'
      ) +
		'<hr noshade size=1 />' +
		make_overlib_link_change_status_test(
      wid, 
      -1, 
      '<img src="icn/thumb.png" title="Oops!" alt="Oops!" /> Oops! [' + ww + ']'
      ) +
		'<hr noshade size=1 />';
    }
    overlib_string +=
		make_overlib_link_change_status_alltest(wid, stat) +
		'</b></center><hr noshade size=1 />';
  }
  overlib_string += '<b>' + escape_html_chars(make_tooltip(txt, trans, roman, stat)) +
  '</b><br />' +
  ' <a href="edit_tword.php?wid=' + wid + 
  '" target="ro" onclick="showRightFrames();">Edit term</a><br />' +
    createTheDictLink(wblink1, txt, 'Dict1', 'Lookup Term: ') +
    createTheDictLink(wblink2, txt, 'Dict2', '') +
    createTheDictLink(wblink3, txt, 'Trans', '') +
    createTheDictLink(wblink3, sent, 'Trans', '<br />Lookup Sentence:');

  return overlib(overlib_string, CAPTION, 'Got it?');
}

/**
 * Return all multiwords
 *
 * @param {int}             txid        Text ID
 * @param {any}             torder 
 * @param {array<string>}   multi_words A list of 8 string elements
 * @param {boolean}         rtl         Right-to-left indicator
 *
 * @return {string} All multiwords
 * 
 * @since 2.8.0-fork LTR texts were wrongly displayed
 */
function make_overlib_link_new_multiword (txid, torder, multi_words, rtl) {
  // Quit if all multiwords are '' or undefined
  if (multi_words.every((x) => !x)) return '';
  const output = Array();
  if (rtl) {
    for (let i = 7; i < 0; i--) { 
      if (multi_words[i]) {
        output.push(make_overlib_link_create_edit_multiword_rtl(
          i + 2, txid, torder, multi_words[i]
        ));
      } 
    }
  } else {
    for (let i = 0; i < 7; i++) {
      if (multi_words[i]) {
        output.push(make_overlib_link_create_edit_multiword(
          i + 2, txid, torder, multi_words[i]
        ));
      } 
    }
  }
  return ' <br />Expr: ' + output.join(' ') + ' ';
}

/**
 * Make link to translations through dictionaries or all sentences lookup.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {string} txt     Word string 
 * @param {int}    txid    Text ID 
 * @param {int}    torder 
 * @returns {string}
 */
function make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder) {
  let s =
	createTheDictLink(wblink1, txt, 'Dict1', 'Lookup Term: ') +
	createTheDictLink(wblink2, txt, 'Dict2', '') +
	createTheDictLink(wblink3, txt, 'Trans', '');
  if (torder > 0 && txid > 0) {
    s += '<br />Lookup Sentence: ' + 
    createSentLookupLink(torder, txid, wblink3, 'Trans');
  }
  return s;
}

/**
 * Create a list of links for dictionary translation.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {string} txt     Word string 
 * @param {int}    txid    Text ID 
 * @param {int}    torder  
 * @returns {string} HTML-formatted list of dictionaries link, and sentece link
 */
function make_overlib_link_wbnl (wblink1, wblink2, wblink3, txt, txid, torder) {
  let s =
	createTheDictLink(wblink1, txt, 'Dict1', 'Term: ') +
	createTheDictLink(wblink2, txt, 'Dict2', '') +
	createTheDictLink(wblink3, txt, 'Trans', '');
  if (torder > 0 && txid > 0) {
    s += ' | Sentence: ' + createSentLookupLink(torder, txid, wblink3, 'Trans');
  }
  return s;
}

/**
 * Create link to dictionaries.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {string} txt     Word string 
 * @param {string} sent    Complete sentence 
 * @returns {string} HTML-formatted list of links
 */
function make_overlib_link_wbnl2 (wblink1, wblink2, wblink3, txt, sent) {
  let s = 
  createTheDictLink(wblink1, txt, 'Dict1', 'Term: ') +
	createTheDictLink(wblink2, txt, 'Dict2', '') +
	createTheDictLink(wblink3, txt, 'Trans', '');
  if (sent != '') {
    s += createTheDictLink(wblink3, sent, 'Trans', ' | Sentence:');
  }
  return s;
}

/**
 * Change the status of a word multiple time.
 * 
 * @param {int} txid Text ID 
 * @param {*} torder 
 * @param {int} wid Word ID 
 * @param {int} oldstat Old word status
 * @returns {string} Multiple links for a new word status.
 */
function make_overlib_link_change_status_all (txid, torder, wid, oldstat) {
  let result = 'St: ';
  for (let newstat = 1; newstat <= 5; newstat++) { 
    result += make_overlib_link_change_status(txid, torder, wid, oldstat, newstat); 
  }
  result += make_overlib_link_change_status(txid, torder, wid, oldstat, 99);
  result += make_overlib_link_change_status(txid, torder, wid, oldstat, 98);
  return result;
}

/**
 * Return a list of links to change word status
 * 
 * @param {int} wid     Word ID 
 * @param {int} oldstat Current status of the word
 * @returns {string} An HTML-formatted list of links.
 */
function make_overlib_link_change_status_alltest (wid, oldstat) {
  let result = '';
  for (let newstat = 1; newstat <= 5; newstat++) { 
    result += make_overlib_link_change_status_test2(wid, oldstat, newstat); 
  }
  result += make_overlib_link_change_status_test2(wid, oldstat, 99);
  result += make_overlib_link_change_status_test2(wid, oldstat, 98);
  return result;
}

/**
 * Return a link to change the status of a word.
 * 
 * @param {int} txid    Text ID 
 * @param {*}   torder 
 * @param {int} wid     Word ID 
 * @param {int} oldstat Old word status 
 * @param {int} newstat New word status
 * @returns {string} HTML formatted link to change word status
 */
function make_overlib_link_change_status (txid, torder, wid, oldstat, newstat) {
  if (oldstat == newstat) {
    return '<span title="' +
			getStatusName(oldstat) + '">◆</span>';
  } 
  return ' <a href="set_word_status.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid +
    '&amp;status=' + newstat + '" target="ro" onclick="showRightFrames();">' + 
    '<span title="' + getStatusName(newstat) + '">[' +
    getStatusAbbr(newstat) + ']</span></a> ';
}

/**
 * Prepare an HTML-formated string containing the new statuses choices
 * 
 * @param {int}    wid     ID of the word
 * @param {int}    oldstat Old status
 * @param {int}    newstat New status
 * @returns {string} HTML-formatted link
 */
function make_overlib_link_change_status_test2 (wid, oldstat, newstat) {
  let output = ' <a href="set_test_status.php?wid=' + wid +
  '&amp;status=' + newstat + '&amp;ajax=1" target="ro" onclick="showRightFrames();">' + 
  '<span title="' + getStatusName(newstat) + '">[';
  output += (oldstat == newstat) ? '◆' : getStatusAbbr(newstat);
  output += ']</span></a> ';
  return output;
}

/**
 * Make a link for a word status change
 * 
 * @param {int}    wid       ID of the word
 * @param {int}    plusminus Amplitude of the change (normally 1 or -1) 
 * @param {string} text      Text to be embed
 *  
 * @returns {string} A tag containing formatted text
 */
function make_overlib_link_change_status_test (wid, plusminus, text) {
  return ' <a href="set_test_status.php?wid=' + wid +
		'&amp;stchange=' + plusminus + 
    '&amp;ajax=1" target="ro" onclick="showRightFrames();' + 
      (plusminus > 0 ? 'successSound()' : 'failureSound()') + ';">' + 
    text + '</a> ';
}

/**
 * Make a link to learn a new word.
 * 
 * 
 * @param {int} txid Text ID
 * @param {*} torder 
 * @param {int} wid Word ID
 * 
 * @returns {string}
 */
function make_overlib_link_new_word (txid, torder, wid) {
  return ' <a href="edit_word.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">Learn term</a> ';
}

/**
 * Create a link to edit a multiword.
 * 
 * @param {int} txid Text ID 
 * @param {*} torder 
 * @param {int} wid Word ID 
 * @returns {string}
 */
function make_overlib_link_edit_multiword (txid, torder, wid) {
  return ' <a href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">Edit term</a> ';
}

/**
 * Create an overlib title for a multiword edition.
 * 
 * @param {*} text 
 * @param {int} txid 
 * @param {*} torder 
 * @param {int} wid 
 * @returns {string}
 */
function make_overlib_link_edit_multiword_title (text, txid, torder, wid) {
  return '<a style="color:yellow" href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">' + 
    text + '</a>';
}

/**
 * Create or edit a multiword with overlib.
 * 
 * @param {int}    len    Number of words in the multi-word 
 * @param {int}    txid   Text ID
 * @param {*}      torder 
 * @param {string} txt    Multi-word text
 * @returns {string}
 */
function make_overlib_link_create_edit_multiword (len, txid, torder, txt) {
  return ' <a href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;txt=' + txt +
		'" target="ro" onclick="showRightFrames();">' + 
    len + '..' + escape_html_chars(txt.substring(2).trim()) + '</a> ';
}

/**
 * Create or edit a right-to-left multiword with overlib.
 * 
 * @param {int}    len    Number of words in the multi-word 
 * @param {int}    txid   Text ID
 * @param {*}      torder 
 * @param {string} txt    Multi-word text
 * @returns {string}
 */
function make_overlib_link_create_edit_multiword_rtl (len, txid, torder, txt) {
  return ' <a dir="rtl" href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;txt=' + txt +
		'" target="ro" onclick="showRightFrames();">' + 
    len + '..' + escape_html_chars(txt.substring(2).trim()) + '</a> ';
}

/**
 * Make a link to edit a word, displaying "Edit term"
 * 
 * @param {int} txid
 * @param {*} torder 
 * @param {int} wid 
 * @returns {string}
 */
function make_overlib_link_edit_word (txid, torder, wid) {
  const url = 'edit_word.php?tid=' + txid + 
  '&amp;ord=' + torder +
  '&amp;wid=' + wid;
  return ' <a href="' + url + 
  ' " target="ro" onclick="showRightFrames()">Edit term</a> ';
}

/**
 * Make a link to edit a word for an overlib title, displaying the word's text.
 * 
 * @param {string} text Word text
 * @param {int}    txid Text ID 
 * @param {*} torder 
 * @param {int}    wid Word ID
 * @returns {string} HTML-formatted link
 */
function make_overlib_link_edit_word_title (text, txid, torder, wid) {
  return '<a style="color:yellow" href="edit_word.php?tid=' +
		txid + '&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">' + 
    text + '</a>';
}

/**
 * Make a link to delete a word with overlib.
 * 
 * @param {int} txid Text ID
 * @param {int} wid  Word ID
 * @returns {string} HTML-formatted link.
 */
function make_overlib_link_delete_word (txid, wid) {
  return ' <a onclick="showRightFrames(); return confirmDelete();" ' + 
  'href="delete_word.php?wid=' + wid + '&amp;tid=' + txid + 
  '" target="ro">Delete term</a> ';
}

/**
 * Make a link to delete a multiword.
 * 
 * @param {int} txid Text ID 
 * @param {int} wid  Word ID
 * @returns {string} HTML-formatted string
 */
function make_overlib_link_delete_multiword (txid, wid) {
  return ' <a onclick="showRightFrames(); return confirmDelete();" ' + 
  'href="delete_mword.php?wid=' + wid + '&amp;tid=' + txid + 
  '" target="ro">Delete term</a> ';
}

/**
 * Return a link to a word well-known.
 * 
 * @param {int} txid 
 * @param {*} torder 
 * @returns {string} HTML link to mark the word well knwown
 */
function make_overlib_link_wellknown_word (txid, torder) {
  return ' <a href="insert_word_wellknown.php?tid=' +
		txid + '&amp;ord=' + torder + 
    '" target="ro" onclick="showRightFrames();">I know this term well</a> ';
}

/**
 * Return a link to ignore a word.
 * 
 * @param {int} txid 
 * @param {*} torder 
 * @returns {string} HTML string to ignore the word
 */
function make_overlib_link_ignore_word (txid, torder) {
  return ' <a href="insert_word_ignore.php?tid=' + txid +
		'&amp;ord=' + torder + 
    '" target="ro" onclick="showRightFrames();">Ignore this term</a> ';
}

/**
 * Create a clickable button to read a word aloud.
 * 
 * @param {string} txt  Word to say
 * @param {string} lang Language name (two letters or four letters separated with a 
 *                      caret)
 * @return {string} HTML-formatted clickable icon
 */
function make_overlib_audio(txt, lang) {
  let img = document.createElement("img");
  img.title = "Click to read!";
  img.src = "icn/speaker-volume.png";
  img.style.cursor = "pointer";
  img.setAttribute(
    "onclick", 
    "readTextAloud('" + escape_html_chars(txt) + "', '" + (lang || "") + "')"
  );
  return img.outerHTML;
}


/**************************************************************
 * Other JS utility functions
 **************************************************************/

/**
 * Return the name of a given status.
 * 
 * @param {int} status Status number (int<1, 5>|98|99)
 * @returns {string}
 */
function getStatusName (status) {
  return STATUSES[status] ? STATUSES[status].name : 'Unknown';
}

/**
 * Return the abbreviation of a status
 * 
 * @param {int} status Status number (int<1, 5>|98|99)
 * @returns {string} Abbreviation
 */
function getStatusAbbr (status) {
  return STATUSES[status] ? STATUSES[status].abbr : '?';
}


/**
 * Translate a sentence.
 * 
 * @param {string} url     Translation URL with "{term}" marking the interesting term 
 * @param {object} sentctl Textarea contaning sentence 
 * @returns {void}
 */
function translateSentence (url, sentctl) {
  if (sentctl !== undefined && url != '') {
    const text = sentctl.value;
    if (typeof text === 'string') {
      showRightFrames(undefined, createTheDictUrl(url, text.replace(/[{}]/g, '')));
    }
  }
}

/**
 * Translate a sentence.
 * 
 * @param {string} url     Translation URL with "{term}" marking the interesting term 
 * @param {object} sentctl Textarea contaning sentence 
 * @returns {void}
 */
function translateSentence2 (url, sentctl) {
  if (typeof sentctl !== 'undefined' && url != '') {
    const text = sentctl.value;
    if (typeof text === 'string') {
      const finalurl = createTheDictUrl(url, text.replace(/[{}]/g, ''));
      owin(finalurl);
    }
  }
}

/**
 * Open a new window with the translation of the word.
 * 
 * @param {string} url     Dictionary URL
 * @param {object} wordctl Textarea containing word to translate.
 * @returns {void}
 */
function translateWord(url, wordctl) {
  if (wordctl !== undefined && url != '') {
    const text = wordctl.value;
    if (typeof text === 'string') {
      showRightFrames(undefined, createTheDictUrl(url, text));
    }
  }
}

/**
 * Open a new window with the translation of the word.
 * 
 * @param {string} url     Dictionary URL
 * @param {object} wordctl Textarea containing word to translate.
 * @returns {void}
 */
function translateWord2(url, wordctl) {
  if (wordctl !== undefined && url != '') {
    const text = wordctl.value;
    if (typeof text === 'string') {
      owin(createTheDictUrl(url, text));
    }
  }
}

/**
 * Open a new window with the translation of the word.
 * 
 * @param {string} url Dictionary URL
 * @param {string} word Word to translate.
 * @returns {void}
 */
function translateWord3(url, word) {
  owin(createTheDictUrl(url, word));
}

/**
 * Get the language name from the Google Translate URL.
 * 
 * @param {string} wblink3 Google Translate Dictionary URL
 * @returns {string} Language name
 * 
 * @since 2.7.0 Also works with a LibreTranslate URL
 */
function getLangFromDict(wblink3) {
  let dictUrl, urlParams;
  if (wblink3.trim() == '') {
    return '';
  }
  // Replace pop-up marker '*'
  if (wblink3.startsWith('*')) {
    wblink3 = wblink3.substring(1);
  }
  if (wblink3.startsWith("trans.php") || wblink3.startsWith("ggl.php")) {
    wblink3 = 'http://' + wblink3;
  }
  dictUrl = new URL(wblink3);
  urlParams = dictUrl.searchParams;
  if (urlParams.get("lwt_translator") == "libretranslate") {
    return urlParams.get("source") || "";
  }
  // Fallback to Google Translate
  return urlParams.get("sl") || "";
}

/**
 * Return a tooltip, a short string describing the word (word, translation, 
 * romanization and learning status) 
 * 
 * @param {string} word   The word 
 * @param {string} trans  Translation of the word
 * @param {string} roman  Romanized version 
 * @param {int}    status Learning status of the word 
 * @returns {string} Tooltip for this word
 */
function make_tooltip (word, trans, roman, status) {
  const nl = '\x0d';
  let title = word;
  if (roman != '') {
    if (title != '') title += nl;
    title += '▶ ' + roman;
  }
  if (trans != '' && trans != '*') {
    if (title != '') title += nl;
    title += '▶ ' + trans;
  }
  if (title != '') title += nl;
  title += '▶ ' + getStatusName(status) + ' [' +
	getStatusAbbr(status) + ']';
  return title;
}

/**
 * Escape the HTML characters, with an eventual annotation
 * 
 * @param {string} title String to be escaped
 * @param {string} ann   An annotation to show in red
 * @returns {string} Escaped string
 */
function escape_html_chars_2 (title, ann) {
  if (ann != '') {
    const ann2 = escape_html_chars(ann);
    return escape_html_chars(title).replace(ann2,
      '<span style="color:red">' + ann2 + '</span>');
  } 
  return escape_html_chars(title);
}

/**
 * Open a window.
 * 
 * @param {string} url URL of the window 
 */
function owin (url) {
  window.open(
    url,
    'dictwin',
    'width=800, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no'
  );
}

/**
 * Open a window in edit mode.
 * 
 * @param {string} url Window URL
 */
function oewin (url) {
  window.open(
    url,
    'editwin',
    'width=800, height=600, scrollbars=yes, menubar=no, resizable=yes, status=no'
  );
}

/**
 * Create a dictionary URL.
 * 
 * JS alter ego of the createTheDictLink PHP function.
 * 
 * Case 1: url without any ### or "lwt_term": append term
 * Case 2: url with one ### or "lwt_term": substitute term
 * 
 * @param {string} u Dictionary URL
 * @param {string} w Term to be inserted in the URL
 * @returns {string} A link to external dictionary to get a translation of the word
 * 
 * @since 2.6.0-fork Internals rewrote, do no longer use PHP code. 
 *                   The option putting encoding between ###enc### does no 
 *                   longer work. It is deprecated and will be removed.
 * @since 2.7.0-fork Using "###" is deprecated, "lwt_term" recommended instead
 */
function createTheDictUrl (u, w) {
  const url = u.trim();
  const trm = w.trim();
  const term_elem = url.match(/lwt_term|###/);
  const pos = (term_elem === null) ? -1 : url.indexOf(term_elem[0]);
  // No ###/lwt_term found
  if (pos == -1) {
      return url + encodeURIComponent(trm);
  }
  // ###/lwt_term found
  const pos2 = url.indexOf('###', pos + 1);
  if (pos2 === -1) {
      // 1 ###/lwt_term found
      return url.replace(term_elem, trm == '' ? '+' : encodeURIComponent(trm));
  }
  // 2 ### found
  // Get encoding
  const enc = url.substring(
    pos + term_elem[0].length, pos2 - pos - term_elem[0].length
  ).trim();
  console.warn(
   "Trying to use encoding '" + enc + "'. This feature is abandonned since " + 
   "2.6.0-fork. Using default UTF-8." 
  );
  let output = url.substring(0, pos) + encodeURIComponent(trm);
  if (pos2+3 < url.length) { 
   output += url.substring(pos2 + 3); 
  }
  return output;
}

/**
 * Create an HTML link for a dictionary.
 * 
 * @param {string} u Dictionary URL
 * @param {string} w Word or sentence to be translated
 * @param {string} t Text to display
 * @param {string} b Some other text to display before the link
 * @returns {string} HTML-formatted link
 */
function createTheDictLink (u, w, t, b) {
  let url = u.trim();
  let popup  = false;
  const trm = w.trim();
  const txt = t.trim();
  const txtbefore = b.trim();
  let r = '';
  if (url == '' || txt == '') {
    return r;
  }
  if (url.startsWith('*')) {
    url = url.substring(1);
    popup = true;
  }
  try {
    let final_url = new URL(url);
    popup |= final_url.searchParams.has('lwt_popup');
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }
  if (popup) {
    r = ' ' + txtbefore +
    ' <span class="click" onclick="owin(\'' 
    + createTheDictUrl(url, escape_apostrophes(trm)) 
    + '\');">' + txt + '</span> ';
  } else {
    r = ' ' + txtbefore +
    ' <a href="' + createTheDictUrl(url, trm) + 
    '" target="ru" onclick="showRightFrames();">' + txt + '</a> ';
  }
  return r;
}

/**
 * Create a sentence lookup link.
 * 
 * @param {int}    torder Text order 
 * @param {int}    txid   Text ID
 * @param {string} url    Translator URL 
 * @param {string} txt    Word text
 * @returns {string} HTML-formatted link.
 */
function createSentLookupLink (torder, txid, url, txt) {
  url = url.trim();
  txt = txt.trim();
  let r = '';
  let popup = false;
  let external = false;
  const target_url = 'trans.php?x=1&i=' + torder + '&t=' + txid;
  if (url == '' || txt == '') {
    return r;
  }
  if (url.startsWith('*')) {
    url = url.substring(1);
    popup = true;
  }
  try {
    let final_url = new URL(url);
    popup |= final_url.searchParams.has('lwt_popup');
    external = true;
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }
  if (popup) {
    return ' <span class="click" onclick="owin(\'' + target_url + '\');">' + 
    txt + '</span> ';
  } 
  if (external) {
    return ' <a href="' + target_url + '" target="ru" onclick="showRightFrames();">'
    + txt + '</a> ';
  }
  return r;
}

/**
 * Replace html characters with encodings
 * 
 * @param {string} s String to be escaped 
 * @returns {string} Escaped string
 */
function escape_html_chars (s) {
  return s
  .replace(/&/g, '%AMP%')
  .replace(/</g, '&#060;').replace(/>/g, '&#062;')
  .replace(/"/g, '&#034;').replace(/'/g, '&#039;')
  .replace(/%AMP%/g, '&#038;')
  .replace(/\x0d/g, '<br />');
}

/**
 * Escape only single apostrophe ("'") from string
 * 
 * @param {string} s String to be escaped
 * @returns {string} Escaped string
 */
function escape_apostrophes (s) {
  return s.replace(/'/g, '\\\'');
}

function selectToggle (toggle, form) {
  const myForm = document.forms[form];
  for (let i = 0; i < myForm.length; i++) {
    if (toggle) {
      myForm.elements[i].checked = 'checked';
    } else {
      myForm.elements[i].checked = '';
    }
  }
  markClick();
}

function multiActionGo (f, sel) {
  if (f !== undefined && sel !== undefined) {
    const v = sel.value;
    const t = sel.options[sel.selectedIndex].text;
    if (typeof v === 'string') {
      if (v == 'addtag' || v == 'deltag') {
        let notok = 1;
        var answer = '';
        while (notok) {
          answer = prompt(
            '*** ' + t + ' ***' +
            '\n\n*** ' + $('input.markcheck:checked').length + 
            ' Record(s) will be affected ***' + 
            '\n\nPlease enter one tag (20 char. max., no spaces, no commas -- ' + 
            'or leave empty to cancel:', 
            answer
          );
          if (typeof answer === 'object') answer = '';
          if (answer.indexOf(' ') > 0 || answer.indexOf(',') > 0) {
            alert('Please no spaces or commas!');
          } else if (answer.length > 20) {
            alert('Please no tags longer than 20 char.!');
          } else {
            notok = 0;
          }
        }
        if (answer != '') {
          f.data.value = answer;
          f.submit();
        }
      } else if (
        v == 'del' || v == 'smi1' || v == 'spl1' || v == 's1' || v == 's5' || 
        v == 's98' || v == 's99' || v == 'today' || v == 'delsent' || 
        v == 'lower' || v == 'cap'
      ) {
        var answer = confirm(
          '*** ' + t + ' ***\n\n*** ' + $('input.markcheck:checked').length + 
          ' Record(s) will be affected ***\n\nAre you sure?'
        );
        if (answer) {
          f.submit();
        }
      } else {
        f.submit();
      }
    }
    sel.value = '';
  }
}

function allActionGo (f, sel, n) {
  if (typeof f !== 'undefined' && typeof sel !== 'undefined') {
    const v = sel.value;
    const t = sel.options[sel.selectedIndex].text;
    if (typeof v === 'string') {
      if (v == 'addtagall' || v == 'deltagall') {
        let notok = 1;
        var answer = '';
        while (notok) {
          answer = prompt(
            'THIS IS AN ACTION ON ALL RECORDS\n' + 
            'ON ALL PAGES OF THE CURRENT QUERY!\n\n' + 
            '*** ' + t + ' ***\n\n*** ' + n + ' Record(s) will be affected ***\n\n' +
            'Please enter one tag (20 char. max., no spaces, no commas -- ' + 
            'or leave empty to cancel:', 
            answer
          );
          if (typeof answer === 'object') answer = '';
          if (answer.indexOf(' ') > 0 || answer.indexOf(',') > 0) {
            alert('Please no spaces or commas!');
          } else if (answer.length > 20) {
            alert('Please no tags longer than 20 char.!');
          } else {
            notok = 0;
          }
        }
        if (answer != '') {
          f.data.value = answer;
          f.submit();
        }
      } else if (
        v == 'delall' || v == 'smi1all' || v == 'spl1all' || v == 's1all' || 
        v == 's5all' || v == 's98all' || v == 's99all' || v == 'todayall' || 
        v == 'delsentall' || v == 'capall' || v == 'lowerall'
      ) {
        var answer = confirm(
          'THIS IS AN ACTION ON ALL RECORDS\nON ALL PAGES OF THE CURRENT QUERY!\n\n'+
          '*** ' + t + ' ***\n\n*** ' + n + ' Record(s) will be affected ***\n\n' + 
          'ARE YOU SURE?'
        );
        if (answer) {
          f.submit();
        }
      } else {
        f.submit();
      }
    }
    sel.value = '';
  }
}

/**
 * Check if cookies are enabled by setting a cookie.
 * 
 * @returns {boolean} true if cookies are enabled, false otherwise
 */
function areCookiesEnabled () {
  setCookie('test', 'none', '', '/', '', '');
  if (getCookie('test')) {
    cookie_set = true;
    deleteCookie('test', '/', '');
  } else {
    cookie_set = false;
  }
  return cookie_set;
}

/**
 * Set the current language.
 * 
 * @param {string} ctl Current language name 
 * @param {string} url 
 * @returns {void}
 */
function setLang (ctl, url) {
  location.href = 'inc/save_setting_redirect.php?k=currentlanguage&v=' +
	ctl.options[ctl.selectedIndex].value +
	'&u=' + url;
}

/**
 * Reset current language to default.
 * 
 * @param {string} url 
 * @returns {void} 
 */
function resetAll (url) {
  location.href = 'inc/save_setting_redirect.php?k=currentlanguage&v=&u=' + url;
}

/**
 * Get a specific cookie by its name.
 * 
 * @param {string} check_name Cookie name 
 * @returns {string|null} Value of the cookie if found, null otherwise
 * 
 * @since 2.6.0-fork Use decodeURIComponent instead of deprecated unescape
 */
function getCookie (check_name) {
  const a_all_cookies = document.cookie.split(';');
  let a_temp_cookie = '';
  let cookie_name = '';
  let cookie_value = '';
  let b_cookie_found = false; // set boolean t/f default f
  let i = '';
  for (i = 0; i < a_all_cookies.length; i++) {
    a_temp_cookie = a_all_cookies[i].split('=');
    cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');
    if (cookie_name == check_name) {
      b_cookie_found = true;
      if (a_temp_cookie.length > 1) {
        cookie_value = decodeURIComponent(
          a_temp_cookie[1].replace(/^\s+|\s+$/g, '')
        );
      }
      return cookie_value;
    }
    a_temp_cookie = null;
    cookie_name = '';
  }
  if (!b_cookie_found) {
    return null;
  }
}

/**
 * Set a new cookie.
 * 
 * @param {string} name    Name of the cookie 
 * @param {string} value   Cookie value 
 * @param {number} expires Number of DAYS before the cookie expires. 
 * @param {string} path    Cookie path
 * @param {string} domain  Cookie domain 
 * @param {boolean} secure If it should only be sent through secure connection 
 * @returns {void}
 * 
 * @since 2.6.0-fork Use encodeURIComponent instead of deprecated escape
 */
function setCookie (name, value, expires, path, domain, secure) {
  const today = new Date();
  today.setTime(today.getTime());
  if (expires) {
    expires = expires * 1000 * 60 * 60 * 24;
  }
  const expires_date = new Date(today.getTime() + (expires));
  document.cookie = name + '=' + encodeURIComponent(value) +
		(expires ? ';expires=' + expires_date.toGMTString() : '') +
		(path ? ';path=' + path : '') +
		(domain ? ';domain=' + domain : '') +
		(secure ? ';secure' : '');
}

/**
 * Delete a cookie.
 * 
 * @param {string} name   Cookie name
 * @param {string} path   Cookie path
 * @param {string} domain Cookie domain
 * @returns {void}
 */
function deleteCookie (name, path, domain) {
  if (getCookie(name)) {
    document.cookie = name + '=' +
		(path ? ';path=' + path : '') +
		(domain ? ';domain=' + domain : '') +
		';expires=Thu, 01-Jan-1970 00:00:01 GMT';
  }
}

/**
 * Prepare a window to make all words from a text well-known
 * 
 * @param {string} t Text ID
 */
function iknowall(t) {
  const answer = confirm('Are you sure?');
  if (answer) {
    showRightFrames('all_words_wellknown.php?text=' + t);
  }
}

/**
 * Check is the table prefix is a valid alphanumeric character.
 * Create an alert if not.
 * 
 * @param {string} p Table prefix 
 * @returns {boolean} true is the prefix is valid
 */
function check_table_prefix (p) {
  const re = /^[_a-zA-Z0-9]*$/;
  const r = p.length <= 20 && p.length > 0 && p.match(re);
  if (!r) { 
    alert(
      'Table Set Name (= Table Prefix) must'
      + '\ncontain 1 to 20 characters (only 0-9, a-z, A-Z and _).' 
      + '\nPlease correct your input.'
    ); 
  }
  return r;
}
