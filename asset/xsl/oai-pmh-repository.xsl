<?xml version="1.0" encoding="utf-8"?>
<!--
Stylesheet to display responses to OAI-PMH requests with a Bootstrap theme.

This stylesheet is primarily designed for Omeka Classic / Omeka S (https://omeka.org)
and the plugins OAI-PMH Repository (https://omeka.org/classic/plugins/OaiPmhRepository /
https://github.com/Daniel-KM/Omeka-S-module-OaiPmhRepository)
and OAI-PMH Gateway (https://github.com/Daniel-KM/OaiPmhGateway), but can be used by
any OAI-PMH Data Provider (https://www.openarchives.org/pmh/register_data_provider).
To customize it, you may need to adapt the paths to the css and js for your application.
CDN are used by default.

Includes
- Bootstrap, published under the MIT licence (see http://getbootstrap.com).
- JQuery, published under the MIT licence (see https://jquery.com).
- XML Verbatim, published under Apache License, Version 2  or LGPL (see below
and see http://www2.informatik.hu-berlin.de/~obecker/XSLT/#xmlverbatim),
Initialy adapted from the stylesheet built by Lyncode for DSpace (http://www.lyncode.com/dspace/addons/xoai/),
published under the DSpace BSD Licence (see http://dspace.org/licence).

Copyright (c) 2015-2017 Daniel Berthereau (see https://github.com/Daniel-KM)
Copyright (c) 2002 Oliver Becker (XML Verbatim)
Copyright (c) 2002-2015, DuraSpace.  All rights reserved.

Copyrights and licences for the third parties above can be found in the specific
files and online.

Published under the BSD-like licence CeCILL-B (https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html).

Basic support (see https://www.openarchives.org/OAI/2.0/guidelines.htm):
- rightsManifest (repository and set levels)
- branding (repository and set levels)
- provenance (record level)
- rights (record level)
- about container.
No support (may depend on server):
- Identify compression.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:oai="http://www.openarchives.org/OAI/2.0/"
    xmlns:oai_identifier="http://www.openarchives.org/OAI/2.0/oai-identifier"
    xmlns:oai_rights="http://www.openarchives.org/OAI/2.0/rights/"
    xmlns:oai_friends="http://www.openarchives.org/OAI/2.0/friends/"
    xmlns:oai_branding="http://www.openarchives.org/OAI/2.0/branding/"
    xmlns:oai_gateway="http://www.openarchives.org/OAI/2.0/gateway/"
    xmlns:oai_provenance="http://www.openarchives.org/OAI/2.0/provenance"
    xmlns:toolkit="http://oai.dlib.vt.edu/OAI/metadata/toolkit"
    xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
    xmlns:dc="http://purl.org/dc/doc:elements/1.1/"
    xmlns:verb="http://informatik.hu-berlin.de/xmlverbatim"
    exclude-result-prefixes="oai oai_identifier oai_rights oai_friends oai_branding oai_gateway toolkit oai_provenance oai_dc dc verb">

    <xsl:output
        method="html"
        doctype-system="about:legacy-compat"
        indent="yes"/>

    <!-- Url for "homepage" link (#logo is set for css if wanted). -->
    <!-- Let empty to use the link to request "Identify". -->
    <xsl:param name="homepage-url" select="''" />
    <xsl:param name="homepage-text" select="'OAI-PMH Repository'" />

    <!-- Url for "powered by" link (#logo-powered-by is set for css if wanted). -->
    <xsl:param name="powered-by-url" select="'https://omeka.org'" />
    <xsl:param name="powered-by-text" select="'Powered by Omeka'" />

    <!-- Url for the xsl stylesheet link (#logo-stylesheet is set for css if wanted). -->
    <xsl:param name="stylesheet-url" select="'https://github.com/Daniel-KM/Omeka-S-module-OaiPmhRepository'" />
    <xsl:param name="stylesheet-text" select="'Stylesheet by Daniel Berthereau'" />

    <!-- Let empty if this a normal repository. -->
    <xsl:param name="gateway-url" select="''" />

    <!-- Url to css and javascripts. Let empty to use the cdn. -->
    <!-- Note: the external css/js libraries are not installed by default. -->
    <!-- Omeka -->
    <!--
    <xsl:param name="css-oai-pmh-repository" select="'../plugins/OaiPmhRepository/views/public/css/oai-pmh-repository.css'" />
    <xsl:param name="css-bootstrap" select="'../plugins/OaiPmhRepository/views/public/css/bootstrap.min.css'" />
    <xsl:param name="css-bootstrap-theme" select="'../plugins/OaiPmhRepository/views/public/css/bootstrap-theme.min.css'" />
    <xsl:param name="javascript-jquery" select="'../application/views/scripts/javascripts/vendor/jquery.js'" />
    <xsl:param name="javascript-bootstrap" select="'../plugins/OaiPmhRepository/views/public/javascripts/bootstrap.min.js'" />
    -->
    <!-- Omeka S -->
    <!--
    <xsl:param name="css-oai-pmh-repository" select="'modules/OaiPmhRepository/asset/css/oai-pmh-repository.css'" />
    <xsl:param name="css-bootstrap" select="'modules/OaiPmhRepository/asset/vendor/bootstrap/css/bootstrap.min.css'" />
    <xsl:param name="css-bootstrap-theme" select="'modules/OaiPmhRepository/asset/vendor/bootstrap/css/bootstrap-theme.min.css'" />
    <xsl:param name="javascript-jquery" select="'modules/OaiPmhRepository/asset/vendor/jquery/jquery.min.js'" />
    <xsl:param name="javascript-bootstrap" select="'modules/OaiPmhRepository/asset/vendor/bootstrap/js/bootstrap.min.js'" />
    -->
    <xsl:param name="css-oai-pmh-repository" select="''" />
    <xsl:param name="css-bootstrap" select="''" />
    <xsl:param name="css-bootstrap-theme" select="''" />
    <xsl:param name="javascript-jquery" select="''" />
    <xsl:param name="javascript-bootstrap" select="''" />

    <!-- This option is used by XML Verbatim. -->
    <xsl:param name="indent-elements" select="false()" />

    <!-- Constants. -->
    <xsl:variable name="url-homepage">
        <xsl:choose>
            <xsl:when test="$homepage-url != ''">
                <xsl:value-of select="$homepage-url" />
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="concat(/oai:OAI-PMH/oai:request/text(), '?verb=Identify')" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:variable>

    <xsl:variable name="url-gateway">
        <xsl:if test="$gateway-url != '' and starts-with(/oai:OAI-PMH/oai:request/text(), $gateway-url)">
            <xsl:value-of select="$gateway-url" />
        </xsl:if>
        <xsl:text></xsl:text>
    </xsl:variable>

    <xsl:variable name="forbidden-characters" select="':/.()#? '" />

    <xsl:template match="/">
        <html lang="en">
            <head>
                <xsl:element name="meta">
                    <xsl:attribute name="charset">utf-8</xsl:attribute>
                </xsl:element>
                <xsl:element name="meta">
                    <xsl:attribute name="http-equiv">X-UA-Compatible</xsl:attribute>
                    <xsl:attribute name="content">IE=edge</xsl:attribute>
                </xsl:element>
                <xsl:element name="meta">
                    <xsl:attribute name="name">viewport</xsl:attribute>
                    <xsl:attribute name="content">width=device-width, initial-scale=1</xsl:attribute>
                </xsl:element>
                <xsl:element name="meta">
                    <xsl:attribute name="name">description</xsl:attribute>
                    <xsl:attribute name="content">OAI-PMH Repository and OAI-PMH Data Provider</xsl:attribute>
                </xsl:element>
                <link rel="icon" href="/favicon.ico" />
                <title><xsl:value-of select="$homepage-text" /></title>
                <xsl:choose>
                    <xsl:when test="$css-bootstrap != ''">
                        <link rel="stylesheet" href="{$css-bootstrap}" type="text/css" />
                    </xsl:when>
                    <xsl:otherwise>
                        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:choose>
                    <xsl:when test="$css-bootstrap != ''">
                        <link rel="stylesheet" href="{$css-bootstrap-theme}" type="text/css" />
                    </xsl:when>
                    <xsl:otherwise>
                        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous" />
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:choose>
                    <xsl:when test="$css-oai-pmh-repository != ''">
                        <link rel="stylesheet" href="{$css-oai-pmh-repository}" type="text/css" />
                    </xsl:when>
                    <xsl:otherwise>
                        <style type="text/css">
                        /* Styles to adapt bootstrap. */

                        body {
                            padding-top: 70px;
                        }
                        footer a {
                            color: #000000;
                        }
                        footer a:hover {
                            color: #000000;
                        }
                        a#logo {
                            background: transparent none no-repeat scroll 0 0 / 100% 100%;
                        /*  padding: 8px 51px; */
                        }
                        .vertical-space {
                            height: 20px;
                        }
                        .xoai-contexts div:nth-child(even) {
                            background-color: #eee;
                        }
                        pre {
                            line-height: 0.8;
                        }

                        /* Styles specific to OAI-PMH. */

                        .oaipmh h2 {
                            border-bottom-style: inset;
                        }
                        .oaipmh table small {
                            font-style: italic;
                            margin-left: 10px;
                        }
                        .oaipmh-response .panel-heading a {
                            color: #3c763d;
                        }
                        .oaipmh-description {
                            padding-top: 16px;
                        }
                        .oaipmh-formats table thead th:first-child,
                        .oaipmh-formats table tbody th,
                        .oaipmh-sets table thead th:first-child,
                        .oaipmh-sets table tbody th,
                        .oaipmh-identifiers table thead th:first-child,
                        .oaipmh-identifiers table tbody th,
                        .oaipmh-records table thead th:first-child,
                        .oaipmh-records table tbody th {
                            text-align: right;
                        }
                        .oaipmh-formats table dl {
                            margin-bottom: 12px;
                            margin-top: 12px;
                        }
                        .oaipmh-records table {
                            width: 100%;
                        }
                        .oaipmh-records table table {
                            table-layout:fixed;
                        }
                        .oaipmh-records table tr.collapse td {
                            word-wrap: break-word;
                        }
                        .oaipmh-record .panel-body {
                            word-wrap: break-word;
                        }
                        .oaipmh-toolkit-icon {
                            margin-left: 8px;
                        }

                        /* Stylesheet from http://www2.informatik.hu-berlin.de/~obecker/XSLT/xmlverbatim/xmlverbatim.css */

                        .xmlverb-default {
                            color: #333333;
                            background-color: #ffffff;
                            font-family: monospace;
                        }
                        .xmlverb-element-name {
                            color: #990000;
                        }
                        .xmlverb-element-nsprefix {
                            color: #666600;
                        }
                        .xmlverb-attr-name {
                            color: #660000;
                        }
                        .xmlverb-attr-content {
                            color: #000099;
                            font-weight: bold;
                        }
                        .xmlverb-ns-name {
                            color: #666600;
                        }
                        .xmlverb-ns-uri {
                            color: #330099;
                        }
                        .xmlverb-text {
                            color: #000000;
                            font-weight: bold;
                        }
                        .xmlverb-comment {
                            color: #006600;
                            font-style: italic;
                        }
                        .xmlverb-pi-name {
                            color: #006600;
                            font-style: italic;
                        }
                        .xmlverb-pi-content {
                            color: #006666;
                            font-style: italic;
                        }
                        </style>
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:comment>HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries</xsl:comment>
                <xsl:comment><![CDATA[[if lt IE 9]>
                <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
                <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
                <![endif]]]></xsl:comment>
            </head>
            <body>
                <nav class="navbar navbar-inverse navbar-fixed-top">
                    <div class="container-fluid">
                        <div class="navbar-header">
                            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                                <span class="sr-only">Toggle navigation</span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                            </button>
                            <a class="navbar-brand" id="logo">
                                <xsl:attribute name="href">
                                    <xsl:value-of select="$url-homepage" />
                                </xsl:attribute>
                                <xsl:value-of select="$homepage-text" />
                            </a>
                        </div>
                        <div id="navbar" class="navbar-collapse collapse">
                            <ul class="nav navbar-nav navbar-right">
                                <xsl:call-template name="nav-link">
                                    <xsl:with-param name="text" select="'Identify'" />
                                    <xsl:with-param name="title" select="'Institutional information'" />
                                    <xsl:with-param name="verb" select="'Identify'" />
                                </xsl:call-template>
                                <xsl:call-template name="nav-link">
                                    <xsl:with-param name="text" select="'Formats'" />
                                    <xsl:with-param name="title" select="'Metadata Formats available'" />
                                    <xsl:with-param name="verb" select="'ListMetadataFormats'" />
                                </xsl:call-template>
                                <xsl:call-template name="nav-link">
                                    <xsl:with-param name="text" select="'Sets'" />
                                    <xsl:with-param name="title" select="'Listing available sets'" />
                                    <xsl:with-param name="verb" select="'ListSets'" />
                                </xsl:call-template>
                                <xsl:call-template name="nav-link">
                                    <xsl:with-param name="text" select="'Identifiers'" />
                                    <xsl:with-param name="title" select="'Listing identifiers only'" />
                                    <xsl:with-param name="verb" select="'ListIdentifiers'" />
                                    <xsl:with-param name="metadataPrefix" select="'oai_dc'" />
                                </xsl:call-template>
                                <xsl:call-template name="nav-link">
                                    <xsl:with-param name="text" select="'Records'" />
                                    <xsl:with-param name="title" select="'Listing records with metadata'" />
                                    <xsl:with-param name="verb" select="'ListRecords'" />
                                    <xsl:with-param name="metadataPrefix" select="'oai_dc'" />
                                </xsl:call-template>
                                <!--
                                <xsl:call-template name="nav-link">
                                    <xsl:with-param name="text" select="'Get Record'" />
                                    <xsl:with-param name="title" select="'Get Record'" />
                                    <xsl:with-param name="verb" select="'GetRecord'" />
                                </xsl:call-template>
                                -->
                            </ul>
                            <!--
                            <form class="navbar-form navbar-right">
                                <input type="text" class="form-control" placeholder="Search..." />
                            </form>
                            -->
                        </div>
                    </div>
                </nav>
                <div class="container">
                    <div class="row">
                        <div class="panel panel-default panel-success oaipmh-response">
                            <xsl:if test="$url-gateway != ''">
                                <div class="panel-heading">
                                    <xsl:text>The access to this repository is provided through the gateway </xsl:text>
                                    <em><a href="{$url-gateway}">
                                        <xsl:value-of select="$url-gateway" />
                                    </a></em>
                                    <xsl:text>.</xsl:text>
                                </div>
                            </xsl:if>
                            <div class="panel-body">
                                <div class="pull-right">
                                    <xsl:text>Response Date: </xsl:text>
                                    <xsl:value-of select="substring(oai:OAI-PMH/oai:responseDate/text(), 1, 10)" />
                                    <xsl:text> </xsl:text>
                                    <xsl:value-of select="substring(oai:OAI-PMH/oai:responseDate/text(), 12, 8)" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <xsl:apply-templates select="oai:OAI-PMH/oai:error" />
                        <xsl:apply-templates select="oai:OAI-PMH/oai:Identify" />
                        <xsl:apply-templates select="oai:OAI-PMH/oai:ListMetadataFormats" />
                        <xsl:apply-templates select="oai:OAI-PMH/oai:ListSets" />
                        <xsl:apply-templates select="oai:OAI-PMH/oai:ListIdentifiers" />
                        <xsl:apply-templates select="oai:OAI-PMH/oai:ListRecords" />
                        <xsl:apply-templates select="oai:OAI-PMH/oai:GetRecord" />
                    </div>
                </div>
                <footer class="footer">
                    <div class="container">
                        <div class="row text-right">
                            <div class="vertical-space"></div>
                                <xsl:if test="$powered-by-url != '' and $powered-by-text != ''">
                                    <p><small><a href="{$powered-by-url}">
                                        <xsl:value-of select="$powered-by-text" />
                                    </a></small></p>
                                </xsl:if>
                                <a href="{$powered-by-url}" id="logo-powered-by" />
                            <div class="vertical-space"></div>
                        </div>
                        <div class="row text-right">
                            <div class="vertical-space"></div>
                                <xsl:if test="$stylesheet-url != '' and $stylesheet-text != ''">
                                    <p><small><a href="{$stylesheet-url}">
                                        <xsl:value-of select="$stylesheet-text" />
                                    </a></small></p>
                                </xsl:if>
                                <a href="{$stylesheet-url}" id="logo-stylesheet" />
                            <div class="vertical-space"></div>
                        </div>
                    </div>
                </footer>
                <xsl:choose>
                    <xsl:when test="$javascript-jquery != ''">
                        <script type="text/javascript" src="{$javascript-jquery}" />
                    </xsl:when>
                    <xsl:otherwise>
                        <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:choose>
                    <xsl:when test="$javascript-bootstrap != ''">
                        <script type="text/javascript" src="{$javascript-bootstrap}" />
                    </xsl:when>
                    <xsl:otherwise>
                        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
                    </xsl:otherwise>
                </xsl:choose>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:error">
        <div class="oaipmh oaipmh-error">
            <h2>Error in request!</h2>
            <div class="alert alert-danger" role="alert">
                <span class="label label-danger">
                    <xsl:text>[</xsl:text>
                    <xsl:value-of select="@code" />
                    <xsl:text>]</xsl:text>
                </span>
                <xsl:text> </xsl:text>
                <xsl:value-of select="text()" />
            </div>
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify">
        <div class="oaipmh oaipmh-identify">
            <h2>Repository Information</h2>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th scope="row">Repository Name</th>
                        <td><xsl:value-of select="oai:repositoryName/text()" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Repository Base Url</th>
                        <td>
                            <a href="{oai:baseURL/text()}">
                                <xsl:value-of select="oai:baseURL/text()" />
                            </a>
                        </td>
                    </tr>
                    <xsl:for-each select="oai:adminEmail">
                        <tr>
                            <th scope="row">E-Mail Contact</th>
                            <td>
                                <a href="{concat('mailto:', text())}">
                                    <xsl:value-of select="text()" />
                                </a>
                            </td>
                        </tr>
                    </xsl:for-each>
                    <tr>
                        <th scope="row">Protocol Version</th>
                        <td><xsl:value-of select="oai:protocolVersion/text()" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Earliest Registered Date</th>
                        <td><xsl:value-of select="translate(oai:earliestDatestamp/text(), 'TZ' , ' ')" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Date Granularity</th>
                        <td><xsl:value-of select="translate(oai:granularity/text(), 'TZ', ' ')" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Deletion Mode</th>
                        <td><xsl:value-of select="oai:deletedRecord/text()" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <xsl:apply-templates select="oai:description"/>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description">
        <xsl:apply-templates select="oai_identifier:oai-identifier" />
        <xsl:apply-templates select="oai_rights:rightsManifest" />
        <xsl:apply-templates select="oai_friends:friends" />
        <xsl:apply-templates select="oai_branding:branding" />
        <xsl:apply-templates select="oai_gateway:gateway" />
        <xsl:apply-templates select="./*[
            local-name() != 'oai-identifier'
            and local-name() != 'rightsManifest'
            and local-name() != 'gateway'
            and local-name() != 'friends'
            and local-name() != 'branding'
            ]"/>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description/*" priority="-100">
        <div class="oaipmh oaipmh-description">
            <h2>Unsupported Description Type</h2>
            <xsl:apply-templates select="." mode='xmlverb' />
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description/oai_identifier:oai-identifier">
        <div class="oaipmh oaipmh-description oaipmh-identifier">
            <h2>Identifiers Format</h2>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th scope="row">Scheme</th>
                        <td><xsl:value-of select="oai_identifier:scheme/text()" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Repository identifier</th>
                        <td><xsl:value-of select="oai_identifier:repositoryIdentifier/text()" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Delimiter</th>
                        <td><code><xsl:value-of select="oai_identifier:delimiter/text()" /></code></td>
                    </tr>
                    <tr>
                        <th scope="row">Sample identifier</th>
                        <td><xsl:value-of select="oai_identifier:sampleIdentifier/text()" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description/oai_rights:rightsManifest">
        <div class="oaipmh oaipmh-description oaipmh-rights">
            <h2>Rights  Manifest</h2>
            <xsl:apply-templates select="." mode='xmlverb' />
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description/oai_friends:friends">
        <div class="oaipmh oaipmh-description oaipmh-friends">
            <h2>Confederated Repositories</h2>
            <xsl:choose>
                <xsl:when test="count(oai_friends:baseURL)">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <xsl:for-each select="oai_friends:baseURL">
                                <tr>
                                    <th scope="row">
                                        <xsl:value-of select="position()" />
                                    </th>
                                    <td>
                                        <a href="{concat(text(), '?verb=Identify')}">
                                            <xsl:value-of select="text()" />
                                        </a>
                                    </td>
                                </tr>
                            </xsl:for-each>
                        </tbody>
                    </table>
                </xsl:when>
                <xsl:otherwise>
                    <p>None.</p>
                </xsl:otherwise>
            </xsl:choose>
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description/oai_branding:branding">
        <div class="oaipmh oaipmh-description oaipmh-branding">
            <h2>Branding</h2>
            <xsl:apply-templates select="." mode='xmlverb' />
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description/oai_gateway:gateway">
        <div class="oaipmh oaipmh-description oaipmh-gateway">
            <h2>OAI-PMH Gateway</h2>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th scope="row">OAI-PMH Gateway</th>
                        <td>
                            <a href="{oai_gateway:gatewayURL/text()}">
                                <xsl:value-of select="oai_gateway:gatewayURL/text()" />
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Source</th>
                        <td>
                            <xsl:value-of select="oai_gateway:source/text()" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">E-Mail Contact</th>
                        <td>
                            <a href="{concat('mailto:', oai_gateway:gatewayAdmin/text())}">
                                <xsl:value-of select="oai_gateway:gatewayAdmin/text()" />
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Policy</th>
                        <td>
                            <a href="{oai_gateway:gatewayNotes/text()}">
                                <xsl:value-of select="oai_gateway:gatewayNotes/text()" />
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:Identify/oai:description/toolkit:toolkit">
        <div class="oaipmh oaipmh-description oaipmh-toolkit">
            <h2>Toolkit</h2>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th scope="row">Title</th>
                        <td>
                            <xsl:value-of select="toolkit:title/text()" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Author</th>
                        <td>
                            <xsl:call-template name="authors-split">
                                <xsl:with-param name="authors" select="toolkit:author/toolkit:name/text()" />
                                <xsl:with-param name="emails" select="toolkit:author/toolkit:email/text()" />
                                <xsl:with-param name="institutions" select="toolkit:author/toolkit:institution/text()" />
                            </xsl:call-template>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Version</th>
                        <td>
                            <xsl:value-of select="toolkit:version/text()" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Toolkit icon</th>
                        <td>
                            <xsl:if test="toolkit:toolkitIcon/text() != ''">
                                <a href="{toolkit:toolkitIcon/text()}">
                                    <xsl:value-of select="toolkit:toolkitIcon/text()" />
                                </a>
                                <img class="oaipmh-toolkit-icon" src="{toolkit:toolkitIcon/text()}" />
                            </xsl:if>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Url</th>
                        <td>
                            <a href="{toolkit:URL/text()}">
                                <xsl:value-of select="toolkit:URL/text()" />
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:ListMetadataFormats">
        <div class="oaipmh oaipmh-formats">
            <h2>List of Metadata Formats</h2>
            <div class="well well-sm">
                <xsl:text>Results fetched </xsl:text>
                 <span class="badge"><xsl:value-of select="count(oai:metadataFormat)" /></span>
            </div>

            <table class="table table-bordered table-striped">
                <tbody>
                    <xsl:for-each select="oai:metadataFormat">
                        <tr>
                            <td>
                                <h4><xsl:value-of select="oai:metadataPrefix/text()" /></h4>
                            </td>
                            <td>
                                <dl class="dl-horizontal">
                                    <dt>Namespace</dt>
                                    <dd><xsl:value-of select="oai:metadataNamespace/text()" /></dd>
                                    <dt>Schema</dt>
                                    <dd><xsl:value-of select="oai:schema/text()" /></dd>
                                </dl>
                            </td>
                            <td>
                                <div class="btn-group-vertical pull-right" aria-label="List identifiers and records" role="group">
                                    <a class="btn btn-default"
                                            href="{concat(/oai:OAI-PMH/oai:request/text(), '?verb=ListIdentifiers&amp;metadataPrefix=', oai:metadataPrefix/text())}">
                                        <xsl:text>List Identifiers</xsl:text>
                                    </a>
                                    <a class="btn btn-default"
                                            href="{concat(/oai:OAI-PMH/oai:request/text(), '?verb=ListRecords&amp;metadataPrefix=', oai:metadataPrefix/text())}">
                                        <xsl:text>List Records</xsl:text>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:ListSets">
        <xsl:variable name="first-result">
            <xsl:call-template name="first-result">
                <xsl:with-param name="path" select="oai:set" />
            </xsl:call-template>
        </xsl:variable>
        <div class="oaipmh oaipmh-sets">
            <h2>List of Sets</h2>
            <div class="well well-sm">
                <xsl:text>Results fetched </xsl:text>
                <span class="badge">
                    <xsl:call-template name="result-count">
                        <xsl:with-param name="path" select="oai:set" />
                    </xsl:call-template>
                </span>
            </div>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">
                            <span class="label label-default">
                                <xsl:text>Set Spec</xsl:text>
                            </span>
                            <xsl:text> </xsl:text>
                            <xsl:text>Set Name</xsl:text>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:for-each select="oai:set">
                        <tr>
                            <th scope="row">
                                <xsl:value-of select="position() + $first-result" />
                            </th>
                            <td>
                                <span class="label label-default">
                                    <xsl:value-of select="oai:setSpec/text()" />
                                </span>
                                <xsl:text> </xsl:text>
                                <xsl:value-of select="oai:setName/text()" />
                                <div class="btn-group pull-right" aria-label="List of identifiers and records" role="group">
                                    <xsl:choose>
                                        <xsl:when test="oai:setDescription != ''">
                                            <a class="btn btn-default collapse-data-btn" data-toggle="collapse"
                                                    href="{concat('#', translate(oai:setSpec/text(), $forbidden-characters, ''))}">
                                                <xsl:text>Description </xsl:text>
                                                <span class="caret"></span>
                                            </a>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <a class="btn btn-default disabled" href="#">
                                                <xsl:text>No Description</xsl:text>
                                            </a>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                    <a class="btn btn-default"
                                            href="{concat(/oai:OAI-PMH/oai:request/text(), '?verb=ListIdentifiers&amp;metadataPrefix=oai_dc&amp;set=', oai:setSpec/text())}">
                                        <xsl:text>List Identifiers</xsl:text>
                                    </a>
                                    <a class="btn btn-default"
                                            href="{concat(/oai:OAI-PMH/oai:request/text(), '?verb=ListRecords&amp;metadataPrefix=oai_dc&amp;set=', oai:setSpec/text())}">
                                        <xsl:text>List Records</xsl:text>
                                    </a>
                                </div>
                                <xsl:if test="oai:setDescription != ''">
                                    <div class="collapse" id="{translate(oai:setSpec/text(), $forbidden-characters, '')}">
                                        <hr />
                                        <xsl:apply-templates select="oai:setDescription/*" mode='xmlverb' />
                                    </div>
                                </xsl:if>
                            </td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
        </div>

        <xsl:apply-templates select="oai:resumptionToken"/>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:ListIdentifiers">
        <xsl:variable name="first-result">
            <xsl:call-template name="first-result">
                <xsl:with-param name="path" select="oai:header" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="metadata-prefix">
            <xsl:call-template name="metadata-prefix" />
        </xsl:variable>
        <div class="oaipmh oaipmh-identifiers">
            <h2>List of Identifiers</h2>
            <div class="well well-sm">
                <xsl:text>Results fetched </xsl:text>
                <span class="badge">
                    <xsl:call-template name="result-count">
                        <xsl:with-param name="path" select="oai:header" />
                    </xsl:call-template>
                </span>
                <xsl:call-template name="list-infos" />
            </div>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Last modified</th>
                        <th scope="col">Identifier</th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:for-each select="oai:header">
                        <tr>
                            <th scope="row">
                                <xsl:value-of select="position() + $first-result" />
                            </th>
                            <td>
                                <xsl:value-of select="translate(oai:datestamp/text(), 'TZ', ' ')" />
                            </td>
                            <td>
                                <xsl:value-of select="oai:identifier/text()" />
                                <div class="btn-group pull-right" aria-label="Get record" role="group">
                                    <xsl:choose>
                                        <xsl:when test="@status = 'deleted'">
                                                <a class="btn btn-default disabled" href="#">
                                                    <xsl:text>Deleted Record</xsl:text>
                                                </a>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <a class="btn btn-default">
                                                <xsl:attribute name="href">
                                                    <xsl:value-of select="/oai:OAI-PMH/oai:request/text()" />
                                                    <xsl:text>?verb=GetRecord&amp;metadataPrefix=</xsl:text>
                                                    <xsl:value-of select="$metadata-prefix" />
                                                    <xsl:text>&amp;identifier=</xsl:text>
                                                    <xsl:value-of select="oai:identifier/text()" />
                                                </xsl:attribute>
                                                <xsl:text>View Record</xsl:text>
                                            </a>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                    <xsl:call-template name="display-button-sets">
                                        <xsl:with-param name="path" select="oai:setSpec" />
                                    </xsl:call-template>
                                </div>
                            </td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
        </div>

        <xsl:apply-templates select="oai:resumptionToken"/>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:ListRecords">
        <xsl:variable name="first-result">
            <xsl:call-template name="first-result">
                <xsl:with-param name="path" select="oai:record" />
            </xsl:call-template>
        </xsl:variable>
        <div class="oaipmh oaipmh-records">
            <h2>List of Records</h2>
            <div class="well well-sm">
                <xsl:text>Results fetched </xsl:text>
                <span class="badge">
                    <xsl:call-template name="result-count">
                        <xsl:with-param name="path" select="oai:record" />
                    </xsl:call-template>
                </span>
                <xsl:call-template name="list-infos" />
            </div>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 48px;">#</th>
                        <th scope="col">Last modified</th>
                        <th scope="col">Identifier</th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:for-each select="oai:record">
                        <tr>
                            <th scope="row">
                                <xsl:value-of select="position() + $first-result" />
                            </th>
                            <td>
                                <xsl:value-of select="translate(oai:header/oai:datestamp/text(), 'TZ', ' ')" />
                            </td>
                            <td>
                                <xsl:value-of select="oai:header/oai:identifier/text()" />
                                <div class="btn-group pull-right" aria-label="Get record" role="group">
                                    <xsl:choose>
                                        <xsl:when test="oai:header/@status = 'deleted'">
                                                <a class="btn btn-default disabled" href="#">
                                                    <xsl:text>Deleted Record</xsl:text>
                                                </a>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <a class="btn btn-default collapse-data-btn" data-toggle="collapse"
                                                    href="{concat('#', translate(oai:header/oai:identifier/text(), $forbidden-characters, ''))}">
                                                <xsl:text>View Metadata</xsl:text>
                                            </a>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                    <xsl:call-template name="display-button-sets">
                                        <xsl:with-param name="path" select="oai:header/oai:setSpec" />
                                    </xsl:call-template>
                                </div>
                            </td>
                        </tr>
                        <xsl:choose>
                            <xsl:when test="oai:header/@status = 'deleted'">
                                <tr>
                                </tr>
                            </xsl:when>
                            <xsl:otherwise>
                                <tr class="collapse" id="{translate(oai:header/oai:identifier/text(), $forbidden-characters, '')}">
                                    <td colspan="3">
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <xsl:choose>
                                                            <xsl:when test="not(oai:about)">
                                                                <xsl:apply-templates select="oai:metadata/*" />
                                                            </xsl:when>
                                                            <xsl:otherwise>
                                                                <h3>Metadata</h3>
                                                                <xsl:apply-templates select="oai:metadata/*" />
                                                                <h3>About</h3>
                                                                <xsl:apply-templates select="oai:about/*" mode='xmlverb' />
                                                            </xsl:otherwise>
                                                        </xsl:choose>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:for-each>
                </tbody>
            </table>
        </div>

        <xsl:apply-templates select="oai:resumptionToken"/>
    </xsl:template>

    <xsl:template match="oai:OAI-PMH/oai:GetRecord">
        <div class="oaipmh oaipmh-record">
            <h2>Record Details</h2>
            <xsl:for-each select="oai:record">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-sm-8 col-md-9 col-lg-10">
                                <dl class="dl-horizontal">
                                    <dt>Metadata Prefix</dt>
                                    <dd><xsl:value-of select="/oai:OAI-PMH/oai:request/@metadataPrefix" /></dd>
                                    <dt>Identifier</dt>
                                    <dd><xsl:value-of select="oai:header/oai:identifier/text()" /></dd>
                                    <dt>Last Modfied </dt>
                                    <dd><xsl:value-of select="translate(oai:header/oai:datestamp/text(), 'TZ', ' ')" /></dd>
                                </dl>
                            </div>
                            <div class="col-sm-4 col-md-3 col-lg-2">
                                <div class="btn-group pull-right" aria-label="List of identifiers and records" role="group">
                                    <xsl:call-template name="display-button-sets">
                                        <xsl:with-param name="path" select="oai:header/oai:setSpec" />
                                    </xsl:call-template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <xsl:choose>
                            <xsl:when test="oai:header/@status = 'deleted'">
                                <h3>Deleted Record</h3>
                            </xsl:when>
                            <xsl:when test="not(oai:about)">
                                <xsl:apply-templates select="oai:metadata/*" />
                            </xsl:when>
                            <xsl:otherwise>
                                <h3>Metadata</h3>
                                <xsl:apply-templates select="oai:metadata/*" />
                                <h3>About</h3>
                                <xsl:apply-templates select="oai:about/*" mode='xmlverb' />
                            </xsl:otherwise>
                        </xsl:choose>
                    </div>
                </div>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template match="oai:resumptionToken">
        <xsl:if test="text() != ''">
            <div class="oaipmh oaipmh-token">
                <div class="text-center">
                    <a class="btn btn-primary"
                            href="{concat(/oai:OAI-PMH/oai:request/text(), '?verb=', /oai:OAI-PMH/oai:request/@verb, '&amp;resumptionToken=', text())}">
                    <xsl:text>Show More</xsl:text>
                    </a>
                </div>
                <xsl:if test="@expirationDate != ''">
                    <div class="text-center">
                        <span class="label label-default">
                            <xsl:text>Expires </xsl:text>
                            <xsl:value-of select="normalize-space(translate(@expirationDate, 'TZ' , ' '))" />
                        </span>
                    </div>
                </xsl:if>
            </div>
        </xsl:if>
    </xsl:template>

    <!-- =========================================================== -->
    <!--                      Special Functions                      -->
    <!-- =========================================================== -->

    <!-- Generate a list element link for the main nav bar. -->
    <xsl:template name="nav-link">
        <xsl:param name="text" />
        <xsl:param name="title" />
        <xsl:param name="verb" />
        <xsl:param name="metadataPrefix" />
        <li>
            <xsl:if test="/oai:OAI-PMH/oai:request/@verb = $verb">
                <xsl:attribute name="class">active</xsl:attribute>
            </xsl:if>
            <xsl:element name="a">
                <xsl:if test="$title != ''">
                    <xsl:attribute name="title"><xsl:value-of select="$title" /></xsl:attribute>
                </xsl:if>
                <xsl:attribute name="href">
                    <xsl:choose>
                        <xsl:when test="$verb = ''">
                            <xsl:text>#</xsl:text>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="concat(/oai:OAI-PMH/oai:request/text(), '?verb=', $verb)" />
                            <xsl:if test="$metadataPrefix != ''">
                                <xsl:text>&amp;metadataPrefix=</xsl:text>
                                <xsl:value-of select="$metadataPrefix" />
                            </xsl:if>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:attribute>
                <xsl:value-of select="$text" />
            </xsl:element>
        </li>
    </xsl:template>

    <xsl:template name="result-count">
        <xsl:param name="path" />
        <xsl:variable name="cursor" select="$path/../oai:resumptionToken/@cursor" />
        <xsl:variable name="count" select="count($path)" />
        <xsl:variable name="total" select="$path/../oai:resumptionToken/@completeListSize" />
        <xsl:choose>
            <!-- One page. -->
            <xsl:when test="not($cursor)">
                <xsl:value-of select="$count" />
            </xsl:when>
            <!-- Not last page. -->
            <xsl:when test="normalize-space($path/../oai:resumptionToken/text()) != ''">
                <xsl:value-of select="$cursor + 1" />
                <xsl:text>-</xsl:text>
                <xsl:value-of select="$cursor + $count" />
            </xsl:when>
            <!-- Last page. -->
            <xsl:when test="$total">
                <xsl:value-of select="($total - $count) + 1" />
                <xsl:text>-</xsl:text>
                <xsl:value-of select="$total" />
            </xsl:when>
            <!-- Last page without total. -->
            <xsl:otherwise>
                <xsl:text>the last </xsl:text>
                <xsl:value-of select="$count" />
            </xsl:otherwise>
        </xsl:choose>
        <xsl:if test="$total">
            <xsl:text> of </xsl:text>
            <xsl:value-of select="$total" />
        </xsl:if>
    </xsl:template>

    <xsl:template name="first-result">
        <xsl:param name="path" />
        <xsl:variable name="cursor" select="$path/../oai:resumptionToken/@cursor" />
        <xsl:variable name="count" select="count($path)" />
        <xsl:variable name="total" select="$path/../oai:resumptionToken/@completeListSize" />
        <xsl:choose>
            <!-- One page. -->
            <xsl:when test="not($cursor)">
                <xsl:value-of select="0" />
            </xsl:when>
            <!-- Not last page. -->
            <xsl:when test="normalize-space($path/../oai:resumptionToken/text()) != ''">
                <xsl:value-of select="$cursor" />
            </xsl:when>
            <!-- Last page. -->
            <xsl:when test="$total">
                <xsl:value-of select="$total - $count" />
            </xsl:when>
            <!-- Last page without total. -->
            <xsl:otherwise>
                <xsl:value-of select="0" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <!-- TODO Find a way to get the prefix when resumption token. -->
    <xsl:template name="metadata-prefix">
        <xsl:choose>
            <xsl:when test="/oai:OAI-PMH/oai:request/@metadataPrefix != ''">
                <xsl:value-of select="/oai:OAI-PMH/oai:request/@metadataPrefix" />
            </xsl:when>
            <xsl:otherwise>
                <xsl:text>oai_dc</xsl:text>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <!-- Write infos about a list of identifiers or records. -->
    <xsl:template name="list-infos">
        <xsl:if test="not(/oai:OAI-PMH/oai:request/@resumptionToken)">
            <xsl:text> </xsl:text>
            <span class="label label-default">
                <xsl:value-of select="/oai:OAI-PMH/oai:request/@metadataPrefix" />
            </span>
            <xsl:if test="/oai:OAI-PMH/oai:request/@from != ''">
                <xsl:text> </xsl:text>
                <span class="label label-default">
                    <xsl:text>from </xsl:text>
                    <xsl:value-of select="normalize-space(translate(/oai:OAI-PMH/oai:request/@from, 'TZ' , ' '))" />
                </span>
            </xsl:if>
            <xsl:if test="/oai:OAI-PMH/oai:request/@until != ''">
                <xsl:text> </xsl:text>
                <span class="label label-default">
                    <xsl:text>until </xsl:text>
                    <xsl:value-of select="normalize-space(translate(/oai:OAI-PMH/oai:request/@until, 'TZ' , ' '))" />
                </span>
            </xsl:if>
        </xsl:if>
    </xsl:template>

    <xsl:template name="display-button-sets">
        <xsl:param name="path" />
        <xsl:choose>
            <xsl:when test="count($path)">
                <xsl:variable name="metadata-prefix">
                    <xsl:call-template name="metadata-prefix" />
                </xsl:variable>
                <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <xsl:text>View sets </xsl:text>
                    <span class="label label-info"><xsl:value-of select="count($path)" /></span>
                    <xsl:text> </xsl:text>
                    <span class="caret"></span>
                </a>
                <ul class="dropdown-menu" role="menu">
                    <li role="presentation" class="dropdown-header pull-right">
                        <xsl:value-of select="$metadata-prefix" />
                    </li>
                    <xsl:for-each select="$path">
                        <li>
                            <a>
                                <xsl:attribute name="href">
                                    <xsl:value-of select="/oai:OAI-PMH/oai:request/text()" />
                                    <xsl:text>?verb=ListRecords&amp;metadataPrefix=</xsl:text>
                                    <xsl:value-of select="$metadata-prefix" />
                                    <xsl:text>&amp;set=</xsl:text>
                                    <xsl:value-of select="text()" />
                                </xsl:attribute>
                                <xsl:value-of select="text()" />
                            </a>
                        </li>
                    </xsl:for-each>
                </ul>
            </xsl:when>
            <xsl:otherwise>
                <a class="btn btn-default disabled" href="#">
                    <xsl:text>Not in a set</xsl:text>
                </a>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text()" name="authors-split">
        <xsl:param name="separator" select="';'" />
        <xsl:param name="authors" />
        <xsl:param name="emails" select="''" />
        <xsl:param name="institutions" select="''" />

        <xsl:variable name="author">
            <xsl:call-template name="split">
                <xsl:with-param name="string" select="$authors" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="email">
            <xsl:call-template name="split">
                <xsl:with-param name="string" select="$emails" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="institution">
            <xsl:call-template name="split">
                <xsl:with-param name="string" select="$institutions" />
            </xsl:call-template>
        </xsl:variable>

        <xsl:choose>
            <xsl:when test="string-length($author)">
                <xsl:choose>
                    <xsl:when test="$email != ''">
                        <a href="{concat('mailto:', $email)}">
                            <xsl:value-of select="$author" />
                        </a>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="$author" />
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:when test="string-length($email)">
                <a href="{concat('mailto:', $email)}">
                    <xsl:value-of select="$email" />
                </a>
            </xsl:when>
        </xsl:choose>
        <xsl:if test="string-length($institution)">
            <xsl:value-of select="concat(' (', normalize-space($institution), ')')" />
        </xsl:if>

        <xsl:if test="$authors != '' or $emails != '' or $institutions != ''">
            <br />
            <xsl:call-template name="authors-split">
                <xsl:with-param name="separator" select="$separator" />
                <xsl:with-param name="authors" select="substring-after($authors, $separator)" />
                <xsl:with-param name="emails" select="substring-after($emails, $separator)" />
                <xsl:with-param name="institutions" select="substring-after($institutions, $separator)" />
            </xsl:call-template>
        </xsl:if>
   </xsl:template>

   <xsl:template match="text()" name="split">
        <xsl:param name="separator" select="';'" />
        <xsl:param name="string" select="''" />
        <xsl:choose>
            <xsl:when test="contains($string, $separator)">
                <xsl:value-of select="substring-before($string, $separator)" />
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$string" />
            </xsl:otherwise>
        </xsl:choose>
   </xsl:template>

    <!-- =========================================================== -->
    <!--                    Included XML Verbatim                    -->
    <!-- =========================================================== -->

<!--
    XML to HTML Verbatim Formatter with Syntax Highlighting
    Version 1.1
    Contributors: Doug Dicks, added auto-indent (parameter indent-elements)
                  for pretty-print

    Copyright 2002 Oliver Becker
    ob@obqo.de

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
    Unless required by applicable law or agreed to in writing, software distributed
    under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
    CONDITIONS OF ANY KIND, either express or implied. See the License for the
    specific language governing permissions and limitations under the License.

    Alternatively, this software may be used under the terms of the
    GNU Lesser General Public License (LGPL).
-->

    <xsl:template match="oai:metadata/*" priority='-20'>
        <xsl:apply-templates select="." mode='xmlverb' />
    </xsl:template>

    <!-- root -->
    <xsl:template match="/" mode="xmlverb">
        <xsl:text>&#xA;</xsl:text>
        <xsl:comment>
            <xsl:text> converted by xmlverbatim.xsl 1.1, (c) O. Becker </xsl:text>
        </xsl:comment>
        <xsl:text>&#xA;</xsl:text>
        <div class="xmlverb-default">
            <xsl:apply-templates mode="xmlverb">
                <xsl:with-param name="indent-elements" select="$indent-elements" />
            </xsl:apply-templates>
        </div>
        <xsl:text>&#xA;</xsl:text>
    </xsl:template>

    <!-- wrapper -->
    <xsl:template match="verb:wrapper">
        <xsl:apply-templates mode="xmlverb">
            <xsl:with-param name="indent-elements" select="$indent-elements" />
        </xsl:apply-templates>
    </xsl:template>

    <xsl:template match="verb:wrapper" mode="xmlverb">
        <xsl:apply-templates mode="xmlverb">
            <xsl:with-param name="indent-elements" select="$indent-elements" />
        </xsl:apply-templates>
    </xsl:template>

    <!-- element nodes -->
    <xsl:template match="*" mode="xmlverb">
        <xsl:param name="indent-elements" select="false()" />
        <xsl:param name="indent" select="''" />
        <xsl:param name="indent-increment" select="'&#xA0;&#xA0;&#xA0;'" />
        <xsl:if test="$indent-elements">
            <br/>
            <xsl:value-of select="$indent" />
        </xsl:if>
        <xsl:text>&lt;</xsl:text>
        <xsl:variable name="ns-prefix"
                      select="substring-before(name(), ':')" />
        <xsl:if test="$ns-prefix != ''">
            <span class="xmlverb-element-nsprefix">
                <xsl:value-of select="$ns-prefix"/>
            </span>
            <xsl:text>:</xsl:text>
        </xsl:if>
        <span class="xmlverb-element-name">
            <xsl:value-of select="local-name()"/>
        </span>
        <xsl:variable name="pns" select="../namespace::*"/>
        <xsl:if test="$pns[name()=''] and not(namespace::*[name()=''])">
            <span class="xmlverb-ns-name">
                <xsl:text> xmlns</xsl:text>
            </span>
            <xsl:text>=&quot;&quot;</xsl:text>
        </xsl:if>
        <xsl:for-each select="namespace::*">
            <xsl:if test="not($pns[name()=name(current()) and
                              .=current()])">
                <xsl:call-template name="xmlverb-ns" />
            </xsl:if>
        </xsl:for-each>
        <xsl:for-each select="@*">
            <xsl:call-template name="xmlverb-attrs" />
        </xsl:for-each>
        <xsl:choose>
            <xsl:when test="node()">
                <xsl:text>&gt;</xsl:text>
                <xsl:apply-templates mode="xmlverb">
                    <xsl:with-param name="indent-elements"
                                    select="$indent-elements"/>
                    <xsl:with-param name="indent"
                                    select="concat($indent, $indent-increment)"/>
                    <xsl:with-param name="indent-increment"
                                    select="$indent-increment"/>
                </xsl:apply-templates>
                <xsl:if test="* and $indent-elements">
                    <br/>
                    <xsl:value-of select="$indent" />
                </xsl:if>
                <xsl:text>&lt;/</xsl:text>
                <xsl:if test="$ns-prefix != ''">
                    <span class="xmlverb-element-nsprefix">
                        <xsl:value-of select="$ns-prefix"/>
                    </span>
                    <xsl:text>:</xsl:text>
                </xsl:if>
                <span class="xmlverb-element-name">
                    <xsl:value-of select="local-name()"/>
                </span>
                <xsl:text>&gt;</xsl:text>
            </xsl:when>
            <xsl:otherwise>
                <xsl:text> /&gt;</xsl:text>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:if test="not(parent::*)"><br /><xsl:text>&#xA;</xsl:text></xsl:if>
    </xsl:template>

    <!-- attribute nodes -->
    <xsl:template name="xmlverb-attrs">
        <xsl:text> </xsl:text>
        <span class="xmlverb-attr-name">
            <xsl:value-of select="name()"/>
        </span>
        <xsl:text>=&quot;</xsl:text>
        <span class="xmlverb-attr-content">
            <xsl:call-template name="html-replace-entities">
                <xsl:with-param name="text" select="normalize-space(.)" />
                <xsl:with-param name="attrs" select="true()" />
            </xsl:call-template>
        </span>
        <xsl:text>&quot;</xsl:text>
    </xsl:template>

    <!-- namespace nodes -->
    <xsl:template name="xmlverb-ns">
        <xsl:if test="name()!='xml'">
            <span class="xmlverb-ns-name">
                <xsl:text> xmlns</xsl:text>
                <xsl:if test="name()!=''">
                    <xsl:text>:</xsl:text>
                </xsl:if>
                <xsl:value-of select="name()"/>
            </span>
            <xsl:text>=&quot;</xsl:text>
            <span class="xmlverb-ns-uri">
                <xsl:value-of select="."/>
            </span>
            <xsl:text>&quot;</xsl:text>
        </xsl:if>
    </xsl:template>

    <!-- text nodes -->
    <xsl:template match="text()" mode="xmlverb">
        <span class="xmlverb-text">
            <xsl:call-template name="preformatted-output">
                <xsl:with-param name="text">
                    <xsl:call-template name="html-replace-entities">
                        <xsl:with-param name="text" select="." />
                    </xsl:call-template>
                </xsl:with-param>
            </xsl:call-template>
        </span>
    </xsl:template>

    <!-- comments -->
    <xsl:template match="comment()" mode="xmlverb">
        <xsl:text>&lt;!--</xsl:text>
        <span class="xmlverb-comment">
            <xsl:call-template name="preformatted-output">
                <xsl:with-param name="text" select="." />
            </xsl:call-template>
        </span>
        <xsl:text>--&gt;</xsl:text>
        <xsl:if test="not(parent::*)"><br /><xsl:text>&#xA;</xsl:text></xsl:if>
    </xsl:template>

    <!-- processing instructions -->
    <xsl:template match="processing-instruction()" mode="xmlverb">
        <xsl:text>&lt;?</xsl:text>
        <span class="xmlverb-pi-name">
            <xsl:value-of select="name()"/>
        </span>
        <xsl:if test=".!=''">
            <xsl:text> </xsl:text>
            <span class="xmlverb-pi-content">
                <xsl:value-of select="."/>
            </span>
        </xsl:if>
        <xsl:text>?&gt;</xsl:text>
        <xsl:if test="not(parent::*)"><br /><xsl:text>&#xA;</xsl:text></xsl:if>
    </xsl:template>

    <!-- =========================================================== -->
    <!--                    Procedures / Functions                   -->
    <!-- =========================================================== -->

    <!-- generate entities by replacing &, ", < and > in $text -->
    <xsl:template name="html-replace-entities">
        <xsl:param name="text" />
        <xsl:param name="attrs" />
        <xsl:variable name="tmp">
            <xsl:call-template name="replace-substring">
                <xsl:with-param name="from" select="'&gt;'" />
                <xsl:with-param name="to" select="'&amp;gt;'" />
                <xsl:with-param name="value">
                    <xsl:call-template name="replace-substring">
                        <xsl:with-param name="from" select="'&lt;'" />
                        <xsl:with-param name="to" select="'&amp;lt;'" />
                        <xsl:with-param name="value">
                            <xsl:call-template name="replace-substring">
                                <xsl:with-param name="from"
                                                select="'&amp;'" />
                                <xsl:with-param name="to"
                                                select="'&amp;amp;'" />
                                <xsl:with-param name="value"
                                                select="$text" />
                            </xsl:call-template>
                        </xsl:with-param>
                    </xsl:call-template>
                </xsl:with-param>
            </xsl:call-template>
        </xsl:variable>
        <xsl:choose>
            <!-- $text is an attribute value -->
            <xsl:when test="$attrs">
                <xsl:call-template name="replace-substring">
                    <xsl:with-param name="from" select="'&#xA;'" />
                    <xsl:with-param name="to" select="'&amp;#xA;'" />
                    <xsl:with-param name="value">
                        <xsl:call-template name="replace-substring">
                            <xsl:with-param name="from"
                                            select="'&quot;'" />
                            <xsl:with-param name="to"
                                            select="'&amp;quot;'" />
                            <xsl:with-param name="value" select="$tmp" />
                        </xsl:call-template>
                    </xsl:with-param>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$tmp" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <!-- replace in $value substring $from with $to -->
    <xsl:template name="replace-substring">
        <xsl:param name="value" />
        <xsl:param name="from" />
        <xsl:param name="to" />
        <xsl:choose>
            <xsl:when test="contains($value,$from)">
                <xsl:value-of select="substring-before($value,$from)" />
                <xsl:value-of select="$to" />
                <xsl:call-template name="replace-substring">
                    <xsl:with-param name="value"
                                    select="substring-after($value,$from)" />
                    <xsl:with-param name="from" select="$from" />
                    <xsl:with-param name="to" select="$to" />
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$value" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <!-- preformatted output: space as &nbsp;, tab as 8 &nbsp;
                              nl as <br> -->
    <xsl:template name="preformatted-output">
        <xsl:param name="text" />
        <xsl:call-template name="output-nl">
            <xsl:with-param name="text">
                <xsl:call-template name="replace-substring">
                    <xsl:with-param name="value"
                                    select="translate($text, ' ', '&#xA0;')" />
                    <xsl:with-param name="from" select="'&#9;'" />
                    <xsl:with-param name="to"
                                    select="'&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;&#xA0;'" />
                </xsl:call-template>
            </xsl:with-param>
        </xsl:call-template>
    </xsl:template>

    <!-- output nl as <br> -->
    <xsl:template name="output-nl">
        <xsl:param name="text" />
        <xsl:choose>
            <xsl:when test="contains($text, '&#xA;')">
                <xsl:value-of select="substring-before($text, '&#xA;')" />
                <br />
                <xsl:text>&#xA;</xsl:text>
                <xsl:call-template name="output-nl">
                    <xsl:with-param name="text"
                                    select="substring-after($text, '&#xA;')" />
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$text" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

</xsl:stylesheet>
