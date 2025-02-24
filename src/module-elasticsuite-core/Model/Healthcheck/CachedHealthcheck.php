<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;

/**
 * Cached healthcheck.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class CachedHealthcheck implements CheckInterface
{
    /** @var string  */
    private $identifier;

    /** @var string  */
    private $status;

    /** @var string  */
    private $description;

    /**
     * Constructor.
     *
     * @param string $identifier  Check identifier.
     * @param string $status      Check status.
     * @param string $description Check description.
     */
    public function __construct($identifier, $status, $description)
    {
        $this->identifier = $identifier;
        $this->status = $status;
        $this->description = $description;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
