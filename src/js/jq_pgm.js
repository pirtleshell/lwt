/**
 * \file
 * \brief Interaction between LWT and jQuery
 * 
 * @package Lwt
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

/**************************************************************
Global variables used in LWT jQuery functions
***************************************************************/

TEXTPOS = -1;
OPENED = 0;
/** @var {int} WID - Word ID */
WID = 0;
/** Text ID (int) */
TID = 0;
/** First dictionary URL */
WBLINK1 = '';
/** Second dictionary URL */
WBLINK2 = '';
/** Google Translate */
WBLINK3 = '';
SOLUTION = '';
ADDFILTER = '';
/** Right-to-left indicator */
RTL = 0;
ANN_ARRAY = {};
DELIMITER = '';
JQ_TOOLTIP = 0;

/**************************************************************
LWT jQuery functions
***************************************************************/

/**
 * Set translation and romanization in a form when possible.
 * 
 * Marj the form as edited if something was changed.
 * 
 * @param {string} tra Translation
 * @param {string} rom Romanization
 */
function setTransRoman(tra, rom) {
  let form_changed = false;
  if ($('textarea[name="WoTranslation"]').length == 1) { 
    $('textarea[name="WoTranslation"]').val(tra);
    form_changed |= true;
  }
  if ($('input[name="WoRomanization"]').length == 1) { 
    $('input[name="WoRomanization"]').val(rom);
    form_changed |= true;
  }
  if (form_changed)
    makeDirty();
}

/**
 * Return whether characters are outside the multilingual plane.
 * 
 * @param {string} s Input string
 * @returns {boolean} true is some characters are outside the plane
 */
function containsCharacterOutsideBasicMultilingualPlane (s) {
  return /[\uD800-\uDFFF]/.test(s);
}

/**
 * Alert if characters are outside the multilingual plane.
 * 
 * @param {string} s Input string
 * @returns {boolean} true is some characters are outside the plane
 */
function alertFirstCharacterOutsideBasicMultilingualPlane (s, info) {
  if (!containsCharacterOutsideBasicMultilingualPlane(s)) {
    return 0;
  }
  const match = /[\uD800-\uDFFF]/.exec(s);
  alert(
    'ERROR\n\nText "' + info + '" contains invalid character(s) ' + 
    '(in the Unicode Supplementary Multilingual Planes, > U+FFFF) like emojis ' + 
    'or very rare characters.\n\nFirst invalid character: "' + 
    s.substring(match.index, match.index + 2) + '" at position ' + 
    (match.index + 1) + '.\n\n' + 
    'More info: https://en.wikipedia.org/wiki/Plane_(Unicode)\n\n' + 
    'Please remove this/these character(s) and try again.'
  );
  return 1;
}

/**
 * Return the memory size of an UTF8 string.
 * 
 * @param {string} s String to evaluate
 * @returns {number} Size in bytes
 */
function getUTF8Length (s) {
  return (new Blob([String(s)])).size;
}

/**
 * Force the user scrolling to an anchor.
 * 
 * @param {string} aid Anchor ID
 */
function scrollToAnchor (aid) {
  document.location.href = '#' + aid;
}

/**
 * Set an existing translation as annotation for a term.
 * 
 * @param {int} textid Text ID 
 * @param {string} elem_name Name of the element of which to change annotation (e. g.: "rg1") 
 * @param {Object} form_data All the data from the form 
 * (e. g. {"rg0": "foo", "rg1": "bar"})
 */
function do_ajax_save_impr_text(textid, elem_name, form_data) {
  const idwait = '#wait' + elem_name.substring(2);
  $(idwait).html('<img src="icn/waiting2.gif" />');
  // elem: "rg2", form_data: {"rg2": "translation"}
  $.post(
    'inc/ajax.php',
    {
      action: "",
      action_type: "set_annotation",
      tid: textid,
      elem: elem_name,
      data: form_data
    },
    function (data) {
      $(idwait).html('<img src="icn/empty.gif" />');
      if ("error" in data)
        alert(
          'Saving your changes failed, please reload the page and try again! ' +
          'Error message: "' + data.error + '".'
        );
    },
    "json"
  );
}

/**
 * Change the annotation for a term by setting its text.
 */
function changeImprAnnText () {
  $(this).prev('input:radio').attr('checked', 'checked');
  const textid = $('#editimprtextdata').attr('data_id');
  const elem_name = $(this).attr('name');
  const form_data = JSON.stringify($('form').serializeObject());
  do_ajax_save_impr_text(textid, elem_name, form_data);
}

/**
 * Change the annotation for a term by setting its text.
 */
function changeImprAnnRadio () {
  const textid = $('#editimprtextdata').attr('data_id');
  const elem_name = $(this).attr('name');
  const form_data = JSON.stringify($('form').serializeObject());
  do_ajax_save_impr_text(textid, elem_name, form_data);
}

/**
 * Add (new word) or update (existing word) a word translation.
 * 
 * @param {int}    wordid Word ID, 0 for new wrod
 * @param {string} txid   Text HTML ID or unique HTML selector
 * @param {string} word   Word text
 * @param {int}    lang   Language ID
 * @returns 
 */
function addTermTranslation(wordid, txid, word, lang) {
  const translation = $(txid).val().trim();
  const pagepos = $(document).scrollTop();
  if (translation == '' || translation == '*') {
    alert('Text Field is empty or = \'*\'!');
    return;
  }
  let request = {
        action: "change_translation",
        translation: translation,
  };
  let failure;
  let action_type;
  if (wordid === 0) {
    action_type = "add";
    request["text"] = word;
    request["lang"] = lang;
    failure = "Adding translation to term failed!";
  } else {
    action_type = "update";
    request["wordid"] = wordid;
    failure = "Updating translation of term failed!";
  }
  request["action_type"] = action_type;
  failure += "Please reload page and try again."
  $.post(
    'inc/ajax.php', 
    request,
    function (d) {
      if (d == '') {
        alert(failure);
        return;
      }
      if ("error" in d) {
        alert(failure + "\n" + d.error);
        return;
      }
      do_ajax_edit_impr_text(pagepos, d[action_type]);
    },
    "json"
  );
}

/**
 * Set a new status for a word in the test table.
 * 
 * @param {string} wordid Word ID
 * @param {bool}   up     true if status sould be increased, false otherwise
 */
function changeTableTestStatus (wordid, up) {
  $.post(
    'inc/ajax.php',
    {
      action: "term_status",
      action_type: "increment",
      wid: parseInt(wordid, 10),
      status_up: (up ? 1 : 0) 
    }, 
    function (data) {
      if (data == "" || "error" in data) {
        return;
      }
      $('#STAT' + wordid).html(data.increment);
    },
    "json"
  );
}

/**
 * Check if there is no problem with the text.
 * 
 * @returns {boolean} true if all checks were successfull
 */
function check () {
  let count = 0;
  $('.notempty').each(function (_n) {
    if ($(this).val().trim() == '') count++;
  });
  if (count > 0) {
    alert('ERROR\n\n' + count + ' field(s) - marked with * - must not be empty!');
    return false;
  }
  count = 0;
  $('input.checkurl').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (($(this).val().trim().indexOf('http://') != 0) && 
      ($(this).val().trim().indexOf('https://') != 0) && 
      ($(this).val().trim().indexOf('#') != 0)) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') + 
          '" must start with "http://" or "https://" if not empty.'
        );
        count++;
      }
    }
  });
  // Note: as of LWT 2.9.0, no field with "checkregexp" property is found in the code base
  $('input.checkregexp').each(function (_n) {
    const regexp = $(this).val().trim();
    if (regexp.length > 0) {
      $.ajax({
        type: 'POST',
        url: 'inc/ajax.php',
        data: {
          action: "",
          action_type: "check_regexp",
          regex: regexp 
        },
			 async: false
      }
      ).always(function (data) {
        if (data != '') {
          alert(data);
          count++;
        }
      });
    }
  });
  // To enable limits of custom feed texts/articl.
  // change the following «input[class*="max_int_"]» into «input[class*="maxint_"]»
  $('input[class*="max_int_"]').each(function (_n) {
    const maxvalue = parseInt($(this).attr('class')
    .replace(/.*maxint_([0-9]+).*/, '$1'));
    if ($(this).val().trim().length > 0) {
      if ($(this).val() > maxvalue) {
        alert(
          'ERROR\n\n Max Value of Field "' + $(this).attr('data_info') + 
          '" is ' + maxvalue
        );
        count++;
      }
    }
  });
  // Check that the Google Translate field is of good type
  $('input.checkdicturl').each(function (_n) {
    const translate_input = $(this).val().trim();
    if (translate_input.length > 0) {
      let refinned = translate_input;
      if (translate_input.startsWith('*')) {
        refinned = translate_input.substring(1);
      }
      if (!/^https?:\/\//.test(refinned)) {
        refinned = 'http://' + refinned;
      }
      try {
        new URL(refinned);
      } catch (err) {
        if (err instanceof TypeError) {
          alert(
            'ERROR\n\nField "' + $(this).attr('data_info') + 
            '" should be an URL if not empty.'
          );
          count++;
        }
      }
    }
  });
  $('input.posintnumber').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (!(isInt($(this).val().trim()) && (parseInt($(this).val().trim(), 10) > 0))) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') + 
          '" must be an integer number > 0.'
        );
        count++;
      }
    }
  });
  $('input.zeroposintnumber').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (!(isInt($(this).val().trim()) && (parseInt($(this).val().trim(), 10) >= 0))) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') + 
          '" must be an integer number >= 0.'
        );
        count++;
      }
    }
  });
  $('input.checkoutsidebmp').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (containsCharacterOutsideBasicMultilingualPlane($(this).val())) {
        count += alertFirstCharacterOutsideBasicMultilingualPlane(
          $(this).val(), $(this).attr('data_info')
        );
      }
    }
  });
  $('textarea.checklength').each(function (_n) {
    if ($(this).val().trim().length > (0 + $(this).attr('data_maxlength'))) {
      alert(
        'ERROR\n\nText is too long in field "' + $(this).attr('data_info') + 
        '", please make it shorter! (Maximum length: ' + 
        $(this).attr('data_maxlength') + ' char.)'
      );
      count++;
    }
  });
  $('textarea.checkoutsidebmp').each(function (_n) {
    if (containsCharacterOutsideBasicMultilingualPlane($(this).val())) {
      count += alertFirstCharacterOutsideBasicMultilingualPlane(
        $(this).val(), $(this).attr('data_info')
      );
    }
  });
  $('textarea.checkbytes').each(function (_n) {
    if (getUTF8Length($(this).val().trim()) > (0 + $(this).attr('data_maxlength'))) {
      alert(
        'ERROR\n\nText is too long in field "' + $(this).attr('data_info') + 
        '", please make it shorter! (Maximum length: ' + 
        $(this).attr('data_maxlength') + ' bytes.)'
      );
      count++;
    }
  });
  $('input.noblanksnocomma').each(function (_n) {
    if ($(this).val().indexOf(' ') > 0 || $(this).val().indexOf(',') > 0) {
      alert(
        'ERROR\n\nNo spaces or commas allowed in field "' + 
        $(this).attr('data_info') + '", please remove!'
      );
      count++;
    }
  });
  return (count == 0);
}

function isInt (value) {
  for (let i = 0; i < value.length; i++) {
    if ((value.charAt(i) < '0') || (value.charAt(i) > '9')) {
      return false;
    }
  }
  return true;
}

function markClick () {
  if ($('input.markcheck:checked').length > 0) {
    $('#markaction').removeAttr('disabled');
  } else {
    $('#markaction').attr('disabled', 'disabled');
  }
}

function confirmDelete () {
  return confirm('CONFIRM\n\nAre you sure you want to delete?');
}

/**
 * Enable/disable words hint. 
 * Function called when clicking on "Show All".
 */
function showAllwordsClick () {
  const showAll = $('#showallwords').prop('checked') ? '1' : '0';
  const showLeaning = $('#showlearningtranslations').prop('checked') ? '1' : '0';
  const text = $('#thetextid').text();
  // Timeout necessary because the button is clicked on the left (would hide frames)
	setTimeout(function () {
    showRightFrames(
      'set_text_mode.php?mode=' + showAll + '&showLearning=' + showLeaning + 
      '&text=' + text
  );}, 500);
  setTimeout(function () {window.location.reload();}, 4000);
}

function textareaKeydown (event) {
  if (event.keyCode && event.keyCode == '13') {
    if (check()) 
      $('input:submit').last().trigger('click');
    return false;
  } else {
    return true;
  }
}

function noShowAfter3Secs () {
  $('#hide3').slideUp();
}

/**
 * Set the focus on an element with the "focus" class.
 * 
 * @returns undefined
 */
function setTheFocus () {
  $('.setfocus')
  .trigger('focus')
  .trigger('select');
}

/**
 * Prepare a dialog when the user clicks a word during a test.
 * 
 * @returns false
 */
function word_click_event_do_test_test () {
  run_overlib_test(
    WBLINK1, WBLINK2, WBLINK3,
    $(this).attr('data_wid'),
    $(this).attr('data_text'),
    $(this).attr('data_trans'),
    $(this).attr('data_rom'),
    $(this).attr('data_status'),
    $(this).attr('data_sent'),
    $(this).attr('data_todo')
  );
  $('.todo').text(SOLUTION);
  return false;
}

/**
 * Handle keyboard interaction when testing a word.
 * 
 * @param {object} e A keystroke object 
 * @returns {bool} true if nothing was done, false otherwise
 */
function keydown_event_do_test_test (e) {
  if (e.key == 'Space'  && OPENED == 0) { 
    // space : show sol.
    $('.word').trigger('click');
    cleanupRightFrames();
    showRightFrames('show_word.php?wid=' + $('.word').attr('data_wid') + '&ann=');
    OPENED = 1;
    return false;
  }
  if (e.which == 38) { 
    // up : status+1
		showRightFrames('set_test_status.php?wid=' + WID + '&stchange=1');
    return false;
  }
  if (e.which == 27) { 
    // esc : dont change status
		showRightFrames(
      'set_test_status.php?wid=' + WID + '&status=' + $('.word').attr('data_status')
    );
    return false;
  }
  if (e.which == 73) { 
    // I : status=98
		showRightFrames('set_test_status.php?wid=' + WID + '&status=98');
    return false;
  }
  if (e.which == 87) { 
    // W : status=99
		showRightFrames('set_test_status.php?wid=' + WID + '&status=99');
    return false;
  }
  if (e.which == 69) { 
    // E : EDIT
		showRightFrames('edit_tword.php?wid=' + WID);
    return false;
  }
  if (OPENED == 0) return true;
  if (e.which == 40) { 
    // down : status-1
		showRightFrames('set_test_status.php?wid=' + WID + '&stchange=-1');
    return false;
  }
  for (let i = 1; i <= 5; i++) {
    if (e.which == (48 + i) || e.which == (96 + i)) { 
      // 1,.. : status=i
			showRightFrames('set_test_status.php?wid=' + WID + '&status=' + i);
      return false;
    }
  }
  return true;
}

/**
 * Add annotations to a word.
 * 
 * @param {*} _ Unused, usually word number 
 */
function word_each_do_text_text(_) {
  const wid = $(this).attr('data_wid');
  if (wid != '') {
    const order = $(this).attr('data_order');
    if (order in ANN_ARRAY) {
      if (wid == ANN_ARRAY[order][1]) {
        const ann = ANN_ARRAY[order][2];
        const re = new RegExp(
          '([' + DELIMITER + '][ ]{0,1}|^)(' + 
          ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' + 
          DELIMITER + '])', 
          ''
        );
        if (!re.test($(this).attr('data_trans').replace(/ \[.*$/, ''))) {
          const trans = ann + ' / ' + $(this).attr('data_trans');
          $(this).attr('data_trans', trans.replace(' / *', ''));
        }
        $(this).attr('data_ann', ann);
      }
    }
  }
  if (!JQ_TOOLTIP) {
    this.title = make_tooltip(
      $(this).text(), 
      $(this).attr('data_trans'), 
      $(this).attr('data_rom'), 
      $(this).attr('data_status')
    );
  }
}

function mword_each_do_text_text(_) {
  if ($(this).attr('data_status') != '') {
    const wid = $(this).attr('data_wid');
    if (wid != '') {
      const order = parseInt($(this).attr('data_order'));
      for (let j = 2; j <= 16; j = j + 2) {
        const index = (order + j).toString();
        if (index in ANN_ARRAY) {
          if (wid == ANN_ARRAY[index][1]) {
            const ann = ANN_ARRAY[index][2];
            const re = new RegExp(
              '([' + DELIMITER + '][ ]{0,1}|^)(' + 
              ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' + 
              DELIMITER + '])', 
              ''
            );
            if (!re.test($(this).attr('data_trans').replace(/ \[.*$/, ''))) {
              const trans = ann + ' / ' + $(this).attr('data_trans');
              $(this).attr('data_trans', trans.replace(' / *', ''));
            }
            $(this).attr('data_ann', ann);
            break;
          }
        }
      }
    }
    if (!JQ_TOOLTIP) {
      this.title = make_tooltip(
        $(this).attr('data_text'),
        $(this).attr('data_trans'), $(this).attr('data_rom'),
        $(this).attr('data_status')
      );
    }
  }
}

function word_dblclick_event_do_text_text () {
  const t = parseInt($('#totalcharcount').text(), 10);
  if (t == 0) return;
  let p = 100 * ($(this).attr('data_pos') - 5) / t;
  if (p < 0) p = 0;
  if (typeof (window.parent.frames.h.new_pos) === 'function') { 
    window.parent.frames.h.new_pos(p); 
  }
}

/**
 * Do a word edition window. Usually called when the user clicks on a word.
 * 
 * @returns {bool} false
 */
function word_click_event_do_text_text () {
  const status = $(this).attr('data_status');
  let ann = '';
  if ($(this).attr('data_ann') !== undefined) { 
    ann = $(this).attr('data_ann'); 
  }

  let hints;
  if (JQ_TOOLTIP) { 
    hints = make_tooltip(
      $(this).text(), $(this).attr('data_trans'), $(this).attr('data_rom'), status
    ); 
  } else { 
    hints = $(this).attr('title'); 
  }

  // Get multi-words containing word
  const multi_words = Array(7);
  for (let i = 0; i < 7; i++) {
    // Start from 2 as multi-words have at least two elements
    multi_words[i] = $(this).attr('data_mw' + (i + 2)); 
  }
  if (status < 1) {
    run_overlib_status_unknown(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), $(this).text(), multi_words, RTL
    );
    showRightFrames(
      'edit_word.php?tid=' + TID + '&ord=' + $(this).attr('data_order') + '&wid='
    );
  } else if (status == 99) {
    run_overlib_status_99(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), 
      $(this).text(), $(this).attr('data_wid'), multi_words, RTL, ann
    );
  } else if (status == 98) {
    run_overlib_status_98(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), 
      $(this).text(), $(this).attr('data_wid'), multi_words, RTL, ann
    );
  } else {
    run_overlib_status_1_to_5(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), 
      $(this).text(), $(this).attr('data_wid'), status, multi_words, RTL, ann
    );
  }
  return false;
}

function mword_click_event_do_text_text () {
  const status = $(this).attr('data_status');
  if (status != '') {
    let ann = '';
    if ((typeof $(this).attr('data_ann')) !== 'undefined') { 
      ann = $(this).attr('data_ann'); 
    }
    run_overlib_multiword(
      WBLINK1, WBLINK2, WBLINK3, 
      JQ_TOOLTIP ? make_tooltip(
        $(this).text(), 
        $(this).attr('data_trans'), 
        $(this).attr('data_rom'), 
        status
      ) : $(this).attr('title'),
      TID, $(this).attr('data_order'), $(this).attr('data_text'),
      $(this).attr('data_wid'), status, $(this).attr('data_code'), ann
    );
  }
  return false;
}

function mword_drag_n_drop_select (event) {
  if (JQ_TOOLTIP)$('.ui-tooltip').remove();
  const context = $(this).parent();
  context.one('mouseup mouseout', $(this), function () {
    clearTimeout(to);
    $('.nword').removeClass('nword');
    $('.tword').removeClass('tword');
    $('.lword').removeClass('lword');
    $('.wsty', context).css('background-color', '').css('border-bottom-color', '');
    $('#pe').remove();
  });

  to = setTimeout(function () {
    let pos;
    context.off('mouseout');
    $('.wsty', context).css('background-color', 'inherit')
    .css('border-bottom-color', 'rgba(0,0,0,0)').not('.hide,.word')
    .each(function () {
      f = parseInt($(this).attr('data_code')) * 2 + 
      parseInt($(this).attr('data_order')) - 1;
      h = '';
      $(this).nextUntil($('[id^="ID-' + f + '-"]', context), '[id$="-1"]')
      .each(function () {
        l = $(this).attr('data_order');
        if (typeof l !== 'undefined') {
          h += '<span class="tword" data_order="' + l + '">' + $(this).text() + 
          '</span>';
        } else {
          h += '<span class="nword" data_order="' + 
          $(this).attr('id').split('-')[1] + '">' + $(this).text() + '</span>';
        }
      });
      $(this).html(h);
    });
    $('#pe').remove();
    $('body')
    .append(
      '<style id="pe">#' + context.attr('id') + ' .wsty:after,#' + 
      context.attr('id') + ' .wsty:before{opacity:0}</style>'
    );

    $('[id$="-1"]', context).not('.hide,.wsty').addClass('nword').each(function () {
      $(this).attr('data_order', $(this).attr('id').split('-')[1]);
    });
    $('.word', context).not('.hide').each(function () {
      $(this).html(
        '<span class="tword" data_order="' + $(this).attr('data_order') + '">' + 
        $(this).text() + '</span>'
      );
    });
    if (event.data.annotation == 1) {
      $('.wsty', context)
      .not('.hide')
      .each(function () { 
        $(this).children('.tword').last()
        .attr('data_ann', $(this).attr('data_ann'))
        .attr('data_trans', $(this).attr('data_trans'))
        .addClass(
          'content' + $(this)
          .removeClass('status1 status2 status3 status4 status5 status98 status99')
          .attr('data_status')
        ); 
      });
    } else if (event.data.annotation == 3) {
      $('.wsty', context)
      .not('.hide')
      .each(function () { 
        $(this).children('.tword').first()
        .attr('data_ann', $(this).attr('data_ann'))
        .attr('data_trans', $(this).attr('data_trans'))
        .addClass(
          'content' + $(this)
          .removeClass('status1 status2 status3 status4 status5 status98 status99')
          .attr('data_status')
        ); 
      });
    }
      $(context).one('mouseover', '.tword', function () {
      $('html').one('mouseup', function () {
        $('.wsty', context).each(function () { 
          $(this).addClass('status' + $(this).attr('data_status')); 
        });
        if (!$(this).hasClass('tword')) {
          $('span', context).removeClass('nword tword lword');
          $('.wsty', context).css('background-color', '')
          .css('border-bottom-color', '');
          $('#pe').remove();
        }
      });
      pos = parseInt($(this).attr('data_order'));
      $('.lword', context).removeClass('lword');
      $(this).addClass('lword');
      $(context).on('mouseleave', function () {
        $('.lword', context).removeClass('lword');
      });
      $(context).one('mouseup', '.nword,.tword', function (ev) {
        if (ev.handled !== true) {
          const len = $('.lword.tword', context).length;
          if (len > 0) {
            g = $('.lword', context).first().attr('data_order');
            if (len > 1) {
              const text = $('.lword', context)
              .map(function () { return $(this).text(); }).get().join('');
              if (text.length > 250) {
                alert('selected text is too long!!!');
              } else {
                showRightFrames(
                  'edit_mword.php?tid=' + TID + '&len=' + len + '&ord=' + g + 
                  '&txt=' + text
                );
              }
            } else {
              showRightFrames(
                'edit_word.php?tid=' + TID + '&ord=' + g + '&txt=' + 
                $('#ID-' + g + '-1').text()
              );
            }
          }
          $('span', context).removeClass('tword nword');
          ev.handled = true;
        }
      });
    });
    $(context).hoverIntent({
      over: function () {
        $('.lword', context).removeClass('lword');
        const lpos = parseInt($(this).attr('data_order'));
        $(this).addClass('lword');
        if (lpos > pos) {
          for (var i = pos; i < lpos; i++) {
            $(
              '.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]', 
              context
            ).addClass('lword');
          }
        } else {
          for (var i = pos; i > lpos; i--) {
            $(
              '.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]', 
              context
            ).addClass('lword');
          }
        }
      },
      out: function () {},
      sensitivity: 18,
      selector: '.tword'
    });
  }, 300);
}

function word_hover_over () {
  if (!$('.tword')[0]) {
    const v = $(this).attr('class').replace(/.*(TERM[^ ]*)( .*)*/, '$1');
    $('.' + v).addClass('hword');
    if (JQ_TOOLTIP) {
      $(this).trigger('mouseover');
    }
  }
}

function word_hover_out () {
  $('.hword').removeClass('hword');
  if (JQ_TOOLTIP)$('.ui-helper-hidden-accessible>div[style]').remove();
}

jQuery.fn.extend({
  tooltip_wsty_content: function () {
    var re = new RegExp('([' + DELIMITER + '])(?! )', 'g');
    let title = '';
    if ($(this).hasClass('mwsty')) {
      title = "<p><b style='font-size:120%'>" + $(this).attr('data_text') + 
      '</b></p>';
    } else {
      title = "<p><b style='font-size:120%'>" + $(this).text() + '</b></p>';
    }
    const roman = $(this).attr('data_rom');
    let trans = $(this).attr('data_trans').replace(re, '$1 ');
    let statname = '';
    const status = parseInt($(this).attr('data_status'));
    if (status == 0)statname = 'Unknown [?]';
    else if (status < 5)statname = 'Learning [' + status + ']';
    if (status == 5)statname = 'Learned [5]';
    if (status == 98)statname = 'Ignored [Ign]';
    if (status == 99)statname = 'Well Known [WKn]';
    if (roman != '') {
      title += '<p><b>Roman.</b>: ' + roman + '</p>';
    }
    if (trans != '' && trans != '*') {
      if ($(this).attr('data_ann')) {
        const ann = $(this).attr('data_ann');
        if (ann != '' && ann != '*') {
          var re = new RegExp(
            '(.*[' + DELIMITER + '][ ]{0,1}|^)(' + 
            ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' + 
            DELIMITER + '].*$| \\[.*$)', 
            ''
          );
          trans = trans.replace(re, '$1<span style="color:red">$2</span>$3');
        }
      }
      title += '<p><b>Transl.</b>: ' + trans + '</p>';
    }
    title += '<p><b>Status</b>: <span class="status' + status + '">' + statname + 
    '</span></p>';
    return title;
  }
});

jQuery.fn.extend({
	 tooltip_wsty_init: function () {
		 $(this).tooltip({
		      position: { my: 'left top+10', at: 'left bottom', collision: 'flipfit' },
		      items: '.hword',
		      show: { easing: 'easeOutCirc' },
		      content: function () { return $(this).tooltip_wsty_content(); }
    });
	 }
});

function get_position_from_id (id_string) {
  if ((typeof id_string) === 'undefined') return -1;
  const arr = id_string.split('-');
  return parseInt(arr[1]) * 10 + 10 - parseInt(arr[2]);
}

function keydown_event_do_text_text (e) {
  if (e.which == 27) { // esc = reset all
    TEXTPOS = -1;
    $('span.uwordmarked').removeClass('uwordmarked');
    $('span.kwordmarked').removeClass('kwordmarked');
    cClick();
    return false;
  }

  if (e.which == 13) { // return = edit next unknown word
    $('span.uwordmarked').removeClass('uwordmarked');
    const unknownwordlist = $('span.status0.word:not(.hide):first');
    if (unknownwordlist.size() == 0) return false;
    $(window).scrollTo(unknownwordlist, { axis: 'y', offset: -150 });
    unknownwordlist.addClass('uwordmarked').trigger('click');
    cClick();
    return false;
  }

  const knownwordlist = $(
    'span.word:not(.hide):not(.status0)' + ADDFILTER + 
    ',span.mword:not(.hide)' + ADDFILTER
  );
  const l_knownwordlist = knownwordlist.size();
  // console.log(knownwordlist);
  if (l_knownwordlist == 0) return true;

  // the following only for a non-zero known words list
  if (e.which == 36) { // home : known word navigation -> first
    $('span.kwordmarked').removeClass('kwordmarked');
    TEXTPOS = 0;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann');
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + 
      encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which == 35) { // end : known word navigation -> last
    $('span.kwordmarked').removeClass('kwordmarked');
    TEXTPOS = l_knownwordlist - 1;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann');
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + 
      encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which == 37) { // left : known word navigation
    var marked = $('span.kwordmarked');
    var currid = (marked.length == 0)
      ? (100000000)
      : get_position_from_id(marked.attr('id'));
    $('span.kwordmarked').removeClass('kwordmarked');
    // console.log(currid);
    TEXTPOS = l_knownwordlist - 1;
    for (var i = l_knownwordlist - 1; i >= 0; i--) {
      var iid = get_position_from_id(knownwordlist.eq(i).attr('id'));
      // console.log(iid);
      if (iid < currid) {
        TEXTPOS = i;
        break;
      }
    }
    // TEXTPOS--;
    // if (TEXTPOS < 0) TEXTPOS = l_knownwordlist - 1;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann'); 
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + 
      encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which == 39 || e.which == 32) { // space /right : known word navigation
    var marked = $('span.kwordmarked');
    var currid = (marked.length == 0)
      ? (-1)
      : get_position_from_id(marked.attr('id'));
    $('span.kwordmarked').removeClass('kwordmarked');
    // console.log(currid);
    TEXTPOS = 0;
    for (var i = 0; i < l_knownwordlist; i++) {
      var iid = get_position_from_id(knownwordlist.eq(i).attr('id'));
      // console.log(iid);
      if (iid > currid) {
        TEXTPOS = i;
        break;
      }
    }
    // TEXTPOS++;
    // if (TEXTPOS >= l_knownwordlist) TEXTPOS = 0;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann'); 
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + 
      encodeURIComponent(ann)
    );
    return false;
  }

  if ((!$('.kwordmarked, .uwordmarked')[0]) && $('.hword:hover')[0]) {
    curr = $('.hword:hover');
  } else {
    if (TEXTPOS < 0 || TEXTPOS >= l_knownwordlist) return true;
    curr = knownwordlist.eq(TEXTPOS);
  }
  const wid = curr.attr('data_wid');
  const ord = curr.attr('data_order');
  const stat = curr.attr('data_status');
  const txt = (curr.hasClass('mwsty')) ? curr.attr('data_text') : curr.text();
  let dict = '';

  for (var i = 1; i <= 5; i++) {
    if (e.which == (48 + i) || e.which == (96 + i)) { // 1,.. : status=i
      if (stat == '0') {
        if (i == 1) {
          /** @var {string} sl Source language */
          const sl = getLangFromDict(WBLINK3);
          const tl = WBLINK3.replace(/.*[?&]tl=([a-zA-Z\-]*)(&.*)*$/, '$1');
          if (sl != WBLINK3 && tl != WBLINK3)
            i = i + '&sl=' + sl + '&tl=' + tl;
        }
        showRightFrames(
          'set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status=' + i
        );
      } else {
				showRightFrames(
          'set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + 
          '&status=' + i
        );
        return false;
      }
    }
  }
  if (e.which == 73) { // I : status=98
    if (stat == '0') {
			showRightFrames(
        'set_word_on_hover.php?text=' + txt + '&tid=' + TID + 
        '&status=98'
      );
    } else {
			showRightFrames(
        'set_word_status.php?wid=' + wid + '&tid=' + TID + 
        '&ord=' + ord + '&status=98'
      );
      return false;
    }
  }
  if (e.which == 87) { // W : status=99
    if (stat == '0') {
			showRightFrames(
        'set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status=99'
      );
    } else {
			showRightFrames(
        'set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + 
        '&status=99'
      );
    }
    return false;
  }
  if (e.which == 80) { // P : pronounce term
    const lg = getLangFromDict(WBLINK3);
    readTextAloud(txt, lg);
    return false;
  }
  if (e.which == 84) { // T : translate sentence
    let popup = false;
    let dict_link = WBLINK3;
    if (WBLINK3.startsWith('*')) {
      popup = true;
      dict_link = substring(dict_link, 1);
    }
    if (dict_link.startsWith('ggl.php')) {
      dict_link = "http://" + dict_link;
    }
    let open_url = true;
    let final_url;
    try {
      final_url = new URL(dict_link);
      popup |= final_url.searchParams.has("lwt_popup");
    } catch (err) {
      if (err instanceof TypeError) {
        open_url = false;
      }
    }
    if (popup) {
      owin('trans.php?x=1&i=' + ord + '&t=' + TID);
    } else if (open_url) {
      showRightFrames(undefined, 'trans.php?x=1&i=' + ord + '&t=' + TID);
    }
    return false;
  }
  if (e.which == 65) { // A : set audio pos.
    let p = curr.attr('data_pos');
    const t = parseInt($('#totalcharcount').text(), 10);
    if (t == 0) return true;
    p = 100 * (p - 5) / t;
    if (p < 0) p = 0;
    if (typeof (window.parent.frames.h.new_pos) === 'function') { 
      window.parent.frames.h.new_pos(p); 
    } else { 
      return true; 
    }
    return false;
  }
  if (e.which == 71) { //  G : edit term and open GTr
    dict = '&nodict';
    setTimeout(function () {
      let target_url = WBLINK3;
      let popup = false;
      popup = target_url.startsWith('*');
      try {
        const final_url = new URL(target_url);
        popup |= final_url.searchParams.has('lwt_popup');
      } catch (err) {
        if (!(err instanceof TypeError)) {
          throw err;
        }
      }
      if (popup) { 
        owin(createTheDictUrl(target_url, txt)); 
      } else {
          showRightFrames(undefined, createTheDictUrl(target_url, txt));
      }
    }, 10);
  }
  if (e.which == 69 || e.which == 71) { //  E / G: edit term
    let url = '';
    if (curr.hasClass('mword')) {
      url = 'edit_mword.php?wid=' + wid + '&len=' + curr.attr('data_code') + 
      '&tid=' + TID + '&ord=' + ord + dict;
    } else if (stat == '0') {
			url =	'edit_word.php?wid=&tid=' + TID + '&ord=' + ord + dict;
    } else {
			url =	'edit_word.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + dict;
    }
    showRightFrames(url);
    return false;
  }
  return true;
}

/**
 * Save a setting to the database.
 * 
 * @param {string} k Setting name as a key 
 * @param {string} v Setting value 
 */
function do_ajax_save_setting (k, v) {
  $.post(
    'inc/ajax.php', 
    {
      action: '',
      action_type: 'save_setting',
      k: k,
      v: v
    }
  );
}

/**
 * Assign the display value of a select element to the value element of another input. 
 * 
 * @param {elem} select_elem 
 * @param {elem} input_elem 
 */
function quick_select_to_input(select_elem, input_elem)
{
  let val = select_elem.options[select_elem.selectedIndex].value; 
  if (val != '') 
    input_elem.value = val; 
  select_elem.value = '';
}

/**
 * Return an HTML group of options to add to a select field.
 * 
 * @param {string[]} paths     All paths (files and folders)
 * @param {string[]} folders   Folders paths, should be a subset of paths
 * @param {string}   base_path Base path for LWT to append
 * 
 * @returns {HTMLOptionElement[]} List of options to append to the select.
 */
function select_media_path(paths, folders, base_path)
{
  let options = [], temp_option = document.createElement('option');
  temp_option.value = "";
  temp_option.text = "[Choose...]";
  options.push(temp_option);
  for (let i = 0; i < paths.length; i++) {
    temp_option = document.createElement('option')
    if (folders.includes(paths[i])) {
      temp_option.setAttribute("disabled", "disabled");
      temp_option.text = '-- Directory: ' + paths[i] + '--';
    } else {
      temp_option.value = base_path + "/" + paths[i];
      temp_option.text = paths[i];
    }
    options.push(temp_option);
  }
  return options;
}

/**
 * Process the received data from media selection query
 * 
 * @param {Object} data Received data as a JSON object 
 */
function media_select_receive_data(data) {
    $('#mediaSelectLoadingImg').css("display", "none");
    if (data["error"] !== undefined) {
      let msg;
      if (data["error"] == "not_a_directory") {
        msg = '[Error: "../' + data["base_path"] + '/media" exists, but it is not a directory.]';
      } else if (data["error"] == "does_not_exist") {
        msg = '[Directory "../' + data["base_path"] + '/media" does not yet exist.]';
      } else {
        msg = "[Unknown error!]";
      }
      $('#mediaSelectErrorMessage').text(msg);
      $('#mediaSelectErrorMessage').css("display", "inherit");
    } else {
      const options = select_media_path(data["paths"], data["folders"], data["base_path"]);
      $('#mediaselect select').empty();
      for (let i = 0; i < options.length; i++) {
        $('#mediaselect select').append(options[i]);
      }
      $('#mediaselect select').css("display", "inherit");
    }
}

/**
 * Perform an AJAX query to retrieve and display the media files path.
 */
function do_ajax_update_media_select () {
  $('#mediaSelectErrorMessage').css("display", "none");
  $('#mediaselect select').css("display", "none");
  $('#mediaSelectLoadingImg').css("display", "inherit");
  $.getJSON(
    'inc/ajax.php',
    {
      action: "query",
      action_type: "media_paths"
    },
    media_select_receive_data
  );
}

/**
 * Prepare am HTML element that formats the sentences
 * 
 * @param {JSON}   sentences    A list of sentences to display. 
 * @param {string} click_target The selector for the element that should change value on click
 * @returns {HTMLElement} A formatted group of sentences
 */
function display_example_sentences(sentences, click_target)
{
  let img, clickable, parentDiv;
  const outElement = document.createElement("div");
  for (let i = 0; i < sentences.length; i++) {
    // Add the checbox
    img = document.createElement("img");
    img.src = "icn/tick-button.png";
    img.title = "Choose";
    // Clickable element
    clickable = document.createElement('span');
    clickable.classList.add("click");
    // Doesn't feel the right way to do it
    clickable.setAttribute(
      "onclick", 
      "{" + 
      click_target + ".value = '" + sentences[i][1].replaceAll("'", "\\'") +"';makeDirty();}"
    );
    clickable.appendChild(img);
    // Create parent
    parentDiv = document.createElement("div");
    parentDiv.appendChild(clickable);
    parentDiv.innerHTML += "&nbsp; " + sentences[i][0];
    // Add to the output
    outElement.appendChild(parentDiv);
  }
  return outElement;
}

/**
 * Get and display the sentences containing specific word.
 * 
 * @param {int}    lang Language ID 
 * @param {string} word Term text (the looked for term) 
 * @param {string} ctl  Selector for the element to edit on click
 * @param {int}    woid Term id (word or multi-word)
 * @returns {undefined}
 */
function do_ajax_show_sentences (lang, word, ctl, woid) {
  $('#exsent-interactable').css("display", "none");
  $('#exsent-waiting').css("display", "inherit");

  $.getJSON(
    'inc/ajax.php', 
    { 
      action: "query",
      action_type: "example_sentences",
      lid: lang, 
      word_lc: word,
      wid: woid
    },
    function (data) {
      $('#exsent-waiting').css("display", "none");
      $('#exsent-sentences').css("display", "inherit");
      const new_element = display_example_sentences(data, ctl);
      $('#exsent-sentences').append(new_element);
    }
  );
}

function do_ajax_show_similar_terms () {
  $('#simwords').html('<img src="icn/waiting2.gif" />');
  $.post(
    'inc/ajax.php',
    {
      "action": "similar_terms",
      "action_type": "similar_terms",
      "simterms_lgid": $('#langfield').val(),
      "simterms_word": $('#wordfield').val()
    },
    function (data) {
      $('#simwords').html(data.similar_terms);
    },
    "json"
  )
}

/**
 * Update WORDCOUNTS in with an AJAX request.
 * 
 * @returns {undefined}
 */
function do_ajax_word_counts () {
  const t = $('.markcheck').map(function () { 
    return $(this).val(); 
  })
  .get().join(',');
  $.getJSON(
    'inc/ajax.php', 
    {
      action: "query",
      action_type: "texts_statistics",
      texts_id: t 
    },
    function (data) {
      WORDCOUNTS = data;
      word_count_click();
      $('.barchart').removeClass('hide');
    }
  );
}

/**
 * Set a unique item in barchart to reflect how many words are known.
 * 
 * @returns {undefined}
 */
function set_barchart_item() {
  const id = $(this).find('span').first().attr('id').split('_')[2];
  /** @var {int} v Number of terms in the text */
  let v;
  if (SUW & 16) {
    v = parseInt(WORDCOUNTS.expru[id] || 0, 10) + 
    parseInt(WORDCOUNTS.totalu[id], 10);
  } else {
    v = parseInt(WORDCOUNTS.expr[id] || 0, 10) + 
    parseInt(WORDCOUNTS.total[id], 10);
  }
  $(this).children('li').each(function () {
    /** {number} Word count in the category */
    let cat_word_count = parseInt($(this).children('span').text(), 10);
    /*
    Linear version
		const h = (v - $(this).children('span').text()) * 25 / v;
    */
    /* 
    Logarithmic version
    (25 / v) is vocab per pixel
    log scale so the size scaled becomes Math.log(($(this).children('span').text()))
    so the total height corresponding to text vocab after scaling should be 
    Math.log(v) the proportion of column height to box height is thus 
    (Math.log(($(this).children('span').text())) / Math.log(v))
    putting this back in pixel, we get 
    (Math.log(($(this).children('span').text())) / Math.log(v)) * 25 
    should be the column height
    so (25 - (Math.log(($(this).children('span').text())) / Math.log(v)) * 25) 
    is the intended border top size.
    */
    // Avoid to put 0 in logarithm
    cat_word_count += 1;
    v += 1;
    const h = 25 - Math.log(cat_word_count) / Math.log(v) * 25;
    $(this).css('border-top-width', h + 'px');
  });
}

/**
 * Set the number of words known in a text (in edit_texts.php main page).
 * 
 * @returns {undefined}
 */
function set_word_counts () {
  $.each(WORDCOUNTS.totalu, function (key, value) {
    let knownu, known, todo, stat0;
    knownu = known = todo = stat0 = 0;
    const expr = WORDCOUNTS.expru[key] ? parseInt((SUW & 2) ? WORDCOUNTS.expru[key] : WORDCOUNTS.expr[key]) : 0;
    if (!WORDCOUNTS.stat[key]) {
      WORDCOUNTS.statu[key] = WORDCOUNTS.stat[key] = [];
    }
    $('#total_' + key).html((SUW & 1 ? value : WORDCOUNTS.total[key]));
    $.each(WORDCOUNTS.statu[key], function (k, v) {
      if (SUW & 8)
        $('#stat_' + k + '_' + key).html(v); 
      knownu += parseInt(v);
    });
    $.each(WORDCOUNTS.stat[key], function (k, v) {
      if (!(SUW & 8))
        $('#stat_' + k + '_' + key).html(v); 
      known += parseInt(v);
    });
    $('#saved_' + key).html(known ? ((SUW & 2 ? knownu : known) - expr + '+' + expr) : 0);
    if (SUW & 4) {
      todo = parseInt(value) + parseInt(WORDCOUNTS.expru[key] || 0) - parseInt(knownu);
    } else {
      todo = parseInt(WORDCOUNTS.total[key]) + parseInt(WORDCOUNTS.expr[key] || 0) - parseInt(known);
    }
    $('#todo_' + key).html(todo);

    // added unknown percent
    // console.log(SUW);
    if (SUW & 8) {
      unknowncount = parseInt(value) + parseInt(WORDCOUNTS.expru[key] || 0) - parseInt(knownu);
      unknownpercent = Math.round(unknowncount * 10000 / (knownu + unknowncount)) / 100;
    } else {
      unknowncount = parseInt(WORDCOUNTS.total[key]) + parseInt(WORDCOUNTS.expr[key] || 0) - parseInt(known);
      unknownpercent = Math.round(unknowncount * 10000 / (known + unknowncount)) / 100;
    }
    $('#unknownpercent_' + key).html(unknownpercent == 0 ? 0 : unknownpercent.toFixed(2));
    // end here

    if (SUW & 16) {
      stat0 = parseInt(value) + parseInt(WORDCOUNTS.expru[key] || 0) - parseInt(knownu);
    } else {
      stat0 = parseInt(WORDCOUNTS.total[key]) + parseInt(WORDCOUNTS.expr[key] || 0) - parseInt(known);
    }
    $('#stat_0_' + key).html(stat0);
  });
  $('.barchart').each(set_barchart_item);
}

/**
 * Handle the click event to switch between total and 
 * unique words count in edit_texts.php.
 * 
 * @returns {undefined}
 */
function word_count_click () {
  $('.wc_cont').children().each(function () {
    if (parseInt($(this).attr('data_wo_cnt')) == 1) {
      $(this).html('u');
    } else {
      $(this).html('t');
    }
    SUW = (parseInt($('#chart').attr('data_wo_cnt')) << 4) + 
    (parseInt($('#unknownpercent').attr('data_wo_cnt')) << 3) + 
    (parseInt($('#unknown').attr('data_wo_cnt')) << 2) + 
    (parseInt($('#saved').attr('data_wo_cnt')) << 1) + 
    (parseInt($('#total').attr('data_wo_cnt')));
    set_word_counts();
  });
}

/**
 * Create a radio button with a candidate choice for a term annotation.
 * 
 * @param {string} curr_trans Current anotation (translation) set for the term 
 * @param {string} trans_data All the useful data for the term
 * @returns {string} An HTML-formatted option
 */
function translation_radio(curr_trans, trans_data) 
{
  if (trans_data.wid === null) {
    return "";
  }
  const trim_trans = curr_trans.trim();
  if (trim_trans == '*' || trim_trans == '') {
    return "";
  }
  const set = trim_trans == trans_data.trans;
  const option = `<span class="nowrap">
    <input class="impr-ann-radio" ` + 
      (set ? 'checked="checked" ' : '') + 'type="radio" name="rg' +
      trans_data.ann_index + `" value="` + escape_html_chars(trim_trans) + `" /> 
          &nbsp; ` + escape_html_chars(trim_trans) + `
  </span>
  <br />`;
  return option;
}

/**
 * When a term translation is edited, recreate it's annotations.
 * 
 * @param {Object} trans_data Useful data for this term
 * @param {int}    text_id    Text ID
 */
function edit_term_ann_translations(trans_data, text_id)
{
  const widset = trans_data.wid !== null;
  // First create a link to edit the word in a new window
  let edit_word_link;
  if (widset) {
    const req_arg = $.param({
      fromAnn: "$(document).scrollTop()",
      wid: trans_data.wid,
      ord: trans_data.term_ord,
      tid: text_id
    })
    edit_word_link = `<a name="rec${trans_data.ann_index}"></a>
    <span class="click"
    onclick="oewin('edit_word.php?` + escape_html_chars(req_arg) + `');">
          <img src="icn/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
      </span>`;
  } else {
    edit_word_link = '&nbsp;';
  }
  $(`#editlink${trans_data.ann_index}`).html(edit_word_link);
  // Now edit translations (if necessary)
  let translations_list = "";
  trans_data.translations.forEach(
    function (candidate_trans) {
      translations_list += translation_radio(candidate_trans, trans_data);
    }
  );

  const select_last = trans_data.translations.length == 0;
  // Empty radio button and text field after the list of translations
  translations_list += `<span class="nowrap">
  <input class="impr-ann-radio" type="radio" name="rg${trans_data.ann_index}" ` + 
  (select_last ? 'checked="checked" ' : '') + `value="" />
  &nbsp;
  <input class="impr-ann-text" type="text" name="tx${trans_data.ann_index}` + 
    `" id="tx${trans_data.ann_index}" value="` +
    (select_last ? escape_html_chars(curr_trans) : '') + 
  `" maxlength="50" size="40" />
   &nbsp;
  <img class="click" src="icn/eraser.png" title="Erase Text Field" 
  alt="Erase Text Field" 
  onclick="$('#tx${trans_data.ann_index}').val('').trigger('change');" />
    &nbsp;
  <img class="click" src="icn/star.png" title="* (Set to Term)" 
  alt="* (Set to Term)" 
  onclick="$('#tx${trans_data.ann_index}').val('*').trigger('change');" />
  &nbsp;`;
  // Add the "plus button" to add a translation
  if (widset) {
    translations_list += 
    `<img class="click" src="icn/plus-button.png" 
    title="Save another translation to existent term" 
    alt="Save another translation to existent term" 
    onclick="addTermTranslation(${trans_data.wid}, ` +
      `'#tx${trans_data.ann_index}', '',${trans_data.lang_id});" />`; 
  } else { 
    translations_list += 
    `<img class="click" src="icn/plus-button.png" 
    title="Save translation to new term" 
    alt="Save translation to new term" 
    onclick="addTermTranslation(0, '#tx${trans_data.ann_index}',` +
      `${trans_data.term_lc},${trans_data.lang_id});" />`; 
  }
  translations_list += `&nbsp;&nbsp;
  <span id="wait${trans_data.ann_index}">
      <img src="icn/empty.gif" />
  </span>
  </span>`;
  $(`#transsel${trans_data.ann_index}`).html(translations_list);
}

/**
 * Load the possible translations for a word.
 * 
 * @param {int} pagepos Position to scroll to 
 * @param {string} word Word to get annotations
 * @returns 
 */
function do_ajax_edit_impr_text(pagepos, word) {
  // Special case, on empty word reload the main annotations form
  if (word == '') {
    $('#editimprtextdata').html('<img src="icn/waiting2.gif" />');
    location.reload();
    return;
  }
  // Load the possible translations for a word
  const textid = $('#editimprtextdata').attr('data_id');
  $.getJSON(
    'inc/ajax.php', 
    {
      action: "query",
      action_type: "term_translations",
      text_id: textid, 
      term_lc: word 
    },
    function (data) {
      if ("error" in data) {
        alert(data["error"]);
      } else {
        edit_term_ann_translations(data, textid);
        $.scrollTo(pagepos);
        $('input.impr-ann-text').on('change', changeImprAnnText);
        $('input.impr-ann-radio').on('change', changeImprAnnRadio);
      }
    }
  );
}

/**
 * Show the right frames if found, and can load an URL in those frames
 * 
 * @param {string|undefined} roUrl Upper-right frame URL to laod 
 * @param {string|undefined} ruUrl Lower-right frame URL to load
 * @returns {boolean} true if frames were found, false otherwise
 */
function showRightFrames(roUrl, ruUrl) {
  if (roUrl !== undefined) {
    top.frames.ro.location.href = roUrl;
  }
  if (ruUrl !== undefined) {
    top.frames.ru.location.href = ruUrl;
  }
  if ($('#frames-r').length) {
    $('#frames-r').animate({right: '5px'});
    return true;
  }
  return false;
}

/**
 * Hide the right frames if found.
 * 
 * @returns {boolean} true if frames were found, false otherwise
 */
function hideRightFrames() {
  if ($('#frames-r').length) {
    $('#frames-r').animate({right: '-100%'});
    return true;
  }
  return false;
}

/**
 * Hide the right frame and any popups.
 *
 * Called from several places: insert_word_ignore.php,
 * set_word_status.php, delete_word.php, etc.
 * 
 * @returns {undefined}
 */
function cleanupRightFrames() {

  // A very annoying hack to get right frames to hide correctly.
  // Calling hideRightFrames directly in window.parent.setTimeout
  // does  //not work* for some reason ... when called that way,
  // in hideRightFrames $('#frames-r').length is always 0.  I'm not
  // sure why.  Using the mytimeout method lets the js find the
  // element at runtime, and then it's clicked, invoking the function
  // hideRightFrames, which then works.
  //
  // We have to use an anon function to ensure that the frames-r
  // gets resolved when the timeout fires.
  const mytimeout = function() {
    const rf = window.parent.document.getElementById('frames-r');
    rf.click();
  }
  window.parent.setTimeout(mytimeout, 800);

  window.parent.document.getElementById('frame-l').focus();
  window.parent.setTimeout(window.parent.cClick, 100);
}


/**
 * Play the success sound.
 *  
 * @returns {object} Promise on the status of sound
 */
function successSound() {
  document.getElementById('success_sound').pause();
  document.getElementById('failure_sound').pause();
  return document.getElementById('success_sound').play();
}

/**
 * Play the failure sound.
 *
 * @returns {object} Promise on the status of sound
 */
function failureSound() {
  document.getElementById('success_sound').pause();
  document.getElementById('failure_sound').pause();
  return document.getElementById('failure_sound').play();
}

const lwt = {

  /**
   * Prepare the action so that a click switches between 
   * unique word count and total word count.
   * 
   * @returns {undefined}
   */
  prepare_word_count_click: function () {
    $('#total,#saved,#unknown,#chart,#unknownpercent')
    .on('click', function( event ) {
        $(this).attr('data_wo_cnt',parseInt($(this).attr('data_wo_cnt'))^1);
        word_count_click();
        event.stopImmediatePropagation();
    }).attr('title',"u: Unique Word Counts\nt: Total  Word  Counts");
    do_ajax_word_counts();
  },

  /**
   * Save the settings about unique/total words count.
   * 
   * @returns {undefined}
   */
  save_text_word_count_settings: function () {
      if (SUW == SHOWUNIQUE) {
          return;
      }
      const a = $('#total').attr('data_wo_cnt') + 
      $('#saved').attr('data_wo_cnt') + 
      $('#unknown').attr('data_wo_cnt') + 
      $('#unknownpercent').attr('data_wo_cnt') + 
      $('#chart').attr('data_wo_cnt');
      do_ajax_save_setting('set-show-text-word-counts', a);
  }
}

// Present data in a handy way, for instance in a form
$.fn.serializeObject = function () {
  const o = {};
  const a = this.serializeArray();
  $.each(a, function () {
    if (o[this.name] !== undefined) {
      if (!o[this.name].push) {
        o[this.name] = [o[this.name]];
      }
      o[this.name].push(this.value || '');
    } else {
      o[this.name] = this.value || '';
    }
  });
  return o;
};

/**
 * Wrap the radio buttons into stylised elements.
 */
function wrapRadioButtons() {
  $(
    ':input,.wrap_checkbox span,.wrap_radio span,a:not([name^=rec]),select,' + 
    '#mediaselect span.click,#forwbutt,#backbutt'
  ).each(function (i) { $(this).attr('tabindex', i + 1); });
  $('.wrap_radio span').on('keydown', function (e) {
    if (e.keyCode == 32) {
      $(this).parent().parent().find('input[type=radio]').trigger('click');
      return false;
    }
  });
}

/**
 * Do a lot of different DOM manipulations
 */
function prepareMainAreas() {
  $('.edit_area').editable('inline_edit.php',
    {
      type: 'textarea',
      indicator: '<img src="icn/indicator.gif">',
      tooltip: 'Click to edit...',
      submit: 'Save',
      cancel: 'Cancel',
      rows: 3,
      cols: 35
    }
  );
  $('select').wrap("<label class='wrap_select'></label>");
  $('form').attr('autocomplete', 'off');
  $('input[type="file"]').each(function () {
    if (!$(this).is(':visible')) {
      $(this).before('<button class="button-file">Choose File</button>')
			 .after('<span style="position:relative" class="fakefile"></span>')
			 .on('change', function () {
          let txt = this.value.replace('C:\\fakepath\\', '');
          if (txt.length > 85)txt = txt.replace(/.*(.{80})$/, ' ... $1');
          $(this).next().text(txt);
			 })
			 .on('onmouseout', function () {
          let txt = this.value.replace('C:\\fakepath\\', '');
          if (txt.length > 85)txt = txt.replace(/.*(.{80})$/, ' ... $1');
          $(this).next().text(txt);
			 });
    }
  });
  $('input[type="checkbox"]').each(function (z) {
    if (typeof z === 'undefined')z = 1;
    if (typeof $(this).attr('id') === 'undefined') {
      $(this).attr('id', 'cb_' + z++);
    }
    $(this).after(
      '<label class="wrap_checkbox" for="' + $(this).attr('id') + 
      '"><span></span></label>'
    );
  });
  $('span[class*="tts_"]').on('click', function () {
    const lg = $(this).attr('class').replace(/.*tts_([a-zA-Z-]+).*/, '$1');
    const txt = $(this).text();
    readRawTextAloud(txt, lg);
  });
  $(document).on('mouseup', function () {
    $('button,input[type=button],.wrap_radio span,.wrap_checkbox span')
    .trigger('blur');
  });
  $('.wrap_checkbox span').on('keydown', function (e) {
    if (e.keyCode == 32) {
      $(this).parent().parent().find('input[type=checkbox]').trigger('click');
      return false;
    }
  });
  $('input[type="radio"]').each(function (z) {
    if (z === undefined) {
      z = 1;
    }
    if (typeof $(this).attr('id') === 'undefined') {
      $(this).attr('id', 'rb_' + z++);
    }
    $(this).after(
      '<label class="wrap_radio" for="' + $(this).attr('id') + 
      '"><span></span></label>'
    );
  });
  $('.button-file').on('click', function () { 
    $(this).next('input[type="file"]').trigger('click'); 
    return false; 
  });
  $('input.impr-ann-text').on('change', changeImprAnnText);
  $('input.impr-ann-radio').on('change', changeImprAnnRadio);
  $('form.validate').on('submit', check);
  $('input.markcheck').on('click', markClick);
  $('.confirmdelete').on('click', confirmDelete);
  $('textarea.textarea-noreturn').on('keydown', textareaKeydown);
  // Resizable from right frames
  $('#frames-r').resizable({
    handles: "w",
    stop: function (_event, ui) {
      // Resize left frames
      $('#frames-l').css('width', ui.position.left - 20);
      // Save settings
      do_ajax_save_setting(
        'set-text-l-framewidth-percent', 
        Math.round($('#frames-l').width() / $(window).width() * 100)
      );
    }
  });
  $('#termtags').tagit(
    {
      beforeTagAdded: function (_event, ui) {
        return !containsCharacterOutsideBasicMultilingualPlane(ui.tag.text());
      },
      availableTags: TAGS,
      fieldName: 'TermTags[TagList][]'
    }
  );
  $('#texttags').tagit(
    {
      beforeTagAdded: function (_event, ui) {
        return !containsCharacterOutsideBasicMultilingualPlane(ui.tag.text());
      },
      availableTags: TEXTTAGS,
      fieldName: 'TextTags[TagList][]'
    }
  );
  markClick();
  setTheFocus();
  if (
    $('#simwords').length > 0 && $('#langfield').length > 0 && 
    $('#wordfield').length > 0
    ) {
  	$('#wordfield').on('blur', do_ajax_show_similar_terms);
  	do_ajax_show_similar_terms();
  }
  window.setTimeout(noShowAfter3Secs, 3000);
}

$(window).on('load', wrapRadioButtons);

$(document).ready(prepareMainAreas);
