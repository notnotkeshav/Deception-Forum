<?php
function generateUsername($length)
{
   if ($length < 15 || $length > 25) {
      return "Invalid length. Username must be between 15 and 25 characters.";
   }

   $filename = 'usernames.txt';
   if (!file_exists($filename)) {
      $words = [
         "Skywalker", "Shadow", "Phoenix", "Blizzard", "Nightmare",
         "Thunder", "Falcon", "Titan", "Dragon", "Venom", "Crimson", "Blaze",
         "Nebula", "Starlight", "Quantum", "Vortex", "Inferno", "Mystic", "Voyager",
         "Cyclone", "Gladiator", "Raider", "Ember", "Zephyr", "Onyx", "Raven", "Echo",
         "Horizon", "Tempest", "Blade", "Wraith", "Oracle", "Stealth", "Chaos", "Nova",
         "Aether", "Pulse", "Griffin", "Tornado", "Harbinger", "Lyric", "Guardian",
         "Cipher", "Hawk", "Gale", "Prowler", "Storm", "Rogue", "Eclipse", "Frost", "Sable", 
         "Swift", "Comet", "Zodiac", "Abyss", "Lunar", "Rift", "Viper"
     ];
   } else {
      $words = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   }

   if (empty($words)) {
      return "No words found in usernames.txt";
   }

   do {
      $username = '';
      $usedWords = [];
      $maxLengthForWords = $length - 4;
      while (strlen($username) < $maxLengthForWords) {
         $word = $words[array_rand($words)];
         if (in_array($word, $usedWords)) {
            continue;
         }
         if (strlen($username) + strlen($word) > $maxLengthForWords) {
            break;
         }
         $username .= ucfirst($word);
         $usedWords[] = $word;
      }

      $number = rand(0, 999);
      $numberString = str_pad($number, 3, '0', STR_PAD_LEFT);
      $finalUsername = $username . '#' . $numberString;
   } while (strlen($finalUsername) < 15 || strlen($finalUsername) > 25);

   return $finalUsername;
}


function generateLoginUrl()
{
   return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 15);
}

function generateInviteCode()
{
   return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 25);
}

function generateToken() {
   return bin2hex(random_bytes(35));
}