<?php

namespace React\HttpClient;

use InvalidArgumentException;

class RequestOptions
{
    /**
     * Whether or not to follow redirects for this requests.
     *
     * @var boolean
     */
    private $followRedirects = false;

    /**
     * Maximum amount of redirects.
     * Note: -1 is unlimited, 0 means no redirects will be accepted.
     *
     * @var integer
     */
    private $maxRedirects = 5;

    /**
     * Creates a new instance.
     *
     * @param array|null $options The optionally provided options.
     */
    public function __construct(array $options = null)
    {
        if (!empty($options)) {
            $this->parseOptions($options);
        }
    }

    /**
     * This will enforce only known options are used and in the right format.
     *
     * @param array $options The provided options.
     */
    protected function parseOptions(array $options)
    {
        foreach ($options as $option => $value) {
            switch ($option) {
                case 'followRedirects':
                    if (!is_bool($value)) {
                        throw new InvalidArgumentException('Option "followRedirects" should be a boolean');
                    }
                    $this->followRedirects = $value;
                    break;

                case 'maxRedirects':
                    if (!is_int($value)) {
                        throw new InvalidArgumentException('Option "maxRedirects" should be an integer');
                    }
                    if ($value < -1) {
                        throw new InvalidArgumentException('Option "maxRedirects" should be -1 or greater');
                    }
                    $this->maxRedirects = $value;
                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unknown option "%s"', $option));
                    break;
            }
        }
    }

    /**
     * Whether or not to follow redirects for this requests.
     *
     * @return boolean
     */
    public function shouldFollowRedirects()
    {
        return $this->followRedirects;
    }

    /**
     * Maximum amount of redirects.
     * Note: -1 is unlimited, 0 means no redirects will be accepted.
     *
     * @return integer
     */
    public function getMaxRedirects()
    {
        return $this->maxRedirects;
    }
}
