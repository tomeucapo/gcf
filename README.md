# GCFramework for PHP
## Introduction
This a minimal framework that uses other frameworks like Laminas, twig and many others to solve some business problems in PHP.
Define Database layer like DataMappers that map to database table and database abstraction layer developed before PDO appears into market.

_This library are part of a business application and is in development stage for separate to independent library._

## Capabilities

* Application configuration layer
* Database abstraction layer, for:
    * Firebird
    * Oracle
    * SQLAnywere
    * PostgreSQL
* Data model abstraction layer
* Data export capabilities, can define DataReport to different output formats: XSL, JSON or PDF.
* Task abstraction layer to execute tasks to job server, actual version only supports Gearman.
* Cache integration to: REDIS, Memcached or File based.
* Web layer utilities:
    * Controllers classes
    * Template engine: Support multi template libraries, now supports twig and patTemplate.
    * Controllers for API REST implementations

Additionally incorporate drivers to connect to biometric terminal systems for ImesD Electrónica, supported models are:
* CP-5000 
* CP-6000

