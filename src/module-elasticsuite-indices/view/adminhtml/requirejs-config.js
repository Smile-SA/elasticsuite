/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

var config = {
    paths: {
        'jquery.json-viewer' : 'Smile_ElasticsuiteIndices/js/jquery.json-viewer'
    },
    shim: {
        'jquery.json-viewer': {
            deps: ['jquery']
        }
    },
    map: {
        '*': {
            jsonViewer: 'Smile_ElasticsuiteIndices/js/jquery.json-viewer'
        }
    }
};
