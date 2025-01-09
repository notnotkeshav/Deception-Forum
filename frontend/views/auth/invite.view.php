<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-5">
   <h1 class="mb-4"><?= $heading ?? "Invite Page" ?></h1>

   <!-- Table to show invite codes -->
   <div class="table-responsive">
      <table class="table table-bordered table-hover">
         <thead class="thead-dark">
            <tr>
               <th>Code</th>
               <th>Used</th>
               <th>Used By</th>
               <th>Created At</th>
            </tr>
         </thead>
         <tbody id="inviteTable">
            <?php if (!empty($inviteCodes)) : ?>
               <?php foreach ($inviteCodes as $invite) : ?>
                  <tr>
                     <td><?= htmlspecialchars($invite['code']) ?></td>
                     <td><?= $invite['used'] ? "Yes" : "No" ?></td>
                     <td><?= htmlspecialchars($invite['usedByName'] ?? "N/A") ?></td>
                     <td><?= htmlspecialchars($invite['createdAt']) ?></td>
                  </tr>
               <?php endforeach; ?>
            <?php else : ?>
               <tr>
                  <td colspan="5" class="text-center">No invite codes found.</td>
               </tr>
            <?php endif; ?>
         </tbody>
      </table>
   </div>

   <!-- Button to generate new invite code --><br><br>
   <div id="inviteCode" class="my-3">
      <?= $inviteCode ?? "Click the button to generate an invite code." ?>
   </div>
   <br>
   <button id="generateInviteBtn" class="btn btn-primary">Generate Invite Code</button>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>

<script>
   $(document).ready(function() {
      $("#generateInviteBtn").on("click", function() {
         $.ajax({
            url: window.location.href,
            type: "POST",
            dataType: "json",
            success: function(response) {
               if (response.success) {
                  var defaultText = $("#inviteCode").text();
                  var styledMessage = '<span style="color: green; font-weight: bold;">' + response.message + '</span>';
                  // Update the text with the styled response message
                  $("#inviteCode").html(styledMessage);
                  setTimeout(function() {
                     $("#inviteCode").text(defaultText);
                  }, 2000);

                  let newRow = `
                            <tr>
                                <td>${response.details.inviteCode}</td>
                                <td>No</td>
                                <td>N/A</td>
                                <td>${response.details.createdAt}</td>
                            </tr>
                        `;
                  $("#inviteTable").append(newRow);
               } else {
                  alert("Failed to generate invite code: " + response.error);
               }
            },
            error: function(xhr) {
               alert("Error: " + xhr.responseText);
            },
         });
      });
   });
</script>