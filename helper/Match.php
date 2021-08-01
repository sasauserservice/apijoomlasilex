<?php

namespace Helper;
use \JFactory;
use \stdClass;
class Matchs {

    static function parameterFromPanel($panel){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT
        DISTINCT CATAPAR.parametro
        FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS CATAPAR
        LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES PANCAT ON PANCAT.categoryid = CATAPAR.categoria
        WHERE PANCAT.panelid = '{$panel}';
        SQL;

        $result = $dbo->setQuery($query)->loadColumn();
        return $result;
    }

    static function parameterAssinedFromPanel($panel){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT
        DISTINCT (PARAMETERS->>'id')::int parameter
        FROM SASA_MATCH_CORE_PANELS PANEL
        CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.generaljudge) GENJUD
        CROSS JOIN JSON_ARRAY_ELEMENTS(GENJUD->'params') PARAMETERS
        WHERE PANEL.id = '{$panel}';
        SQL;

        $result = $dbo->setQuery($query)->loadColumn();
        return $result;
    }

    static function parameterFromEvents($event){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT
        DISTINCT CATAPAR.parametro
        FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS CATAPAR
        LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES PANCAT ON PANCAT.categoryid = CATAPAR.categoria
        LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PANEVENT ON PANEVENT.panel = PANCAT.panelid
        WHERE PANEVENT.event = '{$event}';
        SQL;

        $result = $dbo->setQuery($query)->loadColumn();
        return $result;
    }

    static function parametersAssingedToJudgesOnEvent($event){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT
        DISTINCT (PARAMETERS->>'id')::int parameter
        FROM SASA_MATCH_CORE_PANELS PANEL
        CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.generaljudge) GENJUD
        CROSS JOIN JSON_ARRAY_ELEMENTS(GENJUD->'params') PARAMETERS
        LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PEVENT
        ON PEVENT.panel = PANEL.id
        WHERE PEVENT.event = '{$event}';
        SQL;

        $result = $dbo->setQuery($query)->loadColumn();
        return $result;
    } 

    static function getIdFromEmail($email){
        $dbo = JFactory::getDbo();
        $result = $dbo->setQuery("SELECT ID FROM #__users WHERE LOWER(email) = '".strtolower($email)."'")->loadResult();
        return $result;
    }

    static function getCatId(){
        $dbo = JFactory::getDbo();
        $result = $dbo->setQuery("SELECT ID FROM #__categories WHERE LOWER(title) = 'match'")->loadResult();
        return $result;
    }

    static function getCategories(){
        $dbo = JFactory::getDbo();
        $categories = $dbo->setQuery("SELECT mcat.id, CONCAT(mcat.title, ' (', mcat.description ,')') AS text FROM #__match_core_categories as mcat")->loadObjectList();
        return $categories;
    }
    
    static function getCategoriesById($id){
        $dbo = JFactory::getDbo();
        $categories = $dbo->setQuery("SELECT mcat.id, CONCAT(mcat.title, ' (', mcat.description ,')') AS text FROM #__match_core_categories as mcat where id = '{$id}'")->loadObject();
        return $categories;
    }

    static function getPenalties(){
        $dbo = JFactory::getDbo();
        $penalties = $dbo->setQuery("SELECT id, title as text FROM #__match_core_parameters_penalty")->loadObjectList();
        return $penalties;
    }

    static function getParams($ids){
        if(!is_array($ids) or (count($ids) === 0)){
            return [];
        }

        $select = "SELECT mpar.id, mpar.title FROM #__match_core_categories_parameters as mcatpar Left Join #__match_core_parameters as mpar on mpar.id = mcatpar.parametro WHERE ";

        for($i=0; $i<count($ids); $i++){
            $select .= " categoria = '".$ids[$i]."' OR ";
        }

        $sqlRepair = substr($select, 0,  -3);

        $sqlRepair .= " group by mpar.id";
        
        $dbo = JFactory::getDbo();

        $result = $dbo->setQuery($sqlRepair)->loadObjectList();
        
        return $result;
    }

    static function getJudges(){
        $dbo = JFactory::getDbo();
        $groupid = $dbo->setQuery("SELECT ID FROM #__usergroups WHERE LOWER(title) = 'sasa_judge'")->loadResult();
        $getUsers = $dbo->setQuery("SELECT u.id as identificador, u.email as id, u.email as text From #__users as u Left Join #__user_usergroup_map as umap on umap.user_id = u.id Where umap.group_id = '{$groupid}'")->loadObjectList();

        return $getUsers;
        
    }

    static function deletePanelWithEvent($panel){
        $dbo = JFactory::getDbo();
        $sql = <<<SQL
        DELETE FROM sasa_match_core_panels_events WHERE PANEL = '{$panel}';
        SQL;

        $record = $dbo->setQuery($sql)->execute();

        if($record){
            return true;
        } else {
            return false;
        }
    }

    static function savePanelWithEvent($event, $panel) {
        $dbo = JFactory::getDbo();
        $sql = <<<SQL
        SELECT ID FROM sasa_match_core_panels_events WHERE PANEL = '{$panel}';
        SQL;

        $record = $dbo->setQuery($sql)->loadResult();

        if(!$record){
            $new = new \stdClass;
            $new->panel = $panel;
            $new->event = $event;
            $new->panel_serial = \Helper\get_serial("sasa_match_core_panels", $panel);
            $new->event_serial = \Helper\get_serial("sasa_content", $event);
            $result = $dbo->insertObject('sasa_match_core_panels_events', $new, true);
        } else {
            $old = new \stdClass;
            $old->event = $event;
            $old->panel_serial = \Helper\get_serial("sasa_match_core_panels", $panel);
            $old->event_serial = \Helper\get_serial("sasa_match_core_panels", $event);
            $old->id = $record;
            $result = $dbo->updateObject('sasa_match_core_panels_events', $old, 'id');
        }

        return $result;
    }

    static function deleteChildsFromPanel($id){
        $dbo = JFactory::getDbo();
        $delete = $dbo->setQuery("DELETE FROM sasa_match_core_panels_events WHERE panel = '{$id}'")->execute();
        if(!$delete){
            return true;
        }

        return false;
    }

    static function getCatsFromEvent($id){
        $dbo = JFactory::getDbo();
        $sql = <<<SQL
        SELECT PACAT.CATEGORYID FROM SASA_MATCH_CORE_PANELS_EVENTS PAEVENT LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES AS PACAT ON PACAT.panelid = PAEVENT.panel WHERE PAEVENT.event = '{$id}';
        SQL;
        $result = $dbo->setQuery($sql)->loadColumn();
        return $result;
    }

    static function getPanelFromEvent($id){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT P.*, TO_CHAR(P.CREATED::DATE, 'Mon dd, yyyy') AS CREATEDDATE FROM SASA_MATCH_CORE_PANELS AS P LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PEV ON PEV.PANEL = P.ID WHERE PEV.EVENT = '{$id}'
        SQL;
        $panels = $dbo->setQuery($query)->loadObjectList();
        $newResult = array();
        foreach($panels as $i => $panel){

            //ESTATUS
            $parametersTotals = Matchs::parameterFromPanel($panel->id);
            $parametersAssign = Matchs::parameterAssinedFromPanel($panel->id);
            $diference        = array_diff($parametersTotals, $parametersAssign);
            $status = false;

            if( count($parametersTotals) > 0 ){
                if( count($diference) > 0){
                    $status = false;
                } else {
                    $status = true;
                }
            }
            //ESTATUS

            $append = new stdClass();
            $append->id = $panel->id;

            $append->statusA = $status;
            $jsonmain = json_decode($panel->mainjudge);
            $arraymain = array();
            foreach($jsonmain as $k => $gen){
                $genA   = (array) $gen;
                $userid = (int) $genA['user'];
                $user = Extended::getUserById($userid);
                array_push($arraymain, [
                    "slot" => $k,
                    "user" => [
                        "iduser"   => $userid,
                        "email" => $user['email'],
                    ]
                ]);
            }

            $jsongeneral = json_decode($panel->generaljudge);
            $arraygeneral = array();
            $paramSel = array();
            $generalToEdit = array();
            foreach($jsongeneral as $j => $gen){
                $genA   = (array) $gen;
                $userid = (int) $genA['general'];
                $user = Extended::getUserById($userid);
                array_push($arraygeneral, [
                    "slot" => $j,
                    "user" => [
                        "iduser"   => $userid,
                        "email" => $user['email'],
                        "params" => $genA['params']
                    ]
                ]);

                array_push($generalToEdit, [
                    "user" => $user['email'],
                    "email" => "",
                    "params" => $genA['params']
                ]);

                foreach($genA['params'] as $pen){
                    array_push($paramSel, $pen->id);
                }
            }

            $jsonpenalty = json_decode($panel->penaltyjudge);
            $arraypenalty = array();
            $penaltiesSel = array();

            $penaltiesToEdit = array();

            foreach($jsonpenalty as $j => $gen){
                $genA   = (array) $gen;
                $userid = (int) $genA['penalty'];
                $user = Extended::getUserById($userid);
                array_push($arraypenalty, [
                    "slot" => $j,
                    "user" => [
                        "iduser"   => $userid,
                        "email" =>  $user['email'],
                        "params" => $genA['params']
                    ]
                ]);

                array_push($penaltiesToEdit, [
                    "user" => $user['email'],
                    "email" => "",
                    "penalties" => $genA['params']
                ]);

                foreach($genA['params'] as $pen){
                    array_push($penaltiesSel, $pen->id);
                }
            }

            /**PARAMS**/
            $queryParams = <<<SQL
            SELECT DISTINCT EACH_PARAMS ->> 'id' paramenterid, EACH_PARAMS ->> 'title' parametertitle FROM SASA_MATCH_CORE_PANELS PAN CROSS JOIN JSON_ARRAY_ELEMENTS(GENERALJUDGE) EACH_SECTION CROSS JOIN JSON_ARRAY_ELEMENTS( EACH_SECTION->'params' ) EACH_PARAMS LEFT JOIN SASA_USERS USR ON USR.ID = (EACH_SECTION ->> 'general')::int LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PANEVENT ON PANEVENT.panel = PAN.id WHERE PAN.id = '{$panel->id}'
            SQL;
            $parametersByPanel = $dbo->setQuery($queryParams)->loadObjectList();
            /**PARAMS**/
            
            /**PARAMS**/
            $queryPenaltis = <<<SQL
            SELECT DISTINCT (EACH_PENALTY->>'id') penaltyid, (EACH_PENALTY->>'text') penaltytitle FROM SASA_MATCH_CORE_PANELS PAN CROSS JOIN JSON_ARRAY_ELEMENTS(PENALTYJUDGE) EACH_SECTION CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_SECTION->'params') EACH_PENALTY LEFT JOIN SASA_USERS USR ON USR.ID = (EACH_SECTION ->> 'penalty')::int LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PANEVENT ON PANEVENT.panel = PAN.id WHERE PAN.id = '{$panel->id}'
            SQL;
            $penaltiesByPanel = $dbo->setQuery($queryPenaltis)->loadObjectList();
            /**PARAMS**/

            $append->name = $panel->title;
            $append->serial = $panel->serialid;
            $append->created = $panel->createddate;
            $append->categories = $dbo->setQuery("SELECT cat.id, cat.title FROM sasa_match_core_panels_categories as pacat left join sasa_match_core_categories as cat on cat.id = pacat.categoryid where panelid = '".$panel->id."'")->loadObjectList();
            $append->judges = [
                "main" => $arraymain,
                "general" => $arraygeneral,
                "penalty" => $arraypenalty
            ];

            $append->params = $parametersByPanel;
            $append->penalties = $penaltiesByPanel;

            $append->event_id = $dbo->setQuery("SELECT event from #__match_core_panels_events where panel = '{$panel->id}'")->loadResult();

            $append->toedit = new stdClass();

            $append->toedit->selectedCategories = [];
            foreach($append->categories as $cat){
                array_push($append->toedit->selectedCategories, $cat->id);
            }

            $parameetes = Matchs::getParams($append->toedit->selectedCategories);
            $append->toedit->allparameters = [];
            foreach($parameetes as $par){
                array_push($append->toedit->allparameters, $par->id);
            }

            /******/
            if(!is_array($append->toedit->selectedCategories) or (count($append->toedit->selectedCategories) === 0)){
                return [];
            }
    
            $select = "SELECT mpar.id, mpar.title FROM #__match_core_categories_parameters as mcatpar Left Join #__match_core_parameters as mpar on mpar.id = mcatpar.parametro WHERE ";
    
            for($i=0; $i<count($append->toedit->selectedCategories); $i++){
                $select .= " categoria = '".$append->toedit->selectedCategories[$i]."' OR ";
            }
    
            $sqlRepair = substr($select, 0,  -3);
    
            $sqlRepair .= " group by mpar.id";
    
            $allparemetersToedit = $dbo->setQuery($sqlRepair)->loadObjectList();
            /******/

            $append->toedit->allparameters = $allparemetersToedit;
            $append->toedit->allpenalties = $dbo->setQuery("SELECT id, title as text FROM #__match_core_parameters_penalty")->loadObjectList();
            $append->toedit->selectedPenalties = $penaltiesSel;
            $append->toedit->selectedParameters = $paramSel;
            $append->toedit->selectedJudgePenalties = $penaltiesToEdit;
            $append->toedit->selectedJudgeGeneral = $generalToEdit;
            
            array_push($newResult, $append);
        }

        return $newResult;
    }

    static function getEvents(){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT ID, TITLE AS TEXT FROM SASA_CONTENT WHERE CATID='9' AND STATE = 1
        SQL;

        $result = $dbo->setQuery($query)->loadObjectList();
        return $result;
    }

    static function getPanelsNotAssigned($event){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT pan.id, pan.title as text from sasa_match_core_panels pan where pan.id not in (SELECT panel from sasa_match_core_panels_events where event = '{$event}');
        SQL;

        $result = $dbo->setQuery($query)->loadObjectList();
        return $result;
    }
    
    static function getPanelsAssigned($event){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT pan.id, pan.title as text from sasa_match_core_panels pan where pan.id  in (SELECT panel from sasa_match_core_panels_events where event = '{$event}');
        SQL;

        $result = $dbo->setQuery($query)->loadObjectList();
        return $result;
    }
    
    static function getPanelsAll(){
        $dbo = JFactory::getDbo();
        $query = <<<SQL
        SELECT pan.id, pan.title as text from sasa_match_core_panels pan;
        SQL;

        $result = $dbo->setQuery($query)->loadObjectList();
        return $result;
    }


    static function sendPanelToEvent($event, $panels){
        $dbo = JFactory::getDbo();
        
        $array_send = array_map(function($elem){
            return $elem->id;
        }, $panels);

        $query_find_exist = <<<SQL
        SELECT PANEL FROM SASA_MATCH_CORE_PANELS_EVENTS AS PE WHERE PE.event = '{$event}';
        SQL;
        
        $panelsExists = $dbo->setQuery($query_find_exist)->loadColumn();

        $diff = array_diff($panelsExists, $array_send);
        $error = 0;

        if( count($diff) > 0 ){
            foreach($diff as $f){
                $query = "DELETE FROM SASA_MATCH_CORE_PANELS_EVENTS WHERE event = '{$event}' and panel = '{$f}'";
                $delete = $dbo->setQuery($query)->execute();
                if(!$delete){
                    $error++;
                }                
            }
            
            if($error > 0){
                return false;
            }
        }

        for($i=0; $i<count($array_send); $i++){
            $insert = new stdClass();
            $insert->panel = intval($array_send[$i]);
            $insert->event = intval($event);
            $register = $dbo->insertObject('sasa_match_core_panels_events', $insert);
            if(!$register){
                $error++;
            }
        }

        if($error > 0){
            return false;
        }

        return true;
    }

    static function getCatsEvents(){
        $dbo = JFactory::getDbo();
        $q = <<<SQL
        SELECT ID, TITLE, DESCRIPTION FROM SASA_MATCH_CORE_CATEGORIES;
        SQL;
        $rs = $dbo->setQuery($q)->loadObjectList();
        $return = array();
        foreach($rs as $i => $cat){
            $totalcat = <<<SQL
            SELECT SUM((EACH_CRITERIA->>'points')::int) AS totalcat
            FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR
            LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PAR ON PAR.ID = CATPAR.PARAMETRO
            CROSS JOIN JSON_ARRAY_ELEMENTS(PAR.criteria) AS EACH_CRITERIA
            WHERE CATPAR.categoria  = '{$cat->id}';
            SQL;

            $cat->totalCat = $dbo->setQuery($totalcat)->loadResult();
            $return[$cat->id] = $cat;
        }

        return $return;
    }
    
    static function getCatsEventsSpecified($event){
        $dbo = JFactory::getDbo();
        $q = <<<SQL
        SELECT CAT.ID, CAT.TITLE, CAT.DESCRIPTION FROM SASA_MATCH_CORE_CATEGORIES AS CAT LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES PCAT ON PCAT.categoryid = CAT.id LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PEVE ON PEVE.panel = PCAT.panelid WHERE PEVE.event = '{$event}';
        SQL;
        $rs = $dbo->setQuery($q)->loadObjectList();
        $return = array();
        foreach($rs as $i => $cat){
            $return[$cat->id] = $cat;
        }
        $newreturn = array();
        foreach($return as $i => $rt){
            $t = $rt;
            $totalcat = <<<SQL
            SELECT SUM((EACH_CRITERIA->>'points')::int) AS totalcat
            FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR
            LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PAR ON PAR.ID = CATPAR.PARAMETRO
            CROSS JOIN JSON_ARRAY_ELEMENTS(PAR.criteria) AS EACH_CRITERIA
            WHERE CATPAR.categoria  = '{$rt->id}';
            SQL;

            $t->totalCat = $dbo->setQuery($totalcat)->loadResult();

            array_push($newreturn, $t);
        }

        return $newreturn;
    }

    static function getCatsByEventAndPanel($event, $panel=null){
        $dbo = JFactory::getDbo();
        
        $q = <<<SQL
        SELECT CAT.ID, CAT.TITLE, CAT.DESCRIPTION FROM SASA_MATCH_CORE_CATEGORIES AS CAT LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES PCAT ON PCAT.categoryid = CAT.id LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PEVE ON PEVE.panel = PCAT.panelid WHERE PEVE.event = '{$event}' and PEVE.panel <> '{$panel}';
        SQL;
        $rs = $dbo->setQuery($q)->loadObjectList();
        $return = array();
        foreach($rs as $i => $cat){
            $return[$cat->id] = $cat;
        }
        $newreturn = array();
        foreach($return as $i => $rt){
            $t = $rt;
            $totalcat = <<<SQL
            SELECT SUM((EACH_CRITERIA->>'points')::int) AS totalcat
            FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR
            LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PAR ON PAR.ID = CATPAR.PARAMETRO
            CROSS JOIN JSON_ARRAY_ELEMENTS(PAR.criteria) AS EACH_CRITERIA
            WHERE CATPAR.categoria  = '{$rt->id}';
            SQL;

            $t->totalCat = $dbo->setQuery($totalcat)->loadResult();

            array_push($newreturn, $t);
        }

        return $newreturn;
    }

    static function sortBy($field, &$array, $direction = 'asc'){
        usort($array, function($a, $b) use ($direction, $field){
            $a = $a["' . $field . '"];
            $b = $b["' . $field . '"];
    
            if ($a == $b)
            {
                return 0;
            }
    
            return ($a  . ($direction == 'desc' ? '>' : '<') . $b) ? -1 : 1;
        });
    
        return true;
    }
}