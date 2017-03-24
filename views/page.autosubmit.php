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
    <?php if ($_GET['submitstatus'] == "success") : ?>
      <b>Video has been submitted successfully</b>
      <br /><br />
      <a href="javascript:window.close();">Close this window</a> or <a href="index.php#downloads">Check the status of the download</a>
    <?php else: ?>
      <b>Something went wrong. You can try to submit the video manually.</b>
      <br /><br />
      Error: <?php echo(implode(", ", $_SESSION['errors'])) ?>
      <br /><br />
      <a href="javascript:window.close();">Close this window</a> or <a href="index.php">Try to submit the download manually</a>;
    <?php endif; ?>
  </body>
</html>
