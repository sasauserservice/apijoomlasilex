<?
use Helper\AuthHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Model\Auth AS AuthModel;

$site->get('/applicationdata', function(Request $request) use ($database, $dbo){
    #Groups of users...............
    $sql = <<<SQL
    SELECT joomla.* from DBLINK('dbname=apijoomla', 'SELECT id, title from sasa_usergroups') AS joomla(id bigint, title text) UNION SELECT id, title from sasa_usergroups order by id
    SQL;
    $rs = $database->query($sql);
    $rs->setFetchMode(PDO::FETCH_ASSOC);
    $result = $rs->fetchAll();
    #Groups of users...............

    $response = array(
        "groups" => $result,
    );
    
    return new Response(json_encode($response), 200);
});