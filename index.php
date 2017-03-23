<?php
  $config = require 'config/config.php';
  if ($config['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL); 
  } else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0); 
  }

  require_once 'class/Session.php';
  require_once 'class/Downloader.php';
  require_once 'class/FileHandler.php';
  
  $session = Session::getInstance();
  $file = new FileHandler;
  
  if(isset($_GET['jobs']))
  {
    $jsonString = "{ \"jobs\": [";
    foreach(Downloader::get_current_background_jobs() as $key)
            {
              $jsonString .= "{ \"file\": ".$key['file'].", ";
              $jsonString .= "\"status\": ".$key['status'].", ";
              $jsonString .= "\"site\": ".$key['site'].", ";
              $jsonString .= "\"type\": ".$key['type'].", ";
              $jsonString .= "\"pid\": ".$key['pid'].", ";
              $jsonString .= "\"url\": ".$key['url'];
              $jsonString .= "},";
            }
    $jsonString = trim($jsonString, ",");
    $jsonString .= "],";
    
    $jsonString .= "\"finished\": [";
    foreach(Downloader::get_finished_background_jobs() as $key)
            {
              $jsonString .= "{ \"file\": ".$key['file'].", ";
              $jsonString .= "\"status\": ".$key['status'].", ";
              $jsonString .= "\"site\": ".$key['site'].", ";
              $jsonString .= "\"type\": ".$key['type'].", ";
              $jsonString .= "\"pid\": ".$key['pid'].", ";
              $jsonString .= "\"url\": ".$key['url'];
              $jsonString .= "},";
            }
    $jsonString = trim($jsonString, ",");
    $jsonString .= "],";
    $jsonString .= "\"logURL\": ".json_encode($config['logURL'])." }";
    echo $jsonString;
    die();
  }
  
  if(isset($_GET["delete"]))
  {
    $file->delete($_GET["delete"], $_GET["type"]);
    if ($_GET["type"] == "m")
      header("Location: index.php#music");
    else
      header("Location: index.php#videos");
  }
  
  if(isset($_GET['kill']) && !empty($_GET['kill']))
  {
    if ($_GET['kill'] === "all")
      Downloader::kill_them_all();
    else
      Downloader::kill_one_of_them($_GET['kill']);
    header("Location: index.php#downloads");
  }
  
  if(isset($_GET['clear']) && !empty($_GET['clear']))
  {
    if ($_GET['clear'] === "recent")
      Downloader::clear_finished();
    else
      Downloader::clear_one_finished($_GET['clear']);
    header("Location: index.php#downloads");
  }
  
  if(isset($_GET['restart']) && !empty($_GET['restart']))
  {
    Downloader::restart_download($_GET['restart']);
    header("Location: index.php#downloads");
  }
  
  if(isset($_POST['urls']) && !empty($_POST['urls']))
  {
    $get_parms = "?";
    $audio_only = false;
    $audio_format = "--audio-format mp3";
    $dl_format = "-f best";
    
    if(isset($_POST['audio']) && !empty($_POST['audio']))
    {
      $audio_only = true;
      $get_parms .= "audio=true&";
    }
    
    if(isset($_POST['audio_format']) && !empty($_POST['audio_format']))
    {
      $audio_format = "--audio-format " . $_POST['audio_format'];
      $get_parms .= "audio_format=".$_POST['audio_format']."&";
    }
    
    if(isset($_POST['format']) && !empty($_POST['format']))
    {
      $dl_format = "-f " . $_POST['format'];
      $get_parms .= "format=".$_POST['format']."&";
    }
    
    $downloader = new Downloader($_POST['urls'], $audio_only, $dl_format, $audio_format);
    
    if(!isset($_SESSION['errors']))
    {
      header("Location: index.php".$get_params."#".$config['redirectAfterSubmit']);
    }
  }
  
  $urlvalue = "";
  if (isset($_GET['url']))
  {
    $urlvalue = " value=\"".urldecode($_GET['url'])."\"";
    if (isset($_GET['auto_submit']))
    {
      $audio_format = "--audio-format mp3";
      $dl_format = "-f best";
      if (isset($_GET["audio"]) && $_GET["audio"] == "true")
        $audio_only = true;
      else
        $audio_only = false;
      
      if(isset($_GET['audio_format']) && !empty($_GET['audio_format']))
        $audio_format = "--audio-format " . $_GET['audio_format'];
    
      if(isset($_GET['format']) && !empty($_GET['format']))
        $dl_format = "-f " . $_GET['format'];
      
      $downloader = new Downloader(urldecode($_GET['url']), $audio_only, $dl_format, $audio_format);
     
      if(isset($_SESSION['errors']) && $_SESSION['errors'] > 0)
        header("Location: index.php?submitstatus=error&errors=".implode(", ",$_SESSION['errors']));
      else
        header("Location: index.php?submitstatus=success");
    }
  }

  if (isset($_GET['submitstatus']))
  {
    if ($_GET['submitstatus'] == "success")
    {
      die("<b>Video has been submitted successfully</b><br /><br /><a href=\"javascript:window.close();\">Close this window</a> or <a href=\"index.php#downloads\">Check the status of the download</a>");
    } else {
      echo("<b>Something went wrong. You can try to submit the video manually.</b><br /><br />");
      echo("Error: ".$_GET['errors']."<br /><br />");
      die("<a href=\"javascript:window.close();\">Close this window</a> or <a href=\"index.php\">Try to submit the download manually</a>");
    }
  }
  
  $siteTheme = $config['siteTheme'];
  if (isset($_GET['theme']))
    $siteTheme = $_GET['theme'];
  require 'views/header.php';
  
  if (@$_GET["audio"]=="true" && !$config['disableExtraction']) {
    $audio_check = " checked=\"checked\"";
    $video_form_style = " style=\"display: none;\"";
    $audio_form_style = "";
  } else {
    $audio_check = "";
    $video_form_style = "";
    $audio_form_style = "style=\"display: none;\"";
  }

  $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
  $uri_parts = $uri_parts[0];
  $uri_parts = explode('#', $uri_parts, 2);
  $baseuri = 'http://' . $_SERVER['HTTP_HOST'] . $uri_parts[0];
  $bookmarkletvideo = "javascript:(function(){f='".$baseuri."?url='+encodeURIComponent(window.location.href);a=function(){if(!window.open(f))location.href=f};if(/Firefox/.test(navigator.userAgent)){setTimeout(a,0)}else{a()}})()";
  $bookmarkletmusic = "javascript:(function(){f='".$baseuri."?audio=true&url='+encodeURIComponent(window.location.href);a=function(){if(!window.open(f))location.href=f};if(/Firefox/.test(navigator.userAgent)){setTimeout(a,0)}else{a()}})()";
  ?>
<div class="modal fade" id="custom_bookmarklet" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2" aria-hidden="true">
  <div class="modal-dialog" style="z-index: 10000;">
    <div class="modal-content">
      <div class="modal-header">
        <button class="close" type="button" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">Custom Bookmarklet</h4>
      </div>
      <div class="modal-body">
        <input id="bml_base_uri" type="hidden" value="<?php echo($baseuri); ?>" />
        Select the options below and then drag the button to your bookmarks toolbar.<br /><br />
        <label<?php echo($config['disableExtraction'] ? " style=\"display: none;\"" : ""); ?>>
          <input id="bml_audio_convert" onclick="updateBookmarklet();" type="checkbox" name="bml_audio"> Convert to Audio
        </label><br />
        <label id="bml_audio_group" style="display: none;">
          Audio Format:
          <select style="width: 75px;" name="bml_audio_format" id="bml_audio_format" onchange="updateBookmarklet();">
            <option value="mp3" selected="selected">mp3</option>
            <option value="aac">aac</option>
            <option value="vorbis">vorbis</option>
            <option value="m4a">m4a</option>
            <option value="opus">opus</option>
            <option value="wav">wav</option>
          </select>
        </label>
        <label id="bml_video_group">
          Video Quality:
          <select style="width: 75px;" name="bml_format" id="bml_format" onchange="updateBookmarklet();">
            <option value="best" selected="selected">Best</option>
            <option value="worst">Smallest</option>
          </select>
        </label>
        <br />
        <label>
          <input id="bml_auto_submit" onclick="updateBookmarklet();" type="checkbox" name="bml_auto_submit"> Start download immediately
        </label>
        <br />
        <label>
          <input id="bml_use_custom_text" onclick="updateBookmarklet();" type="checkbox" name="bml_use_custom_text"> Use a custom text for the bookmarklet:&nbsp;
        </label>
        <input id="bml_custom_text" onkeyup="updateBookmarklet(true);" type="text" name="bml_custom_text" value="Download Video" />
        <br />Drag the below button to your bookmarks toolbar.<br />
        <a id="cust_bml" href="<?php echo($bookmarkletvideo); ?>" style="width: 300px;" class="btn btn-primary">Download Video</a>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Done</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="z-index: 10000;">
    <div class="modal-content">
      <div class="modal-header">
        <button class="close" type="button" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">Please confirm</h4>
      </div>
      <div class="modal-body">
        This action cannot be undone!<br /><br />Are you sure you want to continue?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
        <a class="btn btn-danger btn-ok">Yes</a>
      </div>
    </div>
  </div>
</div>
<script>
  $('#confirm-delete').on('show.bs.modal', function(e) {
    $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
  });
</script>
<div class="container" style="margin-bottom: 50px;">
  
  <ul id="mainnav" class="nav nav-pills">
    <li class="active"><a id="home_link" href="#home" data-toggle="tab" aria-expanded="true">Home</a></li>
    <li><a id="dl_link" href="#downloads" data-toggle="tab" aria-expanded="false">Downloading</a></li>
    <li><a id="vid_link" href="#videos" data-toggle="tab" aria-expanded="false">Videos</a></li>
    <li><a id="music_link" href="#music" data-toggle="tab" aria-expanded="false">Music</a></li>
  </ul>
  <div id="myTabContent" class="tab-content">
    <div class="tab-pane fade active in" id="home">
      <div class="row">
        <br />
        <h1 style="text-align: center;"><?php echo($config['siteName']); ?></h1><br />
        <?php
  
  if(isset($_SESSION['errors']) && $_SESSION['errors'] > 0)
  {
    foreach ($_SESSION['errors'] as $e)
             {
               echo "<div class=\"alert alert-warning\" role=\"alert\">$e</div>";
             }
  }
  ?>
        <form id="download-form" class="form-horizontal" action="index.php" method="post">          
          <div class="form-group">
            <div class="col-md-12">
              <input class="form-control" id="url" name="urls"<?php echo($urlvalue); ?> placeholder="Enter the URL to the video you want to download. If you want to enter more that one please separate with a comma." type="text">
            </div>
            <div class="col-md-12">
              <div style="text-align: center;" class="checkbox">
                <button style="width: 300px;" type="submit" class="btn btn-primary">Download</button><br />
                <label<?php echo($config['disableExtraction'] ? " style=\"display: none;\"" : ""); ?>>
                  <input id="audio_convert" onclick="checkControls();"<?php echo($audio_check); ?>  type="checkbox" name="audio"> Convert to Audio
                </label>
                <label id="audio_group"<?php echo($audio_form_style); ?>>
                  Audio Format:
                  <select style="width: 75px;" name="audio_format" id="audio_format">
                    <option value="mp3"<?php echo($_GET["audio_format"]=="mp3" ? " selected=\"selected\"" : ""); ?>>mp3</option>
                    <option value="aac"<?php echo($_GET["audio_format"]=="aac" ? " selected=\"selected\"" : ""); ?>>aac</option>
                    <option value="vorbis"<?php echo($_GET["audio_format"]=="vorbis" ? " selected=\"selected\"" : ""); ?>>vorbis</option>
                    <option value="m4a"<?php echo($_GET["audio_format"]=="m4a" ? " selected=\"selected\"" : ""); ?>>m4a</option>
                    <option value="opus"<?php echo($_GET["audio_format"]=="opus" ? " selected=\"selected\"" : ""); ?>>opus</option>
                    <option value="wav"<?php echo($_GET["audio_format"]=="wav" ? " selected=\"selected\"" : ""); ?>>wav</option>
                  </select>
                </label>
                <label id="video_group"<?php echo($video_form_style); ?>>
                  Video Quality:
                  <select style="width: 75px;" name="format" id="format">
                    <option value="best"<?php echo($_GET["format"]=="best" ? " selected=\"selected\"" : ""); ?>>Best</option>
                    <option value="worst"<?php echo($_GET["format"]=="worst" ? " selected=\"selected\"" : ""); ?>>Smallest</option>
                  </select>
                </label>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="tab-pane fade" id="downloads">
      <div style="text-align: center;" class="row">
        <br /><br />
        <h4>Currently Downloading</h4>
        <table style="text-align: left;" class="table table-striped table-hover ">
          <thead>
            <tr>
              <th style="width: 10%; height:35px;">Site/Type</th>
              <th>File</th>
              <th style="width: 25%;">Status</th>
              <th style="width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody id="dlprogress">
            <?php
  echo "<tr>";
  echo "<td colspan=\"4\">Getting downloads please wait...</td>";
  echo "<td></td>";
  ?>
          </tbody>
        </table>
        <br /><br />
        <h4>Recently Completed</h4>
        <table style="text-align: left;" class="table table-striped table-hover ">
          <thead>
            <tr>
              <th style="width: 10%; height:35px;">Site/Type</th>
              <th>File/Playlist</th>
              <th style="width: 25%;">Status</th>
              <th style="width: 180px;">Actions</th>
            </tr>
          </thead>
          <tbody id="dlcompleted">
            <?php
  echo "<tr>";
  echo "<td colspan=\"4\">Getting downloads please wait...</td>";
  echo "<td></td>";
  ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="tab-pane fade" id="videos">
      <br /><br />
      <h4 style="text-align: center;">Downloaded Videos</h4>
      <table style="text-align: left;" class="table table-striped table-hover ">
        <thead>
          <tr>
            <th style="min-width:800px; height:35px">File</th>
            <th style="min-width:80px">Size</th>
            <th style="min-width:110px">Actions</th>
          </tr>
        </thead>
        <tbody>
      <?php
  $files = $file->listVideos();
  if(!empty($files))
  {
    $i = 0;
    $totalSize = 0;
    
    foreach($files as $f)
            {
              $fileDisplay = "<a href=\"".$file->get_downloads_link().'/'.$f["name"]."\" download>".$f["name"]."</a>";
              if ($config['downloadPath'] == "")
                $fileDisplay = $f["name"];
              echo "<tr>";
              echo "<td>".$fileDisplay."</td>";
              echo "<td>".$f["size"]."</td>";
              echo "<td><a data-href=\"?delete=$i&type=v\" data-toggle=\"modal\" data-target=\"#confirm-delete\" class=\"btn btn-danger btn-xs\">Delete</a></td>";
              echo "</tr>";
              $i++;
            }
  }
  else
  {
    echo "<tr>";
    echo "<td colspan=\"3\">No videos found.</td>";
    echo "</tr>";
  }
  ?>
        </tbody>
      </table>
      <br/>
      <br/>
    </div>
    <div class="tab-pane fade" id="music">
      <br /><br />
      <h4 style="text-align: center;">Downloaded Music</h4>
      <table style="text-align: left;" class="table table-striped table-hover ">
        <thead>
          <tr>
            <th style="min-width:800px; height:35px">File</th>
            <th style="min-width:80px">Size</th>
            <th style="min-width:110px">Actions</th>
          </tr>
        </thead>
        <tbody>
  <?php
  $files = $file->listMusics();
  if(!empty($files))
  {
    $i = 0;
    $totalSize = 0;
    
    foreach($files as $f)
            {
              $fileDisplay = "<a href=\"".$file->get_downloads_link().'/'.$f["name"]."\" download>".$f["name"]."</a>";
              if ($config['downloadPath'] == "")
                $fileDisplay = $f["name"];
              echo "<tr>";
              echo "<td>".$fileDisplay."</td>";
              echo "<td>".$f["size"]."</td>";
              echo "<td><a data-href=\"?delete=$i&type=m\" data-toggle=\"modal\" data-target=\"#confirm-delete\" class=\"btn btn-danger btn-xs\">Delete</a></td>";
              echo "</tr>";
              $i++;
            }
  }
  else
  {
    echo "<tr>";
    echo "<td colspan=\"3\">No music found.</td>";
    echo "</tr>";
  }
  ?>
        </tbody>
      </table>
      <br/>
      <br/>
    </div>
  </div>  
</div>
<script>
  $('#mainnav a').click(function(e) {
    e.preventDefault();
    var id = $(e.target).attr("href").substr(1);
    window.location.hash = id;
    $(this).tab('show');
  });

  var hash = window.location.hash;
  $('#mainnav a[href="' + hash + '"]').tab('show');
</script>
<?php
  unset($_SESSION['errors']);
  require 'views/footer.php';
  ?>
