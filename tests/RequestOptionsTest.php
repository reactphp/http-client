<?php

namespace React\Tests\HttpClient;

use React\HttpClient\RequestOptions;

class RequestOptionsTest extends TestCase
{
    /** @test */
    public function requestOptionsShouldConstructWithoutOptions()
    {
        new RequestOptions();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown option "I'm a teapot"
     */
    public function requestOptionsShouldRejectUnknownOptions()
    {
        new RequestOptions(array(
            "I'm a teapot" => true,
        ));
    }

    /** @test */
    public function requestOptionsShouldSupportFollowRedirectsOption()
    {
        //Default value should be FALSE.
        $requestOptions = new RequestOptions();
        $this->assertFalse($requestOptions->shouldFollowRedirects());

        //Providing a different value should be respected.
        $requestOptions = new RequestOptions(array(
            'followRedirects' => true,
        ));
        $this->assertTrue($requestOptions->shouldFollowRedirects());

        //Should check for boolean type.
        $this->setExpectedException(
          'InvalidArgumentException',
          'Option "followRedirects" should be a boolean'
        );
        new RequestOptions(array(
            'followRedirects' => 7,
        ));
    }

    /** @test */
    public function requestOptionsShouldSupportMaxRedirectsOption()
    {
        //Default value should be 5.
        $requestOptions = new RequestOptions();
        $this->assertSame(5, $requestOptions->getMaxRedirects());

        //Providing a different value should be respected.
        $requestOptions = new RequestOptions(array(
            'maxRedirects' => 42,
        ));
        $this->assertSame(42, $requestOptions->getMaxRedirects());

        //Should check for integer type.
        $this->setExpectedException(
          'InvalidArgumentException',
          'Option "maxRedirects" should be an integer'
        );
        new RequestOptions(array(
            'maxRedirects' => 7.5,
        ));
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Option "maxRedirects" should be -1 or greater
     */
    public function requestOptionsShouldCheckValidMaxRedirectsRange()
    {
        new RequestOptions(array(
            'maxRedirects' => -3,
        ));
    }
}
