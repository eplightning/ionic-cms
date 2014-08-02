<?php namespace Laravel\Session\Drivers;

use Laravel\Config;
use Laravel\Database\Connection;

class Database extends Driver implements Sweeper {

	/**
	 * The database connection.
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Create a new database session driver.
	 *
	 * @param  Connection  $connection
	 * @return void
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Load a session from storage by a given ID.
	 *
	 * If no session is found for the ID, null will be returned.
	 *
	 * @param  string  $id
	 * @return array
	 */
	public function load($id)
	{
		$session = $this->table()->find($id);

		if ( ! is_null($session))
		{
			return array(
				'id'            => $session->id,
				'last_activity' => $session->last_activity,
				'data'          => unserialize($session->data),
				'location_name' => $session->location_name,
				'location_url'  => $session->location_url,
				'type'          => (int) $session->type,
				'user_id'       => (int) $session->user_id
			);
		}
	}

	/**
	 * Save a given session to storage.
	 *
	 * @param  array  $session
	 * @param  array  $config
	 * @param  bool   $exists
	 * @return void
	 */
	public function save($session, $config, $exists)
	{
		if ($exists)
		{
			$this->table()->where('id', '=', $session['id'])->update(array(
				'last_activity' => $session['last_activity'],
				'data'          => serialize($session['data']),
				'location_name' => isset($session['location_name']) ? $session['location_name'] : '',
				'location_url'  => isset($session['location_url']) ? $session['location_url'] : '',
				'type'          => isset($session['type']) ? $session['type'] : 1,
				'user_id'       => isset($session['user_id']) ? $session['user_id'] : 0
			));
		}
		else
		{
			$this->table()->insert(array(
				'id'            => $session['id'],
				'last_activity' => $session['last_activity'],
				'data'          => serialize($session['data']),
				'location_name' => isset($session['location_name']) ? $session['location_name'] : '',
				'location_url'  => isset($session['location_url']) ? $session['location_url'] : '',
				'type'          => isset($session['type']) ? $session['type'] : 1,
				'user_id'       => isset($session['user_id']) ? $session['user_id'] : 0
			));
		}
	}

	/**
	 * Delete a session from storage by a given ID.
	 *
	 * @param  string  $id
	 * @return void
	 */
	public function delete($id)
	{
		$this->table()->delete($id);
	}

	/**
	 * Delete all expired sessions from persistent storage.
	 *
	 * @param  int   $expiration
	 * @return void
	 */
	public function sweep($expiration)
	{
		$this->table()->where('last_activity', '<', $expiration)->delete();
	}

	/**
	 * Get a session database query.
	 *
	 * @return Query
	 */
	private function table()
	{
		return $this->connection->table(Config::get('session.table'));
	}

}