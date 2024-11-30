<div class="comment-section">
   <?php if ($thread['locked']): ?>
      <p><strong>This thread is locked. Commenting is disabled.</strong></p>
   <?php else: ?>
      <form id="create-reply-form">
         <input type="hidden" id="parentCommentId" name="parentCommentId">
         <label for="content">Write a comment/reply:
            <div id="createReplyEditor" style="height: 200px; width: 100%;"></div>
         </label>
         <button type="submit" id="submit-reply">Submit</button>
         <button type="button" id="cancel-reply">Cancel</button>
      </form>
      <?php endif; ?>
</div>

<div id="edit-comment-section" style="display: none;">
   <?php if ($thread['locked']): ?>
      <p><strong>This thread is locked. Commenting is disabled.</strong></p>
   <?php else: ?>
      <form id="edit-comment-form">
         <input type="hidden" id="editCommentId" name="editCommentId">
         <label for="content">Edit Comment:
            <div id="editCommentEditor" style="height: 200px; width: 100%;"></div>
         </label>
         <button type="submit" id="submit-edit">Save Changes</button>
         <button type="button" id="cancel-edit">Cancel</button>
      </form>
   <?php endif; ?>
</div>

<h2><u>Comments</u> :</h2>
<ul id="comments-list">
   <!-- Comments will be dynamically loaded here via AJAX -->
</ul>

<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script src="/public/javascripts/comment.js"></script>