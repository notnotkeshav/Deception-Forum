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
   ]
];