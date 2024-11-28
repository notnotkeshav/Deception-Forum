<div class="comment-section">
   <form id="create-reply-form">
      <input type="hidden" id="parentCommentId" name="parentCommentId">
      <input type="hidden" id="threadId" name="threadId" value="<?php echo htmlspecialchars($thread['id']); ?>">
      <label for="content">Write a comment/reply:
         <div id="createReplyEditor" style="height: 200px; width: 100%;"></div>
      </label>
      <button type="submit" id="submit-reply">Submit</button>
      <button type="button" id="cancel-reply">Cancel</button>
   </form>
</div>

<div id="edit-comment-section" style="display: none;">
   <form id="edit-comment-form">
      <input type="hidden" id="editCommentId" name="editCommentId">
      <label for="content">Edit Comment:
         <div id="editCommentEditor" style="height: 200px; width: 100%;"></div>
      </label>
      <button type="submit" id="submit-edit">Save Changes</button>
      <button type="button" id="cancel-edit">Cancel</button>
   </form>
</div>

<h2><u>Comments</u> :</h2>
<ul id="comments-list">
   <!-- Comments will be dynamically loaded here via AJAX -->
</ul>

<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script src="/public/javascripts/comment.js"></script>
