<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

$autoloadFilePaths = [
    '../../../vendor/autoload.php',
    'vendor/autoload.php',
];

$autoloadFilePath = 'vendor/autoload.php';

if (file_exists('../../../vendor/autoload.php')) {
    $autoloadFilePath = '../../../vendor/autoload.php';
}

require_once($autoloadFilePath);
