# GCFramework for PHP
## Introduction
This a minimal framework that uses other frameworks like Laminas, twig and many others to solve some business problems in PHP.
Define Database layer like DataMappers that map to database table and database abstraction layer developed before PDO appears into market.

_This library are part of a business application and is in development stage for separate to independent library._

## Capabilities

* Application configuration layer
* Database support:
   * Abstration BD driver layer, for:
       * Firebird
       * Oracle
       * SQLAnywere
       * PostgreSQL
   * Entity Data model abstraction layer: DataMapper, Record and ResultSet.
     
* Data Store model abstraction layer using K-V stores (REDIS, MEMCACHED).
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

## Using library

This library is composer package and its available on packagist.org package repository. Can use from your current application or can create web application using this framework.

```
composer require phiconsultors/gcf
```

### Create GCF application

You need initialize composer project application and then execute composer require:

```
composer init
composer require phiconsultors/gcf
```

Now you need create basic application structure like this:

```
Myapp
│   composer.json
│   init.php
├───cfg
│   └───properties_dev.ini
├───app
├───data
│   └───models
└───frontal
    │   index.php
    │
    ├───cfg
    ├───modules
    ├───static
    │   ├───css
    │   ├───img
    │   └───jscript
    └───templates
            index.twig
```

On root that contains init.php to initialize application environment. In this example, only had simple index.php that initialize configuration envinronment and render simple TWIG template.

```php
<?php

/**
 * Application initialization
 */

use gcf\Environment;

include __DIR__."/vendor/autoload.php";

try {
        $env = Environment::getInstance("MyAppName");
} catch (Exception $ex) {
        die($ex->getMessage());
}
```

This file is included on main php (p.e: index.php) program that initialize GCF context.

And composer.json that contains project dependencies and general configuration. This file autogenerated with composer init

```json
{
    "name": "tomeu/myapp",
    "description": "Example application",
    "type": "project",
    "license": "GPL",
    "authors": [
        {
            "name": "Tomeu Capó",
            "email": "tomeucapo@gmail.com"
        }
    ],
    "minimum-stability": "stable",
    "require": {}
}
```

If you need application skeleton you can clone [application example](https://github.com/tomeucapo/gcf-myapp).

### Basic usage

You can use some components/classes of this library without initialize Environment and Configuration. But if you need create application with database access and cache need create environment and configuration.
The environment read configuration ini file that contains all application properties and initializes all database pool connections and cache connections.

- Environment

  Singleton class that create application basic context, parse application properties file according environment deploy name and initialize database pool connection properties (connectionDb) (user, passwd, ...).

- ConfiguratorBase

  This is abstract class you need create own Configurator class extends of it on the app directory of your application, that add methods as your app need.
  
### Database drivers

Depends on your application properties GCF choose database backend driver to work with it. 

You can use SQL sentences using:

- **SQLQuery** class that can execute any SQL sentence to your database.

Or create own models on database using Model abstraction data layer:

- **DataMapper** class maps database table to PHP class that do operations over DB table.
- **Record** class that stores single table record into it.
- **ResultSet** class. Iterator class type that you iterate using foreach and fetch record by record on each step of foreach.

#### Queries

To execute directly any query can call SQLQuery class and work with results, like this:

```php
$myQuery = new SQLQuery($db);
$myQuery->fer_consulta("select ID, USER, NAME from user where id=3");

while(!$consulta->Eof())
{
    $user = $consulta->row['USER'];
    $name = $consulta->row['NAME'];
    
    $consulta->Skip();
}

$consulta->tanca_consulta();
```
this is a traditional fetch record until end of table using primitives.

> ***db*** is a **DatabaseConnector** class type, can get from your Configurator class that extends from **ConfiguratorBase** with Configurator::getInstance()->db

Other way is using ResultSet, simplify the code:

```php
$myQuery = new SQLQuery($db);
$myQuery->PrepareQuery("select ID, USER, NAME from user where id=3");

foreach (new ResultSet($myQuery, "ID") as $record)
{
    // Can access each field with, for example: $record->USER or $record->NAME and so on.
}
```

> Normally its recommended create own ResultSet and own Record to define accurately fields of each record and proper type of record that returns current record of ResultSet.

#### Models

Normally if you need work with your tables I recommend to create one model for each table. Each model extends directly to DataMapper, or class extends DataMapper.
When you create your application with main database connection recommends create main base class called (for example: MyAppDatabaseModel) extends from DataMapper like this:

```php
class MyAppDataMapper extends DataMapper
{
    /**
     * @var DatabaseConnector
     */
    protected DatabaseConnector $dbContext;

    /**
     * @throws errorDatabaseConnection
     * @throws errorDriverDB
     * @throws errorDatabaseAutentication
     */
    public static function GetDBContext() : DatabaseConnector
    {
        $dbPool = connectionPool::getInstance();
        return $dbPool->maindbname->getConnection();
    }

    /**
     * Data\models\taulaBDPersonal constructor.
     * @param string $tableName
     * @param mixed $pk
     * @param mixed $pkType
     * @throws errorDatabaseAutentication
     * @throws errorDatabaseConnection
     * @throws errorDriverDB
     */
    public function __construct(string $tableName, $pk, $pkType)
    {
        $this->dbContext = self::GetDBContext();
        parent::__construct($this->dbContext, $tableName, $pk, $pkType);
    }
```

And if you need create model:

```php
class User extends MyAppDataMapper
{
    public function __construct($tableName="USER", $pk="ID", $pkType="int") 
    {
        parent::__construct($tableName, $pk, $pkType);
    }
}
```

> ***pk*** define a single field primary key and ***pyType*** the type (int or string can be supported). If you have composite key you need pass to ***pk*** and ***pkType*** an array of names of fields and types of each field on both arguments.

Pay attention because DataMapper use magic methods to access each field of table. Therefor you need to put comment before class declaration that contains list of properties to help to IDE detection

```php
/**
 * Class User
 * @property int ID
 * @property string USER
 * @property string NAME
 */
class User extends MyAppDataMapper
{
    public function __construct($tableName="USER", $pk="ID", $pkType="int") 
    {
        parent::__construct($tableName, $pk, $pkType);
    }
}
```

To use this model from your any part of your code can do this to create new record on USER table:

```php
$user = new User();
$user->ID = 3;
$user->USER = "tomeu";
$user->NAME = "Tomeu Capó";
$user->Nou();
```

or can delete record with PK with this:

```php
$user = new User();
$user->Borra(3);
```

or modify vrecord using PK with this:

```php
$user = new User();
$user->NAME="Pep";
$user->Modifica(3);
```

### Modules

The GCF is model-view-controller framework and can help to create any API Controllers, Web Controllers, Views (Templates) and Models as described before.
Modules are define controller part. The class **modulBase** and **controllerBase** defines this functionality and helps to create controllers as you need.
To use modules you need instantiate Router to manage requests to modules.

#### controllerBase

This class defines controller base class. Its basic class that able to create new controllers for application. This clsas not support views only for basic controller like API controllers. Provides database connection context if is needed, logging context, application configuration context and basic filter input class that content incoming data from client.

#### modulBase

Extends from **controllerBase** and includes templates (view) functionality. Normally when you create view that returns dynamic HTML parsed from database data use modulBase.

* modulBaseCRUD Defines all methods on specific database model (table). 
* modulConfig Can load json configuration file for your controller.
* modulBaseDefaultMethods Create dummy methods on controller that needed by controller interface. Only for some special cases.
