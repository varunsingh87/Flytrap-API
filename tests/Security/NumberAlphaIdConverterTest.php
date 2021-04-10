<?php 

namespace Flytrap\Security;

use PHPUnit\Framework\TestCase;

class NumberAlphaIdConverterTest extends TestCase {
    public function testGenerateId() {
        $converter = new NumberAlphaIdConverter(10);
        $generatedId = $converter->generateId();

        $this->assertEquals(10, strlen($generatedId));
    }
}


?>