<div id="comments-block" style="margin:2.2rem auto 2rem auto; max-width: 1400px;">
   <div style="color:#f03;font-size:1.4rem;letter-spacing:1.1px; margin-bottom:1.2em; font-weight:bold;">
      ⛧ Comments & Replies
   </div>

   <form id="create-reply-form" style="margin-top:2rem; background:#181818; border:1.5px solid #960d0d; padding:1.2rem 1.2rem;">
      <input type="hidden" id="parentCommentId" name="parentCommentId" value="">
      <div id="createReplyEditor" style="min-height:100px;background:#0a0a0a;border:1.5px solid #333;margin-bottom:1em;"></div>
      <div style="display:flex;gap:1.0em;margin-top:1.3em;">
         <button type="submit" class="action-btn action-btn-edit" style="width:120px;">Submit</button>
         <button type="button" id="cancel-reply" class="action-btn action-btn-delete" style="width:100px;">Cancel</button>
      </div>
   </form>

   <form id="edit-comment-form" style="display:none; margin-top:2rem; background:#1d1d1d; border:2px solid #ffaa00; padding:1.1rem 1.2rem;">
      <input type="hidden" id="editCommentId" name="editCommentId" value="">
      <div id="editCommentEditor" style="min-height:80px;background:#191919;border:1.5px solid #ffaa00;margin-bottom:1em;"></div>
      <div style="display:flex;gap:1.0em;">
         <button type="submit" class="action-btn action-btn-edit" style="width:120px;">Save</button>
         <button type="button" id="cancel-edit" class="action-btn action-btn-delete" style="width:100px;">Cancel</button>
      </div>
   </form>
   <ul id="comments-list" style="padding:0; margin:1rem 0; list-style:none;"></ul>
</div>

<style>
   #comments-list>li,
   .replies-list>li {
      margin-bottom: 1.1em;
      background: #121212;
      border-left: 2.3px solid #960d0d;
      padding: 0.9em 1.2em 0.7em 1.6em;
      position: relative;
      color: #fff;
      font-size: 1rem;
      box-shadow: 0 1px 8px #15111133;
   }

   .replies-list {
      margin-top: 0.7em !important;
      margin-left: 1.5em !important;
      border-left: 1.5px dotted #333;
      padding-left: 0.6em !important;
      list-style: none;
   }

   .comment-meta-label {
      color: #f03;
      font-weight: bold;
      margin-right: 0.6em;
      font-size: 0.92em;
   }

   .comment-timestamp {
      color: #aaa;
      font-size: 0.93em;
      float: right;
   }

   .comment-author {
      color: #ffd700;
      margin-right: 1.4em;
      font-weight: bold;
      font-size: 0.99em;
   }

   .comment-content {
      color: #e9e9e9;
      margin: .42em 0 .7em 0;
      font-size: 1.07em;
      word-break: break-word;
      line-height: 1.6;
   }

   .comment-edited {
      color: #aaa;
      font-size: 0.85em;
      margin-top: 0.3em;
      font-style: italic;
   }

   .comment-actions {
      margin-top: 0.3em;
      display: flex;
      flex-wrap: wrap;
      gap: 0.8em;
      align-items: center;
   }

   .comment-action-btn {
      background: #121212;
      border: 1.5px solid #333;
      color: #ffd700;
      padding: .45em 1.1em;
      font-size: 0.92em;
      font-family: inherit;
      font-weight: bold;
      margin-right: 0.3em;
      border-radius: 3px;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.9px;
      transition: background .15s, color .15s;
   }

   .comment-action-btn.disabled,
   .comment-action-btn[disabled] {
      opacity: 0.35;
      cursor: not-allowed;
   }

   .comment-action-btn.edit {
      border-color: #ffaa00;
      color: #ffaa00;
   }

   .comment-action-btn.delete {
      border-color: #f03;
      color: #f03;
   }

   .comment-action-btn.reply {
      border-color: #0a0;
      color: #0a0;
   }

   .comment-action-btn.vote {
      border-color: #3498db;
      color: #3498db;
   }

   .comment-action-btn.block {
      border-color: #f03;
      color: #f03;
   }

   .comment-action-btn:hover:not([disabled]) {
      background: #ffaa00;
      color: #000;
      border-color: #ffd700;
   }

   .comment-action-btn.delete:hover:not([disabled]) {
      background: #960d0d;
      color: #fff;
   }

   .comment-action-btn.reply:hover:not([disabled]) {
      background: #1a1;
      color: #fff;
   }

   .comment-action-btn.vote:hover:not([disabled]) {
      background: #3498db;
      color: #fff;
   }

   .comment-action-btn.block:hover:not([disabled]) {
      background: #c41e3a;
      color: #fff;
   }

   .comment-vote-count {
      color: #fff;
      font-weight: bold;
      background: #151515;
      border-radius: 3px;
      padding: 2px 8px 2px 8px;
      font-size: 1em;
      margin-left: 3px;
   }

   /* ===== REACTIONS ===== */
   .reactions-container {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5em;
      margin: 0.7em 0;
      align-items: center;
   }

   .reaction-btn {
      background: #1a1a1a;
      border: 1.2px solid #333;
      color: #fff;
      padding: 0.3em 0.7em;
      font-size: 0.9em;
      font-family: inherit;
      border-radius: 3px;
      cursor: pointer;
      transition: all 0.15s;
      display: flex;
      align-items: center;
      gap: 0.4em;
      white-space: nowrap;
   }

   .reaction-btn:hover {
      background: #2a2a2a;
      border-color: #ffd700;
      color: #ffd700;
      transform: scale(1.05);
   }

   .reaction-btn.user-reacted {
      background: #2a2a2a;
      border-color: #ffd700;
      color: #ffd700;
      font-weight: bold;
   }

   .add-reaction-btn {
      background: #1a1a1a;
      border: 1.2px solid #333;
      color: #999;
      padding: 0.3em 0.5em;
      font-size: 1.1em;
      font-family: inherit;
      border-radius: 3px;
      cursor: pointer;
      transition: all 0.15s;
      line-height: 1;
   }

   .add-reaction-btn:hover {
      background: #2a2a2a;
      border-color: #ffd700;
      color: #ffd700;
   }

   .emoji-picker {
      background: #1d1d1d;
      border: 1.5px solid #333;
      border-radius: 5px;
      padding: 0.8em;
      margin-top: 0.5em;
      display: flex;
      flex-wrap: wrap;
      gap: 0.5em;
      animation: slideDown 0.2s ease-out;
   }

   @keyframes slideDown {
      from {
         opacity: 0;
         transform: translateY(-10px);
      }
      to {
         opacity: 1;
         transform: translateY(0);
      }
   }

   .emoji-option {
      background: #121212;
      border: 1px solid #333;
      color: #fff;
      padding: 0.5em 0.8em;
      font-size: 1.3em;
      border-radius: 3px;
      cursor: pointer;
      transition: all 0.15s;
      line-height: 1;
   }

   .emoji-option:hover {
      background: #2a2a2a;
      border-color: #ffd700;
      transform: scale(1.1);
   }

   /* ===== EDIT FORM ===== */
   .edit-comment-form {
      margin-top: 0.8em;
      padding: 0.8em;
      background: #1d1d1d;
      border: 1.5px solid #ffaa00;
      border-radius: 5px;
      animation: slideDown 0.2s ease-out;
   }

   .edit-comment-form textarea {
      background: #0a0a0a;
      color: #fff;
      border: 1px solid #333;
      padding: 0.6em;
      border-radius: 3px;
      font-family: inherit;
      font-size: 1em;
      min-height: 80px;
      resize: vertical;
      margin-bottom: 0.8em;
   }

   .edit-comment-form textarea:focus {
      outline: none;
      border-color: #ffaa00;
      box-shadow: 0 0 5px rgba(255, 170, 0, 0.3);
   }

   .edit-form-actions {
      display: flex;
      gap: 0.8em;
   }

   .edit-form-actions button {
      padding: 0.4em 1em;
      font-size: 0.9em;
      border-radius: 3px;
      border: none;
      cursor: pointer;
      transition: all 0.15s;
      font-weight: bold;
   }

   .edit-form-actions .save-btn {
      background: #ffaa00;
      color: #000;
   }

   .edit-form-actions .save-btn:hover {
      background: #ffd700;
   }

   .edit-form-actions .cancel-btn {
      background: #333;
      color: #999;
      border: 1px solid #555;
   }

   .edit-form-actions .cancel-btn:hover {
      background: #444;
      color: #fff;
   }

   /* ===== ALERTS ===== */
   .comment-alert {
      padding: 0.7em;
      margin-bottom: 1em;
      border-radius: 3px;
      animation: slideDown 0.2s ease-out;
   }

   .comment-alert.success {
      background: #1a3a1a;
      color: #0a0;
      border-left: 3px solid #0a0;
   }

   .comment-alert.error {
      background: #3a1a1a;
      color: #f03;
      border-left: 3px solid #f03;
   }

   .comment-alert .close-btn {
      background: none;
      border: none;
      color: inherit;
      cursor: pointer;
      font-size: 1.2em;
      float: right;
   }

   /* ===== DELETED MESSAGE ===== */
   .comment-deleted {
      opacity: 0.6;
      color: #777;
      font-style: italic;
   }

   .comment-deleted .comment-content {
      color: #666;
   }

   .comment-deleted .comment-actions {
      opacity: 0.5;
      pointer-events: none;
   }
</style>

<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script src="/public/javascripts/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@2.3.10/dist/purify.min.js"></script>
<script src="/public/javascripts/message-features.js"></script>
<script src="/public/javascripts/comment.js"></script>
<script src="/public/javascripts/moderator.js"></script>