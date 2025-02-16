<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
   <h2>Edit Thread</h2>

   <form id="editThread" method="POST" class="needs-validation" novalidate>
      <input type="hidden" id="threadId" value="<?= htmlspecialchars($thread['id']) ?>" />

      <div class="mb-3">
         <label for="title" class="form-label">Title:</label>
         <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($thread['title']) ?>" required>
         <div class="invalid-feedback">Please provide a title.</div>
      </div>

      <div class="mb-3">
         <label for="content" class="form-label">Content:</label>
         <div id="contentEditor" style="height: 200px;" class="border p-2"></div>
         <input type="hidden" id="content" name="content" required>
      </div>

      <div class="mb-3">
         <label for="category" class="form-label">Category:</label>
         <input type="text" id="category" name="category" class="form-control" value="<?= htmlspecialchars($thread['category_name']) ?>" required>
         <div class="invalid-feedback">Please provide a category.</div>
      </div>

      <div id="imageUrlFields" class="mb-3">
         <label for="imageUrl[]" class="form-label">Image URL:</label>
         <?php if (!empty($thread['images'])): ?>
            <?php foreach ($thread['images'] as $image_url): ?>
               <div class="input-group mb-2">
                  <input type="text" name="imageUrl[]" class="form-control imageUrl" value="<?= htmlspecialchars($image_url) ?>">
                  <button type="button" class="btn btn-danger removeImageUrl">Delete</button>
               </div>
            <?php endforeach; ?>
         <?php else: ?>
            <div class="input-group mb-2">
               <input type="text" name="imageUrl[]" class="form-control imageUrl" placeholder="Image URL">
               <button type="button" class="btn btn-danger removeImageUrl">Delete</button>
            </div>
         <?php endif; ?>
      </div>

      <button type="button" id="addImageUrl" class="btn btn-outline-primary">Add Another Image URL</button>

      <button type="submit" class="btn btn-primary">Save Changes</button>
   </form>

   <div id="error-block" class="mt-3">
      <?= $error['msg'] ?? null ?>
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

   document.addEventListener('DOMContentLoaded', function() {
      const existingContent = <?= json_encode($thread['content']); ?>;
      quill.root.innerHTML = existingContent;
   });

   document.getElementById('editThread').addEventListener('submit', function() {
      var content = quill.root.innerHTML;
      document.getElementById('content').value = content;
   });

   // JavaScript to dynamically add and remove image URL input fields
   document.getElementById('addImageUrl').addEventListener('click', function() {
      let imageUrlFields = document.getElementById('imageUrlFields');

      let newImageUrlField = document.createElement('div');
      newImageUrlField.classList.add('input-group', 'mb-2');

      let newField = document.createElement('input');
      newField.type = 'text';
      newField.name = 'imageUrl[]';
      newField.classList.add('form-control', 'imageUrl');
      newField.placeholder = 'Image URL';

      let deleteButton = document.createElement('button');
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

   // Event listener for the initial delete button
   document.querySelectorAll('.removeImageUrl').forEach(function(button) {
      button.addEventListener('click', function() {
         let imageUrlFields = document.getElementById('imageUrlFields');
         let imageUrlField = this.parentNode;
         imageUrlFields.removeChild(imageUrlField);
      });
   });
</script>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>