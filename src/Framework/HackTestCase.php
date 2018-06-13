<?hh

class HackTestCase {

  public function __construct(private string $className = '', private ?vec<string> $methodNames = null) {
    $this->className = $className;
    $this->methodNames = $methodNames;
  }

  public function run(): void {
    if ($this->methodNames !== null) {
      foreach ($this->methodNames as $method) {
        printf("%s ", $method);
        $instance = new $this->className();
        $instance->$method();
        printf("Passed.\n");
        // TODO: await for async tests
      }
    }
  }
}
