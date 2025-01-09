<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

try {
   // Check if access level is greater than or equal to 5
   if ((isset($_SESSION['user']['accessLevel']) && $_SESSION['user']['accessLevel'] >= 5) || $_SESSION['moderator']) {
      if ($_SERVER['REQUEST_METHOD'] === "GET") {
         // Fetch invite codes
         $stmt = $db->query("
                  SELECT 
                     ic.code,
                     ic.used,
                     u2.name AS usedByName,
                     ic.createdAt
                  FROM inviteCodes ic
                  LEFT JOIN users u2 ON ic.usedBy = u2.id
                  WHERE ic.generatorId = :userId
               ", [":userId" => $_SESSION['userId']]);

         $inviteCodes = $db->getAll($stmt);

         // Render the view
         view("auth/invite.view.php", [
            "heading" => "Invite Page",
            "inviteCodes" => $inviteCodes
         ]);
      } elseif ($_SERVER['REQUEST_METHOD'] === "POST") {
         // Start database transaction
         $db->beginTransaction();

         // Generate a new invite code
         $code = generateInviteCode();
         $generatorId = $_SESSION['userId'];

         // Insert new invite code
         $db->query(
            "INSERT INTO inviteCodes (code, generatorId) VALUES (:code, :generatorId)",
            [
               ":code" => $code,
               ":generatorId" => $generatorId
            ]
         );

         $db->commit();
         sendJsonResponse(true, "Invite code generated successfully.", ["inviteCode" => $code, "createdAt" => date("Y-m-d H:i:s")], 201);
      } else {
         // Invalid HTTP method
         sendJsonResponse(false, "Method Not Allowed. Use GET or POST.", [], 405);
      }
   } else {
      // Access level is less than 5
      sendJsonResponse(false, "You do not have sufficient permissions to view or generate invite codes.", [], 403);
   }
} catch (Exception $e) {
   // Handle exceptions and rollback transactions
   if ($db->inTransaction()) {
      $db->rollBack();
   }
   error_log($e->getMessage());
   sendJsonResponse(false, $e->getMessage(), [], 500);
}
