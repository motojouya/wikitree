<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WikipediaAccess;
use App\Services\Tree\Tree;

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
   * いくつのキーワードを取得するか
   *
   */
  private $keywordLimit = 100;

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct() {
      parent::__construct();
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
   * コマンドを実行する。
   * 引数に与えられたキーワードから、再帰的にWikipediaにアクセスし、
   * キーワードツリーを構築し、出力する。
   *
   * @return mixed
   */
  public function handle() {

    $argKeyword = $this->argument("keyword");

    $keywordTree = Tree::build($argKeyword, $this->keywordLimit, function ($keyword) {
      sleep(1);
      return WikipediaAccess::getNextKeyword($keyword);
    });

    if ($keywordTree[$argKeyword]) {
      $this->showKeywords($keywordTree, 0);
    } else {
      echo "入力されたキーワードはWikipediaに存在しないようです。\n";
    }
  }

}
