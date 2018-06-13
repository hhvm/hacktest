<?hh // strict

namespace Facebook\HackTest;

use type Facebook\DefinitionFinder\{
  FileParser,
};

use function Facebook\FBExpect\expect;
use HH\Lib\{C, Str};

class ClassRetriever {

  public function __construct(private FileParser $fp) {
    $this->fp = $fp;
  }

  public function getTestClassName(): string {
    $name = '';
    foreach ($this->fp->getClassNames() as $name) {
      // TODO: expect only one test class
      // TODO: expect extends new base class
      $classname = $name
        |> Str\split($$, '\\')
        |> C\lastx($$);
      $filename = $this->fp->getFilename()
        |> Str\split($$, '/')
        |> C\lastx($$)
        |> Str\strip_suffix($$, '.php');

      invariant($classname === $filename, 'Class name is not the same as file name.');
      expect($classname)->toBePHPEqual($filename);
    }


    return $name;
  }

}
