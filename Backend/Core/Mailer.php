<?php

namespace Backend\Core;

class Mailer
{
   private $fromEmail;
   private $fromName;

   public function __construct(array $config)
   {
      $this->fromEmail = $config['fromEmail'];
      $this->fromName = $config['fromName'];
   }

   public function sendText($to, $subject, $message, $headers = [])
   {
      $headerString = $this->prepareHeaders($to, $subject, $headers);
      $headerString .= "Content-Type: text/plain; charset=UTF-8\r\n";

      if (mail($to, $subject, $message, $headerString)) {
         return true;
      } else {
         throw new \Exception("Failed to send text email.");
      }
   }

   public function sendHTML($to, $subject, $message, $headers = [])
   {
      $headerString = $this->prepareHeaders($to, $subject, $headers);
      $headerString .= "Content-Type: text/html; charset=UTF-8\r\n";

      if (mail($to, $subject, $message, $headerString)) {
         return true;
      } else {
         throw new \Exception("Failed to send HTML email.");
      }
   }

   public function send($to, $subject, $message, $headers = [], $isHtml = false)
   {
      $headerString = $this->prepareHeaders($to, $subject, $headers);

      if ($isHtml) {
         $headerString .= "Content-Type: text/html; charset=UTF-8\r\n";
      } else {
         $headerString .= "Content-Type: text/plain; charset=UTF-8\r\n";
      }

      // Send the email using PHP's mail() function
      if (mail($to, $subject, $message, $headerString)) {
         return true;
      } else {
         throw new \Exception("Failed to send email.");
      }
   }

   private function prepareHeaders($to, $subject, $headers)
   {
      $headerString = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
      $headerString .= "MIME-Version: 1.0\r\n";

      foreach ($headers as $key => $value) {
         $headerString .= "$key: $value\r\n";
      }

      return $headerString;
   }
}
