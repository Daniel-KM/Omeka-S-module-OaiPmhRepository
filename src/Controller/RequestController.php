<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use OaiPmhRepository\ResponseGenerator;

/**
 * Request page controller.
 *
 * The controller for the outward-facing segment of the repository plugin.  It
 * processes queries, and produces the response in XML format.
 *
 * @uses ResponseGenerator
 */
class RequestController extends AbstractActionController
{
    protected $serviceLocator;

    public function __construct($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function indexAction()
    {
        $query = $this->params()->fromQuery();
        $oaiResponse = new ResponseGenerator($query, $this->serviceLocator);

        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');
        $response->setContent((string) $oaiResponse);

        return $response;
    }
}
