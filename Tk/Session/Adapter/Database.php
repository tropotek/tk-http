<?php
namespace Tk\Session\Adapter;

/**
 * A PDO DB session object
 *
 * <code>
 *    CREATE TABLE session (
 *       session_id VARCHAR(127) NOT NULL PRIMARY KEY,
 *       data TEXT NOT NULL,
 *       modified TIMESTAMP NOT NULL,
 *       created TIMESTAMP NOT NULL
 *   );
 *  </code>
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Database implements Iface
{
    static $DB_TABLE = 'sys_session';

    /**
     * @var \Tk\Db\Pdo
     */
    protected $db = null;

    /**
     * @var bool|mixed
     */
    protected $encrypt = false;

    /**
     * @var string
     */
    private $sessionId = null;
    

    /**
     * Create a Database session adaptor.
     *
     * @param \Tk\Db\Pdo $db
     * @param \Tk\Encrypt $encrypt
     */
    public function __construct(\Tk\Db\Pdo $db, $encrypt = null)
    {
        $this->encrypt = $encrypt;
        $this->setDb($db);
    }

    /**
     * This sql should be DB generic (tested on: mysql, pgsql)
     *
     * @throws \Tk\Db\Exception
     */
    private function install()
    {
        if ($this->getDb()->tableExists($this->getTable())) return;
        $tbl = $this->getDb()->quoteParameter($this->getTable());
        $sql = <<<SQL
CREATE TABLE $tbl (
  session_id VARCHAR(127) NOT NULL PRIMARY KEY,
  data TEXT NOT NULL,
  modified TIMESTAMP NOT NULL,
  created TIMESTAMP NOT NULL
);
SQL;
        $this->getDb()->exec($sql);
    }

    protected function encode($str)
    {
        if ($this->encrypt) {
            $str = $this->encrypt->encode($str);
        } else {
            $str = base64_encode($str);
        }
        return $str;
    }

    protected function decode($str)
    {
        if ($this->encrypt) {
            $str = $this->encrypt->decode($str);
        } else {
            $str = base64_decode($str);
        }
        return $str;
    }


    /**
     * read
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        // Load the session
        $query = sprintf('SELECT * FROM %s WHERE session_id = %s LIMIT 1', $this->getDb()->quoteParameter($this->getTable()), $this->getDb()->quote($id));
        $result = $this->getDb()->query($query);
        $row = $result->fetchObject();
        if (!$row) {  // No current session
            $this->sessionId = null;
            return '';
        }
        // Set the current session id
        $this->sessionId = $id;
        // Load the data
        $data = $row->data;
        return $this->decode($data);
    }

    /**
     * write
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        $data = $this->encode($data);
        if ($this->sessionId === null) {
            // Insert a new session
            $query = sprintf('INSERT INTO %s VALUES (%s, %s, %s, %s)', 
                $this->getTable(), $this->getDb()->quote($id), $this->getDb()->quote($data), $this->getDb()->quote($this->createDate()->format(\Tk\Date::ISO_DATE)), $this->getDb()->quote($this->createDate()->format(\Tk\Date::ISO_DATE)));
            $this->getDb()->query($query);
        } elseif ($id === $this->sessionId) {
            // Update the existing session
            $query = sprintf("UPDATE %s SET modified = %s, data = %s WHERE session_id = %s", 
                $this->getTable(), $this->getDb()->quote($this->createDate()->format(\Tk\Date::ISO_DATE)), $this->getDb()->quote($data), $this->getDb()->quote($id));
            $this->getDb()->query($query);
        } else {
            // Update the session and id
            $query = sprintf("UPDATE %s SET session_id = %s, modified = %s, data = %s WHERE session_id = %s", 
                $this->getTable(), $this->getDb()->quote($id), $this->getDb()->quote($this->createDate()->format(\Tk\Date::ISO_DATE)), $this->getDb()->quote($data), $this->getDb()->quote($this->sessionId));
            $this->getDb()->query($query);
            // Set the new session id
            $this->sessionId = $id;
        }
        return true;
    }

    /**
     * destroy
     *
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        $query = sprintf('DELETE FROM %s WHERE session_id = %s LIMIT 1', $this->getTable(), $this->getDb()->quote($id));
        $this->getDb()->query($query);
        $this->sessionId = null;
        return true;
    }

    /**
     * regenerate and return new session id
     *
     * @return string
     */
    public function regenerate()
    {
        $oid = session_id();
        session_regenerate_id();
        $nid = session_id();
        $query = sprintf("UPDATE %s SET session_id = %s, modified = %s WHERE id = %s",
                $this->getTable(), $this->getDb()->quote($nid), $this->getDb()->quote($this->createDate()->format(\Tk\Date::ISO_DATE)),
                $this->getDb()->quote($oid));
        $this->getDb()->query($query);
        return $nid;
    }

    /**
     * garbage collect
     *
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        // Delete all expired sessions
        $query = sprintf('DELETE FROM %s WHERE modified < %s', $this->getTable(), $this->getDb()->quote($this->createDate(time() - $maxlifetime)->format(\Tk\Date::ISO_DATE)));
        $this->getDb()->query($query);
        return true;
    }



    /**
     * @return \Tk\Db\Pdo
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param \Tk\Db\Pdo $db
     * @return $this
     */
    public function setDb($db)
    {
        $this->db = $db;
        $this->install();
        return $this;
    }

    /**
     * Open the session
     *
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function open($path, $name)
    {
        return true;
    }

    /**
     * close
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Get the table name for queries
     *
     * @return string
     */
    protected function getTable()
    {
        return self::$DB_TABLE;
    }

    /**
     * Use this to put creation in one place.
     *
     *
     * @param string $time
     * @param null $timezone
     * @return \DateTime
     */
    private function createDate($time = 'now', $timezone = null)
    {
        return \Tk\Date::create($time, $timezone);
    }
}