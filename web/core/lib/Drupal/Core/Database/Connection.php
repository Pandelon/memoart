<?php

namespace Drupal\Core\Database;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Database\Event\DatabaseEvent;
use Drupal\Core\Database\Exception\EventException;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Truncate;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\Transaction\TransactionManagerInterface;
use Drupal\Core\Pager\PagerManagerInterface;

/**
 * Base Database API class.
 *
 * This class provides a Drupal extension for a client database connection.
 * Every database driver implementation must provide a concrete implementation
 * of it to support special handling required by that database.
 * The most common database abstraction layer in PHP is PDO.
 *
 * @see http://php.net/manual/book.pdo.php
 */
abstract class Connection {

  /**
   * The database target this connection is for.
   *
   * We need this information for later auditing and logging.
   *
   * @var string|null
   */
  protected $target = NULL;

  /**
   * The key representing this connection.
   *
   * The key is a unique string which identifies a database connection. A
   * connection can be a single server or a cluster of primary and replicas
   * (use target to pick between primary and replica).
   *
   * @var string|null
   */
  protected $key = NULL;

  /**
   * The current database logging object for this connection.
   *
   * @var \Drupal\Core\Database\Log|null
   */
  protected $logger = NULL;

  /**
   * Index of what driver-specific class to use for various operations.
   *
   * @var array
   */
  protected $driverClasses = [];

  /**
   * The name of the StatementWrapper class for this connection.
   *
   * @var string|null
   */
  protected $statementWrapperClass = NULL;

  /**
   * Whether this database connection supports transactional DDL.
   *
   * Set to FALSE by default because few databases support this feature.
   *
   * @var bool
   */
  protected $transactionalDDLSupport = FALSE;

  /**
   * The actual client connection.
   *
   * @var object
   */
  protected $connection;

  /**
   * The connection information for this connection object.
   *
   * @var array
   */
  protected $connectionOptions = [];

  /**
   * The schema object for this connection.
   *
   * Set to NULL when the schema is destroyed.
   *
   * @var \Drupal\Core\Database\Schema|null
   */
  protected $schema = NULL;

  /**
   * The prefix used by this database connection.
   *
   * @var string
   */
  protected string $prefix;

  /**
   * Replacements to fully qualify {table} placeholders in SQL strings.
   *
   * An array of two strings, the first being the replacement for opening curly
   * brace '{', the second for closing curly brace '}'.
   *
   * @var string[]
   */
  protected array $tablePlaceholderReplacements;

  /**
   * List of escaped table names, keyed by unescaped names.
   *
   * @var array
   */
  protected $escapedTables = [];

  /**
   * List of escaped field names, keyed by unescaped names.
   *
   * There are cases in which escapeField() is called on an empty string. In
   * this case it should always return an empty string.
   *
   * @var array
   */
  protected $escapedFields = ["" => ""];

  /**
   * List of escaped aliases names, keyed by unescaped aliases.
   *
   * @var array
   */
  protected $escapedAliases = [];

  /**
   * The identifier quote characters for the database type.
   *
   * An array containing the start and end identifier quote characters for the
   * database type. The ANSI SQL standard identifier quote character is a double
   * quotation mark.
   *
   * @var string[]
   */
  protected $identifierQuotes;

  /**
   * Tracks the database API events to be dispatched.
   *
   * For performance reasons, database API events are not yielded by default.
   * Call ::enableEvents() to enable them.
   */
  private array $enabledEvents = [];

  /**
   * The transaction manager.
   */
  protected TransactionManagerInterface $transactionManager;

  /**
   * Constructs a Connection object.
   *
   * @param object $connection
   *   An object of the client class representing a database connection.
   * @param array $connection_options
   *   An array of options for the connection. May include the following:
   *   - prefix
   *   - namespace
   *   - Other driver-specific options.
   */
  public function __construct(object $connection, array $connection_options) {
    assert(count($this->identifierQuotes) === 2 && Inspector::assertAllStrings($this->identifierQuotes), '\Drupal\Core\Database\Connection::$identifierQuotes must contain 2 string values');

    // Manage the table prefix.
    $connection_options['prefix'] = $connection_options['prefix'] ?? '';
    $this->setPrefix($connection_options['prefix']);

    // Work out the database driver namespace if none is provided. This normally
    // written to setting.php by installer or set by
    // \Drupal\Core\Database\Database::parseConnectionInfo().
    if (empty($connection_options['namespace'])) {
      $connection_options['namespace'] = (new \ReflectionObject($this))->getNamespaceName();
    }

    $this->connection = $connection;
    $this->connectionOptions = $connection_options;
  }

  /**
   * Opens a client connection.
   *
   * @param array $connection_options
   *   The database connection settings array.
   *
   * @return object
   *   A client connection object.
   */
  abstract public static function open(array &$connection_options = []);

  /**
   * Ensures that the client connection can be garbage collected.
   */
  public function __destruct() {
    // Ensure that the circular reference caused by Connection::__construct()
    // using $this in the call to set the statement class can be garbage
    // collected.
    $this->connection = NULL;
  }

  /**
   * Commits all the open transactions.
   *
   * @internal
   *   This method exists only to work around a bug caused by Drupal incorrectly
   *   relying on object destruction order to commit transactions. Xdebug 3.3.0
   *   changes the order of object destruction when the develop mode is enabled.
   */
  public function commitAll() {
    $manager = $this->transactionManager();
    if ($manager->inTransaction() && method_exists($manager, 'commitAll')) {
      $this->transactionManager()->commitAll();
    }
  }

  /**
   * Returns the client-level database connection object.
   *
   * This method should normally be used only within database driver code. Not
   * doing so constitutes a risk of introducing code that is not database
   * independent.
   *
   * @return object
   *   The client-level database connection, for example \PDO.
   */
  public function getClientConnection(): object {
    return $this->connection;
  }

  /**
   * Returns the default query options for any given query.
   *
   * A given query can be customized with a number of option flags in an
   * associative array:
   * - fetch: This element controls how rows from a result set will be
   *   returned. Legal values include one of the enumeration cases of FetchAs or
   *   a string representing the name of a class. If a string is specified, each
   *   record will be fetched into a new object of that class. The behavior of
   *   all other values is described in the FetchAs enum.
   * - allow_delimiter_in_query: By default, queries which have the ; delimiter
   *   any place in them will cause an exception. This reduces the chance of SQL
   *   injection attacks that terminate the original query and add one or more
   *   additional queries (such as inserting new user accounts). In rare cases,
   *   such as creating an SQL function, a ; is needed and can be allowed by
   *   changing this option to TRUE.
   * - allow_square_brackets: By default, queries which contain square brackets
   *   will have them replaced with the identifier quote character for the
   *   database type. In rare cases, such as creating an SQL function, []
   *   characters might be needed and can be allowed by changing this option to
   *   TRUE.
   * - pdo: By default, queries will execute with the client connection options
   *   set on the connection. In particular cases, it could be necessary to
   *   override the driver options on the statement level. In such case, pass
   *   the required setting as an array here, and they will be passed to the
   *   prepared statement.
   *
   * @return array
   *   An array of default query options.
   */
  protected function defaultOptions() {
    return [
      'fetch' => FetchAs::Object,
      'allow_delimiter_in_query' => FALSE,
      'allow_square_brackets' => FALSE,
      'pdo' => [],
    ];
  }

  /**
   * Returns the connection information for this connection object.
   *
   * Note that Database::getConnectionInfo() is for requesting information
   * about an arbitrary database connection that is defined. This method
   * is for requesting the connection information of this specific
   * open connection object.
   *
   * @return array
   *   An array of the connection information. The exact list of
   *   properties is driver-dependent.
   */
  public function getConnectionOptions() {
    return $this->connectionOptions;
  }

  /**
   * Allows the connection to access additional databases.
   *
   * Database systems usually group tables in 'databases' or 'schemas', that
   * can be accessed with syntax like 'SELECT * FROM database.table'. Normally
   * Drupal accesses tables in a single database/schema, but in some cases it
   * may be necessary to access tables from other databases/schemas in the same
   * database server. This method can be called to ensure that the additional
   * database/schema is accessible.
   *
   * For MySQL, PostgreSQL and most other databases no action need to be taken
   * to query data in another database or schema. For SQLite this is however
   * necessary and the database driver for SQLite will override this method.
   *
   * @param string $database
   *   The database to be attached to the connection.
   *
   * @internal
   */
  public function attachDatabase(string $database): void {
  }

  /**
   * Returns the prefix of the tables.
   *
   * @return string
   *   The table prefix.
   */
  public function getPrefix(): string {
    return $this->prefix;
  }

  /**
   * Set the prefix used by this database connection.
   *
   * @param string $prefix
   *   A single prefix.
   */
  protected function setPrefix($prefix) {
    assert(is_string($prefix), 'The \'$prefix\' argument to ' . __METHOD__ . '() must be a string');
    $this->prefix = $prefix;
    $this->tablePlaceholderReplacements = [
      $this->identifierQuotes[0] . str_replace('.', $this->identifierQuotes[1] . '.' . $this->identifierQuotes[0], $prefix),
      $this->identifierQuotes[1],
    ];
  }

  /**
   * Appends a database prefix to all tables in a query.
   *
   * Queries sent to Drupal should wrap all table names in curly brackets. This
   * function searches for this syntax and adds Drupal's table prefix to all
   * tables, allowing Drupal to coexist with other systems in the same database
   * and/or schema if necessary.
   *
   * @param string $sql
   *   A string containing a partial or entire SQL query.
   *
   * @return string
   *   The properly-prefixed string.
   */
  public function prefixTables($sql) {
    return str_replace(['{', '}'], $this->tablePlaceholderReplacements, $sql);
  }

  /**
   * Quotes all identifiers in a query.
   *
   * Queries sent to Drupal should wrap all unquoted identifiers in square
   * brackets. This function searches for this syntax and replaces them with the
   * database specific identifier. In ANSI SQL this a double quote.
   *
   * Note that :variable[] is used to denote array arguments but
   * Connection::expandArguments() is always called first.
   *
   * @param string $sql
   *   A string containing a partial or entire SQL query.
   *
   * @return string
   *   The string containing a partial or entire SQL query with all identifiers
   *   quoted.
   *
   * @internal
   *   This method should only be called by database API code.
   */
  public function quoteIdentifiers($sql) {
    return str_replace(['[', ']'], $this->identifierQuotes, $sql);
  }

  /**
   * Get a fully qualified table name.
   *
   * @param string $table
   *   The name of the table in question.
   *
   * @return string
   *   The fully qualified table name.
   */
  public function getFullQualifiedTableName($table) {
    $options = $this->getConnectionOptions();
    $prefix = $this->getPrefix();
    return $options['database'] . '.' . $prefix . $table;
  }

  /**
   * Returns a prepared statement given a SQL string.
   *
   * This method caches prepared statements, reusing them when possible. It also
   * prefixes tables names enclosed in curly braces and, optionally, quotes
   * identifiers enclosed in square brackets.
   *
   * @param string $query
   *   The query string as SQL, with curly braces surrounding the table names,
   *   and square brackets surrounding identifiers.
   * @param array $options
   *   An associative array of options to control how the query is run. See
   *   the documentation for self::defaultOptions() for details. The content of
   *   the 'pdo' key will be passed to the prepared statement.
   * @param bool $allow_row_count
   *   (optional) A flag indicating if row count is allowed on the statement
   *   object. Defaults to FALSE.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   A prepared statement ready for its execute() method.
   *
   * @throws \InvalidArgumentException
   *   If multiple statements are included in the string, and delimiters are
   *   not allowed in the query.
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   */
  public function prepareStatement(string $query, array $options, bool $allow_row_count = FALSE): StatementInterface {
    assert(!isset($options['return']), 'Passing "return" option to prepareStatement() has no effect. See https://www.drupal.org/node/3185520');
    if (isset($options['fetch']) && is_int($options['fetch'])) {
      @trigger_error("Passing the 'fetch' key as an integer to \$options in prepareStatement() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
    }

    try {
      $query = $this->preprocessStatement($query, $options);
      $statement = new $this->statementWrapperClass($this, $this->connection, $query, $options['pdo'] ?? [], $allow_row_count);
    }
    catch (\Exception $e) {
      $this->exceptionHandler()->handleStatementException($e, $query, $options);
    }

    return $statement;
  }

  /**
   * Returns a string SQL statement ready for preparation.
   *
   * This method replaces table names in curly braces and identifiers in square
   * brackets with platform specific replacements, appropriately escaping them
   * and wrapping them with platform quote characters.
   *
   * @param string $query
   *   The query string as SQL, with curly braces surrounding the table names,
   *   and square brackets surrounding identifiers.
   * @param array $options
   *   An associative array of options to control how the query is run. See
   *   the documentation for self::defaultOptions() for details.
   *
   * @return string
   *   A string SQL statement ready for preparation.
   *
   * @throws \InvalidArgumentException
   *   If multiple statements are included in the string, and delimiters are
   *   not allowed in the query.
   */
  protected function preprocessStatement(string $query, array $options): string {
    // To protect against SQL injection, Drupal only supports executing one
    // statement at a time.  Thus, the presence of a SQL delimiter (the
    // semicolon) is not allowed unless the option is set.  Allowing semicolons
    // should only be needed for special cases like defining a function or
    // stored procedure in SQL. Trim any trailing delimiter to minimize false
    // positives unless delimiter is allowed.
    $trim_chars = " \xA0\t\n\r\0\x0B";
    if (empty($options['allow_delimiter_in_query'])) {
      $trim_chars .= ';';
    }
    $query = rtrim($query, $trim_chars);
    if (str_contains($query, ';') && empty($options['allow_delimiter_in_query'])) {
      throw new \InvalidArgumentException('; is not supported in SQL strings. Use only one statement at a time.');
    }

    // Resolve {tables} and [identifiers] to the platform specific syntax.
    $query = $this->prefixTables($query);
    if (!($options['allow_square_brackets'] ?? FALSE)) {
      $query = $this->quoteIdentifiers($query);
    }

    return $query;
  }

  /**
   * Tells this connection object what its target value is.
   *
   * This is needed for logging and auditing. It's sloppy to do in the
   * constructor because the constructor for child classes has a different
   * signature. We therefore also ensure that this function is only ever
   * called once.
   *
   * @param string $target
   *   (optional) The target this connection is for.
   */
  public function setTarget($target = NULL) {
    if (!isset($this->target)) {
      $this->target = $target;
    }
  }

  /**
   * Returns the target this connection is associated with.
   *
   * @return string|null
   *   The target string of this connection, or NULL if no target is set.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Tells this connection object what its key is.
   *
   * @param string $key
   *   The key this connection is for.
   */
  public function setKey($key) {
    if (!isset($this->key)) {
      $this->key = $key;
    }
  }

  /**
   * Returns the key this connection is associated with.
   *
   * @return string|null
   *   The key of this connection, or NULL if no key is set.
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Associates a logging object with this connection.
   *
   * @param \Drupal\Core\Database\Log $logger
   *   The logging object we want to use.
   */
  public function setLogger(Log $logger) {
    $this->logger = $logger;
  }

  /**
   * Gets the current logging object for this connection.
   *
   * @return \Drupal\Core\Database\Log|null
   *   The current logging object for this connection. If there isn't one,
   *   NULL is returned.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Flatten an array of query comments into a single comment string.
   *
   * The comment string will be sanitized to avoid SQL injection attacks.
   *
   * @param string[] $comments
   *   An array of query comment strings.
   *
   * @return string
   *   A sanitized comment string.
   */
  public function makeComment($comments) {
    if (empty($comments)) {
      return '';
    }

    // Flatten the array of comments.
    $comment = implode('. ', $comments);

    // Sanitize the comment string so as to avoid SQL injection attacks.
    return '/* ' . $this->filterComment($comment) . ' */ ';
  }

  /**
   * Sanitize a query comment string.
   *
   * Ensure a query comment does not include strings such as "* /" that might
   * terminate the comment early. This avoids SQL injection attacks via the
   * query comment. The comment strings in this example are separated by a
   * space to avoid PHP parse errors.
   *
   * For example, the comment:
   * @code
   * \Drupal::database()->update('example')
   *  ->condition('id', $id)
   *  ->fields(['field2' => 10])
   *  ->comment('Exploit * / DROP TABLE node; --')
   *  ->execute()
   * @endcode
   *
   * Would result in the following SQL statement being generated:
   * @code
   * "/ * Exploit * / DROP TABLE node. -- * / UPDATE example SET field2=..."
   * @endcode
   *
   * Unless the comment is sanitized first, the SQL server would drop the
   * node table and ignore the rest of the SQL statement.
   *
   * @param string $comment
   *   A query comment string.
   *
   * @return string
   *   A sanitized version of the query comment string.
   */
  protected function filterComment($comment = '') {
    // Change semicolons to period to avoid triggering multi-statement check.
    return strtr($comment, ['*' => ' * ', ';' => '.']);
  }

  /**
   * Executes a query string against the database.
   *
   * This method provides a central handler for the actual execution of every
   * query. All queries executed by Drupal are executed as prepared statements.
   *
   * @param string $query
   *   The query to execute. This is a string containing an SQL query with
   *   placeholders.
   * @param array $args
   *   The associative array of arguments for the prepared statement.
   * @param array $options
   *   An associative array of options to control how the query is run. The
   *   given options will be merged with self::defaultOptions(). See the
   *   documentation for self::defaultOptions() for details.
   *   Typically, $options['return'] will be set by a default or by a query
   *   builder, and should not be set by a user.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   The executed statement.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   * @throws \InvalidArgumentException
   *
   * @see \Drupal\Core\Database\Connection::defaultOptions()
   */
  public function query($query, array $args = [], $options = []) {
    assert(is_string($query), 'The \'$query\' argument to ' . __METHOD__ . '() must be a string');
    assert(!isset($options['return']), 'Passing "return" option to query() has no effect. See https://www.drupal.org/node/3185520');
    assert(!isset($options['target']), 'Passing "target" option to query() has no effect. See https://www.drupal.org/node/2993033');
    if (isset($options['fetch']) && is_int($options['fetch'])) {
      @trigger_error("Passing the 'fetch' key as an integer to \$options in query() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
    }

    // Use default values if not already set.
    $options += $this->defaultOptions();

    $this->expandArguments($query, $args);
    $statement = $this->prepareStatement($query, $options);
    try {
      $result = $statement->execute($args, $options);
    }
    catch (\Exception $e) {
      $this->exceptionHandler()->handleExecutionException($e, $statement, $args, $options);
      $result = FALSE;
    }
    return $result ? $statement : NULL;
  }

  /**
   * Expands out shorthand placeholders.
   *
   * Drupal supports an alternate syntax for doing arrays of values. We
   * therefore need to expand them out into a full, executable query string.
   *
   * @param string $query
   *   The query string to modify.
   * @param array $args
   *   The arguments for the query.
   *
   * @return bool
   *   TRUE if the query was modified, FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   This exception is thrown when:
   *   - A placeholder that ends in [] is supplied, and the supplied value is
   *     not an array.
   *   - A placeholder that does not end in [] is supplied, and the supplied
   *     value is an array.
   */
  protected function expandArguments(&$query, &$args) {
    $modified = FALSE;

    // If the placeholder indicated the value to use is an array,  we need to
    // expand it out into a comma-delimited set of placeholders.
    foreach ($args as $key => $data) {
      $is_bracket_placeholder = str_ends_with($key, '[]');
      $is_array_data = is_array($data);
      if ($is_bracket_placeholder && !$is_array_data) {
        throw new \InvalidArgumentException('Placeholders with a trailing [] can only be expanded with an array of values.');
      }
      elseif (!$is_bracket_placeholder) {
        if ($is_array_data) {
          throw new \InvalidArgumentException('Placeholders must have a trailing [] if they are to be expanded with an array of values.');
        }
        // Scalar placeholder - does not need to be expanded.
        continue;
      }
      // Handle expansion of arrays.
      $key_name = str_replace('[]', '__', $key);
      $new_keys = [];
      // We require placeholders to have trailing brackets if the developer
      // intends them to be expanded to an array to make the intent explicit.
      foreach (array_values($data) as $i => $value) {
        // This assumes that there are no other placeholders that use the same
        // name.  For example, if the array placeholder is defined as :example[]
        // and there is already an :example_2 placeholder, this will generate
        // a duplicate key.  We do not account for that as the calling code
        // is already broken if that happens.
        $new_keys[$key_name . $i] = $value;
      }

      // Update the query with the new placeholders.
      $query = str_replace($key, implode(', ', array_keys($new_keys)), $query);

      // Update the args array with the new placeholders.
      unset($args[$key]);
      $args += $new_keys;

      $modified = TRUE;
    }

    return $modified;
  }

  /**
   * Gets the driver-specific override class if any for the specified class.
   *
   * @param string $class
   *   The class for which we want the potentially driver-specific class.
   *
   * @return string
   *   The name of the class that should be used for this driver.
   */
  public function getDriverClass($class) {
    match($class) {
      'Install\\Tasks',
      'ExceptionHandler',
      'Select',
      'Insert',
      'Merge',
      'Upsert',
      'Update',
      'Delete',
      'Truncate',
      'Schema',
      'Condition',
      'Transaction' => throw new InvalidQueryException('Calling ' . __METHOD__ . '() for \'' . $class . '\' is not supported. Use standard autoloading in the methods that return database operations. See https://www.drupal.org/node/3217534'),
      default => NULL,
    };
    if (empty($this->driverClasses[$class])) {
      $driver_class = $this->connectionOptions['namespace'] . '\\' . $class;
      $this->driverClasses[$class] = class_exists($driver_class) ? $driver_class : $class;
    }
    return $this->driverClasses[$class];
  }

  /**
   * Returns the database exceptions handler.
   *
   * @return \Drupal\Core\Database\ExceptionHandler
   *   The database exceptions handler.
   */
  public function exceptionHandler() {
    return new ExceptionHandler();
  }

  /**
   * Prepares and returns a SELECT query object.
   *
   * @param string|\Drupal\Core\Database\Query\SelectInterface $table
   *   The base table name or subquery for this query, used in the FROM clause.
   *   If a string, the table specified will also be used as the "base" table
   *   for query_alter hook implementations.
   * @param string $alias
   *   (optional) The alias of the base table of this query.
   * @param array $options
   *   An array of options on the query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   An appropriate SelectQuery object for this database connection. Note that
   *   it may be a driver-specific subclass of SelectQuery, depending on the
   *   driver.
   *
   * @see \Drupal\Core\Database\Query\Select
   */
  public function select($table, $alias = NULL, array $options = []) {
    assert(is_string($alias) || $alias === NULL, 'The \'$alias\' argument to ' . __METHOD__ . '() must be a string or NULL');
    return new Select($this, $table, $alias, $options);
  }

  /**
   * Prepares and returns an INSERT query object.
   *
   * @param string $table
   *   The table to use for the insert statement.
   * @param array $options
   *   (optional) An associative array of options to control how the query is
   *   run. The given options will be merged with
   *   \Drupal\Core\Database\Connection::defaultOptions().
   *
   * @return \Drupal\Core\Database\Query\Insert
   *   A new Insert query object.
   *
   * @see \Drupal\Core\Database\Query\Insert
   * @see \Drupal\Core\Database\Connection::defaultOptions()
   */
  public function insert($table, array $options = []) {
    return new Insert($this, $table, $options);
  }

  /**
   * Returns the ID of the last inserted row or sequence value.
   *
   * This method should normally be used only within database driver code.
   *
   * This is a proxy to invoke lastInsertId() from the wrapped connection.
   * If a sequence name is not specified for the name parameter, this returns a
   * string representing the row ID of the last row that was inserted into the
   * database.
   * If a sequence name is specified for the name parameter, this returns a
   * string representing the last value retrieved from the specified sequence
   * object.
   *
   * @param string|null $name
   *   (Optional) Name of the sequence object from which the ID should be
   *   returned.
   *
   * @return string
   *   The value returned by the wrapped connection.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   In case of failure.
   */
  public function lastInsertId(?string $name = NULL): string {
    if (($last_insert_id = $this->connection->lastInsertId($name)) === FALSE) {
      throw new DatabaseExceptionWrapper("Could not determine last insert id" . $name === NULL ? '' : " for sequence $name");
    }
    return $last_insert_id;
  }

  /**
   * Prepares and returns a MERGE query object.
   *
   * @param string $table
   *   The table to use for the merge statement.
   * @param array $options
   *   (optional) An array of options on the query.
   *
   * @return \Drupal\Core\Database\Query\Merge
   *   A new Merge query object.
   *
   * @see \Drupal\Core\Database\Query\Merge
   */
  public function merge($table, array $options = []) {
    return new Merge($this, $table, $options);
  }

  /**
   * Prepares and returns an UPSERT query object.
   *
   * @param string $table
   *   The table to use for the upsert query.
   * @param array $options
   *   (optional) An array of options on the query.
   *
   * @return \Drupal\Core\Database\Query\Upsert
   *   A new Upsert query object.
   *
   * @see \Drupal\Core\Database\Query\Upsert
   */
  abstract public function upsert($table, array $options = []);

  /**
   * Prepares and returns an UPDATE query object.
   *
   * @param string $table
   *   The table to use for the update statement.
   * @param array $options
   *   (optional) An associative array of options to control how the query is
   *   run. The given options will be merged with
   *   \Drupal\Core\Database\Connection::defaultOptions().
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update query object.
   *
   * @see \Drupal\Core\Database\Query\Update
   * @see \Drupal\Core\Database\Connection::defaultOptions()
   */
  public function update($table, array $options = []) {
    return new Update($this, $table, $options);
  }

  /**
   * Prepares and returns a DELETE query object.
   *
   * @param string $table
   *   The table to use for the delete statement.
   * @param array $options
   *   (optional) An associative array of options to control how the query is
   *   run. The given options will be merged with
   *   \Drupal\Core\Database\Connection::defaultOptions().
   *
   * @return \Drupal\Core\Database\Query\Delete
   *   A new Delete query object.
   *
   * @see \Drupal\Core\Database\Query\Delete
   * @see \Drupal\Core\Database\Connection::defaultOptions()
   */
  public function delete($table, array $options = []) {
    return new Delete($this, $table, $options);
  }

  /**
   * Prepares and returns a TRUNCATE query object.
   *
   * @param string $table
   *   The table to use for the truncate statement.
   * @param array $options
   *   (optional) An array of options on the query.
   *
   * @return \Drupal\Core\Database\Query\Truncate
   *   A new Truncate query object.
   *
   * @see \Drupal\Core\Database\Query\Truncate
   */
  public function truncate($table, array $options = []) {
    return new Truncate($this, $table, $options);
  }

  /**
   * Returns a DatabaseSchema object for manipulating the schema.
   *
   * This method will lazy-load the appropriate schema library file.
   *
   * @return \Drupal\Core\Database\Schema
   *   The database Schema object for this connection.
   */
  abstract public function schema();

  /**
   * Prepares and returns a CONDITION query object.
   *
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   *
   * @return \Drupal\Core\Database\Query\Condition
   *   A new Condition query object.
   *
   * @see \Drupal\Core\Database\Query\Condition
   */
  public function condition($conjunction) {
    // Creating an instance of the class Drupal\Core\Database\Query\Condition
    // should only be created from the database layer. This will allow database
    // drivers to override the default Condition class.
    return new Condition($conjunction);
  }

  /**
   * Escapes a database name string.
   *
   * Force all database names to be strictly alphanumeric-plus-underscore.
   * For some database drivers, it may also wrap the database name in
   * database-specific escape characters.
   *
   * @param string $database
   *   An unsanitized database name.
   *
   * @return string
   *   The sanitized database name.
   */
  public function escapeDatabase($database) {
    $database = preg_replace('/[^A-Za-z0-9_]+/', '', $database);
    [$start_quote, $end_quote] = $this->identifierQuotes;
    return $start_quote . $database . $end_quote;
  }

  /**
   * Escapes a table name string.
   *
   * Force all table names to be strictly alphanumeric-plus-underscore.
   * Database drivers should never wrap the table name in database-specific
   * escape characters. This is done in Connection::prefixTables(). The
   * database-specific escape characters are added in Connection::setPrefix().
   *
   * @param string $table
   *   An unsanitized table name.
   *
   * @return string
   *   The sanitized table name.
   *
   * @see \Drupal\Core\Database\Connection::prefixTables()
   * @see \Drupal\Core\Database\Connection::setPrefix()
   */
  public function escapeTable($table) {
    if (!isset($this->escapedTables[$table])) {
      $this->escapedTables[$table] = preg_replace('/[^A-Za-z0-9_.]+/', '', $table);
    }
    return $this->escapedTables[$table];
  }

  /**
   * Escapes a field name string.
   *
   * Force all field names to be strictly alphanumeric-plus-underscore.
   * For some database drivers, it may also wrap the field name in
   * database-specific escape characters.
   *
   * @param string $field
   *   An unsanitized field name.
   *
   * @return string
   *   The sanitized field name.
   */
  public function escapeField($field) {
    if (!isset($this->escapedFields[$field])) {
      $escaped = preg_replace('/[^A-Za-z0-9_.]+/', '', $field);
      [$start_quote, $end_quote] = $this->identifierQuotes;
      // Sometimes fields have the format table_alias.field. In such cases
      // both identifiers should be quoted, for example, "table_alias"."field".
      $this->escapedFields[$field] = $start_quote . str_replace('.', $end_quote . '.' . $start_quote, $escaped) . $end_quote;
    }
    return $this->escapedFields[$field];
  }

  /**
   * Escapes an alias name string.
   *
   * Force all alias names to be strictly alphanumeric-plus-underscore. In
   * contrast to DatabaseConnection::escapeField() /
   * DatabaseConnection::escapeTable(), this doesn't allow the period (".")
   * because that is not allowed in aliases.
   *
   * @param string $field
   *   An unsanitized alias name.
   *
   * @return string
   *   The sanitized alias name.
   */
  public function escapeAlias($field) {
    if (!isset($this->escapedAliases[$field])) {
      [$start_quote, $end_quote] = $this->identifierQuotes;
      $this->escapedAliases[$field] = $start_quote . preg_replace('/[^A-Za-z0-9_]+/', '', $field) . $end_quote;
    }
    return $this->escapedAliases[$field];
  }

  /**
   * Escapes characters that work as wildcard characters in a LIKE pattern.
   *
   * The wildcard characters "%" and "_" as well as backslash are prefixed with
   * a backslash. Use this to do a search for a verbatim string without any
   * wildcard behavior.
   *
   * For example, the following does a case-insensitive query for all rows whose
   * name starts with $prefix:
   * @code
   * $result = $injected_connection->query(
   *   'SELECT * FROM person WHERE name LIKE :pattern',
   *   [':pattern' => $injected_connection->escapeLike($prefix) . '%']
   * );
   * @endcode
   *
   * Backslash is defined as escape character for LIKE patterns in
   * Drupal\Core\Database\Query\Condition::mapConditionOperator().
   *
   * @param string $string
   *   The string to escape.
   *
   * @return string
   *   The escaped string.
   */
  public function escapeLike($string) {
    return addcslashes($string, '\%_');
  }

  /**
   * Returns the transaction manager.
   *
   * @return \Drupal\Core\Database\Transaction\TransactionManagerInterface
   *   The transaction manager, or FALSE if not available.
   *
   * @throws \LogicException
   *   If the transaction manager is undefined or unavailable.
   */
  public function transactionManager(): TransactionManagerInterface {
    if (!isset($this->transactionManager)) {
      $this->transactionManager = $this->driverTransactionManager();
    }
    return $this->transactionManager;
  }

  /**
   * Returns a new instance of the driver's transaction manager.
   *
   * Database drivers must implement their own class extending from
   * \Drupal\Core\Database\Transaction\TransactionManagerBase, and instantiate
   * it here.
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.InvalidNoReturn
   * @return \Drupal\Core\Database\Transaction\TransactionManagerInterface
   *   The transaction manager.
   *
   * @throws \LogicException
   *   If the transaction manager is undefined or unavailable.
   */
  protected function driverTransactionManager(): TransactionManagerInterface {
    throw new \LogicException('The database driver has no TransactionManager implementation');
  }

  /**
   * Determines if there is an active transaction open.
   *
   * @return bool
   *   TRUE if we're currently in a transaction, FALSE otherwise.
   */
  public function inTransaction() {
    return $this->transactionManager()->inTransaction();
  }

  /**
   * Returns a new DatabaseTransaction object on this connection.
   *
   * @param string $name
   *   (optional) The name of the savepoint.
   *
   * @return \Drupal\Core\Database\Transaction
   *   A Transaction object.
   *
   * @see \Drupal\Core\Database\Transaction
   */
  public function startTransaction($name = '') {
    return $this->transactionManager()->push($name);
  }

  /**
   * Runs a limited-range query on this database object.
   *
   * Use this as a substitute for ->query() when a subset of the query is to be
   * returned. User-supplied arguments to the query should be passed in as
   * separate parameters so that they can be properly escaped to avoid SQL
   * injection attacks.
   *
   * @param string $query
   *   A string containing an SQL query.
   * @param int $from
   *   The first result row to return.
   * @param int $count
   *   The maximum number of result rows to return.
   * @param array $args
   *   (optional) An array of values to substitute into the query at placeholder
   *    markers.
   * @param array $options
   *   (optional) An array of options on the query.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   A database query result resource, or NULL if the query was not executed
   *   correctly.
   */
  abstract public function queryRange($query, $from, $count, array $args = [], array $options = []);

  /**
   * Returns the type of database driver.
   *
   * This is not necessarily the same as the type of the database itself. For
   * instance, there could be two MySQL drivers, mysql and mysqlMock. This
   * function would return different values for each, but both would return
   * "mysql" for databaseType().
   *
   * @return string
   *   The type of database driver.
   */
  abstract public function driver();

  /**
   * Returns the version of the database server.
   *
   * Assumes the client connection is \PDO. Non-PDO based drivers need to
   * override this method.
   *
   * @return string
   *   The version of the database server.
   */
  public function version() {
    return $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
  }

  /**
   * Returns the version of the database client.
   *
   * Assumes the client connection is \PDO. Non-PDO based drivers need to
   * override this method.
   *
   * @return string
   *   The version of the database client.
   */
  public function clientVersion() {
    return $this->connection->getAttribute(\PDO::ATTR_CLIENT_VERSION);
  }

  /**
   * Determines if this driver supports transactional DDL.
   *
   * DDL queries are those that change the schema, such as ALTER queries.
   *
   * @return bool
   *   TRUE if this connection supports transactions for DDL queries, FALSE
   *   otherwise.
   */
  public function supportsTransactionalDDL() {
    return $this->transactionalDDLSupport;
  }

  /**
   * Returns the name of the database engine accessed by this driver.
   *
   * @return string
   *   The database engine name.
   */
  abstract public function databaseType();

  /**
   * Creates a database.
   *
   * In order to use this method, you must be connected without a database
   * specified.
   *
   * @param string $database
   *   The name of the database to create.
   */
  abstract public function createDatabase($database);

  /**
   * Gets any special processing requirements for the condition operator.
   *
   * Some condition types require special processing, such as IN, because
   * the value data they pass in is not a simple value. This is a simple
   * overridable lookup function. Database connections should define only
   * those operators they wish to be handled differently than the default.
   *
   * @param string $operator
   *   The condition operator, such as "IN", "BETWEEN", etc. Case-sensitive.
   *
   * @return array|null
   *   The extra handling directives for the specified operator, or NULL.
   *
   * @see \Drupal\Core\Database\Query\Condition::compile()
   */
  abstract public function mapConditionOperator($operator);

  /**
   * Quotes a string for use in a query.
   *
   * @param string $string
   *   The string to be quoted.
   * @param int $parameter_type
   *   (optional) Provides a data type hint for drivers that have alternate
   *   quoting styles. Defaults to \PDO::PARAM_STR.
   *
   * @return string|bool
   *   A quoted string that is theoretically safe to pass into an SQL statement.
   *   Returns FALSE if the driver does not support quoting in this way.
   *
   * @see \PDO::quote()
   */
  public function quote($string, $parameter_type = \PDO::PARAM_STR) {
    return $this->connection->quote($string, $parameter_type);
  }

  /**
   * Extracts the SQLSTATE error from a PDOException.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @return string
   *   The five character error code.
   */
  protected static function getSQLState(\Exception $e) {
    // The PDOException code is not always reliable, try to see whether the
    // message has something usable.
    if (preg_match('/^SQLSTATE\[(\w{5})\]/', $e->getMessage(), $matches)) {
      return $matches[1];
    }
    else {
      return $e->getCode();
    }
  }

  /**
   * Prevents the database connection from being serialized.
   */
  public function __sleep(): array {
    throw new \LogicException('The database connection is not serializable. This probably means you are serializing an object that has an indirect reference to the database connection. Adjust your code so that is not necessary. Alternatively, look at DependencySerializationTrait as a temporary solution.');
  }

  /**
   * Creates an array of database connection options from a URL.
   *
   * @param string $url
   *   The URL.
   * @param string $root
   *   (deprecated) The root directory of the Drupal installation. Some
   *   database drivers, like for example SQLite, need this information.
   *
   * @return array
   *   The connection options.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the provided URL does not meet the minimum
   *   requirements.
   *
   * @internal
   *   This method should only be called from
   *   \Drupal\Core\Database\Database::convertDbUrlToConnectionInfo().
   *
   * @see \Drupal\Core\Database\Database::convertDbUrlToConnectionInfo()
   */
  public static function createConnectionOptionsFromUrl($url, $root) {
    if ($root !== NULL) {
      @trigger_error("Passing the \$root value to " . __METHOD__ . "() is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3511287", E_USER_DEPRECATED);
    }

    $url_components = parse_url($url);
    if (!isset($url_components['scheme'], $url_components['host'], $url_components['path'])) {
      throw new \InvalidArgumentException("The database connection URL '$url' is invalid. The minimum requirement is: 'driver://host/database'");
    }

    $url_components += [
      'user' => '',
      'pass' => '',
      'fragment' => '',
    ];

    // Remove leading slash from the URL path.
    if ($url_components['path'][0] === '/') {
      $url_components['path'] = substr($url_components['path'], 1);
    }

    // Use reflection to get the namespace of the class being called.
    $reflector = new \ReflectionClass(static::class);

    $database = [
      'driver' => $url_components['scheme'],
      'username' => $url_components['user'],
      'password' => $url_components['pass'],
      'host' => $url_components['host'],
      'database' => $url_components['path'],
      'namespace' => $reflector->getNamespaceName(),
    ];

    if (isset($url_components['port'])) {
      $database['port'] = $url_components['port'];
    }

    if (!empty($url_components['fragment'])) {
      $database['prefix'] = $url_components['fragment'];
    }

    return $database;
  }

  /**
   * Creates a URL from an array of database connection options.
   *
   * @param array $connection_options
   *   The array of connection options for a database connection. An additional
   *   key of 'module' is added by Database::getConnectionInfoAsUrl() for
   *   drivers provided my contributed or custom modules for convenience.
   *
   * @return string
   *   The connection info as a URL.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the provided array of connection options does not
   *   meet the minimum requirements.
   *
   * @internal
   *   This method should only be called from
   *   \Drupal\Core\Database\Database::getConnectionInfoAsUrl().
   *
   * @see \Drupal\Core\Database\Database::getConnectionInfoAsUrl()
   */
  public static function createUrlFromConnectionOptions(array $connection_options) {
    if (!isset($connection_options['driver'], $connection_options['database'])) {
      throw new \InvalidArgumentException("As a minimum, the connection options array must contain at least the 'driver' and 'database' keys");
    }

    $user = '';
    if (isset($connection_options['username'])) {
      $user = $connection_options['username'];
      if (isset($connection_options['password'])) {
        $user .= ':' . $connection_options['password'];
      }
      $user .= '@';
    }

    $host = empty($connection_options['host']) ? 'localhost' : $connection_options['host'];

    $db_url = $connection_options['driver'] . '://' . $user . $host;

    if (isset($connection_options['port'])) {
      $db_url .= ':' . $connection_options['port'];
    }

    $db_url .= '/' . $connection_options['database'];

    // Add the module when the driver is provided by a module.
    if (isset($connection_options['module'])) {
      $db_url .= '?module=' . $connection_options['module'];
    }

    if (isset($connection_options['prefix']) && $connection_options['prefix'] !== '') {
      $db_url .= '#' . $connection_options['prefix'];
    }

    return $db_url;
  }

  /**
   * Get the module name of the module that is providing the database driver.
   *
   * @return string
   *   The module name of the module that is providing the database driver, or
   *   "core" when the driver is not provided as part of a module.
   */
  public function getProvider(): string {
    [$first, $second] = explode('\\', $this->connectionOptions['namespace'], 3);

    // The namespace for Drupal modules is Drupal\MODULE_NAME, and the module
    // name must be all lowercase. Second-level namespaces containing uppercase
    // letters (e.g., "Core", "Component", "Driver") are not modules.
    // @see \Drupal\Core\DrupalKernel::getModuleNamespacesPsr4()
    // @see https://www.drupal.org/docs/8/creating-custom-modules/naming-and-placing-your-drupal-8-module#s-name-your-module
    return ($first === 'Drupal' && strtolower($second) === $second) ? $second : 'core';
  }

  /**
   * Get the pager manager service, if available.
   *
   * @return \Drupal\Core\Pager\PagerManagerInterface
   *   The pager manager service, if available.
   *
   * @throws \Drupal\Core\DependencyInjection\ContainerNotInitializedException
   *   If the container has not been initialized yet.
   */
  public function getPagerManager(): PagerManagerInterface {
    return \Drupal::service('pager.manager');
  }

  /**
   * Runs a simple query to validate json datatype support.
   *
   * @return bool
   *   Returns the query result.
   */
  public function hasJson(): bool {
    try {
      return (bool) $this->query('SELECT JSON_TYPE(\'1\')');
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Returns the status of a database API event toggle.
   *
   * @param string $eventName
   *   The name of the event to check.
   *
   * @return bool
   *   TRUE if the event is going to be fired by the database API, FALSE
   *   otherwise.
   */
  public function isEventEnabled(string $eventName): bool {
    return $this->enabledEvents[$eventName] ?? FALSE;
  }

  /**
   * Enables database API events dispatching.
   *
   * @param string[] $eventNames
   *   A list of database events to be enabled.
   *
   * @return static
   */
  public function enableEvents(array $eventNames): static {
    foreach ($eventNames as $eventName) {
      assert(class_exists($eventName), "Event class {$eventName} does not exist");
      $this->enabledEvents[$eventName] = TRUE;
    }
    return $this;
  }

  /**
   * Disables database API events dispatching.
   *
   * @param string[] $eventNames
   *   A list of database events to be disabled.
   *
   * @return static
   */
  public function disableEvents(array $eventNames): static {
    foreach ($eventNames as $eventName) {
      assert(class_exists($eventName), "Event class {$eventName} does not exist");
      $this->enabledEvents[$eventName] = FALSE;
    }
    return $this;
  }

  /**
   * Dispatches a database API event via the container dispatcher.
   *
   * @param \Drupal\Core\Database\Event\DatabaseEvent $event
   *   The database event.
   * @param string|null $eventName
   *   (Optional) the name of the event to dispatch.
   *
   * @return \Drupal\Core\Database\Event\DatabaseEvent
   *   The database event.
   *
   * @throws \Drupal\Core\Database\Exception\EventException
   *   If the container is not initialized.
   */
  public function dispatchEvent(DatabaseEvent $event, ?string $eventName = NULL): DatabaseEvent {
    if (\Drupal::hasService('event_dispatcher')) {
      return \Drupal::service('event_dispatcher')->dispatch($event, $eventName);
    }
    throw new EventException('The event dispatcher service is not available. Database API events can only be fired if the container is initialized');
  }

  /**
   * Determine the last non-database method that called the database API.
   *
   * Traversing the call stack from the very first call made during the
   * request, we define "the routine that called this query" as the last entry
   * in the call stack that is not any method called from the namespace of the
   * database driver, is not inside the Drupal\Core\Database namespace and does
   * have a file (which excludes call_user_func_array(), anonymous functions
   * and similar). That makes the climbing logic very simple, and handles the
   * variable stack depth caused by the query builders.
   *
   * See the @link http://php.net/debug_backtrace debug_backtrace() @endlink
   * function.
   *
   * @return array
   *   This method returns a stack trace entry similar to that generated by
   *   debug_backtrace(). However, it flattens the trace entry and the trace
   *   entry before it so that we get the function and args of the function that
   *   called into the database system, not the function and args of the
   *   database call itself.
   */
  public function findCallerFromDebugBacktrace(): array {
    $stack = $this->removeDatabaseEntriesFromDebugBacktrace($this->getDebugBacktrace(), $this->getConnectionOptions()['namespace']);
    // Return the first function call whose stack entry has a 'file' key, that
    // is, it is not a callback or a closure.
    for ($i = 0; $i < count($stack); $i++) {
      if (!empty($stack[$i]['file'])) {
        return [
          'file' => $stack[$i]['file'],
          'line' => $stack[$i]['line'],
          'function' => $stack[$i + 1]['function'],
          'class' => $stack[$i + 1]['class'] ?? NULL,
          'type' => $stack[$i + 1]['type'] ?? NULL,
          'args' => $stack[$i + 1]['args'] ?? [],
        ];
      }
    }

    return [];
  }

  /**
   * Removes database related calls from a backtrace array.
   *
   * @param array $backtrace
   *   A standard PHP backtrace. Passed by reference.
   * @param string $driver_namespace
   *   The PHP namespace of the database driver.
   *
   * @return array
   *   The cleaned backtrace array.
   */
  public static function removeDatabaseEntriesFromDebugBacktrace(array $backtrace, string $driver_namespace): array {
    // Starting from the very first entry processed during the request, find
    // the first function call that can be identified as a call to a
    // method/function in the database layer.
    for ($n = count($backtrace) - 1; $n >= 0; $n--) {
      // If the call was made from a function, 'class' will be empty. We give
      // it a default empty string value in that case.
      $class = $backtrace[$n]['class'] ?? '';
      if (str_starts_with($class, __NAMESPACE__) || str_starts_with($class, $driver_namespace)) {
        break;
      }
    }

    return array_values(array_slice($backtrace, $n));
  }

  /**
   * Gets the debug backtrace.
   *
   * Wraps the debug_backtrace function to allow mocking results in PHPUnit
   * tests.
   *
   * @return array[]
   *   The debug backtrace.
   */
  protected function getDebugBacktrace(): array {
    // @todo Allow a backtrace including all arguments as an option.
    //   https://www.drupal.org/project/drupal/issues/3401906
    return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
  }

}
