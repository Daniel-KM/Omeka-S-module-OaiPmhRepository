<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\Controller;

use OaiPmhRepository\OaiPmh\ResponseGenerator;
use Zend\Mvc\Controller\AbstractActionController;

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
        $oaiRepository = $this->params()->fromRoute('oai-repository');
        $oaiRepositoryOption = $oaiRepository === 'global'
            ? $this->settings()->get('oaipmhrepository_global_repository')
            : $this->settings()->get('oaipmhrepository_by_site_repository');
        if (empty($oaiRepositoryOption) || $oaiRepositoryOption === 'disabled') {
            return  $this->notFoundAction();
        }

        $request = $this->getRequest();
        $oaiResponse = new ResponseGenerator($request, $this->serviceLocator);

        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');
        $response->setContent((string) $oaiResponse);

        return $response;
    }

    public function redirectAction()
    {
        $urlHelper = $this->viewHelpers()->get('url');
        $url = $urlHelper('oai-pmh', [], ['query' => $this->params()->fromQuery()]);
        return $this->redirect()->toUrl($url)->setStatusCode(301);
    }
}
