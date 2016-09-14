# JSONDB
Manage local databases with JSON files and JSONDB Query Language (JQL)

[![Build Status](https://travis-ci.org/na2axl/jsondb-php.svg?branch=master)](https://travis-ci.org/na2axl/jsondb-php)
[![Packagist Version](https://img.shields.io/badge/packagist-v1.2.0-brightgreen.svg)](https://packagist.org/packages/na2axl/jsondb)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/na2axl/jsondb-php/blob/master/LICENSE)


## What's that ?
JSONDB is a database manager using JSON files and a custom query
language named **JQL** (**J**SONDB **Q**uery **L**anguage).

## Features
* Database management with servers, databases and tables
* Secure connections to servers with username and password
* Easy custom query language
* Object oriented, with a PDO-like syntax
* Supported JQL queries:
    * select()
    * insert()
    * replace()
    * delete()
    * update()
    * truncate()
    * count()

## Getting Started
_Full API and documentation will be soon available on the JSONDB website..._

### Install using composer
JSONDB can be installed through composer:
```sh
$ composer require na2axl/jsondb
```

### Instantiate JSONDB
All JSONDB classes are in the namespace JSONDB. So, you have to
instantiate the class with:
```php
try {
    $jsondb = new \JSONDB\JSONDB();
}
catch (\JSONDB\Exception $e) {
    echo $e->getMessage();
}
```

### Create a server
If you don't have created a server yet, then:
```php
if (!file_exists($server_path)) {
    $jsondb->createServer($server_path, $username, $password);
}
```
It's useful to check if the destination folder doesn't exist before create a server
to avoid errors.

### Connect to a server
Once instantiated, you have to connect to a server before send queries.
```php
$db = $jsondb->connect($server_path, $username, $password, $database_name);
```
* The `$server_path` is the path to the folder which represents a server
(a folder which contains databases)
* The `$username` and the `$password` are the information used to connect
 to the database. These information are the same used when creating the server
* The `$database_name` is the name of the database to use with current connection.
This parameter is optional and can be set manually later.

### Create a database
After connection to a server, you can create a database:
```php
$db->createDatabase($database_name);
```

### Use a database
The database to use can be set using the `JSONDB::connect()` method, or manually
using `JSONDB::setDatabase()` method after a connection to a database:
```php
$db->setDatabase($database_name);
```

### Create a table
Once JSONDB is properly connected to a server and use a database, you can create
a table in this database:
```php
$db->createTable($table_name, $prototype);
```
The `$prototype` is an array of `$column_name` => `$column_propeties` pairs.

#### Column properties
There is a list of currently supported column properties:
* `type`: Defines the type of values that the column accepts. Supported types are:
    * `int`, `integer`, `number`
    * `decimal`, `float`
    * `string`
    * `char`
    * `bool`, `boolean`
    * `array`
* `default`: Sets the default value of column
* `max_length`: Used by some type:
    * When used with `float`, the number of decimals is reduced to his value
    * When used with `string`, the number of characters is reduced to his value
    (starting with the first character)
* `auto_increment`: Defines if a column will be an auto incremented column. When
used, the column is automatically set to UNIQUE KEY
* `primary_key`: Defines if a column is a PRIMARY KEY
* `unique_key`: Defines if a column is an UNIQUE KEY

### Send a query
JSONDB can send both direct and prepared queries.

#### Direct queries
```php
$results = $db->query($my_query_string);

//// Specially for select() queries
// You can change the fecth mode
$results->setFetchMode(\JSONDB\JSONDB::FETCH_ARRAY);
// or...
$results->setFetchMode(\JSONDB\JSONDB::FETCH_OBJECT);
// Explore results using a while loop
while ($result = $results->fetch()) {
    // Do stuff...
}
// Explore results using a foreach loop
forach ($results as $result) {
    // Do stuff...
}
```

#### Prepared queries
```php
$query = $db->prepare($my_prepared_query);
$query->bindValue(':key1', $val, \JSONDB\JSONDB::PARAM_INT);
$query->bindValue(':key2', $val, \JSONDB\JSONDB::PARAM_STRING);
$query->bindValue(':key3', $val, \JSONDB\JSONDB::PARAM_BOOL);
$query->bindValue(':key4', $val, \JSONDB\JSONDB::PARAM_NULL);
$results = $query->execute();

//// Specially for select() queries
// You can change the fecth mode
$results->setFetchMode(\JSONDB\JSONDB::FETCH_ARRAY);
// or...
$results->setFetchMode(\JSONDB\JSONDB::FETCH_OBJECT);
// Explore results using a while loop
while ($result = $results->fetch()) {
    // Do stuff...
}
// Explore results using a foreach loop
forach ($results as $result) {
    // Do stuff...
}
```

### JQL (JSONDB Query Language)
The JQL is the query language used in JSONDB. It's a very easy language based on _extensions_.
A JQL query is in this form:
```php
$db->query('table_name.query(parameters,...).extension1().extension2()...');
```

#### Query Examples

##### select()
Select all from table `users` where `pseudo` = `$id` and `password` = `$pass` or where `mail` = `$id` and `password` = `$pass`
```php
$id = \JSONDB\JSONDB::quote($form_id);
$pass = \JSONDB\JSONDB::quote($form_password);
$db->query("users.select(*).where(pseudo={$id},password={$pass}).where(mail={$id},password={$pass})");
```

Select `pseudo` and `mail` from table `users` where `activated` = `true`, order the results by `pseudo` with `desc`endant method, limit the results to the `10` users after the `5`th.
```php
$db->query("users.select(pseudo,mail).where(activated=true).order(pseudo,desc).limit(5,10)");
```

##### insert()
Insert a new user in table `users`
```php
$pseudo = \JSONDB\JSONDB::quote($form_pseudo);
$pass = \JSONDB\JSONDB::quote($form_password);
$mail = \JSONDB\JSONDB::quote($form_mail);
$db->query("users.insert({$pseudo},{$pass},{$mail}).in(pseudo,password,mail)");
```
Multiple insertion...
```php
$db->query("users.insert({$pseudo1},{$pass1},{$mail1}).and({$pseudo2},{$pass2},{$mail2}).and({$pseudo3},{$pass3},{$mail3}).in(pseudo,password,mail)");
```

##### replace()
Replace information of the first user
```php
$db->query("users.replace({$pseudo},{$pass},{$mail}).in(pseudo,password,mail)");
```
Multiple replacement...
```php
$db->query("users.replace({$pseudo1},{$pass1},{$mail1}).and({$pseudo2},{$pass2},{$mail2}).and({$pseudo3},{$pass3},{$mail3}).in(pseudo,password,mail)");
```

##### delete()
Delete all users
```php
$db->query("users.delete()");
```
Delete all banished users
```php
$db->query("users.delete().where(banished = true)");
```
Delete a specific user
```php
$db->query("users.delete().where(pseudo = {$pseudo}, mail = {$mail})");
```

##### update()
Activate all users
```php
$db->query("users.update(activated).with(true)");
```
Update my information ;-)
```php
$db->query("users.update(mail, password, activated, banished).with({$mail}, {$pseudo}, true, false).where(pseudo = 'na2axl')");
```

##### truncate()
Reset the table `users`
```php
$db->query("users.truncate()");
```

##### count()
Count all banished users
```php
$db->query("users.count(*).as(banished_nb).where(banished = true)");
```
Count all users and group by `activated`
```php
$db->query("users.count(*).as(users_nb).group(activated)");
```

## Full example
```php
try {
    $jsondb = new \JSONDB\JSONDB();

    if (!file_exists('./test')) {
        $jsondb->createServer('./test', 'root', '');
    }

    $db = $jsondb->connect('./test', 'root', '')
                 ->createDatabase('test_database')
                 ->setDatabase('test_database'); // Yes, is chainable ! ;-)

    $db->createTable('users', array('id' => array('type' => 'int', 'auto_increment' => TRUE),
                     'name' => array('type' => 'string', 'max_length' => 30, 'not_null' => TRUE),
                     'surname' => array('type' => 'string', 'max_length' => 30, 'not_null' => TRUE),
                     'pseudo' => array('type' => 'string', 'max_length' => 15, 'unique_key' => TRUE),
                     'mail' => array('type' => 'string', 'unique_key' => TRUE),
                     'password' => array('type' => 'string', 'not_null' => TRUE),
                     'website' => array('type' => 'string'),
                     'activated' => array('type' => 'bool', 'default' => false),
                     'banished' => array('type' => 'bool', 'default' => false)));
    
    // A prepared query
    $query = $db->prepare('users.insert(:name, :sname, :pseudo, :mail, :pass).in(name, surname, pseudo, mail, password)');
    $query->bindValue(':name', 'Nana', \JSONDB\JSONDB::PARAM_STRING);
    $query->bindValue(':sname', 'Axel', \JSONDB\JSONDB::PARAM_STRING);
    $query->bindValue(':pseudo', 'na2axl', \JSONDB\JSONDB::PARAM_STRING);
    $query->bindValue(':mail', 'ax.lnana@outlook.com', \JSONDB\JSONDB::PARAM_STRING);
    $query->bindValue(':pass', $password, \JSONDB\JSONDB::PARAM_STRING);
    $query->execute();

    // After some insertions...

    // Select all users
    $results = $db->query('users.select(id, name, surname, pseudo)');
    // Fetch as object
    while ($result = $results->fetch(\JSONDB\JSONDB::FETCH_OBJECT)) {
        echo "The user with id: {$result->id} has the name: {$result->name} {$result->surname} and the pseudo: {$result->pseudo}.\n";
    }
}
catch (\JSONDB\Exception $e) {
    echo $e->getMessage();
}
```

## Authors
* **Axel Nana**: <ax.lnana@outlook.com> - [https://tutorialcenters.tk](https://tutorialcenters.tk)

## Copyright
(c) 2016 Centers Technologies. Licensed under MIT ([read license](https://github.com/na2axl/jsondb-php/blob/master/LICENSE)).
