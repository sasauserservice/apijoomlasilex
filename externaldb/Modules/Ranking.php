<?php
use Symfony\Component\HttpFoundation\Response;
use Helper\Extended;
use Helper\Matchs;

$ranking->get('/preview/{event}', function($event) use ($dbo, $database) {
    $dataeventQuery = <<<SQL
    SELECT id, title, alias, fulltext, catid FROM SASA_CONTENT WHERE ID = '{$event}';
    SQL;
    $dataeventResult = $dbo->setQuery($dataeventQuery)->loadObject();

    $dataParticipationsQuery = <<<SQL
    SELECT 
        COMP.id AS competitor_id, 
        COMP.data->>'name' AS competitor_name, 
        PAR.data->>'video_url' AS video,
        PAR.category_id AS category,
        GF.data AS score, 
        PF.data AS penals 
    FROM SASA_MATCH_CORE_SCORE_GENERAL_FINALLY AS GF 
        LEFT JOIN SASA_MATCH_CORE_SCORE_PENALTY_FINALLY AS PF 
            ON PF.entry = GF.entry 
        LEFT JOIN SASA_MATCH_MODULES_PARTICIPATIONS AS PAR 
            ON PAR.id = GF.entry 
        LEFT JOIN SASA_MATCH_COMPETITOR AS COMP 
            ON COMP.id = PAR.partipant_id 
    WHERE PAR.event_id = '{$event}';
    SQL;

    $dataParticipationsResult = $database->query($dataParticipationsQuery);
    $dataParticipationsResult->setFetchMode(PDO::FETCH_ASSOC);
    $dataParticipationsFetch = $dataParticipationsResult->fetchAll();

    $allCategories = Matchs::getCatsEvents();
    $catsbyevent   = Matchs::getCatsEventsSpecified($event);
    $participationsRank = array();

    foreach($dataParticipationsFetch as $i => $parti){
        $p = $parti;
        $p['category_data'] = $allCategories[$p['category']];
        $p['score']         = json_decode($p['score']);
        $p['penals']        = json_decode($p['penals']);
        $totalCategoryPoints = floatval($p['category_data']->totalCat);
        
        /**CALCULATE SCORE**/
        $points = 0;
        $quals  = 0;        
        $indexScore = 1;

        if(is_string($p['score'])){
            $p['score'] = json_decode($p['score']);
        }
        
        while($indexScore <= count($p['score'])){
            $a = $p['score'][$indexScore-1];
            for($j=0; $j<count($a->criteria); $j++){
                $points = $points + $a->criteria[$j]->points;
                $quals  = $quals +  $a->criteria[$j]->qualpoints;
            }
            #UP INDEX
            $indexScore++;
        }
        $p['pointsCri'] = $points;
        $p['pointsMe'] = $quals;

        /**CALCULATE SCORE**/
        
        /**CALCULATE PENAL**/
        $penal = 0;
        $indexPenal = 1;
        if(is_array($p['penals'])){
            while($indexPenal <= count($p['penals'])){
                $a = $p['penals'][$indexPenal-1];
                if(is_array($a->judgements)){
                    $pointByPenal = doubleval($a->points);
                    $incidence = count($a->judgements);
                    $penal = $penal + ($pointByPenal * $incidence);
                }
                
                #UP INDEX
                $indexPenal++;
            }
        }
        

        $p['penalsMe'] = $penal;
        /**CALCULATE PENAL**/

        $resta = ($p['pointsMe'] - $p['penalsMe'] );

        if($resta < 0){
            $resta = 0;
        }

        $porcent = (($resta * 100) /  $totalCategoryPoints);

        $p['porcentMe'] = round($porcent, 2);

        $p['pointsReal'] = $resta;

        unset($p['score']);
        unset($p['penals']);

        array_push($participationsRank, $p);
    }

    $rankingOrdenado = array();

    /**ORDENANDO ARRAY**/
    usort($participationsRank, Helper\build_sorter('porcentMe', 'desc'));
    foreach($participationsRank as $p => $pa){
        array_push($rankingOrdenado, $pa);
    }
    /**ORDENANDO ARRAY**/

    /**RECOLECTANDO POR CATEGORIA**/
    $rankingByCats = array();
    foreach($catsbyevent as $cat){
        $new = $cat;
        $new->participations = array();
        $p = array_map(function($e) use ($cat){
            if( $cat->id == strval($e['category']) ){
                return $e;
            }            
        }, $rankingOrdenado);

        $new->participations = array_filter($p, function($e){
            return !is_null($e);
        });

        $kill = array();
        $iindex = 0;
        foreach($new->participations as $ik => $kl){
            $kill[$iindex] = $kl;
            $iindex++;
        }

        $new->participations = $kill;

        $top3 = array_slice($kill, 0, 3);

        $new->top3 = $top3;

        array_push($rankingByCats, $new);

    }
    /**RECOLECTANDO POR CATEGORIA**/

    #RETURN DATA
    $dataeventResult->fulltext = json_decode($dataeventResult->fulltext);
    $top3 = array_slice($rankingOrdenado, 0, 3);
    $response = array(
        "event" => $dataeventResult,
        "all"   => $rankingOrdenado,
        "top3"  => $top3,
        "categories" => $rankingByCats,
    );

    return new Response(json_encode($response), 200);
});

?>