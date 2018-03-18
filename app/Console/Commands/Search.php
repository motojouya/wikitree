<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Search extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'command:search {keyword}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Search Wikipedia';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct() {
      parent::__construct();
  }

  /**
   * WikipediaにリクエストするURLを構築する。
   * リクエストパラメータは以下
   *   - titles: $query (検索対象キーワード)
   *   - action: 'query' (検索アクション)
   *   - prop: 'revisions' (revisionの指定だが本文取得のために必要なパラメータ)
   *   - rvprop: 'content' (記事の何を取得するか contentは本文)
   *   - rvparse: true (パラメータ指定しない)
   *   - format: 'json' (取得形式)
   */
  private function getURL($keyword) {
    $query = urlencode($keyword);
    return "https://ja.wikipedia.org/w/api.php?titles=$query&action=query&prop=revisions&rvprop=content&rvparse&format=json";
  }

  /**
   * Wikipedia APIから取得したJSONから記事本文のHTMLを取得する。
   * 構造的には以下の階層となる。
   *   query > pages > $pageid > revisions > 0 > *
   */
  private function extractHTML($jsonObj) {
    $pages = $jsonObj['query']['pages'];
    $pageid;
    foreach($pages as $key => $val) {
      $pageid = $key;
    }
    return $pages[$pageid]['revisions'][0]['*'];
  }

  /**
   * HTML textをXML textに変換する。
   */
  private function html2xml($html) {
    $domDocument = new \DOMDocument();
    libxml_use_internal_errors(true);
    $domDocument->loadHTML($html);
    libxml_clear_errors();
    return $domDocument->saveXML();
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
  private function linkKeywords($xmlObj) {

    $aAry = $xmlObj->body->div->p->a;
    $keywords = [];

    foreach($aAry as $key => $val) {

      $href = $val['href'];
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
   */
  private function getNextKeyword($keyword) {
    $json = file_get_contents($this->getURL($keyword));
    $jsonFormatted = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $jsonObj = json_decode($jsonFormatted, true);
    $html = $this->extractHTML($jsonObj);
    $xml = $this->html2xml($html);
    $xmlObj = simplexml_load_string($xml);
    return $this->linkKeywords($xmlObj);
  }

  /**
   * キーワードのリストを取得する。
   * 取得する形式は以下となる。
   * array(2) {
   *   ["keyword01"] => array() {}
   * }
   * 
   * 
   * 
   * 
   * 
   */
  private function getKeywordList($keyword, $maxKeywordCount) {

    $keywordList = [];
    $untreated = [$keyword];

    while (count($keywordList) + count($untreated) < $maxKeywordCount + 1) {
      foreach($untreated as $key => $word) {

        sleep(1);
        $nextKeywords = $this->getNextKeyword($word);
        unset($untreated[$key]);

        $keywordList[$word] = $nextKeywords;

        //すでにキーワードリストに存在しなければ、未処理リストにも追加
        //未処理リストに追加することで、繰り返し取得することができる
        foreach($nextKeywords as $nextKey => $nextWord) {
          if (!array_key_exists($nextWord, $keywordList)) {
            $untreated[] = $nextWord;
          }
        }
      }
      $untreated = array_values($untreated);
    }

    //未処理リストにあるものをリストに追加しておく
    foreach($untreated as $key => $word) {
      $keywordList[$word] = NULL;
    }

    return $keywordList;
  }

  /**
   * 取得したキーワードリストから、表示用のツリーを取得する。
   * 取得する形式は以下となる。
   * array(2) {
   *   ["keyword01"] => array() {}
   * }
   * 
   * 
   * 
   * 
   * 
   * 
   */
  private function list2tree($keyword, $keywordList, $maxKeywordCount) {

    $keywordTree = [];
    $keywordTree[$keyword] = [];

    $untreated = [];
    $untreated[$keyword] = &$keywordTree[$keyword];

    $addedList = [];
    $addedList[] = $keyword;
    $keywordTotal = 0;

    while (true) {

      $currentUntreated = $untreated;
      foreach($currentUntreated as $key => $wordUnuse) {

        $nextKeywords = $keywordList[$key];

        if ($nextKeywords) {
          foreach($nextKeywords as $nextKey => $nextWord) {

            if (!in_array($nextWord, $addedList)) {
              $untreated[$nextWord] = [];
              $untreated[$key][$nextWord] = &$untreated[$nextWord];
              $addedList[] = $nextWord;

            } else {
              $untreated[$key][$nextWord] = "@";
            }

            $keywordTotal++;
            if ($keywordTotal === $maxKeywordCount - 1) {
              if ($untreated[$key][$nextWord]) {
                $untreated[$key][$nextWord] = $untreated[$key][$nextWord] + "$";
              } else {
                $untreated[$key][$nextWord] = "$";
              }
              break 3;
            }
          }
        }
        unset($untreated[$key]);
      }

      if (count($untreated) === 0) {
        break;
      }
    }

    return $keywordTree;
  }

  /**
   * Tree構造になっているキーワードを標準出力に出力する。
   */
  private function showKeywords($keywordTree, $level) {

    foreach ($keywordTree as $keyword => $belowTree) {

      foreach(range(0, $level) as $i) {
        echo "  ";
      }

      if (!$belowTree) {
        echo "- $keyword\n";

      } else if (is_string($belowTree)) {
        echo "- $keyword$belowTree\n";

      } else {
        echo "- $keyword\n";
        $this->showKeywords($belowTree, $level + 1);
      }
    }
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {

    $maxKeywordCount = 100;
    $keyword = $this->argument("keyword");

    $keywordList = $this->getKeywordList($keyword, $maxKeywordCount);
    $keywordTree = $this->list2tree($keyword, $keywordList, $maxKeywordCount);
    $this->showKeywords($keywordTree, 0);
  }

}
