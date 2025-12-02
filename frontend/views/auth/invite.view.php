<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>⛧ Gather Lost Souls ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
   <style>
      @font-face {
         font-family: 'vamp';
         src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
      }

      body {
         background: #000;
         color: #f8f8f8;
         font-family: 'Courier New', monospace;
         margin: 0;
         padding: 0;
      }

      .hacker-container {
         max-width: 1100px;
         margin: 3rem auto 2rem auto;
         background: #111;
         border: 2px solid #960d0d;
         box-shadow: 0 0 22px #a00a, 0 0 30px #a00a0a11 inset;
         padding: 2.3rem 2.5rem 2rem 2.5rem;
         border-radius: 6px;
      }

      .hacker-title {
         text-align: center;
         color: #f03;
         font-family: 'vamp', sans-serif;
         font-size: 2.3rem;
         letter-spacing: 2px;
         margin-bottom: 2rem;
         text-shadow: 0 0 14px rgba(255, 0, 0, 0.5);
         border-bottom: 1.5px solid #960d0d;
         padding-bottom: .8rem;
      }

      .invite-table-wrapper {
         background: #0a0a0a;
         border: 1.5px solid #960d0d;
         border-radius: 5px;
         padding: 1rem;
         margin-bottom: 2rem;
         overflow-x: auto;
      }

      .invite-table {
         width: 100%;
         border-collapse: collapse;
         font-size: 0.95rem;
      }

      .invite-table thead {
         border-bottom: 2px solid #960d0d;
      }

      .invite-table th {
         color: #f03;
         font-weight: bold;
         text-transform: uppercase;
         padding: 0.9rem 1rem;
         text-align: center;
         letter-spacing: 1px;
         font-size: 0.95rem;
      }

      .invite-table tbody tr {
         border-bottom: 1px solid #333;
         transition: background 0.2s;
      }

      .invite-table tbody tr:hover {
         background: #1a0000;
      }

      .invite-table td {
         padding: 0.8rem 1rem;
         text-align: center;
         color: #ddd;
      }

      .invite-table tbody tr:last-child {
         border-bottom: none;
      }

      .empty-row {
         color: #777 !important;
         font-style: italic;
      }

      .invite-message {
         text-align: center;
         font-size: 1.15rem;
         color: #aaa;
         margin: 1.5rem 0;
         padding: 1rem;
         background: #0a0a0a;
         border: 1px solid #333;
         border-radius: 4px;
      }

      .success-message {
         color: #00ff00 !important;
         font-weight: bold;
      }

      .generate-btn {
         display: block;
         width: 100%;
         max-width: 500px;
         margin: 2rem auto 0 auto;
         background: #1a0000;
         border: 2px solid #960d0d;
         color: #f03;
         font-family: 'Courier New', monospace;
         font-size: 1.4rem;
         font-weight: bold;
         padding: 0.9rem 1.5rem;
         border-radius: 5px;
         cursor: pointer;
         letter-spacing: 2px;
         text-transform: uppercase;
         transition: background 0.25s, color 0.25s, box-shadow 0.25s;
         box-shadow: 0 0 18px #a00a;
      }

      .generate-btn:hover {
         background: #960d0d;
         color: #fff;
         box-shadow: 0 0 22px #f03;
      }

      .yes-badge {
         color: #00ff00;
         font-weight: bold;
      }

      .no-badge {
         color: #f03;
         font-weight: bold;
      }
   </style>
</head>

<body>

   <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

   <div class="hacker-container">
      <div class="hacker-title">⛧ Gather Lost Souls ⛧</div>

      <div class="invite-table-wrapper">
         <table class="invite-table">
            <thead>
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
                        <td><span class="<?= $invite['used'] ? 'yes-badge' : 'no-badge' ?>"><?= $invite['used'] ? "Yes" : "No" ?></span></td>
                        <td><?= htmlspecialchars($invite['usedByName'] ?? "N/A") ?></td>
                        <td><?= htmlspecialchars($invite['createdAt']) ?></td>
                     </tr>
                  <?php endforeach; ?>
               <?php else : ?>
                  <tr>
                     <td colspan="4" class="empty-row">No invite codes found.</td>
                  </tr>
               <?php endif; ?>
            </tbody>
         </table>
      </div>

      <div id="inviteCode" class="invite-message">
         <?= htmlspecialchars("Click the button below to generate an invite code.") ?>
         <?= htmlspecialchars("Everything is monitored by the Red Skull.") ?>
      </div>

      <button id="generateInviteBtn" class="generate-btn">Generate Invite Code</button>
   </div>

   <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
   <script>
      $(document).ready(function() {
         $("#generateInviteBtn").on("click", function() {
            $.ajax({
               url: window.location.href,
               type: "POST",
               dataType: "json",
               success: function(response) {
                  if (response.success) {
                     var defaultText = "Click the button below to generate an invite code.";
                     var styledMessage = '<span class="success-message">' + response.message + '</span>';
                     $("#inviteCode").html(styledMessage);

                     setTimeout(function() {
                        $("#inviteCode").text(defaultText);
                     }, 2000);

                     let newRow = `
                     <tr>
                        <td>${response.details.inviteCode}</td>
                        <td><span class="no-badge">No</span></td>
                        <td>N/A</td>
                        <td>${response.details.createdAt}</td>
                     </tr>
                  `;
                     $("#inviteTable").append(newRow);

                     // Remove empty row if it exists
                     $(".empty-row").closest("tr").remove();
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

</body>

</html>