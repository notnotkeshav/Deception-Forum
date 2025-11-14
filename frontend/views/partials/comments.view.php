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

   .comment-actions {
      margin-top: 0.3em;
      display: flex;
      flex-wrap: wrap;
      gap: 0.8em;
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

   .comment-vote-count {
      color: #fff;
      font-weight: bold;
      background: #151515;
      border-radius: 3px;
      padding: 2px 8px 2px 8px;
      font-size: 1em;
      margin-left: 3px;
   }
</style>

<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script src="/public/javascripts/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@2.3.10/dist/purify.min.js"></script>
<script src="/public/javascripts/comment.js"></script>
<script src="/public/javascripts/moderator.js"></script>