<?php
return [
   "database" => [
      "host" => getenv("DB_HOST"),
      "port" => getenv("DB_PORT"),
      "dbname" => getenv("DB_NAME"),
      "charset" => getenv("DB_CHARSET"),
   ],
   "services"=>[
      "pre_render"=>[
         "token"=>"",
      ]
   ],
   "mailer" => [
       "host" => getenv("MAILER_HOST"),
       "port" => getenv("MAILER_PORT"),
       "username" => getenv("MAILER_USERNAME"),
       "password" => getenv("MAILER_PASSWORD"),
       "encryption" => getenv("MAILER_ENCRYPTION"), // Optional: "ssl" or "tls"
       "fromEmail" => getenv("MAILER_FROM_EMAIL"),
       "fromName" => getenv("MAILER_FROM_NAME"),
   ],
];