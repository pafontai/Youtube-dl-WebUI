<?php if (!isset($GLOBALS['config'])) { die("No direct script access");
} ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title><?php echo($config['siteName']); ?></title>
    <link rel="stylesheet" href="css/<?php echo($siteTheme); ?>.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/youtubedlwebui.js"></script>
    <link rel="icon" href="img/favicon.png">
  </head>
  <body>
    <div class="navbar navbar-default">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-responsive-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="">kiot.eu Tube Downloader</a>
      </div>
    </div>
