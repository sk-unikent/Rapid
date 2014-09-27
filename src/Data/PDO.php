<?php
/**
 * Rapid Prototyping Framework in PHP.
 * 
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

namespace Rapid\Data;

class PDO extends \PDO
{
	/**
	 * Table prefix.
	 */
	private $prefix;

	/**
	 * Constructor
	 */
	public function __construct($engine, $hostname, $port, $dbname, $username, $password, $prefix) {
		parent::__construct("$engine:host=$hostname;port=$port;dbname=$dbname", $username, $password);
		$this->prefix = $prefix;
	}

	/**
	 * Get table name.
	 */
	private function get_table($name) {
		return $this->prefix . $name;
	}

	/**
	 * Replace curley brace table names with real table names.
	 */
	private function substitute_tables($sql) {
		if (preg_match_all('/\{(.*?)\}/', $sql, $matches) > 0) {
			foreach ($matches[1] as $match) {
				$table = $this->get_table($match);
				$sql = str_replace("{{$match}}", $table, $sql);
			}
		}

		return $sql;
	}

	/**
	 * Get records from DB.
	 */
	public function get_records_sql($sql, $params = array()) {
		$sql = $this->substitute_tables($sql);
		$stmt = $this->prepare($sql);

		foreach ($params as $k => $v) {
			$stmt->bindValue(":$k", $v);
		}

		$stmt->execute();

		$results = array();
		while (($obj = $stmt->fetchObject()) !== false) {
            if (isset($obj->id)) {
    			$results[$obj->id] = $obj;
            } else {
                $results[] = $obj;
            }
		}

		$stmt->closeCursor();

		return $results;
	}

	/**
	 * Get records from DB.
	 */
	public function get_records($table, $params = array(), $fields = '*') {
        if (is_array($fields)) {
            $fields = implode(', ', $fields);
        }

		$sql = "SELECT * FROM {{$table}}";

		if (!empty($params)) {
			$sql .= ' WHERE';

			$joins = array();
			foreach ($params as $k => $v) {
				$joins[] = "`{$k}`= :{$k}";
			}

			$sql .= ' ' . implode(' AND ', $joins);
		}

		return $this->get_records_sql($sql, $params);
	}

	/**
	 * Get a record from DB.
	 */
	public function get_record($table, $params = array()) {
		$results = $this->get_records($table, $params);
		$count = count($results);

		if ($count > 1) {
			throw new \Exception('get_record() yielded multiple results!');
		}

		if ($count === 0) {
			return false;
		}

		return array_pop($results);
	}

	/**
	 * Get records from DB and convert them to models.
	 */
	public function get_models($model, $params = array()) {
		$obj = new $model();
		$table = $obj->get_table();

		$data = $this->get_records($table, $params);

		$results = array();
		foreach ($data as $datum) {
			$obj = new $model();
			$obj->bulk_set_data($datum, true);
			$results[] = $obj;
		}

		return $results;
	}

	/**
	 * Get a record from DB and convert it to a model.
	 */
	public function get_model($model, $params = array()) {
		$results = $this->get_models($model, $params);
		$count = count($results);

		if ($count > 1) {
			throw new \Exception('get_model() yielded multiple results!');
		}

		if ($count === 0) {
			return false;
		}

		return array_pop($results);
	}

    /**
     * Delete a record.
     */
    public function delete_record($table, $values) {
        $table = $this->get_table($table);

        $sql = array();
        foreach ($values as $k => $v) {
            $sql[] = "`{$k}` = :{$k}";
        }
        $sql = join(' AND ', $sql);

        $stmt = $this->prepare("DELETE FROM {$table} WHERE {$sql}");
        foreach ($values as $k => $v) {
            $stmt->bindValue(":{$k}", $v);
        }

        if ($stmt->execute() === false) {
            print_r($this->errorInfo());
            return false;
        }

        return true;
    }

    /**
     * Returns an array containing values of $field
     */
    public function get_fieldset($table, $field, $params = array()) {
        $records = $this->get_records($table, $params, $field);

        $results = array_map(function($obj) use($field) {
            return $obj->$field;
        }, $records);

        return $results;
    }
}
