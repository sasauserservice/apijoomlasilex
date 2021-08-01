CREATE FUNCTION submitjudgemain(mode INT, entry UUID, datageneral JSON, datapenal JSON, judge BIGINT) RETURNS VOID AS
$$
BEGIN
	SELECT testfunc(mode, entry, datageneral);
	SELECT testfuncpenalty(mode, entry, datapenal);
END
$$
LANGUAGE 'plpgsql';





-----------------------------

CREATE FUNCTION testfunc(mode INT, entry UUID, datageneral JSON, judge BIGINT) RETURNS int AS
$$
BEGIN
	IF mode = 1 THEN
		INSERT INTO SASA_MATCH_CORE_SCORE_GENERAL_FINALLY(judge, data, entry) VALUES (judge, datageneral, entry);
		RETURN 1;
	ELSE
		UPDATE SASA_MATCH_CORE_SCORE_GENERAL_FINALLY SET data = data WHERE entry = entry;
		RETURN 1;
	END IF;
END
$$
LANGUAGE 'plpgsql';


--SELECT submitjudgemain(1, '835612e0-90d1-48c6-b963-a7312d230113', '[{"name":"PARAMETRO 2","criteria":[{"title":"Sonrisa","points":5,"qualpoints":2},{"title":"Amor","points":5,"qualpoints":2},{"title":"Comprension","points":5,"qualpoints":1}],"total":15,"qualtotal":5},{"name":"Sonrrisa","criteria":[{"title":"r","points":1,"qualpoints":1}],"total":1,"qualtotal":1},{"name":"Tecnica","criteria":[{"title":"Tecnica","points":6,"qualpoints":3}],"total":6,"qualtotal":3},{"name":"Actitud","criteria":[{"title":"Actitud","points":5,"qualpoints":4}],"total":5,"qualtotal":4},{"name":"RUN 1 PARAM 2","criteria":[{"title":"Crit 2 a","points":5,"qualpoints":2},{"title":"Crit 2 b","points":5,"qualpoints":2}],"total":10,"qualtotal":4}]', '[{"id":"8","name":"TEST PENALTY 1","points":"10"},{"id":"26","name":"TEST PENAL 2","points":"5"}]', '0');

----------------------


testfunc

BEGIN
	IF mode = 1 THEN
		INSERT INTO SASA_MATCH_CORE_SCORE_GENERAL_FINALLY(judge, data, entry) VALUES (judge, data, entry);
	ELSE
		UPDATE SASA_MATCH_CORE_SCORE_GENERAL_FINALLY SET data = data WHERE entry = entry;
	END IF;
END

testfuncpenalty

BEGIN
	IF mode = 1 THEN
		INSERT INTO SASA_MATCH_CORE_SCORE_PENALTY_FINALLY(judge, data, entry) VALUES (judge, data, entry);
	ELSE
		UPDATE SASA_MATCH_CORE_SCORE_PENALTY_FINALLY SET data = data WHERE entry = entry;
	END IF;
END


submitjudgemain

BEGIN
	SELECT testfunc(mode, entry, datageneral, judge);
	SELECT testfuncpenalty(mode, entry, datapenal, judge);
END


SELECT testfunc(2, '835612e0-90d1-48c6-b963-a7312d230113', '[{"name":"PARAMETRO 2","criteria":[{"title":"Sonrisa","points":5,"qualpoints":2},{"title":"Amor","points":5,"qualpoints":2},{"title":"Comprension","points":5,"qualpoints":1}],"total":15,"qualtotal":5},{"name":"Sonrrisa","criteria":[{"title":"r","points":1,"qualpoints":1}],"total":1,"qualtotal":1},{"name":"Tecnica","criteria":[{"title":"Tecnica","points":6,"qualpoints":3}],"total":6,"qualtotal":3},{"name":"Actitud","criteria":[{"title":"Actitud","points":5,"qualpoints":4}],"total":5,"qualtotal":4},{"name":"RUN 1 PARAM 2","criteria":[{"title":"Crit 2 a","points":5,"qualpoints":2},{"title":"Crit 2 b","points":5,"qualpoints":3}],"total":10,"qualtotal":4}]', 0);

CREATE FUNCTION submitjudgemain(mode INT, entryid UUID, datageneral JSON, datapenal JSON, judge BIGINT) RETURNS int[] AS
$$
DECLARE
	response1 integer := 0;
	response2 integer := 0;
BEGIN
	IF mode = 1 THEN
		SELECT testfunc(1, entryid, datageneral, judge) into response1;
		SELECT testfuncpenalty(1, entryid, datapenal, judge) into response2;
		RETURN array[response1, response2];
	ELSE
		
	END IF;
END
$$
LANGUAGE 'plpgsql'; 


-------------------------------------

SELECT submitjudgemain(1, '835612e0-90d1-48c6-b963-a7312d230113', '[{"name":"PARAMETRO 2","criteria":[{"title":"Sonrisa","points":5,"qualpoints":2},{"title":"Amor","points":5,"qualpoints":2},{"title":"Comprension","points":5,"qualpoints":1}],"total":15,"qualtotal":5},{"name":"Sonrrisa","criteria":[{"title":"r","points":1,"qualpoints":1}],"total":1,"qualtotal":1},{"name":"Tecnica","criteria":[{"title":"Tecnica","points":6,"qualpoints":3}],"total":6,"qualtotal":3},{"name":"Actitud","criteria":[{"title":"Actitud","points":5,"qualpoints":4}],"total":5,"qualtotal":4},{"name":"RUN 1 PARAM 2","criteria":[{"title":"Crit 2 a","points":5,"qualpoints":2},{"title":"Crit 2 b","points":5,"qualpoints":2}],"total":10,"qualtotal":4}]', '[{"id":"8","name":"TEST PENALTY 1","points":"10"},{"id":"26","name":"TEST PENAL 2","points":"5"}]', '0');

--------------------------------------




CREATE FUNCTION submitrank(eventid BIGINT) RETURNS SASA_MATCH_MODULES_PARTICIPATIONS AS
$$
DECLARE
	caja SASA_MATCH_MODULES_PARTICIPATIONS[];
BEGIN
	SELECT * INTO caja FROM SASA_MATCH_MODULES_PARTICIPATIONS WHERE event_id = eventid;
	RETURN caja;
END
$$
LANGUAGE 'plpgsql'; 