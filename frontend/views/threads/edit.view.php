<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>⛧ Edit Thread - Red Skull ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
   <link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <style>
      @font-face {
         font-family: 'vamp';
         src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
      }

      body {
         background: #000;
         color: #fff;
         font-family: 'Courier New', monospace;
         min-height: 100vh;
      }

      .main-container {
         width: 50vw;
         min-width: 600px;
         max-width: 800px;
         margin: 2.5rem auto;
         background: #111;
         border: 2px solid #960d0d;
         box-shadow: 0 0 22px #a00a, 0 0 30px #a00a0a11 inset;
         padding: 2.3rem 2.5rem 1.5rem 2.5rem;
         border-radius: 6px;
      }

      h2.page-title {
         font-family: 'vamp', sans-serif;
         text-align: center;
         color: #f03;
         font-size: 2.35rem;
         letter-spacing: 2px;
         margin-bottom: 2.2rem;
         text-shadow: 0 0 14px rgba(255, 0, 0, 0.5);
         border-bottom: 1.5px solid #960d0d;
         padding-bottom: .8rem;
      }

      .form-group {
         margin-bottom: 1.35rem;
      }

      .form-label {
         font-size: 1.08rem;
         margin-bottom: 6px;
         color: #f03;
         font-family: 'vamp', sans-serif;
         letter-spacing: 1px;
      }

      .form-control {
         background: #191919;
         border: 1.5px solid #333;
         outline: none;
         color: #f8f8f8;
         width: 100%;
         padding: 0.7rem 1.1rem;
         border-radius: 3px;
         font-size: 1rem;
         font-family: inherit;
         transition: border-color 0.25s, box-shadow 0.25s;
      }

      .form-control:focus {
         border: 1.5px solid #f03;
         box-shadow: 0 0 5px #f03a;
      }

      .invalid-feedback {
         color: #f03;
         margin-top: 0.3rem;
         font-size: 0.88rem;
         display: none;
      }

      .form-control:invalid:not(:focus):not(:placeholder-shown)+.invalid-feedback {
         display: block;
      }

      .content-editor {
         border: 1.5px solid #333;
         border-radius: 3px;
         min-height: 160px;
         background: #191919;
      }

      #imageUrlFields label {
         font-size: 1.08rem;
         color: #f03;
         font-family: 'vamp', sans-serif;
         margin-bottom: 0.4rem;
      }

      #imageUrlFields .input-group {
         display: flex;
         align-items: center;
         margin-bottom: 0.5rem;
      }

      #imageUrlFields .form-control {
         flex: 1 1;
      }

      #imageUrlFields .btn-danger {
         margin-left: 0.8rem;
         padding: 0.35rem 0.85rem;
         min-width: 80px;
      }

      .btn,
      .btn-primary,
      .btn-outline-primary,
      .btn-danger {
         font-family: inherit;
         font-size: 1rem;
         font-weight: bold;
         letter-spacing: 1px;
         cursor: pointer;
         border-radius: 3px;
         border: 2px solid transparent;
         transition: all 0.2s;
         margin-right: 0.5rem;
      }

      .btn-primary {
         background: #960d0d;
         border-color: #960d0d;
         color: #fff;
         padding: 0.68rem 2.1rem;
         box-shadow: 0 0 10px #a00a0a55;
      }

      .btn-primary:hover {
         background: #c00;
         border-color: #f03;
      }

      .btn-outline-primary {
         background: transparent;
         border: 2px solid #960d0d;
         color: #f03;
         padding: 0.68rem 2.1rem;
      }

      .btn-outline-primary:hover {
         background: #960d0d;
         color: #fff;
      }

      .btn-danger {
         background: #191919;
         border: 2px solid #960d0d;
         color: #f03;
      }

      .btn-danger:hover {
         background: #960d0d;
         color: #fff;
      }

      .btn-small {
         font-size: 0.95rem;
         padding: 0.51rem 1.4rem;
      }

      .btn-large {
         font-size: 1.14rem;
         padding: 0.82rem 2.75rem;
      }

      .error-block .alert {
         background: #1a0000e0;
         color: #ff3535;
         padding: 1rem 1.5rem;
         border: 1.5px solid #a00;
         border-radius: 4px;
         margin: 1.4rem 0 0.5rem 0;
         font-size: 1rem;
      }
   </style>
</head>

<body>
   <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

   <div class="main-container">
      <h2 class="page-title">⛧ EDIT THREAD ⛧</h2>
      <form id="editThread" method="POST" class="needs-validation" novalidate autocomplete="off">
         <input type="hidden" id="threadId" value="<?= htmlspecialchars($thread['id']) ?>" />

         <div class="form-group">
            <label for="title" class="form-label">THREAD TITLE:</label>
            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($thread['title']) ?>" required>
            <div class="invalid-feedback">Please provide a title.</div>
         </div>

         <div class="form-group">
            <label for="content" class="form-label">CONTENT:</label>
            <div id="contentEditor" class="content-editor"></div>
            <input type="hidden" id="content" name="content" required>
         </div>

         <div class="form-group">
            <label for="category" class="form-label">CATEGORY:</label>
            <input type="text" id="category" name="category" class="form-control" value="<?= htmlspecialchars($thread['category_name']) ?>" required>
            <div class="invalid-feedback">Please provide a category.</div>
         </div>

         <div id="imageUrlFields" class="form-group">
            <label>IMAGE URLS:</label>
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
         <button type="button" id="addImageUrl" class="btn btn-outline-primary btn-small">+ ADD IMAGE URL</button>
         <button type="submit" class="btn btn-primary btn-large">SAVE CHANGES</button>
      </form>
      <div id="error-block" class="error-block"><?= $error['msg'] ?? null ?></div>
   </div>

   <script src="/public/javascripts/quill.min.js"></script>
   <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
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

      // Dynamic add/remove image fields
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

      // Remove button on initial fields
      document.querySelectorAll('.removeImageUrl').forEach(function(button) {
         button.addEventListener('click', function() {
            let imageUrlFields = document.getElementById('imageUrlFields');
            let imageUrlField = this.parentNode;
            imageUrlFields.removeChild(imageUrlField);
         });
      });

      // ===== JQUERY AJAX THREAD UPDATE =====
      const API_BASE_URL = '';
      let token = sessionStorage.getItem('token');
      $(document).ready(() => {
         const showError = (msg) => {
            $('#error-block').text(msg).css("color", "#f03");
         };
         const showSuccess = (msg) => {
            $('#error-block').text(msg).css("color", "#0f0");
         };
         const handleAjaxError = (xhr, status, error) => {
            console.error(`[${status}] AJAX Error:`, error);
            if (xhr.status === 401) {
               showError('Unauthorized access. Please log in.');
               sessionStorage.removeItem('token');
               setTimeout(() => window.location.href = '/signin', 800);
            } else {
               showError(xhr.responseJSON?.message || 'An unexpected error occurred. Please try again.');
            }
         };
         const sendRequest = async (url, method, data, contentType, successCallback) => {
            try {
               const response = await $.ajax({
                  url,
                  method,
                  contentType,
                  dataType: 'json',
                  data,
                  headers: {
                     'Authorization': `Bearer ${token}`
                  }
               });
               if (response) successCallback(response);
            } catch (xhr) {
               handleAjaxError(xhr, 'error', xhr.responseText || xhr.statusText);
            }
         };

         $('#editThread').on('submit', function(event) {
            event.preventDefault();

            $('#content').val(quill.root.innerHTML);

            const threadId = $('#threadId').val();
            const title = $('#title').val();
            const content = $('#content').val();
            const category = $('#category').val();

            const images = [];
            $('.imageUrl').each(function() {
               const imageUrl = $(this).val();
               if (imageUrl) images.push(imageUrl);
            });

            if (!title || !content || !category) {
               showError('Title, content, and category are required.');
               return;
            }

            const jsonData = JSON.stringify({
               title,
               content,
               category,
               images,
            });

            sendRequest(
               `${API_BASE_URL}/thread?id=${threadId}`,
               'PUT',
               jsonData,
               'application/json',
               (response) => {
                  if (response.success) {
                     showSuccess('Refreshing to thread...');
                     setTimeout(() => (window.location.href = `/thread?id=${threadId}`), 1200);
                  } else {
                     showError(response.error || 'Failed to update thread.');
                  }
               }
            );
         });
      });
   </script>
</body>

</html>