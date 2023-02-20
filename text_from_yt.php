<?php 
/**
 * \file
 * \brief From to import a file from YouTube.
 * 
 * You need a personal YouTube API key. 
 */
namespace text_from_youtube;

require_once 'inc/settings.php';

/**
 * @var string|null YT_API_KEY Text from YouTube API key
 * 
 * You can change the key here.
 */
define('YT_API_KEY', null);

function do_form_fragment()
{
?>
<tr>
  <td class="td1 right">YouTube Video Id:</td>
  <td class="td1">
    <input type='text' id='ytVideoId'>
    <input type='button' value='Fetch Text from Youtube' onclick='getYtTextData()'>
    <input type='text' id='ytApiKey' value='<?php echo YT_API_KEY ?>' style='visibility:collapse' aria='hidden'>
    <p id='ytDataStatus'></p>
  </td>
</tr>
<?php
}

function do_js() 
{
  ?>
<script>
const API_KEY = document.getElementById('ytApiKey').value;
function setYtDataStatus(msg) {
  const el = document.getElementById('ytDataStatus');
  el.textContent = msg;
}

function getYtTextData() {
  setYtDataStatus('Fetching YouTube data...');
  const ytVideoId = document.getElementById('ytVideoId').value;
  const url = `https://www.googleapis.com/youtube/v3/videos?part=snippet&id=${ytVideoId}&key=${API_KEY}`;
  console.log('fetching', url);
  const req = new XMLHttpRequest();
  req.onload = function(e) {
    const res = JSON.parse(req.responseText);
    if (res.items.length == 0) {
      setYtDataStatus('No videos found.');
    } else {
      setYtDataStatus('Success!');
      const snippet = res.items[0].snippet;
      $('[name=TxTitle]')[0].value = snippet.title;
      $('[name=TxText]')[0].value = snippet.description;
      $('[name=TxSourceURI]')[0].value = `https://youtube.com/watch?v=${ytVideoId}`;
    }
  };
  req.open('GET', url);
  req.send();

}
</script>
  <?php
}