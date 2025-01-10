<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
   <h2>Create New Thread</h2>

   <form id="createThread" method="POST" class="needs-validation" novalidate>
      <div class="mb-3">
         <label for="title" class="form-label">Title:</label>
         <input type="text" id="title" name="title" class="form-control" required>
         <div class="invalid-feedback">
            Please provide a title.
         </div>
      </div>

      <div class="mb-3">
         <label for="content" class="form-label">Content:</label>
         <div id="contentEditor" style="height: 200px; width:100%;" class="border p-2"></div>
         <input type="hidden" id="content" name="content" required>
      </div>

      <div class="mb-3">
         <label for="category" class="form-label">Category:</label>
         <input type="text" id="category" name="category" class="form-control" required>
         <div class="invalid-feedback">
            Please provide a category.
         </div>
      </div>

      <div id="imageUrlFields" class="mb-3">
         <label for="imageUrl[]" class="form-label">Image URL:</label>
         <div class="input-group mb-2">
            <input type="text" name="imageUrl[]" class="form-control imageUrl" required>
            <button type="button" class="btn btn-danger removeImageUrl" hidden>Delete</button>
         </div>
      </div>

      <button type="button" id="addImageUrl" class="btn btn-outline-primary ">Add Another Image URL</button>

      <button type="submit" class="btn btn-primary">Create Now!</button>
   </form>

   <div id="error-block" class="mt-3">
      <?= isset($error['msg']) ? '<div class="alert alert-danger">' . $error['msg'] . '</div>' : ''; ?>
   </div>
</div>

<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script>
   var quill = new Quill('#contentEditor', {
      theme: 'snow',
      modules: {
         toolbar: [
            [{
               'header': [2, 3, false]
            }],
            [{
               'list': 'ordered'
            }, {
               'list': 'bullet'
            }],
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
      newImageUrlField.classList.add('input-group', 'mb-2');

      const newField = document.createElement('input');
      newField.type = 'text';
      newField.name = 'imageUrl[]';
      newField.classList.add('form-control', 'imageUrl');
      newField.required = true;

      const deleteButton = document.createElement('button');
      deleteButton.type = 'button';
      deleteButton.classList.add('btn', 'btn-danger', 'removeImageUrl');
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