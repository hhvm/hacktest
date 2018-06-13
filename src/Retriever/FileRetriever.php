<?hh // strict

namespace Facebook\HackTest;

use type Facebook\DefinitionFinder\{FileParser};

use HH\Lib\Str;
class FileRetriever {

  public function __construct(private string $dir = '') {
    $this->dir = $dir;
  }

  public function getAllFiles(): vec<FileParser> {
    $vec = vec[];
    $glob = \glob($this->dir.'/*Test.php');
    foreach ($glob as $filename) {
      \printf("%s\n", $filename);
      $vec[] = FileParser::FromFile($filename);
    }
    // TODO: test individual files

    return $vec;
  }

}
