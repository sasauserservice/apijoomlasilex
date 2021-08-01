<?php 

namespace Helper;

class Status
{
    static function updateStateEvent($event, $value = 0){
        global $database;
        $database->update('match_status_event', [
            "status" => $value,
        ], [
            "eventid" => $event,
        ]);
        
        if($database->error){
            return false;
        }

        return true;
    }

    static function getStatusEvent($event){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_event AS stevent WHERE eventid = '{$event}';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();
        return $rsfetch;
    }

    static function createStatusEvent($event) {
        global $database;
        $database->insert('match_status_event', [
            "eventid" => intval($event),
        ]);

        if($database->error){
            return ["error" => true, "Message" => $database->error];
        }

        return ["error" => false, "Message" => $database->error];

    }

    static function setStatusEvent($event){
        global $database;

        $cantentriesFromEvent = <<<SQL
        SELECT COUNT(party.*) FROM sasa_match_modules_participations as party where party.event_id = '{$event}';
        SQL;
        $rs = $database->query($cantentriesFromEvent);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();
        
        $cantentriesWithState1 = <<<SQL
        SELECT 
        COUNT(PARTY.*)
        FROM SASA_MATCH_MODULES_PARTICIPATIONS AS PARTY
        LEFT JOIN SASA_MATCH_STATUS_ENTRIES AS ENTRIE
        ON ENTRIE.entryid = PARTY.ID
        WHERE PARTY.EVENT_ID = '{$event}' AND ENTRIE.statusmain = 1
        SQL;

        $rs1 = $database->query($cantentriesWithState1);
        $rs1->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch1 = $rs1->fetch();
        if($rsfetch['count'] == $rsfetch1['count']){
            $result = self::updateStateEvent($event, 1);
            return $result;
        } else {
            $result = self::updateStateEvent($event);
            return $result;
        }
    }

    static function getStatusParticipation($id){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_entries AS stpar WHERE entryid = '{$id}';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();
        return $rsfetch;
    }

    static function setStatusParticipation($id, $status = 0, $statusPenal=0, $statusMain=0){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_entries AS stpar WHERE entryid = '{$id}';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();
        
        if($rsfetch === false){

            $database->insert('match_status_entries', [
                "entryid" => $id,
                "status" => is_null($status) ? 0 : $status,
                "statuspenal"=> is_null($statusPenal) ? 2 : $statusPenal,
                "statusmain" => is_null($statusMain) ? 0 : $statusMain,
            ]);

            if(!$database->error){
                return ["error" => true, "message" => $database->error];
            }

            return ["error" => false, "message" => $database->error];

        } else {

            $database->update('match_status_entries', [
                "status" => is_null($status) ? $rsfetch['status'] : $status,
                "statuspenal"=> is_null($statusPenal) ? $rsfetch['statuspenal'] : $statusPenal,
                "statusmain" => is_null($statusMain) ? $rsfetch['statusmain'] : $statusMain,
            ], [
                "entryid" => $id,
            ]);

            if(!$database->error){
                return ["error" => true, "message" => $database->error];
            }
            
            return ["error" => false, "message" => $database->error];

        }
    }

    static function getStatus($type, $id){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_judment AS stjug WHERE entryid = '{$id}' AND type='{$type}';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();
        return $rsfetch;
    }

    static function setStatusMainPanel($id, $data, $change=false){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_judment AS stjug WHERE entryid = '{$id}' AND type='4';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();

        $newData = array();
        foreach($data as $p){
            $cl = $p;
            if($change==false){
                
            }
            
            array_push($newData, $cl);
        }

        if($rsfetch == false){
            $database->insert('match_status_judment', [
                "data" => json_encode($newData),
                "type" => 4,
                "entryid" => $id,
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];

        } else {

            $database->update('match_status_judment', [
                "data" => json_encode($newData),
                "type" => 4,
                "entryid" => $id,
            ], [
                "id" => $rsfetch['id']
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];
        }
    }

    static function setStatusMain($id, $data, $change=false ){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_judment AS stjug WHERE entryid = '{$id}' AND type='3';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();

        $newData = array();
        foreach($data as $p){
            $cl = $p;
            if($change==false){
                
            }
            
            array_push($newData, $cl);
        }

        if($rsfetch == false){
            $database->insert('match_status_judment', [
                "data" => json_encode($newData),
                "type" => 3,
                "entryid" => $id,
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];

        } else {

            $database->update('match_status_judment', [
                "data" => json_encode($newData),
                "type" => 3,
                "entryid" => $id,
            ], [
                "id" => $rsfetch['id']
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];
        }
    }

    static function setStatusMainPenal($id, $data, $change=false){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_judment AS stjug WHERE entryid = '{$id}' AND type='4';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();

        $newData = array();
        if(is_array($data)==true){
            foreach($data as $p){
                $cl = $p;
                if($change==false){
                    
                }
                
                array_push($newData, $cl);
            }
        }

        if($rsfetch == false){
            $database->insert('match_status_judment', [
                "data" => json_encode($newData),
                "type" => 4,
                "entryid" => $id,
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];

        } else {

            $database->update('match_status_judment', [
                "data" => json_encode($newData),
                "type" => 4,
                "entryid" => $id,
            ], [
                "id" => $rsfetch['id']
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];
        }
    }

    static function setStatus($type, $id, $data, $judge=null ){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_status_judment AS stjug WHERE entryid = '{$id}' AND type='{$type}';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();

        $newData = array();
        foreach($data as $p){
            $cl = $p;
            if($cl->flag==0){
                $cl->flag = 1;
            }
            array_push($newData, $cl);
        }

        if($rsfetch == false){
            $database->insert('match_status_judment', [
                "data" => json_encode($newData),
                "type" => $type,
                "entryid" => $id,
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];
        } else {
            $database->update('match_status_judment', [
                "data" => json_encode($newData),
                "type" => $type,
                "entryid" => $id,
            ], [
                "id" => $rsfetch['id']
            ]);

            if(!$database->error){
                return ["error" => true, "msg" => $database->error];
            }

            return ["error" => false, "msg" => $database->error];
        }
    }

    #ATHLETE VALIDATE AND UPDATE TO STATUS
    static function validateAndUpdateAthleteStatus($entry){
        global $database;
        $query = <<<SQL
        SELECT * FROM sasa_match_core_score_general_finally AS stjug WHERE entry = '{$entry}';
        SQL;
        $rs = $database->query($query);
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();

        $tiempoEnCode = new \DateTime($rsfetch['last_updated']);
        $tiempoEnCode->setTimezone(new \DateTimeZone('America/Bogota'));
        $tiempoNuevo = $tiempoEnCode->format('Y-m-d H:i:sP');
        $tiempoInMS  = $tiempoEnCode->format('U');
        $sumatorias  = $tiempoEnCode->modify("+1 minute");
        

        $return = [
            "entry"         => $entry,
            "dateToCounter" => $tiempoNuevo,
            "dateToMS"      => (int) $tiempoInMS,
            "dateWithPlus"  => $sumatorias->format('Y-m-d H:i:sP'),
            "dateWithPlusMS" => (int) $sumatorias->format('U'),
        ];

        if((time() >= $return["dateWithPlusMS"]) && ($rsfetch['status'] == 0)){

            $database->update('match_core_score_general_finally', [
                "status" => 1,
            ], [
                "entry" => $entry
            ]);
            
            $database->update('match_core_score_penalty_finally', [
                "status" => 1,
            ], [
                "entry" => $entry
            ]);
        }


        return $return;
    }
}