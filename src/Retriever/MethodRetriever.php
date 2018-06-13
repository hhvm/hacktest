<?hh

namespace Facebook\HackTest;

use type Facebook\DefinitionFinder\{
  ScannedClass,
  FileParser,
};

use function Facebook\FBExpect\expect;
use HH\Lib\Str;

class MethodRetriever {

  public function __construct(private ScannedClass $sbc) {
    $this->sbc = $sbc;
  }

  public function getTestMethodNames(): vec<string> {
    $methods = $this->sbc->getMethods();
    $method_names = vec[];
    foreach ($methods as $method) {
      $method_name = $method->getName();

      $method_names[] = $method_name;

      // TODO: set up data providers
      if (!Str\starts_with($method_name, "provide")) {
        invariant($method->isPublic(), "Test methods must be public.");
        expect($method_name)->toMatchRegExp('/^test/');
      }

      // TODO: expect void return type for non-async methods
      // TODO: expect async keyword and Awaitable<void> return type for async methods
    }
    return $method_names;
  }
}
