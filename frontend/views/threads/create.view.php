<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>
<h2>Create New Thread</h2>

<form id="createThread" method="POST">
   <div>
      <label for="title">Title:</label>
      <input type="text" id="title" name="title" required>
   </div>

   <div>
      <label for="content">Content:</label>
      <div id="contentEditor" style="height: 200px; width:50%;"></div>
      <input type="hidden" id="content" name="content">
   </div>

   <div>
      <label for="category">Category:</label>
      <input type="text" id="category" name="category" required>
   </div>

   <div id="imageUrlFields">
      <label for="imageUrl[]">Image URL:</label>
      <input type="text" name="imageUrl[]" class="imageUrl">
      <button type="button" class="removeImageUrl" hidden>Delete</button>
   </div>

   <button type="button" id="addImageUrl">Add Another Image URL</button>

   <button type="submit">Create Now!</button>
</form>

<div id="error-block">
   <?= isset($error['msg']) ? $error['msg'] : ''; ?>
</div>

<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script>
   var quill = new Quill('#contentEditor', {
      theme: 'snow',
      modules: {
         toolbar: [
            [{ 'header':[2,3,false] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['bold', 'italic', 'underline'],
            ['link'],
         ]
      }
   });

   document.getElementById('createThread').addEventListener('submit', function() {
      var content = quill.root.innerHTML;
      document.getElementById('content').value = content;
   });

   document.getElementById('addImageUrl').addEventListener('click', function() {
      const imageUrlFields = document.getElementById('imageUrlFields');
      const newImageUrlField = document.createElement('div');
      newImageUrlField.classList.add('imageUrlField');

      const newField = document.createElement('input');
      newField.type = 'text';
      newField.name = 'imageUrl[]';
      newField.classList.add('imageUrl');

      const deleteButton = document.createElement('button');
      deleteButton.type = 'button';
      deleteButton.classList.add('removeImageUrl');
      deleteButton.textContent = 'Delete';

      deleteButton.addEventListener('click', function() {
         imageUrlFields.removeChild(newImageUrlField);
      });

      newImageUrlField.appendChild(newField);
      newImageUrlField.appendChild(deleteButton);

      imageUrlFields.appendChild(newImageUrlField);
   });

   document.querySelectorAll('.removeImageUrl').forEach(button => {
      button.addEventListener('click', function() {
         const imageUrlFields = document.getElementById('imageUrlFields');
         const imageUrlField = this.parentNode;
         imageUrlFields.removeChild(imageUrlField);
      });
   });
</script>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>
