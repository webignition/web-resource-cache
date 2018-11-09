<?php

namespace App\Tests\Functional\Services;

use App\Services\CallbackUrlValidator;
use App\Tests\Functional\AbstractFunctionalTestCase;

class CallbackUrlValidatorTest extends AbstractFunctionalTestCase
{
    /**
     * @var CallbackUrlValidator
     */
    private $callbackUrlValidator;

    protected function setUp()
    {
        parent::setUp();

        $this->callbackUrlValidator = self::$container->get('app.services.callbackurlvalidator');
    }

    public function testServiceIsOfExpectedType()
    {
        $this->assertInstanceOf(CallbackUrlValidator::class, $this->callbackUrlValidator);
    }
}
