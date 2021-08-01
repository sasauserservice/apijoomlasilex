<?php

namespace Helper;

class Extended {
    static function getUserById($id){
        global $database;
        $query = <<<SQL
        SELECT * FROM SASA_MATCH_USERS AS USR WHERE ID = '{$id}';
        SQL;
        $result = $database->query($query);
        if(is_null($database->error)){
            $result = $result->fetch();
        } else {
            $result = null;
        }

        return $result;
    }
    
    static function getUserById2($id){
        global $database;
        $query = <<<SQL
        SELECT * FROM SASA_MATCH_USERS AS USR WHERE ID = '{$id}';
        SQL;
        $result = $database->query($query);
        if(is_null($database->error)){
            $result = $result->fetch();
        } else {
            $result = null;
        }

        return $result;
    }
    
    static function getUserByEmail($email){
        global $database;
        global $dbo;
        $lower = strtolower($email);
        $query = <<<SQL
        SELECT * FROM SASA_MATCH_USERS AS USR WHERE LOWER(EMAIL) = '{$lower}';
        SQL;
        $result = $database->query($query);
        if(is_null($database->error)){
            $result = $result->fetch();
            if($result == false){
                $query = <<<SQL
                SELECT * FROM SASA_USERS AS USR WHERE LOWER(EMAIL) = '{$lower}';
                SQL;
                $result = $dbo->setQuery($query)->loadAssoc();
            }
        } else {
            $result = null;
        }

        return $result;
    }

    static function getGroupsFromUser($id)
    {
        global $database;
        $query = <<<SQL
        SELECT GRP.* FROM SASA_USERGROUPS AS GRP LEFT JOIN SASA_USER_USERGROUP_MAP AS USRMAP ON USRMAP.GROUP_ID = GRP.ID WHERE USRMAP.USER_ID = '{$id}';
        SQL;
        $result = $database->query($query);
        if(is_null($database->error)){
            $result = $result->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $result = [];
        }

        $response = array();
        foreach($result as $i => $grp){
            switch(strtolower($grp['title'])){
                case 'athlete':
                    $grp['icon'] = 'star';
                    break;
                case 'judge':
                    $grp['icon'] = 'bolt';
                    break;
                case 'coach':
                    $grp['icon'] = 'heart';
                    break;
                default:
                    $grp['icon'] = null;
                    break;
            }

            $grp['id'] = strval($grp['id']);

            array_push($response, $grp);
        }

        return $response;
    }
}