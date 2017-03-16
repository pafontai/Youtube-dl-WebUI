function loadList()
{
  $.get( "index.php?jobs", function( data ) {
    var arrayLength = data.jobs.length;
    if (arrayLength==0) {
      $('#dlprogress').html( "<tr><td colspan=\"4\">No downloads in progress.</td></tr>" );
    } else {
      var htmlString = "";
      var liString = "";
      $('#dlprogress').html("");
      for (var i = 0; i < arrayLength; i++) {
        liString = '<i class=\"fa fa-video-camera\"></i>';
        if (data.jobs[i].type == "audio")
          liString = '<i class=\"fa fa-music\"></i>';
        htmlString += "<tr>";       
        htmlString += "<td style=\"vertical-align: middle;\">"+data.jobs[i].site+"</td>";
        htmlString += "<td style=\"vertical-align: middle;\">"+liString+" "+data.jobs[i].file+"</td>";
        htmlString += "<td style=\"vertical-align: middle;\">"+data.jobs[i].status+"</td>";
        htmlString += "<td style=\"vertical-align: middle;\">";
        htmlString += "<div class=\"btn-group\">";
        htmlString += "<a style=\"width: 100px;\" data-href=\"?kill="+data.jobs[i].pid+"\" data-toggle=\"modal\" data-target=\"#confirm-delete\" class=\"btn btn-danger btn-xs\">Stop</a>";
        htmlString += "</div>";
        htmlString += "</tr>";       
      }
      htmlString += "<tr>";
      htmlString += "<td></td>";
      htmlString += "<td></td>";
      htmlString += "<td></td>";
      htmlString += "<td>";
      htmlString += "<div class=\"btn-group\">";
      htmlString += "<button id=\"killallbutton\" style=\"width: 100px;\" class=\"btn btn-danger btn-xs\" data-href=\"?kill=all\" data-toggle=\"modal\" data-target=\"#confirm-delete\">";
      htmlString += "Stop All";
      htmlString += "</button>";
      htmlString += "</div>";
      htmlString += "</td>";
      htmlString += "</tr>";
      $('#dlprogress').html(htmlString);
    }
    arrayLength = data.finished.length;
    if (arrayLength==0) {
      $('#dlcompleted').html( "<tr><td colspan=\"4\">No completed downloads on record.</td></tr>" );
    } else {
      var htmlString = "";
      var liString = "";
      $('#dlcompleted').html("");
      for (var i = 0; i < arrayLength; i++) {
        liString = '<i class=\"fa fa-video-camera\"></i>';
        if (data.finished[i].type == "audio")
          liString = '<i class=\"fa fa-music\"></i>';
        htmlString += "<tr>";       
        htmlString += "<td style=\"vertical-align: middle;\">"+data.finished[i].site+"</td>";
        htmlString += "<td style=\"vertical-align: middle;\">"+liString+" "+data.finished[i].file+"</td>";
        htmlString += "<td style=\"vertical-align: middle;\">"+data.finished[i].status+"</td>";
        htmlString += "<td style=\"vertical-align: middle;\">";
        htmlString += "<div class=\"btn-group\">";
        var buttonWidth = "100px;";
        if (data.logURL != "") {
          buttonWidth = "60px;";
          htmlString += "<a href=\""+data.logURL+"/"+data.finished[i].pid+"\" style=\"width: 40px;\" target=\"_blank\" class=\"btn btn-default btn-xs\">Log</a>";
        }
        htmlString += "<a style=\"width: "+buttonWidth+"\" data-href=\"?clear="+data.finished[i].pid+"\" data-toggle=\"modal\" data-target=\"#confirm-delete\" class=\"btn btn-danger btn-xs\">Remove</a>";
        htmlString += "</div>";
        htmlString += "</td>";
        htmlString += "</tr>";       
      }
      htmlString += "<tr>";
      htmlString += "<td></td>";
      htmlString += "<td></td>";
      htmlString += "<td></td>";
      htmlString += "<td>";
      htmlString += "<div class=\"btn-group\">";
      htmlString += "<button id=\"clearallbutton\" style=\"width: 100px;\" class=\"btn btn-danger btn-xs\" data-href=\"?clear=recent\" data-toggle=\"modal\" data-target=\"#confirm-delete\">";
      htmlString += "Remove All";
      htmlString += "</button>";
      htmlString += "</div>";
      htmlString += "</td>";
      htmlString += "</tr>";
      $('#dlcompleted').html(htmlString);
    }
  }, "json");
} 
var refreshInterval;
$(document).ready(function() {
  if ($("#dlprogress").length) {
    loadList();
    refreshInterval = setInterval(loadList, 3000);
    $("#url").focus();
  }
});


function checkControls()
{
  var isChecked = $('#audio_convert').prop("checked");
  $('#video_group').hide();
  $('#audio_group').hide();
  if (isChecked) {
    $('#audio_group').show();
  } else {
    $('#video_group').show();
  }
}

function helpPanel()
{
  var panelBody = $('#helppanel');
  if(!panelBody .hasClass('panel-collapsed')) {
    panelBody.slideUp();
    panelBody.addClass('panel-collapsed');
  } else {
    panelBody.slideDown();
    panelBody.removeClass('panel-collapsed');
  }
}

function updateBookmarklet()
{
  var audio_format = $('#bml_audio_format').val();
  var video_format = $('#bml_format').val();
  var audio_extract = $('#bml_audio_convert').is(":checked");
  var auto_submit = $('#bml_auto_submit').is(":checked");
  var base_url = $('#bml_base_uri').val();
  var getparm = "?";

  if (audio_extract)
  {
    getparm += "audio=true&";
    getparm += "audio_format="+audio_format;
    $('#bml_audio_group').show();
    $('#bml_video_group').hide();
    $('#cust_bml').html("Download Audio");
  } else {
    getparm += "format="+video_format;
    $('#bml_audio_group').hide();
    $('#bml_video_group').show();
    $('#cust_bml').html("Download Video");
  }
  if (auto_submit)
    getparm += "&auto_submit=true";
  getparm += "&url=";

  $('#cust_bml').attr('href', "javascript:(function(){f='"+base_url+getparm+"'+encodeURIComponent(window.location.href);a=function(){if(!window.open(f))location.href=f};if(/Firefox/.test(navigator.userAgent)){setTimeout(a,0)}else{a()}})()");
}
