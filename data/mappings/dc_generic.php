<?php
/**
 * Refinements of Dublin Core.
 *
 * Dublin Core properties and property refinements. See DCMI Metadata Terms:
 * http://dublincore.org/documents/dcmi-terms/
 * The order is the Omeka one.
 *
 * This mapping may be used by the filter `oaipmhrepository.values`.
 * @todo Use the rdf relations directly.
 */
return [
    'dcterms:title' => [
        'dcterms:alternative',
    ],
    'dcterms:creator' => [
    ],
    'dcterms:subject' => [
    ],
    'dcterms:description' => [
        'dcterms:tableOfContents',
        'dcterms:abstract',
    ],
    'dcterms:publisher' => [
    ],
    'dcterms:contributor' => [
    ],
    'dcterms:date' => [
        'dcterms:created',
        'dcterms:valid',
        'dcterms:available',
        'dcterms:issued',
        'dcterms:modified',
        'dcterms:dateAccepted',
        'dcterms:dateCopyrighted',
        'dcterms:dateSubmitted',
    ],
    'dcterms:type' => [
    ],
    'dcterms:format' => [
        'dcterms:extent',
        'dcterms:medium',
    ],
    'dcterms:identifier' => [
        'dcterms:bibliographicCitation',
    ],
    // Source is a refinement of Relation too.
    'dcterms:source' => [
    ],
    'dcterms:language' => [
    ],
    'dcterms:relation' => [
        'dcterms:isVersionOf',
        'dcterms:hasVersion',
        'dcterms:isReplacedBy',
        'dcterms:replaces',
        'dcterms:isRequiredBy',
        'dcterms:requires',
        'dcterms:isPartOf',
        'dcterms:hasPart',
        'dcterms:isReferencedBy',
        'dcterms:references',
        'dcterms:isFormatOf',
        'dcterms:hasFormat',
        'dcterms:conformsTo',
    ],
    'dcterms:coverage' => [
        'dcterms:spatial',
        'dcterms:temporal',
    ],
    'dcterms:rights' => [
        'dcterms:accessRights',
        'dcterms:license',
    ],
    // Ungenerized terms.
    // 'dcterms:audience',
        // 'dcterms:mediator',
        // 'dcterms:educationLevel',
    // 'dcterms:rightsHolder',
    // 'dcterms:provenance',
    // 'dcterms:instructionalMethod',
    // 'dcterms:accrualMethod',
    // 'dcterms:accrualPeriodicity',
    // 'dcterms:accrualPolicy',
];
