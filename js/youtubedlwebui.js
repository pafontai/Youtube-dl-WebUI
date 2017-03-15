function loadList()
{
  $.get( "index.php?jobs", function( data ) {
    var arrayLength = data.jobs.length;
    if (arrayLength==0) {
      $('#dlprogress').html( "<tr><td colspan=\"3\">No downloads in progress.</td></tr>" );
      $('#killallbutton').hide();
    } else {
      var htmlString = "";
      var liString = "";
      $('#dlprogress').html("");
      for (var i = 0; i < arrayLength; i++) {
        liString = '<i class=\"fa fa-video-camera\"></i>';
        if (data.jobs[i].type == "audio")
          liString = '<i class=\"fa fa-music\"></i>';
        htmlString += "<tr>";       
        htmlString += "<td>"+data.jobs[i].site+"</td>";
        htmlString += "<td>"+liString+" "+data.jobs[i].file+"</td>";
        htmlString += "<td>"+data.jobs[i].status+"</td>";
        htmlString += "</tr>";       
      }
      $('#dlprogress').html(htmlString);
      $('#killallbutton').show();
    }
    arrayLength = data.finished.length;
    if (arrayLength==0) {
      $('#dlcompleted').html( "<tr><td colspan=\"3\">No completed downloads on record.</td></tr>" );
      $('#clearallbutton').hide();
    } else {
      var htmlString = "";
      var liString = "";
      $('#dlcompleted').html("");
      for (var i = 0; i < arrayLength; i++) {
        liString = '<i class=\"fa fa-video-camera\"></i>';
        if (data.finished[i].type == "audio")
          liString = '<i class=\"fa fa-music\"></i>';
        htmlString += "<tr>";       
        htmlString += "<td>"+data.finished[i].site+"</td>";
        htmlString += "<td>"+liString+" "+data.finished[i].file+"</td>";
        htmlString += "<td>"+data.finished[i].status+"</td>";
        htmlString += "</tr>";       
      }
      $('#dlcompleted').html(htmlString);
      $('#clearallbutton').show();
    }
  }, "json");
} 

$(document).ready(function() {
  if ($("#dlprogress").length) {
    loadList();
    var refreshInterval = setInterval(loadList, 3000);
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
