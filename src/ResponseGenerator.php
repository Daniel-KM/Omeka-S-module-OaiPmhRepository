<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository;

use DomDocument;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Zend\Http\Request;

/**
 * ResponseGenerator generates the XML responses to OAI-PMH
 * requests recieved by the repository.  The DOM extension is used to generate
 * all the XML output on-the-fly.
 */
class ResponseGenerator extends XmlGeneratorAbstract
{
    /**
     * General OAI-PMH constants
     */
    const OAI_PMH_NAMESPACE_URI = 'http://www.openarchives.org/OAI/2.0/';
    const OAI_PMH_SCHEMA_URI = 'http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd';
    const OAI_PMH_PROTOCOL_VERSION = '2.0';

    /**
     * Error codes
     */
    const OAI_ERR_BAD_ARGUMENT = 'badArgument';
    const OAI_ERR_BAD_RESUMPTION_TOKEN = 'badResumptionToken';
    const OAI_ERR_BAD_VERB = 'badVerb';
    const OAI_ERR_CANNOT_DISSEMINATE_FORMAT = 'cannotDisseminateFormat';
    const OAI_ERR_ID_DOES_NOT_EXIST = 'idDoesNotExist';
    const OAI_ERR_NO_RECORDS_MATCH = 'noRecordsMatch';
    const OAI_ERR_NO_METADATA_FORMATS = 'noMetadataFormats';
    const OAI_ERR_NO_SET_HIERARCHY = 'noSetHierarchy';

    /*
     * Date/time constants
     */
    const OAI_DATE_PCRE = '/^\\d{4}\\-\\d{2}\\-\\d{2}$/';
    const OAI_DATETIME_PCRE = '/^\\d{4}\\-\\d{2}\\-\\d{2}T\\d{2}\\:\\d{2}\\:\\d{2}Z$/';

    const OAI_GRANULARITY_STRING = 'YYYY-MM-DDThh:mm:ssZ';
    const OAI_GRANULARITY_DATE = 1;
    const OAI_GRANULARITY_DATETIME = 2;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var DOMDocument
     */
    protected $document;

    /**
     * HTTP query string or POST vars formatted as an associative array.
     *
     * @var array
     */
    private $query;

    private $_listLimit;

    private $_tokenExpirationTime;

    protected $serviceLocator;

    /**
     * Flags if an error has occurred during the response.
     *
     * @var bool
     */
    protected $error;

    /**
     * Returns the granularity of the given utcDateTime string.  Returns zero
     * if the given string is not in utcDateTime format.
     *
     * @param string $dateTime Time string
     *
     * @return int OAI_GRANULARITY_DATE, OAI_GRANULARITY_DATETIME, or zero
     */
    public static function getGranularity($dateTime)
    {
        if (preg_match(self::OAI_DATE_PCRE, $dateTime)) {
            return self::OAI_GRANULARITY_DATE;
        } elseif (preg_match(self::OAI_DATETIME_PCRE, $dateTime)) {
            return self::OAI_GRANULARITY_DATETIME;
        } else {
            return false;
        }
    }

    /**
     * Constructor.
     *
     * Creates the DomDocument object, and adds XML elements common to all
     * OAI-PMH responses.  Dispatches control to appropriate verb, if any.
     *
     * @param Request $request HTTP POST/GET query key-value pair array
     *
     * @uses dispatchRequest()
     */
    public function __construct(Request $request, $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        $settings = $serviceLocator->get('Omeka\Settings');

        $this->_loadConfig();

        $this->error = false;
        $this->request = $request;
        $this->query = $request->getQuery()->toArray();
        $this->document = new DomDocument('1.0', 'UTF-8');

        OaiIdentifier::initializeNamespace($settings->get('oaipmh_repository_namespace_id'));

        //formatOutput makes DOM output "pretty" XML.  Good for debugging, but
        //adds some overhead, especially on large outputs.
        $this->document->formatOutput = true;
        $this->document->xmlStandalone = true;

        $root = $this->document->createElementNS(self::OAI_PMH_NAMESPACE_URI,
            'OAI-PMH');
        $this->document->appendChild($root);

        $root->setAttributeNS(self::XML_SCHEMA_NAMESPACE_URI, 'xsi:schemaLocation',
            self::OAI_PMH_NAMESPACE_URI . ' ' . self::OAI_PMH_SCHEMA_URI);

        $responseDate = $this->document->createElement('responseDate',
            \OaiPmhRepository\Date::unixToUtc(time()));
        $root->appendChild($responseDate);

        $this->dispatchRequest();
    }

    private function _loadConfig()
    {
        $config = $this->serviceLocator->get('Config');

        $this->_listLimit = $config['oaipmhrepository']['list_limit'];
        $this->_tokenExpirationTime = $config['oaipmhrepository']['token_expiration_time'];
    }

    /**
     * Parses the HTTP query and dispatches to the correct verb handler.
     *
     * Checks arguments for each verb type, and sets XML request tag.
     *
     * @uses checkArguments()
     */
    private function dispatchRequest()
    {
        $viewHelpers = $this->serviceLocator->get('ViewHelperManager');
        $serverUrlHelper = $viewHelpers->get('serverUrl');

        $request = $this->document->createElement('request', $serverUrlHelper());
        $this->document->documentElement->appendChild($request);

        $requiredArgs = [];
        $optionalArgs = [];
        if (!($verb = $this->_getParam('verb'))) {
            $this->throwError(self::OAI_ERR_BAD_VERB, 'No verb specified.');

            return;
        }
        $resumptionToken = $this->_getParam('resumptionToken');

        if ($resumptionToken) {
            $requiredArgs = ['resumptionToken'];
        } else {
            switch ($this->query['verb']) {
                case 'Identify':
                    break;
                case 'GetRecord':
                    $requiredArgs = ['identifier', 'metadataPrefix'];
                    break;
                case 'ListRecords':
                    $requiredArgs = ['metadataPrefix'];
                    $optionalArgs = ['from', 'until', 'set'];
                    break;
                case 'ListIdentifiers':
                    $requiredArgs = ['metadataPrefix'];
                    $optionalArgs = ['from', 'until', 'set'];
                    break;
                case 'ListSets':
                    break;
                case 'ListMetadataFormats':
                    $optionalArgs = ['identifier'];
                    break;
                default:
                    $this->throwError(self::OAI_ERR_BAD_VERB);
            }
        }

        $this->checkArguments($requiredArgs, $optionalArgs);

        if (!$this->error) {
            foreach ($this->query as $key => $value) {
                $request->setAttribute($key, $value);
            }

            if ($resumptionToken) {
                $this->resumeListResponse($resumptionToken);
            }
            /* ListRecords and ListIdentifiers use a common code base and share
               all possible arguments, and are handled by one function. */
            elseif ($verb == 'ListRecords' || $verb == 'ListIdentifiers') {
                $this->initListResponse();
            } else {
                $functionName = lcfirst($verb);
                $this->$functionName();
            }
        }
    }

    /**
     * Checks the argument list from the POST/GET query.
     *
     * Checks if the required arguments are present, and no invalid extra
     * arguments are present.  All valid arguments must be in either the
     * required or optional array.
     *
     * @param array requiredArgs Array of required argument names
     * @param array optionalArgs Array of optional, but valid argument names
     */
    private function checkArguments($requiredArgs = [], $optionalArgs = [])
    {
        $requiredArgs[] = 'verb';

        /* Checks (essentially), if there are more arguments in the query string
           than in PHP's returned array, if so there were duplicate arguments,
           which is not allowed. */
        if ($this->request->isGet() && $this->request->getUri()->getQuery() != urldecode(http_build_query($this->query))) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, 'Duplicate arguments in request.');
        }

        $keys = array_keys($this->query);

        foreach (array_diff($requiredArgs, $keys) as $arg) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Missing required argument $arg.");
        }
        foreach (array_diff($keys, $requiredArgs, $optionalArgs) as $arg) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Unknown argument $arg.");
        }

        $from = $this->_getParam('from');
        $until = $this->_getParam('until');

        $fromGran = self::getGranularity($from);
        $untilGran = self::getGranularity($until);

        if ($from && !$fromGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, 'Invalid date/time argument.');
        }
        if ($until && !$untilGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, 'Invalid date/time argument.');
        }
        if ($from && $until && $fromGran != $untilGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, 'Date/time arguments of differing granularity.');
        }

        $metadataPrefix = $this->_getParam('metadataPrefix');

        $metadataFormatManager = $this->serviceLocator->get('OaiPmhRepository\MetadataFormatManager');
        if ($metadataPrefix && !$metadataFormatManager->has($metadataPrefix)) {
            $this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT);
        }
    }

    /**
     * Responds to the Identify verb.
     *
     * Appends the Identify element for the repository to the response.
     */
    public function identify()
    {
        if ($this->error) {
            return;
        }

        $settings = $this->serviceLocator->get('Omeka\Settings');
        $viewHelpers = $this->serviceLocator->get('ViewHelperManager');
        $serverUrlHelper = $viewHelpers->get('serverUrl');

        /* according to the schema, this order of elements is required for the
           response to validate */
        $elements = [
            'repositoryName' => $settings->get('oaipmh_repository_name'),
            'baseURL' => $serverUrlHelper(),
            'protocolVersion' => self::OAI_PMH_PROTOCOL_VERSION,
            'adminEmail' => $settings->get('administrator_email'),
            'earliestDatestamp' => \OaiPmhRepository\Date::unixToUtc(0),
            'deletedRecord' => 'no',
            'granularity' => self::OAI_GRANULARITY_STRING,
        ];
        $identify = $this->createElementWithChildren(
            $this->document->documentElement, 'Identify', $elements);

        // Publish support for compression, if appropriate
        // This defers to compression set in Omeka's paths.php
        if (extension_loaded('zlib') && ini_get('zlib.output_compression')) {
            $gzip = $this->document->createElement('compression', 'gzip');
            $deflate = $this->document->createElement('compression', 'deflate');
            $identify->appendChild($gzip);
            $identify->appendChild($deflate);
        }

        $description = $this->document->createElement('description');
        $identify->appendChild($description);
        OaiIdentifier::describeIdentifier($description);

        $toolkitDescription = $this->document->createElement('description');
        $identify->appendChild($toolkitDescription);
        $this->describeToolkit($toolkitDescription);
    }

    private function describeToolkit($parentElement)
    {
        $toolkitNamespace = 'http://oai.dlib.vt.edu/OAI/metadata/toolkit';
        $toolkitSchema = 'http://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd';
        $modules = $this->serviceLocator->get('Omeka\ModuleManager');
        $version = $modules->getModule('OaiPmhRepository')->getIni('version');

        $elements = [
            'title' => 'Omeka OAI-PMH Repository Plugin',
            'author' => [
                'name' => 'John Flatness',
                'email' => 'john@zerocrates.org',
            ],
            'version' => $version,
            'URL' => 'http://omeka.org/codex/Plugins/OaiPmhRepository',
        ];
        $toolkit = $this->createElementWithChildren($parentElement, 'toolkit', $elements);
        $toolkit->setAttribute('xsi:schemaLocation', "$toolkitNamespace $toolkitSchema");
        $toolkit->setAttribute('xmlns', $toolkitNamespace);
    }

    /**
     * Responds to the GetRecord verb.
     *
     * Outputs the header and metadata in the specified format for the specified
     * identifier.
     */
    private function getRecord()
    {
        $identifier = $this->_getParam('identifier');
        $metadataPrefix = $this->_getParam('metadataPrefix');

        $itemId = OaiIdentifier::oaiIdToItem($identifier);

        if (!$itemId) {
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);

            return;
        }

        $api = $this->serviceLocator->get('Omeka\ApiManager');

        $item = $api->read('items', $itemId)->getContent();

        if (!$item) {
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
        }

        if (!$this->error) {
            $getRecord = $this->document->createElement('GetRecord');
            $this->document->documentElement->appendChild($getRecord);
            $settings = $this->serviceLocator->get('Omeka\Settings');
            $metadataFormatManager = $this->serviceLocator->get('OaiPmhRepository\MetadataFormatManager');
            $metadataFormat = $metadataFormatManager->get($metadataPrefix);
            $metadataFormat->appendRecord($getRecord, $item);
        }
    }

    /**
     * Responds to the ListMetadataFormats verb.
     *
     * Outputs records for all of the items in the database in the specified
     * metadata format.
     *
     * @todo extend for additional metadata formats
     */
    private function listMetadataFormats()
    {
        $identifier = $this->_getParam('identifier');
        /* Items are not used for lookup, simply checks for an invalid id */
        if ($identifier) {
            $itemId = OaiIdentifier::oaiIdToItem($identifier);

            if (!$itemId) {
                $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);

                return;
            }
        }
        if (!$this->error) {
            $listMetadataFormats = $this->document->createElement('ListMetadataFormats');
            $this->document->documentElement->appendChild($listMetadataFormats);
            $settings = $this->serviceLocator->get('Omeka\Settings');
            foreach ($this->getFormats() as $format) {
                $format->declareMetadataFormat($listMetadataFormats);
            }
        }
    }

    /**
     * Responds to the ListSets verb.
     *
     * Outputs setSpec and setName for all OAI-PMH sets (Omeka collections).
     *
     * @todo replace with Zend_Db_Select to allow use of limit or pageLimit
     */
    private function listSets()
    {
        $api = $this->serviceLocator->get('Omeka\ApiManager');
        $collections = $api->search('item_sets')->getContent();

        if (count($collections) == 0) {
            $this->throwError(self::OAI_ERR_NO_SET_HIERARCHY);
        }

        $listSets = $this->document->createElement('ListSets');

        if (!$this->error) {
            $this->document->documentElement->appendChild($listSets);
            foreach ($collections as $collection) {
                $elements = [
                    'setSpec' => $collection->id(),
                    'setName' => $collection->value('dcterms:title'),
                ];
                $this->createElementWithChildren($listSets, 'set', $elements);
            }
        }
    }

    /**
     * Responds to the ListIdentifiers and ListRecords verbs.
     *
     * Only called for the initial request in the case of multiple incomplete
     * list responses
     *
     * @uses listResponse()
     */
    private function initListResponse()
    {
        $fromDate = null;
        $untilDate = null;

        if (($from = $this->_getParam('from'))) {
            $fromDate = \OaiPmhRepository\Date::utcToDb($from);
        }
        if (($until = $this->_getParam('until'))) {
            $untilDate = \OaiPmhRepository\Date::utcToDb($until);
        }

        $this->listResponse($this->query['verb'],
                            $this->query['metadataPrefix'],
                            0,
                            $this->_getParam('set'),
                            $fromDate,
                            $untilDate);
    }

    /**
     * Returns the next incomplete list response based on the given resumption
     * token.
     *
     * @param string $token Resumption token
     *
     * @uses listResponse()
     */
    private function resumeListResponse($token)
    {
        $api = $this->serviceLocator->get('Omeka\ApiManager');
        $expiredTokens = $api->search('oaipmh_repository_tokens', [
            'expired' => true,
        ])->getContent();
        foreach ($expiredTokens as $expiredToken) {
            $api->delete('oaipmh_repository_tokens', $expiredToken->id());
        }

        $tokenObject = $api->read('oaipmh_repository_tokens', $token)->getContent();

        if (!$tokenObject || ($tokenObject->verb() != $this->query['verb'])) {
            $this->throwError(self::OAI_ERR_BAD_RESUMPTION_TOKEN);
        } else {
            $this->listResponse($tokenObject->verb(),
                                $tokenObject->metadataPrefix(),
                                $tokenObject->cursor(),
                                $tokenObject->set(),
                                $tokenObject->from(),
                                $tokenObject->until());
        }
    }

    /**
     * Responds to the two main List verbs, includes resumption and limiting.
     *
     * @param string $verb           OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int    $cursor         Offset in response to begin output at
     * @param mixed  $set            Optional set argument
     * @param string $from           Optional from date argument
     * @param string $until          Optional until date argument
     *
     * @uses createResumptionToken()
     */
    private function listResponse($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        $entityManager = $this->serviceLocator->get('Omeka\EntityManager');
        $itemRepository = $entityManager->getRepository('Omeka\Entity\Item');
        $qb = $itemRepository->createQueryBuilder('Item');

        $qb->andWhere($qb->expr()->eq('Item.isPublic', true));
        if ($set) {
            $qb->innerJoin(
                'Item.itemSets',
                'is', 'WITH',
                $qb->expr()->in('is.id', [$set])
            );
        }
        if ($from) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->gte('Item.modified', $from),
                $qb->expr()->gte('Item.created', $from)
            ));
        }
        if ($until) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->gte('Item.modified', $until),
                $qb->expr()->gte('Item.created', $until)
            ));
        }
        $qb->groupBy('Item.id');

        $qb->select('Item');

        // This limit call will form the basis of the flow control
        $qb->setMaxResults($this->_listLimit);
        $qb->setFirstResult($cursor);

        $paginator = new Paginator($qb, false);
        $rows = count($paginator);

        if ($rows == 0) {
            $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, 'No records match the given criteria');
        } else {
            if ($verb == 'ListIdentifiers') {
                $method = 'appendHeader';
            } elseif ($verb == 'ListRecords') {
                $method = 'appendRecord';
            }

            $adapters = $this->serviceLocator->get('Omeka\ApiAdapterManager');
            $itemAdapter = $adapters->get('items');

            $settings = $this->serviceLocator->get('Omeka\Settings');
            $metadataFormatManager = $this->serviceLocator->get('OaiPmhRepository\MetadataFormatManager');

            $verbElement = $this->document->createElement($verb);
            $this->document->documentElement->appendChild($verbElement);
            foreach ($paginator as $itemEntity) {
                $item = $itemAdapter->getRepresentation($itemEntity);
                $metadataFormat = $metadataFormatManager->get($metadataPrefix);
                $metadataFormat->$method($verbElement, $item);
            }
            if ($rows > ($cursor + $this->_listLimit)) {
                $token = $this->createResumptionToken($verb, $metadataPrefix,
                    $cursor + $this->_listLimit, $set, $from, $until);

                $tokenElement = $this->document->createElement('resumptionToken', $token->id());
                $tokenElement->setAttribute('expirationDate',
                    $token->expiration()->format('Y-m-d\TH:i:s\Z'));
                $tokenElement->setAttribute('completeListSize', $rows);
                $tokenElement->setAttribute('cursor', $cursor);
                $verbElement->appendChild($tokenElement);
            } elseif ($cursor != 0) {
                $tokenElement = $this->document->createElement('resumptionToken');
                $verbElement->appendChild($tokenElement);
            }
        }
    }

    /**
     * Stores a new resumption token record in the database.
     *
     * @param string $verb           OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int    $cursor         Offset in response to begin output at
     * @param mixed  $set            Optional set argument
     * @param string $from           Optional from date argument
     * @param string $until          Optional until date argument
     *
     * @return OaiPmhRepositoryTokenRepresentation
     */
    private function createResumptionToken($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        $api = $this->serviceLocator->get('Omeka\ApiManager');

        $token = $api->create('oaipmh_repository_tokens', [
            'o:verb' => $verb,
            'o:metadata_prefix' => $metadataPrefix,
            'o:cursor' => $cursor,
            'o:set' => $set ?: null,
            'o:from' => $from ?: null,
            'o:until' => $until ?: null,
            'o:expiration' => \OaiPmhRepository\Date::unixToDb(time() + ($this->_tokenExpirationTime * 60)),
        ])->getContent();

        return $token;
    }

    /**
     * Builds an array of entries for all included metadata mapping classes.
     * Derived heavily from OaipmhHarvester's getMaps().
     *
     * @return array An array, with metadataPrefix => class
     */
    private function getFormats()
    {
        $metadataFormatManager = $this->serviceLocator->get('OaiPmhRepository\MetadataFormatManager');

        $metadataFormats = [];
        foreach ($metadataFormatManager->getRegisteredNames() as $name) {
            $metadataFormat = $metadataFormatManager->get($name);
            $metadataPrefix = $metadataFormat->getMetadataPrefix();
            $metadataFormats[$metadataPrefix] = $metadataFormat;
        }

        return $metadataFormats;
    }

    private function _getParam($param)
    {
        if (array_key_exists($param, $this->query)) {
            return $this->query[$param];
        }

        return null;
    }

    /**
     * Throws an OAI-PMH error on the given response.
     *
     * @param string $error   OAI-PMH error code
     * @param string $message Optional human-readable error message
     */
    protected function throwError($error, $message = null)
    {
        $this->error = true;
        $errorElement = $this->document->createElement('error', $message);
        $this->document->documentElement->appendChild($errorElement);
        $errorElement->setAttribute('code', $error);
    }

    /**
     * Outputs the XML response as a string.
     *
     * Called once processing is complete to return the XML to the client.
     *
     * @return string the response XML
     */
    public function __toString()
    {
        return $this->document->saveXML();
    }
}
