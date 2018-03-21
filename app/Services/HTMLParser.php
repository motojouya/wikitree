<?php

namespace App\Services;

class HTMLParser
{

  /**
   * Create a new command instance.
   *
   * @return void
   */
  private function __construct() {}

  /**
   * HTML textをXML textに変換する。
   */
  public static function html2xml($html) {
    $domDocument = new \DOMDocument();
    libxml_use_internal_errors(true);
    $domDocument->loadHTML($html);
    libxml_clear_errors();
    return $domDocument->saveXML();
  }

}
