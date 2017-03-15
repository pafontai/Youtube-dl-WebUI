<?php
  require_once 'class/Session.php';
  require_once 'class/Downloader.php';
  require_once 'class/FileHandler.php';
  $config = require 'config/config.php';
  
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
              $jsonString .= "\"type\": ".$key['type'];
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
              $jsonString .= "\"type\": ".$key['type'];
              $jsonString .= "},";
            }
    $jsonString = trim($jsonString, ",");
    $jsonString .= "]}";
    echo $jsonString;
    die();
  }
  
  if(isset($_GET["delete"]))
  {
    $file->delete($_GET["delete"], $_GET["type"]);
    header("Location: index.php");
  }
  
  if(isset($_GET['kill']) && !empty($_GET['kill']) && $_GET['kill'] === "all")
  {
    Downloader::kill_them_all();
    header("Location: index.php?tab=downloading");
  }
  
  if(isset($_GET['clear']) && !empty($_GET['clear']) && $_GET['clear'] === "recent")
  {
    Downloader::clear_finished();
    header("Location: index.php?tab=downloading");
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
      $dl_format = "--audio-format " . $_POST['audio_format'];
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
      header("Location: index.php".$get_parms."tab=".$config['redirectAfterSubmit']);
    }
  }
  $siteTheme = $config['siteTheme'];
  if (isset($_GET['theme']))
    $siteTheme = $_GET['theme'];
  require 'views/header.php';
  
  // Process get headers for page navigation
  $tab_home_class = "";
  $page_home_class = "";
  $tab_dl_class = "";
  $page_dl_class = "";
  $tab_vid_class = "";
  $page_vid_class = "";
  $tab_music_class = "";
  $page_music_class = "";
  switch (@$_GET["tab"]) {
    case "downloading":
      $tab_dl_class = "active";
      $page_dl_class = " active in";
      break;
    case "vid":
      $tab_vid_class = "active";
      $page_vid_class = " active in";
      break;
    case "music":
      $tab_music_class = "active";
      $page_music_class = " active in";
      break;
    default:
      $tab_home_class = "active";
      $page_home_class = " active in";
  }
  if (@$_GET["audio"]=="true" && !$config['disableExtraction']) {
    $audio_check = " checked=\"checked\"";
    $video_form_style = " style=\"display: none;\"";
    $audio_form_style = "";
  } else {
    $audio_check = "";
    $video_form_style = "";
    $audio_form_style = "style=\"display: none;\"";
  }
  ?>
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
  
  <ul class="nav nav-pills">
    <li class="<?php echo($tab_home_class); ?>"><a id="home_link" href="#home" data-toggle="tab" aria-expanded="true">Home</a></li>
    <li class="<?php echo($tab_dl_class); ?>"><a id="dl_link" href="#downloading" data-toggle="tab" aria-expanded="false">Downloading</a></li>
    <li class="<?php echo($tab_vid_class); ?>"><a id="vid_link" href="#vidlist" data-toggle="tab" aria-expanded="false">Videos</a></li>
    <li class="<?php echo($tab_music_class); ?>"><a id="music_link" href="#songlist" data-toggle="tab" aria-expanded="false">Songs</a></li>
  </ul>
  <div id="myTabContent" class="tab-content">
    <div class="tab-pane fade<?php echo($page_home_class); ?>" id="home">
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
              <input class="form-control" id="url" name="urls" placeholder="Enter the URL to the video you want to download. If you want to enter more that one please separate with a comma." type="text">
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
    <div class="tab-pane fade<?php echo($page_dl_class); ?>" id="downloading">
      <div style="text-align: center;" class="row">
        <br /><br />
        <h4>Currently Downloading</h4>
        <table style="text-align: left;" class="table table-striped table-hover ">
          <thead>
            <tr>
              <th style="width: 10%; height:35px;">Site</th>
              <th>File</th>
              <th style="width: 25%;">Status</th>
            </tr>
          </thead>
          <tbody id="dlprogress">
            <?php
  echo "<tr>";
  echo "<td colspan=\"3\">Getting downloads please wait...</td>";
  echo "<td></td>";
  ?>
          </tbody>
        </table>
        <button id="killallbutton" style="width: 300px;" class="btn btn-default btn-xs" data-href="?kill=all" data-toggle="modal" data-target="#confirm-delete">
          Stop All Downloads
        </button>
        <br /><br />
        <h4>Recently Completed</h4>
        <table style="text-align: left;" class="table table-striped table-hover ">
          <thead>
            <tr>
              <th style="width: 10%; height:35px;">Site</th>
              <th>File</th>
              <th style="width: 25%;">Status</th>
            </tr>
          </thead>
          <tbody id="dlcompleted">
            <?php
  echo "<tr>";
  echo "<td colspan=\"3\">Getting downloads please wait...</td>";
  echo "<td></td>";
  ?>
          </tbody>
        </table>
        <button id="clearallbutton" style="width: 300px;" class="btn btn-default btn-xs" data-href="?clear=recent" data-toggle="modal" data-target="#confirm-delete">
          Clear List
        </button>
      </div>
    </div>
    <div class="tab-pane fade<?php echo($page_vid_class); ?>" id="vidlist">
      <br /><br />
      <h4 style="text-align: center;">All Downloaded Videos</h4>
      <?php
  $files = $file->listVideos();
  if(!empty($files))
  {
    ?>
      <table style="text-align: left;" class="table table-striped table-hover ">
        <thead>
          <tr>
            <th style="min-width:800px; height:35px">Title</th>
            <th style="min-width:80px">Size</th>
            <th style="min-width:110px">Delete link</th>
          </tr>
        </thead>
        <tbody>
          <?php
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
              echo "<td><a data-href=\"?delete=$i&type=v\" data-toggle=\"modal\" data-target=\"#confirm-delete\" class=\"btn btn-danger btn-sm\">Delete</a></td>";
              echo "</tr>";
              $i++;
            }
    ?>
        </tbody>
      </table>
      <br/>
      <br/>
      <?php
  }
  else
  {
    echo "<br><div class=\"alert alert-warning\" role=\"alert\">No Videos!</div>";
  }
  ?>
    </div>
    <div class="tab-pane fade<?php echo($page_music_class); ?>" id="songlist">
      <br /><br />
      <h4 style="text-align: center;">All Downloaded Songs</h4>
      <?php
  $files = $file->listMusics();
  if(!empty($files))
  {
    ?>
      <table style="text-align: left;" class="table table-striped table-hover ">
        <thead>
          <tr>
            <th style="min-width:800px; height:35px">Title</th>
            <th style="min-width:80px">Size</th>
            <th style="min-width:110px">Delete link</th>
          </tr>
        </thead>
        <tbody>
          <?php
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
              echo "<td><a data-href=\"?delete=$i&type=m\" data-toggle=\"modal\" data-target=\"#confirm-delete\" class=\"btn btn-danger btn-sm\">Delete</a></td>";
              echo "</tr>";
              $i++;
            }
    ?>
        </tbody>
      </table>
      <br/>
      <br/>
      <?php
  }
  else
  {
    echo "<br><div class=\"alert alert-warning\" role=\"alert\">No Songs!</div>";
  }
  ?>
    </div>
  </div>  
</div>
<?php
  unset($_SESSION['errors']);
  require 'views/footer.php';
  ?>
