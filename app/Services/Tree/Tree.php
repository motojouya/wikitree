<?php

namespace App\Services\Tree;

class Tree
{

  /**
   * Create a new command instance.
   *
   * @return void
   */
  private function __construct() {}

  /**
   * 引数に与えられたcallbackからデータを取得し、木を構築する。
   *
   * 構築は幅優先で構築される。
   * このとき、既出のキーワードがあった場合は、
   * そのノードは探索終了し、"@"を末端とする。
   *
   * 引数に与えられたノード数となるまで探索し、
   * 探索を打ち切ったノードの末端は"$"とする。
   * 上記のノード数にはルートも含める。
   *
   * 結果例)
   * array(1) {
   *   ["キーワード1"]=>
   *   array(2) {
   *     ["キーワード2"]=>
   *     array(3) {
   *       ["キーワード4"]=>
   *       array(0) {
   *       }
   *       ["キーワード5"]=>
   *       array(0) {
   *       }
   *       ["キーワード6"]=>
   *       array(0) {
   *       }
   *     }
   *     ["キーワード3"]=>
   *     array(2) {
   *       ["キーワード2"]=>
   *       string(1) "@"
   *       ["キーワード7"]=>
   *       string(1) "$"
   *     }
   *   }
   * }
   *
   * 本関数のアルゴリズムについては、同ディレクトリ上の以下ファイルを参照
   * build_tree_algorithm.svg
   *
   */
  public static function build($firstword, $keywordLimit, callable $getNextKeywords) {

    $keywordTree = [];
    $keywordTree[$firstword] = [];

    $untreated = [];
    $untreated[$firstword] = &$keywordTree[$firstword];

    $addedList = [];
    $addedList[] = $firstword;
    $keywordCount = 0;

    while (true) {

      $currentUntreated = $untreated;
      foreach($currentUntreated as $keyword => $_unuse) {

        $nextKeywords = $getNextKeywords($keyword);

        if ($nextKeywords) {
          foreach($nextKeywords as $nextword) {

            if (!in_array($nextword, $addedList)) {
              $untreated[$nextword] = [];
              $untreated[$keyword][$nextword] = &$untreated[$nextword];
              $addedList[] = $nextword;

            } else {
              $untreated[$keyword][$nextword] = "@";
            }

            $keywordCount++;
            if ($keywordCount === $keywordLimit - 1) {
              if ($untreated[$keyword][$nextword]) {
                $untreated[$keyword][$nextword] = $untreated[$keyword][$nextword] . "$";
              } else {
                $untreated[$keyword][$nextword] = "$";
              }
              break 3;
            }
          }
        }
        unset($untreated[$keyword]);
      }

      if (count($untreated) === 0) {
        break;
      }
    }

    return $keywordTree;
  }

}
