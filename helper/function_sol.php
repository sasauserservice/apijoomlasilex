<?php

namespace Helper;

function GadgetSol($termino){
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

function loopGadgeSol($termino, $table='',$ciclos=0){

    $response = array();

    $retorno1er = GadgetSol($termino);

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
        $result = GadgetSol($element);
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