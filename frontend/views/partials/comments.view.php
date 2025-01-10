<div class="comment-section mx-auto my-4" style="max-width: 85%;">
   <?php if ($thread['locked']): ?>
      <div class="alert alert-warning" role="alert">
         <strong>This thread is locked. Commenting is disabled.</strong>
      </div>
   <?php else: ?>
      <form id="create-reply-form">
         <input type="hidden" id="parentCommentId" name="parentCommentId">
         <div class="form-group">
            <label for="content">Write a comment/reply:</label>
            <div id="createReplyEditor" class="border p-2" style="height: 200px; width: 100%;"></div>
         </div>
         <div class="d-flex justify-content-end mt-2">
            <button type="submit" id="submit-reply" class="btn btn-primary me-2">Submit</button>
            <button type="button" id="cancel-reply" class="btn btn-secondary">Cancel</button>
         </div>
      </form>
   <?php endif; ?>
</div>

<div id="edit-comment-section" class="mx-auto my-4" style="max-width: 85%; display: none;">
   <?php if ($thread['locked']): ?>
      <div class="alert alert-warning" role="alert">
         <strong>This thread is locked. Commenting is disabled.</strong>
      </div>
   <?php else: ?>
      <form id="edit-comment-form">
         <input type="hidden" id="editCommentId" name="editCommentId">
         <div class="form-group">
            <label for="content">Edit Comment:</label>
            <div id="editCommentEditor" class="border p-2" style="height: 200px; width: 100%;"></div>
         </div>
         <div class="d-flex justify-content-end mt-2">
            <button type="submit" id="submit-edit" class="btn btn-success me-2">Save Changes</button>
            <button type="button" id="cancel-edit" class="btn btn-secondary">Cancel</button>
         </div>
      </form>
   <?php endif; ?>
</div>

<h2 class="mt-4 mx-auto" style="max-width: 85%;"><u>Comments</u> :</h2>
<ul id="comments-list" class="list-unstyled mx-auto" style="max-width: 800px;">
   <!-- Comments will be dynamically loaded here via AJAX -->
</ul>


<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script src="/public/javascripts/comment.js"></script>
<script src="/public/javascripts/moderator.js"></script>