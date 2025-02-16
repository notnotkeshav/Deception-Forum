<?php

namespace Backend\Core;

class TemplateLoader
{
   private $templateDir;

   public function __construct($templateDir)
   {
      $this->templateDir = rtrim($templateDir, '/');
   }

   public function render($templateName, $variables = [])
   {
      $filePath = "{$this->templateDir}/{$templateName}";

      if (!file_exists($filePath)) {
         throw new \Exception("Template file not found: {$filePath}");
      }

      $content = file_get_contents($filePath);

      foreach ($variables as $key => $value) {
         $content = str_replace("{" . $key . "}", $value, $content);
      }

      return $content;
   }
}
