<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository;

/**
 * Abstract class containing functions for tasks common to all OAI-PMH
 * responses.
 */
class OaiXmlGeneratorAbstract extends XmlGeneratorAbstract
{
    // =========================
    // General OAI-PMH constants
    // =========================

    const OAI_PMH_NAMESPACE_URI = 'http://www.openarchives.org/OAI/2.0/';
    const OAI_PMH_SCHEMA_URI = 'http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd';
    const OAI_PMH_PROTOCOL_VERSION = '2.0';

    // =========================
    // Error codes
    // =========================

    const OAI_ERR_BAD_ARGUMENT = 'badArgument';
    const OAI_ERR_BAD_RESUMPTION_TOKEN = 'badResumptionToken';
    const OAI_ERR_BAD_VERB = 'badVerb';
    const OAI_ERR_CANNOT_DISSEMINATE_FORMAT = 'cannotDisseminateFormat';
    const OAI_ERR_ID_DOES_NOT_EXIST = 'idDoesNotExist';
    const OAI_ERR_NO_RECORDS_MATCH = 'noRecordsMatch';
    const OAI_ERR_NO_METADATA_FORMATS = 'noMetadataFormats';
    const OAI_ERR_NO_SET_HIERARCHY = 'noSetHierarchy';

    // =========================
    // Date/time constants
    // =========================

    const OAI_DATE_PCRE = '/^\\d{4}\\-\\d{2}\\-\\d{2}$/';
    const OAI_DATETIME_PCRE = '/^\\d{4}\\-\\d{2}\\-\\d{2}T\\d{2}\\:\\d{2}\\:\\d{2}Z$/';

    const OAI_GRANULARITY_STRING = 'YYYY-MM-DDThh:mm:ssZ';
    const OAI_GRANULARITY_DATE = 1;
    const OAI_GRANULARITY_DATETIME = 2;

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
}
