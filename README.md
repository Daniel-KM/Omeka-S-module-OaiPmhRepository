OAI-PMH Repository (module for Omeka S)
=======================================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[OAI-PMH Repository] is a module for [Omeka S] that implements an Open Archives
Initiative Protocol for Metadata Harvesting ([OAI-PMH]) repository for Omeka S,
allowing Omeka S items, item sets, and media to be harvested by OAI-PMH
harvesters. The module implements version 2.0 of the protocol.

It provides a way to map non-Dublin Core properties to Dublin Core properties in
order to expose all values of all items, in particular records for author
managed with foaf. Of course, the mapping is far from perfect, because it is not
simple to map two ontologies that don't describe the same content.

Anyway, there is a specific format, `simple_xml`, similar to the default `oai_dc`,
that returns resource as a flat xml, included all values of all vocabularies
that are used.


Installation
------------

Uncompress the zip inside the folder `modules` and rename it `OaiPmhRepository`.

See general end user documentation for [Installing a module].


Config
------

There is a global oai server, with all the data of the Omeka S instance, and a
server for each site, if wanted, to manage the case where there are multiple
institutional entities that share the same Omeka S server. Nevertheless, all
options are defined globally in order to keep consistency between servers, in
particulars for the identifiers of the sets and the items.

### Repository name

Name for this OAI-PMH repository. This value is sent as part of the response to
an Identify request, and it is how the repository will be identified by
well-behaved harvesters.

Default: The name of the Omeka S installation.

### Namespace identifier

The oai-identifier specification requires repositories to specify a namespace
identifier. This will be used to form globally unique IDs for the exposed
metadata items. This value is required to be a domain name you have registered.
Using other values will generate invalid identifiers.

Default: If it can, the module will try to automatically detect the domain of
the server hosting the site, and use that as the default namespace identifier.
If a name can’t be detected (for example, if the site is accessed through the
`localhost` domain), the default will be `default.must.change` (as you might
think, this value is intended to be changed, not used as-is). The module will
function with this, or any other string, as the namespace identifier, but this
breaks the assumption that each identifier is globally unique. Best practice is
to set this value to the domain name the Omeka server is published at, possibly
with a prefix like "oai."

### Metadata formats

Select the formats that will be available. Only `oai_dc` is required.

### Expose media

Whether the repository should expose direct URLs to all the files associated
with an item as part of its returned metadata. This gives harvesters the ability
to directly access the media described by the metadata.

Default: true

### Hide empty oai sets

Whether the module should expose empty oai sets. If enabled, only collections or
sites that actually contain at least one public item will be included in the
ListSets output. If disabled, all public oai sets are included in ListSets
output.

Default: true

### Global repository

The global repository contains all the resources of Omeka S, in one place. If
enabled, it can exposes different types of oai sets: the sites, or the item
sets. The url is simply `/oai`.

**TODO**: Expose the resource classes as oai sets or subsets. Expose sets via
created via queries.

### Site repositories

The site repositories simulate multiple oai servers, with the site pools of
items and the attached item sets as oai sets.

### Oai set identifiers

There is only one format currently, but new ones can be added, for example when
the resource have a unique standard identifier.

### Genericize Dublin Core terms

This option allows to expose all metadata in the standard oai_dc and use refined
terms for Dublin Core elements, for example "dcterms:abstract" will be merged
with "dc:description". For other merges, the event `oaipmhrepository.values.pre`
can be used. For a quick hack, the file `data/mapping/generic_dcterms.php` can
be updated.

### Map properties

This option allows to expose any metadata as another one. The [default mapping]
maps Bibliographic Ontology (bibo) and Friend of a Friend (foaf) into Dublin Core.
Of course is not perfect, but you can adapt it to your real data. See the default
For foaf, a [spreadsheet] can help to improve the mapping.

### Metadata for mets (item and media)

Mets can use any format to describe the item and the files. The formats that are
commonly used are `dc` and `mods` (currently unavailable).

### Human interface

The OAI-PMH pages can be displayed and browsed with a themable responsive human
interface based on [Bootstrap]. To theme it, create a folder `asset/xsl` in your
theme and copy the file [`oai-pmh-repository.xsl`] inside it, then edit it. The
css file can be copied and updated as well.

### Global repository redirect route

An alias (redirect 301) for backward compatibility with Omeka Classic, that used
`/oai-pmh-repository/request`, or any other old OAI-PMH repository, so the
harvesters will continue to harvest your metadata.

### List response limit

Number of individual items that can be returned in a response at once. Larger
values will increase memory usage but reduce the number of database queries and
HTTP requests. Smaller values will reduce memory usage but increase the number
of DB queries and requests.

Default: 50 (recommended in most cases)

### List expiration time

Amount of time in minutes a resumptionToken is valid for. The specification
suggests a number in the tens of minutes. This boils down to the length of time
a harvester has to request the next part of an incomplete list request.

Default: 10 (minutes)


Metadata formats
----------------

The module ships with several default formats. Other modules can alter these or
add their own.

### [Dublin Core] (prefix `oai_dc`)

The Dublin Core is required by the OAI-PMH specification for all repositories.
Omeka S metadata fields are mapped one-to-one with fields for this output
format.

### [Dublin Core Terms] (prefix `oai_dcterms`)

The OAI-PMH standard does not manage the complete set of Dublin Core, but it is
largely used. So this format is similar to `oai_dc`, but with all 55 terms.

**NOTE**: The namespace and the schema don’t exist.

### [CDWA Lite] (prefix `cdwalite`)

The mapping between Omeka’s metadata and CDWA Lite metadata is more complicated,
and certain fields may not be populated correctly. The chief advantage of using
CDWA Lite output is that file URLs can be output in a controlled format, unlike
Dublin Core. Harvesters may therefore be able to harvest or link to files in
addition to metadata.

### [MODS] (prefix `mods`)

This output crosswalks the Dublin Core metadata to MODS using the mapping
recommended by the Library of Congress.

### [METS] (prefix `mets`)

The Metadata Encoding and Transmission Standard format exposes files to
harvesters alongside Dublin Core metadata.

### [RDF] (prefix `rdf`)

This format exposes metadata as RDF/XML. Unlike many of the other formats, RDF
allows the repository to expose metadata from different standards all in the
same output.

**NOTE**: The rdf output is currently not implemented, but it is available
through the standard api of Omeka S as json.

### [Omeka XML] (prefix `omeka-xml`)

This output format uses an Omeka-specific XML output that includes all metadata
elements without requiring crosswalking or subsetting, but is not well-supported
by harvesters or other tools and because the RRCHNM itself removed the [schema]
from the last site.

**NOTE**: Because of its limited support by harvesters, the format is not
implemented in Omeka S. Nevertheless, you can use the format "simple xml" that
provides all metadata too.

### Simple flat xml (prefix `simple_xml`)

This format exposes metadata as a simple flat XML. Unlike most of the other
standard formats, this format expose all metadata from different vocabularies.

### Other formats

Other formats can be added or replace an existing one via a key in the config
`['oaipmhrepository']['metadata_formats']`.

#### [EAD]

The format EAD, designed to describe archives, is available through the module
[`EAD`].

### Customization

The output can be customized via the filter `oaipmhrepository.values.pre`, that
is triggered before processing and that contains all the values of the current
resource, so it is possible to remove, to update or to append some values, or to
convert some properties from other vocabularies into the standard formats.

The old event `oaipmhrepository.values` called for each value is deprecated
since version 3.3.5.2 and was removed in version 3.3.6.


TODO
----

- [ ] Normalize simple_xml (or omeka xml v5/v6?).
- [ ] Improve data output for media html in simple xml, in particular for html.
- [ ] Include namespace and metadata of modules when needed for simple_xml (module Mapping, etc.)
- [ ] Native rdf output (from json serialization? From easyrdf?).
- [ ] Improve performance for big bases and items with many medias. With a cache? In a table? As a xml file?


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

### Module

This module is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

### Human interface (xslt stylesheet)

The human interface is published under the [CeCILL-B] BSD-like licence. See its
header for other licenses notes. It is based on [Bootstrap].


Copyright
---------

See commits for full list of contributors.

* Copyright Yu-Hsun Lin, 2009
* Copyright John Flatness, 2009-2016
* Copyright Julian Maurice for BibLibre, 2016-2017
* Copyright Daniel Berthereau, 2014-2022 (see [Daniel-KM])

This [Omeka S] module is based on the rewrite of the [OAI-PMH Repository plugin]
for [Omeka] by [BibLibre] and provide the same features as the original plugin
and many more.


[OAI-PMH Repository]: https://gitlab.com/Daniel-KM/Omeka-S-module-OaiPmhRepository
[Omeka S]: https://omeka.org/s
[OAI-PMH]: https://www.openarchives.org/OAI/openarchivesprotocol.html
[OAI-PMH Repository plugin]: https://github.com/omeka/plugin-OaiPmhRepository
[Omeka]: https://omeka.org/classic
[BibLibre]: https://github.com/biblibre
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[Bootstrap]: https://getbootstrap.com
[default mapping]: https://gitlab.com/Daniel-KM/Omeka-S-module-OaiPmhRepository/-/blob/master/config/module.config.php#L130
[spreadsheet]: https://gitlab.com/Daniel-KM/Omeka-S-module-OaiPmhRepository/-/blob/master/foaf_to_dcterms.ods
[`oai-pmh-repository.xsl`]: https://gitlab.com/Daniel-KM/Omeka-S-module-OaiPmhRepository/blob/master/asset/xsl/oai-pmh-repository.xsl
[Dublin Core]: http://dublincore.org
[Dublin Core Terms]: http://www.dublincore.org/documents/dcmi-terms/
[CDWA Lite]: https://www.getty.edu/research/publications/electronic_publications/cdwa/cdwalite.html
[MODS]: http://www.loc.gov/standards/mods/
[METS]: http://www.loc.gov/standards/mets/
[RDF]: https://www.w3.org/TR/rdf-syntax-grammar/
[Omeka XML]: http://omeka.org/schemas/omeka-xml/v5/omeka-xml-5-0.xsd
[schema]: http://omeka.org/schemas/omeka-xml/v5/omeka-xml-5-0.xsd
[EAD]: http://www.loc.gov/ead/
[`EAD`]: https://gitlab.com/Daniel-KM/Omeka-S-module-Ead
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-OaiPmhRepository/-/issues
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[CeCILL-B]: https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
