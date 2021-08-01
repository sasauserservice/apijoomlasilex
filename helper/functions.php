<?php

namespace Helper;


function build_sorter($clave, $dire='asc') {
    return function ($a, $b) use ($clave, $dire) {
        if( $a[$clave] == $b[$clave]){
            return 0;
        }
        if($dire == 'desc'){
            return ($a[$clave] > $b[$clave]) ? -1 : 1;
        } else {
            return ($a[$clave] < $b[$clave]) ? -1 : 1;
        }
    };
}


function get_event_by_entry($entry){
    global $database;
    $query = <<<SQL
    SELECT event_id from sasa_match_modules_participations where id = '{$entry}';
    SQL;
    $rs = $database->query($query);
    $rsfetch = array();
    if(!is_null($rs)){
        $rs->setFetchMode(\PDO::FETCH_ASSOC);
        $rsfetch = $rs->fetch();
    }
    
    return $rsfetch;
}

function get_serial($table, $id){
    global $dbo;
    $query = <<<SQL
    SELECT serial from {$table} where id = '{$id}';
    SQL;
    $rs = $dbo->setQuery($query)->loadResult();

    return $rs;
}

function get_serialExtender($table, $id){
    global $database;
    $query = <<<SQL
    SELECT serial from {$table} where id = '{$id}';
    SQL;
    $qs = $database->query($query);
    if(is_null($qs)){
        return null;
    }
    $ps = $qs->fetchColumn();
    return $ps;
}



/* FUNCION CICLO DE BUSCADOR GLOBAL */
/* FUNCION CICLO DE BUSCADOR GLOBAL */
/* FUNCION CICLO DE BUSCADOR GLOBAL */
/* FUNCION CICLO DE BUSCADOR GLOBAL */
/* FUNCION CICLO DE BUSCADOR GLOBAL */
/* FUNCION CICLO DE BUSCADOR GLOBAL */
/* FUNCION CICLO DE BUSCADOR GLOBAL */
/* FUNCION CICLO DE BUSCADOR GLOBAL */


function loopGadget($termino, $table='',$ciclos=0){

    $response = array();

    $retorno1er = Gadget($termino);

    $response[0]['result'] = $retorno1er;

    echo "/*/*TERMINO DE BUSQUEDA INICIO/*/*\n";
    echo "/*/*TERMINO DE BUSQUEDA INICIO/*/*\n";
    var_dump($retorno1er);

    echo "/*/*TERMINO DE BUSQUEDA FIN/*/*\n";
    echo "/*/*TERMINO DE BUSQUEDA FIN/*/*\n\n";


    echo "----------  SEPARADOS   ----------\n\n";

    $temporalArray = array();

    foreach($retorno1er as $k=>$element){
        echo "TABLA {$element['tabla']} \n";
        echo "CAMPO {$element['campo']} \n";
        echo "VALUE {$element['value']} \n\n";    
        array_push($temporalArray, $element['value']);    
    }

    echo "SON ".count($temporalArray)." \n\n";
    echo "----------  SEPARADOS ONLY VALUES   ----------\n\n";

    $result = array();

    foreach($temporalArray as $k=>$element){
        $result = Gadget($element);
        array_push($response, [
            $k => [
                "result" => $result,
            ]
        ]);  
    }

    foreach($response as $k=>$element){
        echo "CAPA ".($k+1)."\n";
        var_dump($element);
        echo " \n\n";
        //echo "CAMPO {$element['campo']} \n";
        //echo "VALUE {$element['value']} \n\n";    
        //echo "VALUE {$element['value']} \n\n";    
        //array_push($temporalArray, $element['value']);    
    }

    return '';
}




    /* FUNCION DE BUSCADOR GLOBAL */
    /* FUNCION DE BUSCADOR GLOBAL */
    /* FUNCION DE BUSCADOR GLOBAL */
    /* FUNCION DE BUSCADOR GLOBAL */
    /* FUNCION DE BUSCADOR GLOBAL */
    /* FUNCION DE BUSCADOR GLOBAL */
    /* FUNCION DE BUSCADOR GLOBAL */
    /* FUNCION DE BUSCADOR GLOBAL */







    function Gadget($termino){
        global $dbo;
        global $database;

        $retorno = array();

        $bitserial = $termino;
        $bitserial2 = $termino;

        echo "/*/*TERMINO DE BUSQUEDA INICIO/*/*\n";
        echo "/*/*TERMINO DE BUSQUEDA INICIO/*/*\n";
        var_dump($bitserial);
        echo "/*/*TERMINO DE BUSQUEDA FIN/*/*\n";
        echo "/*/*TERMINO DE BUSQUEDA FIN/*/*\n";

        echo "\n\n\n\n\n";

        $queryFunc = <<<SQL
        SELECT * FROM search_whole_db('{$bitserial}');
        SQL;

        $queryOtradb = $dbo->setQuery($queryFunc)->loadObjectList();

        $paneles = array();
        echo "// DB JOOMLA CONTEO \n";
        echo "// DB JOOMLA CONTEO \n";
        echo "// DB JOOMLA CONTEO \n";
        echo "// DB JOOMLA CONTEO \n";
        var_dump($queryOtradb);
        echo "// DB JOOMLA CONTEO FIN \n";
        echo "// DB JOOMLA CONTEO FIN \n";
        echo "// DB JOOMLA CONTEO FIN \n";
        echo "// DB JOOMLA CONTEO FIN \n";

        echo "\n\n";

        $variable_busqueda = [];

        foreach($queryOtradb as $popl){
            $fg = <<<SQL
            SELECT * from {$popl->_tbl} where ctid = '{$popl->_ctid}';
            SQL;
            echo "\n\n\n";    
            echo  "//// DB JOOMLA COINCIDENCIAS \n";
            echo  "//// DB JOOMLA COINCIDENCIAS \n";
            echo  "//// DB JOOMLA COINCIDENCIAS \n";
            echo  "//// DB JOOMLA COINCIDENCIAS \n\n";

            echo "/*/*QUERY START/*/*\n";
            $gh = $dbo->setQuery($fg)->loadAssoc();

            echo $fg."\n";
            echo "/*/*QUERY FIN/*/*\n\n";     

            /*/* ESTO ME MUESTRA EL ARRAY */         
            var_dump($gh);
            /*/* ESTO ME MUESTRA EL ARRAY */

            echo "\n";
            echo "**VALORES EXACTOS** \n";

            foreach($gh as $lkj => $elk){

                $varibleBusqueda = array();
                if( str_contains($elk, $bitserial2) ){
                    $varibleBusqueda['tabla'] = $popl->_tbl;
                    $varibleBusqueda['encontrado'] = $lkj;
                    $varibleBusqueda['encontradoValor'] = $elk;
                    $varibleBusqueda['esebuscado'] = $gh['serial'];
                    echo "TABLA {$popl->_tbl} \n";
                    echo "ENCONTRADO {$lkj} VALOR {$elk} \n";
                    echo "ESEBUSCADO SERIAL VALOR {$gh['serial']} \n";
                    echo "\n\n";
                }

                if( str_contains($gh['serial'], $bitserial2) == false ){
                    if(count($varibleBusqueda) > 0){
                        array_push($variable_busqueda, $varibleBusqueda);
                    }
                }

            } 

            echo "**OTROS VALORES** \n";

            foreach($gh as $lkj => $elk){


                if( strlen($elk) == 36 ){
                    echo "CAMPO {$lkj} VALOR {$elk} \n";
                    $varibleBusqueda['tabla'] = $popl->_tbl;
                    $varibleBusqueda['encontrado'] = $lkj;
                    $varibleBusqueda['encontradoValor'] = $elk;
                    $varibleBusqueda['esebuscado'] = $elk;
                    array_push($variable_busqueda, $varibleBusqueda);
                }
            }
            echo "\n";
            echo  "//// DB JOOMLA COINCIDENCIAS END \n";
            echo  "//// DB JOOMLA COINCIDENCIAS END \n";
            echo  "//// DB JOOMLA COINCIDENCIAS END \n";
            echo  "//// DB JOOMLA COINCIDENCIAS END \n\n";

        }

        $respuesta = $database->query($queryFunc);

        if( !is_null($respuesta) ){
            $respuesta->setFetchMode(\PDO::FETCH_ASSOC);
            $rs = $respuesta->fetchAll();

            echo "\n\n\n\n";

            echo "// DB EXTERNAL CONTEO \n";
            echo "// DB EXTERNAL CONTEO \n";
            echo "// DB EXTERNAL CONTEO \n";
            echo "// DB EXTERNAL CONTEO \n";
            var_dump($rs);
            echo "// DB EXTERNAL CONTEO FIN \n";
            echo "// DB EXTERNAL CONTEO FIN \n";
            echo "// DB EXTERNAL CONTEO FIN \n";
            echo "// DB EXTERNAL CONTEO FIN \n";

            echo "\n\n";




            foreach($rs as $popl){
                $fg = <<<SQL
                SELECT * from {$popl["_tbl"]} where ctid = '{$popl["_ctid"]}';
                SQL;

                echo  "//// DB EXTERNAL COINCIDENCIAS \n";
                echo  "//// DB EXTERNAL COINCIDENCIAS \n";
                echo  "//// DB EXTERNAL COINCIDENCIAS \n\n";

                echo "/*/*QUERY START/*/*\n";
                $gh = $database->query($fg)->fetch(\PDO::FETCH_ASSOC);
                echo $fg."\n";
                echo "/*/*QUERY FIN/*/*\n\n";              
                var_dump($gh);
                echo "\n\n";

                foreach($gh as $lkj => $elk){
                    $varibleBusqueda = array();
                    if( str_contains($elk, $bitserial2) ){
                        echo "TABLA {$popl["_tbl"]}\n";
                        echo "ENCONTRADO {$lkj} VALOR {$elk} \n";
                        echo "ESEBUSCADO SERIAL VALOR {$gh['serial']} \n";
                        echo "OTROS VALORES \n";

                        $varibleBusqueda['tabla'] = $popl["_tbl"];
                        $varibleBusqueda['encontrado'] = $lkj;
                        $varibleBusqueda['encontradoValor'] = $elk;
                        $varibleBusqueda['esebuscado'] = $gh['serial'];
                    }

                    if( strlen($elk) == 36 ){
                        echo "CAMPO {$lkj} VALOR {$elk} \n";
                        $varibleBusqueda['tabla'] = $popl['_tbl'];
                        $varibleBusqueda['encontrado'] = $lkj;
                        $varibleBusqueda['encontradoValor'] = $elk;
                        $varibleBusqueda['esebuscado'] = $elk;
                        array_push($variable_busqueda, $varibleBusqueda);
                    }

                    if( str_contains($gh['serial'], $bitserial2) == false ){
                        if(count($varibleBusqueda) > 0){
                            array_push($variable_busqueda, $varibleBusqueda);
                        }
                    }
                }
                echo "\n";
                echo  "//// DB EXTERNAL COINCIDENCIAS END \n";
                echo  "//// DB EXTERNAL COINCIDENCIAS END \n";
                echo  "//// DB EXTERNAL COINCIDENCIAS END \n";
                echo  "//// DB EXTERNAL COINCIDENCIAS END \n\n";
            }

            echo "\n\n\n\n\n\n\n\n\n\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/* VARIABLES DE BUSQUEDA EN LISTA START \n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "\n\n\n\n\n\n\n\n\n\n";


            foreach($variable_busqueda as $vrs){
            //echo "----------------------------------------- \n";
                echo "VALOR REGISTRO SERIAL {$vrs['esebuscado']} \n";
                echo "TABLA {$vrs['tabla']} \n";
                echo "ENCONTRADO {$vrs['encontrado']} VALOR {$vrs['encontradoValor']} \n";



                echo "\n\n";

                $index = array_search($vrs['esebuscado'], array_column($retorno,'value'));

                if($termino != $vrs['esebuscado']){
                    array_push($retorno, [
                        "tabla" => $vrs['tabla'],
                        "campo" => $vrs['encontrado'],
                        "value" => $vrs['esebuscado']
                    ]); 
                }


            }

            foreach($retorno as $l=>$p){
                $klves = array_keys($p);
                print_r($klves);
                echo "0000";
            }

            echo "\n\n\n\n\n\n\n\n\n\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/* VARIABLES DE BUSQUEDA EN LISTA END \n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "/*/*/*/*\n";
            echo "\n\n\n\n\n\n\n\n\n\n";

            /***VARIABLE DE RETORNO***/

            foreach($variable_busqueda as $vrsult){
                $index = array_search($vrsult['esebuscado'], array_column($retorno, 'value'));

            }

            return $retorno;
        }
    }
    /* FUNCION DE BUSCADOR GLOBAL END */
    /* FUNCION DE BUSCADOR GLOBAL END */
    /* FUNCION DE BUSCADOR GLOBAL END */
    /* FUNCION DE BUSCADOR GLOBAL END */
    /* FUNCION DE BUSCADOR GLOBAL END */
    /* FUNCION DE BUSCADOR GLOBAL END */
    /* FUNCION DE BUSCADOR GLOBAL END */
    /* FUNCION DE BUSCADOR GLOBAL END */


    function EntryTo($serial, $id=""){
        global $dbo;
        global $database;

        $retorno = array();

        $quienSoy = ThatIAm($serial, $id);

        if(count($quienSoy['general']) > 0){
            $catsBySerialUserGeneral = <<<SQL
            select array_to_json(
                array(
                    select  
                    cats.serial
                    from sasa_match_core_panels as panel
                    cross join json_array_elements(panel.generaljudge) slots_general
                    cross join json_array_elements(slots_general->'params') myparams
                    left join sasa_match_core_parameters as params
                    on params.id = (myparams->>'id')::int
                    left join sasa_match_core_panels_categories as panelcat
                    on panelcat.panelid = panel.id
                    left join sasa_match_core_categories_parameters as catpar
                    on catpar.categoria = panelcat.categoryid
                    and catpar.parametro = params.id
                    left join sasa_match_core_categories as cats
                    on cats.serial = panelcat.category_serial
                    where slots_general->>'general' like '%{$serial}'
                )
            ) as categorias;
            SQL;
            $resutlCatsBySerialUserGeneral = $dbo->setQuery($catsBySerialUserGeneral)->loadAssoc();
            $categoriasEnLasQueEstoyComoGeneral = json_decode($resutlCatsBySerialUserGeneral['categorias']);
            $categoriasEnLasQueEstoyComoGeneralRaw = array();
            array_walk($categoriasEnLasQueEstoyComoGeneral, function($e) use (&$categoriasEnLasQueEstoyComoGeneralRaw){
                $modeQuotes = str_replace('"', "'", json_encode($e));
                if(!in_array($modeQuotes, $categoriasEnLasQueEstoyComoGeneralRaw)){
                    array_push($categoriasEnLasQueEstoyComoGeneralRaw, $modeQuotes);
                }
            });
            
            $categoriasEnLasQueEstoyComoGeneralRawToQuery = implode(',', $categoriasEnLasQueEstoyComoGeneralRaw);
            #querys by competitor.........................
            $queryP = <<<SQL
            SELECT 
            TO_CHAR(PARTI.created::DATE, 'Mon dd, yyyy') CREATED, 
            PARTI.id, 
            PARTI.event_id, 
            PARTI.category_id, 
            PARTI.data->>'video_url' video, 
            COMP.type,
            COMP.data->>'name' as team_name, COMP.id team_id
            FROM sasa_match_competitor as COMP 
            left join sasa_match_modules_participations as PARTI
            on PARTI.participant_serial = COMP.serial
            where '{$serial}' = any(comp.manager)
            and PARTI.category_serial IN ({$categoriasEnLasQueEstoyComoGeneralRawToQuery});
            SQL;

            $entriesTOgeneral = $database->query($queryP);
            $resultEntriesToGeneral = array();
            if(!is_null($entriesTOgeneral)){
                $resultEntriesToGeneral = $entriesTOgeneral->fetchAll(\PDO::FETCH_ASSOC);
            }

            array_walk($resultEntriesToGeneral, function($entry) use(&$retorno){
                $index = array_search($entry['id'], array_column($retorno, 'id'));
                if(is_bool($index)){
                    array_push($retorno, $entry);
                }
            });
        }
        
        if(count($quienSoy['penalty']) > 0){
            $catByUserPenal = <<<SQL
            select array_to_json(
                array(
                    select  
                    cats.serial
                    from sasa_match_core_panels as panel
                    cross join json_array_elements(panel.penaltyjudge) slots_penalty
                    cross join json_array_elements(slots_penalty->'params') mypenaltys
                    left join sasa_match_core_panels_categories as panelcat
                    on panelcat.panelid = panel.id
                    left join sasa_match_core_categories as cats
                    on cats.id = panelcat.categoryid
                    where slots_penalty->>'penalty' like '%{$serial}'
                )
            ) as categorias;
            SQL;
            $resutlCatsBySerialUserPenal = $dbo->setQuery($catByUserPenal)->loadAssoc();
            $categoriasEnLasQueEstoyComoPenal = json_decode($resutlCatsBySerialUserPenal['categorias']);
            $categoriasEnLasQueEstoyComoPenalRaw = array();
            array_walk($categoriasEnLasQueEstoyComoPenal, function($e) use (&$categoriasEnLasQueEstoyComoPenalRaw){
                $modeQuotes = str_replace('"', "'", json_encode($e));
                if(!in_array($modeQuotes, $categoriasEnLasQueEstoyComoPenalRaw)){
                    array_push($categoriasEnLasQueEstoyComoPenalRaw, $modeQuotes);
                }
            });

            $categoriasEnLasQueEstoyComoPenalRawToQuery = implode(',', $categoriasEnLasQueEstoyComoPenalRaw);

            #querys by competitor.........................
            $queryP = <<<SQL
            SELECT 
            TO_CHAR(PARTI.created::DATE, 'Mon dd, yyyy') CREATED, 
            PARTI.id, 
            PARTI.event_id, 
            PARTI.category_id, 
            PARTI.data->>'video_url' video, 
            COMP.type,
            COMP.data->>'name' as team_name, COMP.id team_id
            FROM sasa_match_competitor as COMP 
            left join sasa_match_modules_participations as PARTI
            on PARTI.participant_serial = COMP.serial
            where '{$serial}' = any(comp.manager)
            and PARTI.category_serial IN ({$categoriasEnLasQueEstoyComoPenalRawToQuery});
            SQL;

            $entriesTOpenalty = $database->query($queryP);
            $resultEntriesToPenalty = array();
            if(!is_null($entriesTOpenalty)){
                $resultEntriesToPenalty = $entriesTOpenalty->fetchAll(\PDO::FETCH_ASSOC);
            }

            array_walk($resultEntriesToPenalty, function($entry) use(&$retorno){
                $index = array_search($entry['id'], array_column($retorno, 'id'));
                if(is_bool($index)){
                    array_push($retorno, $entry);
                }
            });
        } 
        
        if(count($quienSoy['main']) > 0){
            $catsByUserMain = <<<SQL
            select array_to_json(
                array(
                    select  
                    cats.serial
                    from sasa_match_core_panels as panel
                    cross join json_array_elements(panel.mainjudge) as slots_main
                    left join sasa_match_core_panels_categories as panelcat
                    on panelcat.panelid = panel.id
                    left join sasa_match_core_categories as cats
                    on cats.id = panelcat.categoryid
                    where slots_main->>'user' like '%{$serial}'
                )
            ) as categorias;
            SQL;
            $resutlCatsBySerialUserMain = $dbo->setQuery($catsByUserMain)->loadAssoc();
            $categoriasEnLasQueEstoyComoMain = json_decode($resutlCatsBySerialUserMain['categorias']);
            $categoriasEnLasQueEstoyComoMainRaw = array();
            array_walk($categoriasEnLasQueEstoyComoMain, function($e) use (&$categoriasEnLasQueEstoyComoMainRaw){
                $modeQuotes = str_replace('"', "'", json_encode($e));
                if(!in_array($modeQuotes, $categoriasEnLasQueEstoyComoMainRaw)){
                    array_push($categoriasEnLasQueEstoyComoMainRaw, $modeQuotes);
                }
            });

            $categoriasEnLasQueEstoyComoMainRawToQuery = implode(',', $categoriasEnLasQueEstoyComoMainRaw);

            #querys by competitor.........................
            $queryP = <<<SQL
            SELECT 
            TO_CHAR(PARTI.created::DATE, 'Mon dd, yyyy') CREATED, 
            PARTI.id, 
            PARTI.event_id, 
            PARTI.category_id, 
            PARTI.data->>'video_url' video, 
            COMP.type,
            COMP.data->>'name' as team_name, COMP.id team_id
            FROM sasa_match_competitor as COMP 
            left join sasa_match_modules_participations as PARTI
            on PARTI.participant_serial = COMP.serial
            where '{$serial}' = any(comp.manager)
            and PARTI.category_serial IN ({$categoriasEnLasQueEstoyComoMainRawToQuery});
            SQL;

            $entriesTOmain = $database->query($queryP);
            $resultEntriesTomain = array();
            if(!is_null($entriesTOmain)){
                $resultEntriesTomain = $entriesTOmain->fetchAll(\PDO::FETCH_ASSOC);
            }

            array_walk($resultEntriesTomain, function($entry) use(&$retorno){
                $index = array_search($entry['id'], array_column($retorno, 'id'));
                if(is_bool($index)){
                    array_push($retorno, $entry);
                }
            });

        } 
        
        if(count($quienSoy['competitor']) > 0){
            #querys by competitor.........................
            $queryP = <<<SQL
            SELECT 
            TO_CHAR(PARTI.created::DATE, 'Mon dd, yyyy') CREATED, 
            PARTI.id, 
            PARTI.event_id, 
            PARTI.category_id, 
            PARTI.data->>'video_url' video, 
            COMP.type,
            COMP.data->>'name' as team_name, COMP.id team_id
            FROM sasa_match_competitor as COMP 
            left join sasa_match_modules_participations as PARTI
            on PARTI.participant_serial = COMP.serial
            where '{$serial}' = any(COMP.manager)
            SQL;
            
            $entriesTOcompetitor = $database->query($queryP);
            $resultEntriesTocompetitor = array();
            if(!is_null($entriesTOcompetitor)){
                $resultEntriesTocompetitor = $entriesTOcompetitor->fetchAll(\PDO::FETCH_ASSOC);
            }

            array_walk($resultEntriesTocompetitor, function($entry) use(&$retorno){
                $index = array_search($entry['id'], array_column($retorno, 'id'));
                if(is_bool($index)){
                    array_push($retorno, $entry);
                }
            });
        }

        return $retorno;
    }

    function ThatIAm($serial, $id=""){
        global $dbo;
        global $database;
        
        #Sabiendo en que panels estoy...
        $queryEnQuePanelEstoy = <<<SQL
        select
        array_to_json(
            array(
                select panel.serial from sasa_match_core_panels as panel 
                    cross join json_array_elements(panel.generaljudge) as each_general
                where each_general->>'general' like '%{$serial}'
                group by panel.serial
            )
        ) as panels_general,
        array_to_json(
            array(
                select panel.serial from sasa_match_core_panels as panel 
                    cross join json_array_elements(panel.penaltyjudge) as each_penal
                where each_penal->>'penalty' like '%{$serial}'
                group by panel.serial
            )
        ) as panels_penal,
        array_to_json(
            array(
                select panel.serial from sasa_match_core_panels as panel 
                    cross join json_array_elements(panel.mainjudge) as each_main
                where each_main->>'user' like '%{$serial}'
                group by panel.serial
            )
        ) as panels_main,
        array_to_json(
            array( select competitor.id from dblink('dbname=extendsystem', 'select comp.id from sasa_match_competitor as comp where ''e3f8bd7b-174c-4802-919a-752f550c5724''::text = any(comp.manager::text[])') as competitor(id uuid))
        ) as competitor
        SQL;

        $resultEnQuePanelEstoy = $dbo->setQuery($queryEnQuePanelEstoy)->loadAssoc();

        $querySiTengoCompetidoresComoManager = <<<SQL
        select * from sasa_match_competitor as comp where '{$serial}'::text = any(comp.manager::text[]);
        SQL;
        
        if(!is_null($resultEnQuePanelEstoy)){
            $as_general       = json_decode($resultEnQuePanelEstoy['panels_general']);
            $as_penal         = json_decode($resultEnQuePanelEstoy['panels_penal']);
            $as_main          = json_decode($resultEnQuePanelEstoy['panels_main']);
            $as_competitor    = json_decode($resultEnQuePanelEstoy['competitor']);

            return [
                "general" => $as_general,
                "penalty" => $as_penal,
                "main" => $as_main,
                "competitor" => $as_competitor
            ];
        }       
    }
?>