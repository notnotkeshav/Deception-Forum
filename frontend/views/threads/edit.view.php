<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>
<h2>Edit Thread</h2>

<form id="editThread">
   <input type="hidden" id="threadId" value="<?= htmlspecialchars($thread['id']) ?>" />
   <!-- Title Field -->
   <div>
      <label for="title">Title:</label>
      <input type="text" id="title" name="title" value="<?= htmlspecialchars($thread['title']) ?>" required>
   </div>

   <!-- Content Field -->
   <div>
      <label for="content">Content:</label>
      <textarea id="content" name="content" required><?= htmlspecialchars($thread['content']) ?></textarea>
   </div>

   <!-- Category Field -->
   <div>
      <label for="category">Category:</label>
      <input type="text" id="category" name="category" value="<?= htmlspecialchars($thread['category_name']) ?>" required>
   </div>

   <!-- Image URL Fields -->
   <div id="imageUrlFields">
      <label for="imageUrl[]">Image URL:</label>
      <?php if (!empty($thread['images'])): ?>
         <?php foreach ($thread['images'] as $image_url): ?>
            <div class="imageUrlField">
               <input type="text" name="imageUrl[]" class="imageUrl" value="<?= htmlspecialchars($image_url) ?>">
               <button type="button" class="removeImageUrl">Delete</button>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <div class="imageUrlField">
            <input type="text" name="imageUrl[]" class="imageUrl" placeholder="Image URL">
            <button type="button" class="removeImageUrl">Delete</button>
         </div>
      <?php endif; ?>
   </div>

   <!-- Button to Add More Image URL Fields -->
   <button type="button" id="addImageUrl">Add Another Image URL</button>

   <!-- Submit Button -->
   <button type="submit">Save Changes</button>
</form>

<div id="error-block">
   <?= $error['msg'] ?? null ?>
</div>

<script>
   // JavaScript to dynamically add and remove image URL input fields
   document.getElementById('addImageUrl').addEventListener('click', function() {
      let imageUrlFields = document.getElementById('imageUrlFields');

      let newImageUrlField = document.createElement('div');
      newImageUrlField.classList.add('imageUrlField');

      let newField = document.createElement('input');
      newField.type = 'text';
      newField.name = 'imageUrl[]';
      newField.classList.add('imageUrl');

      let deleteButton = document.createElement('button');
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