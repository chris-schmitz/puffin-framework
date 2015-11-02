<?php
namespace puffin\model;

class mongo
{
	public $db = false;
	public $connection = 'default';
	public $collection = false;
	public $params = [];

	protected $_db;
	protected $_connection;
	protected $_collection;

	public function __construct()
	{
		$this->_connection = $this->set_connection();
		$this->_db = $this->set_db();
		$this->_collection = $this->set_collection();
	}

	protected function connection()
	{
		return $this->connection;
	}

	protected function set_connection()
	{
		return \puffin\dsn::get( $this->connection );
	}

	protected function collection()
	{
		return $this->_collection;
	}

	protected function set_collection()
	{
		return $this->_collection = $this->db->selectCollection( $this->collection_name );
	}

	protected function db()
	{
		return $this->_db;
	}

	protected function set_db()
	{
		return $this->connection()->selectDB( $this->db );
	}

	#======================================================================
	# CRUD
	#======================================================================

	public function create( $data )
	{
		$record = $this->fill( $data );

		if( is_array($record) )
		{
			$this->collection()->insert($record);
		}

		return $record;
	}

	public function read( $id = false )
	{
		if( $id )
		{
			return $this->collection()->findOne( [ '_id' => new MongoId($id) ]);
		}
		else
		{
			$records = $this->collection()->find();
			return iterator_to_array($records, false);
		}
	}

	public function update( $id, $data )
	{
		$record = $this->read( $id );

		if( $this->validate() ) //wut
		{
			foreach( $data as $key => $value )
			{
				$record[ $key ] = $value;
			}

			$record['updated_at'] = $this->timestamp();
			return $this->collection()->save($record);
		}

		return false;
	}

	public function delete( $id )
	{
		return $this->update($id, ['is_archived' => true]);
	}

	#======================================================================
	# /CRUD
	#======================================================================

	public function fill( $input )
	{
		$fill_array = [];

		if( $this->validate($input) )
		{
			foreach( $this->params as $key => $value )
			{
				if( is_null( $input[$key] ) )
				{
					$fill_array[ $key ] = $value[ 'default' ];
				}
				else
				{
					$fill_array[ $key ] = $input[ $key ];
				}
			}

			return $this->add_data($fill_array);

		}

		return false;
	}

	public function timestamp()
	{
		$dt = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('UTC'));
		$ts = $dt->getTimestamp();
		return new MongoDate($ts);
	}

	protected function validate()
	{
		// Do Validation
		return true;
	}

	private function add_data( $array )
	{
		$array['created_at'] = $array['updated_at'] = $this->timestamp();
		$array['is_archived'] = false;
		return $array;
	}


}