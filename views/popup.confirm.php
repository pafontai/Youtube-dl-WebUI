<?php if (!isset($GLOBALS['config'])) { die("No direct script access");
} ?>
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
 
