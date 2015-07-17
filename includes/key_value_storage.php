<?php


class HashTable
{
  // Ссылка на соединение с MySQL
  protected $connect;
  // Имя таблица
  protected $table;

  /**
   *
   * @param resource MySQL $connect
   * @param string $table
   */
  public function  __construct($connect, $table) {
    $this->connect = $connect;
    $this->table = $table;
  }

  /**
   *
   * @param string $key
   * @param string $val
   * @return boolean
   */


  /**
   *
   * @param string $key
   * @return void
   */
  public function get($key) {
    $key = md5($key);
    $query = 'SELECT `value` FROM `'.$this->table.'` WHERE `key`="'.$key.'"';
    $result = mysql_query($query, $this->connect);
    if ($result) {
      $row = mysql_fetch_row($result);
      return unserialize($row[0]);
    } else {
      return false;
    }
  }

  /**
   *
   * @param string $key
   * @return boolean
   */
  public function check($key) {
    $key = md5($key);
    $query = 'SELECT COUNT(*) FROM `'.$this->table.'` WHERE `key`="'.$key.'"';
    $result = mysql_query($query, $this->connect);
    $row = mysql_fecth_row($result);
    return (bool)$row[0];
  }

  /**
   *
   * @param string $key
   * @return boolean
   */
  public function delete($key) {
    $key = md5($key);
    $query = 'DELETE FROM `'.$this->table.'` WHERE `key`="'.$key.'"';
    return mysql_query($query, $this->connect) ? true : false;
  }
}