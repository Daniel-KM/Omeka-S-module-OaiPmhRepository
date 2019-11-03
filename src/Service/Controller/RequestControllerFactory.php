<?php
/**
 * @author Julian Maurice <julian.maurice@biblibre.com>
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\Service\Controller;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\Controller\RequestController;
use Zend\ServiceManager\Factory\FactoryInterface;

class RequestControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new RequestController($services);
    }
}
