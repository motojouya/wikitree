<?php

namespace App\Services;

use App\Services\HTMLParser;

class WikipediaAccess
{

  /**
   * Create a new command instance.
   *
   * @return void
   */
  private function __construct() {}

  /**
   * WikipediaにリクエストするURLを構築する。
   * リクエストパラメータは以下
   *   - titles: $query (検索対象キーワード)
   *   - action: 'query' (検索アクション)
   *   - prop: 'revisions' (revisionの指定だが本文取得のために必要なパラメータ)
   *   - rvprop: 'content' (記事の何を取得するか contentは本文)
   *   - rvparse: true (パラメータ指定しない)
   *   - format: 'json' (取得形式)
   *
   * 詳細は以下を参照
   * https://www.mediawiki.org/wiki/API:Main_page/ja
   *
   */
  private static function getURL($keyword) {
    $query = urlencode($keyword);
    return "https://ja.wikipedia.org/w/api.php?titles=$query&action=query&prop=revisions&rvprop=content&rvparse&format=json";
  }

  /**
   * Wikipedia APIから取得したJSONから記事本文のHTMLを取得する。
   * 構造的には以下の階層となる。
   *   query > pages > $pageid > revisions > 0 > *
   */
  private static function extractHTML($jsonObj) {
    $pages = $jsonObj['query']['pages'];
    $pageid;
    foreach($pages as $key => $val) {
      $pageid = $key;
    }
    $htmlWrap = $pages[$pageid];
    if (array_key_exists('revisions', $htmlWrap)) {
      return $htmlWrap['revisions'][0]['*'];
    } else {
      return null;
    }
  }

  /**
   * XMLオブジェクトからキーワードのリストを取得する。
   * 構造的には以下の階層となる。
   *   body > div > p > a
   *
   * さらに上記のaタグの中のhref要素からキーワードを抽出する。
   * href要素は以下を想定。
   *   /wiki/キーワード#ハッシュタグ
   */
  private static function linkKeywords($xmlObj) {

    $aAry = $xmlObj->body->div->p->a;
    $keywords = [];

    foreach($aAry as $key => $val) {

      $href = $val['href'];

      if ((strpos($href, '/w/index.php') === 0)) {
        continue;
      }

      if (strpos($href, '#')) {
        $hrefHashdevide = explode("#", $href);
        $href = $hrefHashdevide[0];
      }

      $directries = explode("/", urldecode($href));
      if (count($directries) > 2) {
        $keywords[] = $directries[2];
      }
    }
    return $keywords;
  }

  /**
   * キーワードからwikipediaを検索し、次のキーワードを取得する。
   * WikipediaからはJSON形式で取得するので、その中のHTMLを取り出し、
   * そこからキーワードを取り出す。
   */
  public static function getNextKeyword($keyword) {

    $json = file_get_contents(WikipediaAccess::getURL($keyword));
    $jsonFormatted = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $jsonObj = json_decode($jsonFormatted, true);

    $html = WikipediaAccess::extractHTML($jsonObj);
    if ($html) {
      $xml = HTMLParser::html2xml($html);
      $xmlObj = simplexml_load_string($xml);
      return WikipediaAccess::linkKeywords($xmlObj);

    } else {
      return $html;
    }
  }

}
