<?php
namespace Model;

use Site\Config;
use Exception;
use ImageBoard\Board;
use mysqli;

/**
 * Class for getting, changing, and removing data. Expect big additions to this
 * as I update the site in order to make maintenance easier.
 *
 */
class OldModel
{
    /**
     * Gets a query for the board catalog.
     * @param Board $board
     * @param boolean $active
     * @return mysqli::query
     */
    static function getCatalog($board, $active = true)
    {
        $dbl = Config::getMysqliConnection();
        $board_shortname = $dbl->real_escape_string($board->getName());
        $prefix = $board_shortname . "_";
        $pTable = $prefix . "post";
        $tTable = $prefix . "thread";
        $pageQuery = "SELECT {$tTable}.*, {$pTable}.*  FROM {$tTable} LEFT JOIN {$pTable} ON {$pTable}.no = {$tTable}.threadid ";
        if ($active) $pageQuery .= "WHERE {$tTable}.active = 1 ";
        $pageQuery .= "ORDER BY {$tTable}.lastreply DESC LIMIT 0,{$board->getMaxActiveThreads()}";
        $q = $dbl->query($pageQuery);
        return $q;
    }

    /**
     * Get the last few deleted posts in case you mistakenly delete one from the archive.
     *
     * @param Board|string $board
     */
    static function getLastNDeletedPosts($board, $n)
    {
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $n = abs((int)$n);
        $prefix = $board . "_";
        $query = "SELECT * FROM `{$prefix}deleted` ORDER BY `no` DESC LIMIT 0,$n";
        $result = $dbl->query($query);
        $postArr = array();
        while ($row = $result->fetch_assoc())
            $postArr[] = $row;
        return $postArr;
    }

    public static function ban($ip, $reason, $expires = 0)
    {
        $dbl = Config::getMysqliConnectionRW();
        $ip = $dbl->real_escape_string($ip);
        $reason = $dbl->real_escape_string($reason);
        $expires = $dbl->real_escape_string($expires);
        $dbl->query("INSERT INTO `bans` (`ip`,`reason`,`expires`) VALUES ('$ip','$reason','$expires')");
    }

    public static function getBanInfo($ip)
    {
        $dbl = Config::getMysqliConnection();
        $ip = $dbl->real_escape_string($ip);
        return $dbl->query("SELECT * FROM `bans` WHERE `ip`='$ip'")->fetch_assoc();
    }

    public static function banHash($hash)
    {
        $dbl = Config::getMysqliConnectionRW();
        $hashcln = $dbl->real_escape_string($hash);
        if (bin2hex(hex2bin($hashcln)) === $hash) {
            $dbl->query("INSERT IGNORE INTO `banned_hashes` (`hash`) VALUES (UNHEX('$hashcln'))");
            if ($dbl->errno) {
                throw new Exception($dbl->error);
            }
        } else {
            throw new Exception("Invalid hash: $hash");
        }
    }

    static $banned_hashes = null;

    public static function getBannedHashes()
    {
        if (self::$banned_hashes != null) {
            return self::$banned_hashes;
        }
        $dbl = Config::getMysqliConnection();
        $q = $dbl->query("SELECT `hash` FROM `banned_hashes`");
        while ($row = $q->fetch_assoc()) {
            $ret[] = bin2hex($row['hash']);
        }
        self::$banned_hashes = $ret;
        return $ret;
    }

    public static function deletePost($no, $board)
    {
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $dbl->query("INSERT INTO `{$board}_deleted` (SELECT * FROM `{$board}_post` WHERE `{$board}_post`.`no`=$no)");
        if (!$dbl->errno) {
            $dbl->query("DELETE FROM `{$board}_post` WHERE `no`=$no");
            if (!$dbl->errno)
                self::deleteReport($no, $board);
            else
                throw new Exception("Query failed: " . $dbl->error);
        } else
            throw new Exception("Query failed: " . $dbl->error);
    }

    public static function deleteReport($no, $board)
    {
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $dbl->query("DELETE FROM `reports` WHERE `no`=$no AND `board`='$board'");
        if ($dbl->errno) {
            throw new Exception("Query failed.");
        }
    }

    public static function banReporter($no, $board)
    {
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $query = $dbl->query("SELECT `ip` FROM `reports` WHERE `no`=$no AND `board`='$board'");
        if (!$dbl->errno) {
            while ($row = $query->fetch_assoc()) {
                self::ban($row['ip'], "Frivolous reporting");
            }
            self::deleteReport($no, $board);
        } else
            throw new Exception("Query failed.");
    }

    public static function restorePost($no, $board)
    {
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $dbl->query("INSERT INTO `{$board}_post` (SELECT * FROM `{$board}_deleted` WHERE `{$board}_deleted`.`no`=$no)");
        if (!$dbl->errno) {
            $dbl->query("DELETE FROM `{$board}_deleted` WHERE `no`=$no");
            if ($dbl->errno)
                throw new Exception("Delete query failed: " . $dbl->error);
        } else
            throw new Exception("Restore query failed: " . $dbl->error);
    }

    public static function getAllNewsArticles()
    {
        $db = Config::getMysqliConnection();
        $query = "SELECT `users`.`username`,`users`.`uid`,`news`.`article_id`,`news`.`title`,`news`.`content`,`news`.`time`,`news`.`update` FROM `news` JOIN `users` ON `news`.`author_id`=`users`.`uid` WHERE `news`.`time` < UNIX_TIMESTAMP() ORDER BY `news`.`article_id` DESC";
        $q = $db->query($query);
        return $q->fetch_all(MYSQLI_ASSOC);
    }

    public static function updateUserTheme($uid, $theme)
    {
        try {
            $db = Config::getMysqliConnectionRW();
            $q = $db->prepare("UPDATE `users` SET `theme`=? WHERE `uid`=?");
            $q->bind_param("si", $theme, $uid);
            $q->execute();
        } catch (Exception $ex) {

        }
    }
}