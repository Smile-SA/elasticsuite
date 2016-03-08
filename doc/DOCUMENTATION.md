# Magento ElasticSuite Documentation

# Install

## Requirements

* ElasticSearch 2.1 or higher
* Magento 2.0.2 or higher

## Install the ElasticSuite through composer :

```bash
composer install smile-sa/elasticsuite-full
```

**Note :**
The command above will install the full ElasticSuite distribution.

All components are available individually and you can compose your own distribution :

Component                 | Package name                          |Description
--------------------------|---------------------------------------|------------
**ElasticSuite Core**     | `smile-sa/module-elasticsuite-code`   | Provides all ElasticSuite core features (ElasticSearch client, base configuration, ...). Required by most module into the suite.
**ElastiscSuite Catalog** | `smile-sa/module-elasticsuite-catalog`| Provides ElasticSuite catalog search and navigation base features.

## Enable the module  and run ElasticSuite setup:

```bash
bin/magento module:enable Smile_ElasticSuiteCore Smile_ElasticSuiteCatalog
bin/magento setup:upgrade
```

## Configure ElasticSearch server :

TODO (screenshot available into doc/static).

## Configure ElasticSuite as Magento Search Engine :

TODO (screenshot available into doc/static).

# User guide

### Search engine & navigation

TODO : intro.

#### Fulltext search relevance

TODO : describe releavance configuration screen and params.
TODO : attribute relevance.

#### Faceting configuration

TODO : products facet attributes configuration 
