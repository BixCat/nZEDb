<?php

class SphinxSearch
{
	/**
	 * SphinxQL connection.
	 * @var nzedb\db\DB
	 */
	public $sphinxQL = null;

	/**
	 * Establish connection to SphinxQL.
	 */
	public function __construct()
	{
		if (nZEDb_RELEASE_SEARCH_TYPE == ReleaseSearch::SPHINX) {
			if (!defined('nZEDb_SPHINXQL_HOST_NAME')) {
				define('nZEDb_SPHINXQL_HOST_NAME', '0');
			}
			if (!defined('nZEDb_SPHINXQL_PORT')) {
				define('nZEDb_SPHINXQL_PORT', 9306);
			}
			if (!defined('nZEDb_SPHINXQL_SOCK_FILE')) {
				define('nZEDb_SPHINXQL_SOCK_FILE', '');
			}
			$this->sphinxQL = new nzedb\db\DB(
				[
					'dbname' => '',
					'dbport' => nZEDb_SPHINXQL_PORT,
					'dbhost' => nZEDb_SPHINXQL_HOST_NAME,
					'dbsock' => nZEDb_SPHINXQL_SOCK_FILE
				]
			);
		}
	}

	/**
	 * Insert release into Sphinx RT table.
	 * @param $parameters
	 */
	public function insertRelease($parameters)
	{
		if (!is_null($this->sphinxQL) && $parameters['id']) {
			$this->sphinxQL->queryExec(
				sprintf(
					'REPLACE INTO releases_rt (id, guid, name, searchname, fromname) VALUES (%s, %s, %s, %s, %s)',
					$parameters['id'],
					$parameters['guid'],
					$parameters['name'],
					$parameters['searchname'],
					$parameters['fromname']
				)
			);
		}
	}

	/**
	 * Delete release from Sphinx RT table.
	 * @param string $GUID release GUID
	 * @param nzedb\db\Settings $pdo
	 */
	public function deleteRelease($GUID, nzedb\db\Settings $pdo)
	{
		if (!is_null($this->sphinxQL)) {
			$releaseID = $pdo->queryOneRow(sprintf('SELECT id FROM releases WHERE guid = %s', $pdo->escapeString($GUID)));
			if ($releaseID !== false) {
				$this->sphinxQL->queryExec(sprintf('DELETE FROM releases_rt WHERE id = %s', $releaseID['id']));
			}
		}
	}

	/**
	 * Truncate a RT index.
	 * @param string $indexName
	 */
	public function truncateRTIndex($indexName)
	{
		if (!is_null($this->sphinxQL)) {
			$this->sphinxQL->queryExec(sprintf('TRUNCATE RTINDEX %s', $indexName));
		}
	}
}