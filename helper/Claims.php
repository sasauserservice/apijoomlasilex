<?php 

namespace Helper;

class Claims
{
    static function getEvents(){
        global $dbo;
        $sql = <<<SQL
        SELECT id, title from #__content;
        SQL;
        $response = $dbo->setQuery($sql)->loadObjectList();
        $return = array();
        foreach($response as $r){
            $return[$r->id] = $r;
        }
        return $return;
    }

    static function getCompetitors(){
        global $database;
        $q = <<<SQL
        SELECT 
        COMP.id,
        COMP.data->>'name' AS name
        FROM 
        SASA_MATCH_COMPETITOR AS COMP
        SQL;
        $information = $database->query($q);
        $information->setFetchMode(\PDO::FETCH_ASSOC);
        $informationFetch = $information->fetchAll();
        $return = array();
        foreach($informationFetch as $rt){
            $return[$rt['id']] = $rt;
        }
        return $return;
    }

    static function updateMessage($id, $messages){
        global $database;
        $update = array("messages" => json_encode($messages));
        $database->update("match_core_modules_participations_messages", $update, ["id" => $id]);

        if($database->error){
            return ["error" => $database->error];
        }

        return true;
    }

    static function createNewCase($judge, $type, $event, $entry, $param, $competitor){
        global $database;
        $insertClaim = array(
            "eventid"    => $event,
            "entryid"    => $entry,
            "competitor" => $competitor,
            "judge"      => $judge,
            "caseid"       => generate_string(15),
            "typecase"   => $type,
            "parametro"  => $param,
            "messages"   => json_encode(array()),
        );

        $database->insert('match_core_modules_participations_messages', $insertClaim);

        if($database->error){
            return ["error" => $database->error];
        }
        $data = $database->id();
        return $data;
    }

    static function getAllClaims($event){
        
    }
}