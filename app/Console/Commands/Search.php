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
   * Wikipedia�Ƀ��N�G�X�g����URL���\�z����B
   * ���N�G�X�g�p�����[�^�͈ȉ�
   *   - titles: $query (�����ΏۃL�[���[�h)
   *   - action: 'query' (�����A�N�V����)
   *   - prop: 'revisions' (revision�̎w�肾���{���擾�̂��߂ɕK�v�ȃp�����[�^)
   *   - rvprop: 'content' (�L���̉����擾���邩 content�͖{��)
   *   - rvparse: true (�p�����[�^�w�肵�Ȃ�)
   *   - format: 'json' (�擾�`��)
   */
  private function getURL($keyword) {
    $query = urlencode($keyword);
    return "https://ja.wikipedia.org/w/api.php?titles=$query&action=query&prop=revisions&rvprop=content&rvparse&format=json";
  }

  /**
   * Wikipedia API����擾����JSON����L���{����HTML���擾����B
   * �\���I�ɂ͈ȉ��̊K�w�ƂȂ�B
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
   * HTML text��XML text�ɕϊ�����B
   */
  private function html2xml($html) {
    $domDocument = new \DOMDocument();
    libxml_use_internal_errors(true);
    $domDocument->loadHTML($html);
    libxml_clear_errors();
    return $domDocument->saveXML();
  }

  /**
   * XML�I�u�W�F�N�g����L�[���[�h�̃��X�g���擾����B
   * �\���I�ɂ͈ȉ��̊K�w�ƂȂ�B
   *   body > div > p > a
   *
   * ����ɏ�L��a�^�O�̒���href�v�f����L�[���[�h�𒊏o����B
   * href�v�f�͈ȉ���z��B
   *   /wiki/�L�[���[�h#�n�b�V���^�O
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
   * �L�[���[�h����wikipedia���������A���̃L�[���[�h���擾����B
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
   * �L�[���[�h�̃��X�g���擾����B
   * �擾����`���͈ȉ��ƂȂ�B
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

        //���łɃL�[���[�h���X�g�ɑ��݂��Ȃ���΁A���������X�g�ɂ��ǉ�
        //���������X�g�ɒǉ����邱�ƂŁA�J��Ԃ��擾���邱�Ƃ��ł���
        foreach($nextKeywords as $nextKey => $nextWord) {
          if (!array_key_exists($nextWord, $keywordList)) {
            $untreated[] = $nextWord;
          }
        }
      }
      $untreated = array_values($untreated);
    }

    //���������X�g�ɂ�����̂����X�g�ɒǉ����Ă���
    foreach($untreated as $key => $word) {
      $keywordList[$word] = NULL;
    }

    return $keywordList;
  }

  /**
   * �擾�����L�[���[�h���X�g����A�\���p�̃c���[���擾����B
   * �擾����`���͈ȉ��ƂȂ�B
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
   * Tree�\���ɂȂ��Ă���L�[���[�h��W���o�͂ɏo�͂���B
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
