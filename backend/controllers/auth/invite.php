<?php

if($_SERVER['REQUEST_METHOD']==="GET"){
   view("auth/invite.view.php", [
      "heading" => "Invite Page"
   ]);
}else{
   // will generate an Invite code
   $inviteCode = generateInviteCode();
   view("auth/invite.view.php", [
      "heading" => "Invite Page",
      "inviteCode"=> $inviteCode
   ]);
}

