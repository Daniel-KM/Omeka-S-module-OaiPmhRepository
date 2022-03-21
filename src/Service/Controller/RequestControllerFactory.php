<?php declare(strict_types=1);
/**
 * @author Julian Maurice <julian.maurice@biblibre.com>
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\Service\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\Controller\RequestController;

class RequestControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new RequestController($services);
    }
}
