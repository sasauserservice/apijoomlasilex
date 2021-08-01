<?php

namespace Model;

class Auth {
    protected $dbo;
    protected $database;
    protected $joomla;
    protected $groups;

    function __construct()
    {
        global $database;
        global $Joomla;
        global $dbo;

        $this->dbo = $dbo;
        $this->database = $database;
        $this->joomla = $Joomla;
    }

    function is($user, $group){
        $groups = $this->getGorupsOfUser($user)->getOnlyGroupsNames(true);
        $pop =  in_array($group, $groups);
        var_dump($pop);
    }

    function userJoomlaManager($id){
        $joomla = \JFactory::getUser($id);
        $groups = $this->dbo->setQuery("SELECT id from sasa_usergroups where LOWER(title) = 'sasa_manager'")->loadResult();
        return in_array($groups, $joomla->groups);
    }

    function getOnlyGroupsId($yes=true)
    {
        $elements = array_map(function($e){
            return (int) $e->id;
        }, $this->groups);

        return ($yes) ? $elements : $this->groups;
    }
    
    function getOnlyGroupsNames($yes=true)
    {
        $elements = array_map(function($e){
            return $e->title;
        }, $this->groups);

        return ($yes) ? $elements : $this->groups;
    }

    function getGorupsOfUser($user)
    {
        $q = <<<SQL
        SELECT ug.id, ug.title FROM sasa_usergroups as ug
        left join sasa_user_usergroup_map as umap
        on umap.group_id = ug.id
        where umap.user_id = '{$user}'
        SQL;

        $result = $this->database->query($q);
        $result->setFetchMode(\PDO::FETCH_OBJ);
        $response = $result->fetchAll();
        $this->groups = $response;
        return $this;
    }

    function getUser($id)
    {
        $query = <<<SQL
        SELECT * FROM sasa_match_users as u where u.id = '{$id}';
        SQL;

        $result = $this->database->query($query);
        if(is_null($result)){
            return false;
        }
        $result->setFetchMode(\PDO::FETCH_OBJ);
        $response = $result->fetch();

        $return = new \stdClass;

        if(!$response){
            return false;
        }

        $return->name  = $response->name;
        $return->email = $response->email;
        $return->registerDate = $response->registerDate;
        $return->lastVisitDate = $response->lastlogin;
        $return->groups = $this->getGorupsOfUser($response->id)->getOnlyGroupsNames(true);

        return $return;
    }

    function validateLogin(string $email, string $password)
    {
        $queryDbo = <<<SQL
        SELECT * from sasa_users where LOWER(email) = '{$email}';
        SQL;

        $ifexist_mainuser = $this->dbo->setQuery($queryDbo)->loadObject();

        if(is_null($ifexist_mainuser) == true){
            $query = <<<SQL
            SELECT id FROM sasa_match_users as u where LOWER(u.email) = '{$email}' and u.password = crypt('{$password}', password);
            SQL;

            $result = $this->database->query($query);
            if(is_null($result)){
                return false;
            }
            $response = $result->fetch(\PDO::FETCH_OBJ);
            
            return $this->getUser($response->id);
        } else {

            $credentials = array(
                "username" => $email, 
                "password" => $password
            );

            $rsLogin = $this->joomla->login($credentials, array());

            if(!$rsLogin){
                return false;
            }
            $sql = <<<SQL
            SELECT joomla.* from DBLINK('dbname=apijoomla', 'SELECT id, title from sasa_usergroups') AS joomla(id bigint, title text) UNION SELECT id, title from sasa_usergroups order by id
            SQL;
            $rs = $this->database->query($sql);
            $rs->setFetchMode(\PDO::FETCH_ASSOC);
            $groupsUsersQrs = $rs->fetchAll();
            
            $joomlauser = \JFactory::getUser($ifexist_mainuser->id);
            
            $array_grupos = array();
            foreach($joomlauser->groups as $k => $v){
                $index = array_search($k, array_column($groupsUsersQrs, 'id'));
                array_push( $array_grupos, $groupsUsersQrs[$index]['title'] );
            }
            
            $joomlauser->groups = $array_grupos;
            $response = $joomlauser;

            return $response;
        }

        /*
        */
    }
}