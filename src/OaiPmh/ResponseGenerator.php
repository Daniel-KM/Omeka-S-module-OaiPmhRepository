<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016-2017
 * @copyright Daniel Berthereau, 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh;

use ArrayObject;
use DateTime;
use DomDocument;
use Doctrine\ORM\Tools\Pagination\Paginator;
use OaiPmhRepository\Api\Representation\OaiPmhRepositoryTokenRepresentation;
use OaiPmhRepository\OaiPmh\OaiSet\OaiSetInterface;
use OaiPmhRepository\OaiPmh\Plugin\OaiIdentifier;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\Message;
use Zend\Http\Request;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * OaiPmhXmlGenerator generates the XML responses to OAI-PMH
 * requests recieved by the repository.  The DOM extension is used to generate
 * all the XML output on-the-fly.
 */
class ResponseGenerator extends AbstractXmlGenerator
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

    /**
     * Number of records to display by page.
     *
     * @var int
     */
    private $_listLimit;

    /**
     * Number of minutes before expiration of token.
     *
     * @var int
     */
    private $_tokenExpirationTime;

    /**
     * The base url of the server, used for the OAI-PMH request.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Flags if an error has occurred during the response.
     *
     * @var bool
     */
    protected $error;

    /**
     * Add the current site in order to provide a specific response by site.
     *
     * @var SiteRepresentation
     */
    protected $site;

    /**
     * The type of oai sets: "item_set", "site_pool", or "none".
     *
     * "site_pool" can be used only for global oai-pmh repository.
     *
     * @var string
     */
    protected $setSpecType;

    /**
     * The set format.
     *
     * @var OaiSetInterface
     */
    protected $oaiSet;

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
     * Creates the DomDocument object, and adds XML elements common to all
     * OAI-PMH responses.  Dispatches control to appropriate verb, if any.
     *
     * @uses dispatchRequest()
     *
     * @param Request $request HTTP POST/GET query key-value pair array
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(Request $request, ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        $settings = $serviceLocator->get('Omeka\Settings');

        $this->_listLimit = $settings->get('oaipmhrepository_list_limit');
        $this->_tokenExpirationTime = $settings->get('oaipmhrepository_token_expiration_time');

        $viewHelpers = $serviceLocator->get('ViewHelperManager');
        $serverUrlHelper = $viewHelpers->get('serverUrl');
        $this->baseUrl = strtok($serverUrlHelper(true), '?');

        $this->error = false;
        $this->request = $request;
        $this->query = $request->isGet()
            ? $request->getQuery()->toArray()
            : $request->getPost()->toArray();

        $this->document = new DomDocument('1.0', 'UTF-8');

        OaiIdentifier::initializeNamespace($settings->get('oaipmhrepository_namespace_id'));

        $currentSite = $serviceLocator->get('ControllerPluginManager')->get('currentSite');
        $this->site = $currentSite();

        if ($this->site) {
            $this->setSpecType = $settings->get('oaipmhrepository_by_site_repository', 'none');
            if (!in_array($this->setSpecType, ['item_set', 'none'])) {
                $this->setSpecType = 'none';
            }
        } else {
            $this->setSpecType = $settings->get('oaipmhrepository_global_repository', 'none');
            if (!in_array($this->setSpecType, ['item_set', 'site_pool', 'none'])) {
                $this->setSpecType = 'none';
            }
        }

        $oaiSetManager = $serviceLocator->get(\OaiPmhRepository\OaiPmh\OaiSetManager::class);
        $this->oaiSet = $oaiSetManager->get($settings->get('oaipmhrepository_oai_set_format', 'base'));
        $this->oaiSet->setSetSpecType($this->setSpecType);
        $this->oaiSet->setSite($this->site);
        $this->oaiSet->setOptions([
            'hide_empty_sets' => $settings->get('oaipmhrepository_hide_empty_sets', true),
        ]);

        //formatOutput makes DOM output "pretty" XML.  Good for debugging, but
        //adds some overhead, especially on large outputs.
        $this->document->formatOutput = true;
        $this->document->xmlStandalone = true;

        if ($settings->get('oaipmhrepository_human_interface')) {
            $assetUrl = $viewHelpers->get('assetUrl');
            $stylesheet = $assetUrl('xsl/oai-pmh-repository.xsl', 'OaiPmhRepository', true);
            $xslt = $this->document->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="' . $stylesheet . '"');
            $this->document->appendChild($xslt);
        }

        $root = $this->document->createElementNS(self::OAI_PMH_NAMESPACE_URI,
            'OAI-PMH');
        $this->document->appendChild($root);

        $root->setAttributeNS(self::XML_SCHEMA_NAMESPACE_URI, 'xsi:schemaLocation',
            self::OAI_PMH_NAMESPACE_URI . ' ' . self::OAI_PMH_SCHEMA_URI);

        $responseDate = $this->document->createElement('responseDate',
            \OaiPmhRepository\OaiPmh\Plugin\Date::unixToUtc(time()));
        $root->appendChild($responseDate);

        $this->dispatchRequest();
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
        $request = $this->document->createElement('request', $this->baseUrl);
        $this->document->documentElement->appendChild($request);

        $this->checkRequestMethod();

        $requiredArgs = [];
        $optionalArgs = [];
        if (!($verb = $this->_getParam('verb'))) {
            $this->throwError(self::OAI_ERR_BAD_VERB, new Message('No verb specified.')); // @translate

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
     * Check the method of the request.
     */
    private function checkRequestMethod()
    {
        $method = $this->request->getMethod();
        if (!in_array($method, ['GET', 'POST'])) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, new Message(
                'The OAI-PMH protocol version 2.0 supports only "GET" and "POST" requests, not "%s".', $method)); // @translate
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

        // Checks (essentially), if there are more arguments in the query string
        // than in PHP's returned array, if so there were duplicate arguments,
        // which is not allowed.
        switch ($this->request->getMethod()) {
            case 'GET':
                $query = $this->request->getUri()->getQuery();
                break;
            case 'POST':
                $query = $this->request->getContent();
                break;
        }
        if (urldecode($query) !== urldecode(http_build_query($this->query))) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, new Message('Duplicate arguments in request.')); // @translate
        }

        $keys = array_keys($this->query);

        foreach (array_diff($requiredArgs, $keys) as $arg) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, new Message('Missing required argument %s.', $arg)); // @translate
        }
        foreach (array_diff($keys, $requiredArgs, $optionalArgs) as $arg) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, new Message('Unknown argument %s.', $arg)); // @translate
        }

        $from = $this->_getParam('from');
        $until = $this->_getParam('until');

        $fromGran = self::getGranularity($from);
        $untilGran = self::getGranularity($until);

        if ($from && !$fromGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, new Message('Invalid date/time argument.')); // @translate
        }
        if ($until && !$untilGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, new Message('Invalid date/time argument.')); // @translate
        }
        if ($from && $until && $fromGran != $untilGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, new Message('Date/time arguments of differing granularity.')); // @translate
        }

        $metadataPrefix = $this->_getParam('metadataPrefix');


        $metadataFormatManager = $this->serviceLocator->get(\OaiPmhRepository\OaiPmh\MetadataFormatManager::class);
        $metadataFormats = $this->serviceLocator->get('Omeka\Settings')->get('oaipmhrepository_metadata_formats');
        if ($metadataPrefix
            && (
                !$metadataFormatManager->has($metadataPrefix)
                || !in_array($metadataPrefix, $metadataFormats)
            )
        ) {
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

        /* according to the schema, this order of elements is required for the
           response to validate */
        $elements = [
            'repositoryName' => $settings->get('oaipmhrepository_name'),
            'baseURL' => $this->baseUrl,
            'protocolVersion' => self::OAI_PMH_PROTOCOL_VERSION,
            'adminEmail' => $settings->get('administrator_email'),
            'earliestDatestamp' => \OaiPmhRepository\OaiPmh\Plugin\Date::unixToUtc(0),
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
        $config = $this->serviceLocator->get('Config');
        $elements = $config['oaipmhrepository']['xml']['identify']['description']['toolkit'];
        $elements['version'] = $version;

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

        $data = [];
        $data['id'] = $itemId;
        $data['limit'] = 1;
        if ($this->site) {
            $data['site_id'] = $this->site->id();
        }
        $items = $api->search('items', $data)->getContent();

        if ($items) {
            $item = reset($items);
        } else {
            $item = null;
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
        }

        if (!$this->error) {
            $getRecord = $this->document->createElement('GetRecord');
            $this->document->documentElement->appendChild($getRecord);
            $metadataFormatManager = $this->serviceLocator->get(\OaiPmhRepository\OaiPmh\MetadataFormatManager::class);
            $metadataFormat = $metadataFormatManager->get($metadataPrefix);
            $metadataFormat->setOaiSet($this->oaiSet);
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
        $oaiSets = $this->oaiSet->listSets();
        if (empty($oaiSets)) {
            $this->throwError(self::OAI_ERR_NO_SET_HIERARCHY);
            return;
        }

        $listSets = $this->document->createElement('ListSets');

        $this->document->documentElement->appendChild($listSets);
        foreach ($oaiSets as $oaiSet) {
            $this->createElementWithChildren($listSets, 'set', $oaiSet);
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
            $fromDate = new DateTime($from);
        }
        if (($until = $this->_getParam('until'))) {
            $untilDate = new DateTime($until);
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
        $api = $this->serviceLocator->get('ControllerPluginManager')->get('api');
        $expiredTokens = $api->search('oaipmh_repository_tokens', [
            'expired' => true,
        ])->getContent();
        foreach ($expiredTokens as $expiredToken) {
            $api->delete('oaipmh_repository_tokens', $expiredToken->id());
        }

        // TODO Purge tokens.

        $tokenObject = $api->searchOne('oaipmh_repository_tokens', ['id' => $token])->getContent();

        if (!$tokenObject || ($tokenObject->verb() != $this->query['verb'])) {
            $this->throwError(self::OAI_ERR_BAD_RESUMPTION_TOKEN);
        } else {
            $this->listResponse(
                $tokenObject->verb(),
                $tokenObject->metadataPrefix(),
                $tokenObject->cursor(),
                $tokenObject->set(),
                $tokenObject->from(),
                $tokenObject->until()
            );
        }
    }

    /**
     * Responds to the two main List verbs, includes resumption and limiting.
     *
     * @param string $verb           OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int    $cursor         Offset in response to begin output at
     * @param mixed  $set            Optional set argument
     * @param DateTime $from           Optional from date argument
     * @param DateTime $until          Optional until date argument
     *
     * @uses createResumptionToken()
     */
    private function listResponse($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        $apiAdapterManager = $this->serviceLocator->get('Omeka\ApiAdapterManager');
        $entityManager = $this->serviceLocator->get('Omeka\EntityManager');

        $itemRepository = $entityManager->getRepository('Omeka\Entity\Item');
        $qb = $itemRepository->createQueryBuilder('omeka_root');
        $qb->select('omeka_root');

        $query = new ArrayObject;

        // Public/private is automatically managed for anonymous requests.

        if ($this->site) {
            $query['site_id'] = $this->site->id();
        }

        if ($set) {
            $resourceSet = $this->oaiSet->findResource($set);
            if (empty($resourceSet)) {
                $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH,
                    new Message('The set "%s" doesn’t exist.', $set)); // @translate
                return;
            }
            switch ($this->setSpecType) {
                case 'site_pool':
                    $query['site_id'] = $resourceSet->id();
                    break;
                case 'item_set':
                    $query['item_set_id'] = $resourceSet->id();
                    break;
                case 'none':
                default:
                    $this->throwError(self::OAI_ERR_NO_SET_HIERARCHY);
                    return;
            }
        }

        $metadataFormatManager = $this->serviceLocator->get(\OaiPmhRepository\OaiPmh\MetadataFormatManager::class);
        $metadataFormat = $metadataFormatManager->get($metadataPrefix);
        $metadataFormat->setOaiSet($this->oaiSet);

        $metadataFormat->filterList($query);

        /** @var \Omeka\Api\Adapter\ItemAdapter $itemAdapter */
        $itemAdapter = $apiAdapterManager->get('items');
        $itemAdapter->buildQuery($qb, $query->getArrayCopy());

        if ($from) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('omeka_root.modified'),
                    $qb->expr()->gte('omeka_root.modified', ':from_1')
                ),
                $qb->expr()->andX(
                    $qb->expr()->isNull('omeka_root.modified'),
                    $qb->expr()->gte('omeka_root.created', ':from_2')
                )
            ));
            $qb->setParameter('from_1', $from);
            $qb->setParameter('from_2', $from);
        }
        if ($until) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('omeka_root.modified'),
                    $qb->expr()->lte('omeka_root.modified', ':until_1')
                ),
                $qb->expr()->andX(
                    $qb->expr()->isNull('omeka_root.modified'),
                    $qb->expr()->lte('omeka_root.created', ':until_2')
                )
            ));
            $qb->setParameter('until_1', $until);
            $qb->setParameter('until_2', $until);
        }

        $qb->groupBy('omeka_root.id');

        // This limit call will form the basis of the flow control
        $qb->setMaxResults($this->_listLimit);
        $qb->setFirstResult($cursor);

        $paginator = new Paginator($qb, false);
        $rows = count($paginator);

        if ($rows == 0) {
            $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, new Message('No records match the given criteria.')); // @translate
        } else {
            if ($verb == 'ListIdentifiers') {
                $method = 'appendHeader';
            } elseif ($verb == 'ListRecords') {
                $method = 'appendRecord';
            }

            $verbElement = $this->document->createElement($verb);
            $this->document->documentElement->appendChild($verbElement);
            foreach ($paginator as $itemEntity) {
                $item = $itemAdapter->getRepresentation($itemEntity);
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
     * @param DateTime $from           Optional from date argument
     * @param DateTime $until          Optional until date argument
     *
     * @return OaiPmhRepositoryTokenRepresentation
     */
    private function createResumptionToken($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        $api = $this->serviceLocator->get('Omeka\ApiManager');

        $expiration = new DateTime();
        $expiration->setTimestamp(time() + ($this->_tokenExpirationTime * 60));

        $token = $api->create('oaipmh_repository_tokens', [
            'o:verb' => $verb,
            'o:metadata_prefix' => $metadataPrefix,
            'o:cursor' => $cursor,
            'o:set' => $set ?: null,
            'o:from' => $from ?: null,
            'o:until' => $until ?: null,
            'o:expiration' => $expiration,
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
        $metadataFormatManager = $this->serviceLocator->get(\OaiPmhRepository\OaiPmh\MetadataFormatManager::class);
        $metadataFormatUsed = $this->serviceLocator->get('Omeka\Settings')->get('oaipmhrepository_metadata_formats');

        $metadataFormats = [];
        foreach ($metadataFormatManager->getRegisteredNames() as $name) {
            $metadataFormat = $metadataFormatManager->get($name);
            $metadataPrefix = $metadataFormat->getMetadataPrefix();
            if (!in_array($metadataPrefix, $metadataFormatUsed)) {
                continue;
            }
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
