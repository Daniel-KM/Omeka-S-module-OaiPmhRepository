<?php
/**
 * @author John Flatness
 * @copyright Copyright 2012 John Flatness
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository;

/**
 * Class containing static functions for date tasks.
 */
class Date
{
    /**
     * PHP date() format string to produce the required date format.
     * Must be used with gmdate() to conform to spec.
     */
    const OAI_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Converts the given Unix timestamp to OAI-PMH's specified ISO 8601 format.
     *
     * @param int $timestamp Unix timestamp
     *
     * @return string Time in ISO 8601 format
     */
    public static function unixToUtc($timestamp)
    {
        return gmdate(self::OAI_DATE_FORMAT, $timestamp);
    }
}
