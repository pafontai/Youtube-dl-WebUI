	<footer class="footer" style="position: fixed;bottom: 0;padding: 0;margin: 0;width: 100%;">
            <div class="well text-center" style="padding: 0;margin: 0;">

    <div class="panel panel-default" style="margin: 0;">
      <div onclick="helpPanel()" style="cursor: pointer;" class="panel-heading">
        <h3 class="panel-title">Click here for Info and Help</h3>
      </div>
      <div id="helppanel" class="panel-body panel-collapsed" style="display: none;">
        <table class="table table-striped table-hover" style="text-align: left;">
          <tr>
            <td>
              <b>Free space:</b>
            </td>
            <td>
              <?php echo $file->free_space(); ?>iB
            </td>
          </tr>
          <tr>
            <td>
              <b>Download folder:</b>
            </td>
            <td>
              <?php echo $file->get_downloads_folder(); ?>
            </td>
          </tr>
          <tr>
            <td>
              <b>How does it work?</b>
            </td>
            <td>
              Simply paste your video link(s) in the field, select the quality or audio format and click "Download". 
            </td>
          </tr>
          <tr>
            <td>
              <b>With which sites does it work?</b>
            </td>
            <td>
              <a href="http://rg3.github.io/youtube-dl/supportedsites.html" targe="_blank">Here's</a> a list of the 
supported sites
            </td>
          </tr>
          <tr>
            <td>
              <b>How can I download the video/audio to my computer?</b>
            </td>
            <td>
              Go to <a href="#vidlist" onclick="$('#vid_link').click()" data-toggle="tab" aria-expanded="false">List 
of videos</a> or <a href="#songlist" 
onclick="$('#music_link').click()" data-toggle="tab" 
aria-expanded="false">List of songs</a> >> choose one >> right click on the link >> "Save target as ..." or go to the 
share \\zz9\Download in your file manager.
            </td>
          </tr>
        </table>
      </div>
    </div>



            </div>
        </footer>
    </body>
</html>

