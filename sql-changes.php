<?php

function processLineSection($lines = array()){
	$query = implode("\n\t", $lines);

	if(preg_match("/^ALTER TABLE \[dbo\].\[([A-z0-9]+)\] (ADD|DROP) CONSTRAINT \[([A-z0-9\_]+)\]/", $lines[0], $matches)){

		$tableName = $matches[1];
		$type = $matches[2];
		$constraint = $matches[3];
		$constraintLookupLocation;

		// type is foreign key
		if(preg_match("/^FK_/", $constraint)){
			$constraintLookupLocation = "REFERENTIAL_CONSTRAINTS";
		}else if(preg_match("/^PK_/", $constraint)){
			$constraintLookupLocation = "TABLE_CONSTRAINTS";
		}else{
			throw new Error("invalid type $constraint");
		}

		if($type == "ADD"){

$out = "IF NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME='$constraint')
BEGIN
	$query;
END";
		}else if($type == "DROP"){

	$out = "IF EXISTS(SELECT * FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME='$constraint')
BEGIN
	$query;
END";

		}


		return $out;
	}

	if(preg_match("/^CREATE TABLE \[dbo\].\[([A-z0-9]+)\]/", $lines[0], $matches)){

		$tableName = $matches[1];

		$cols = array();
		foreach($lines as $k => $v){
			if(preg_match("/\[([A-z0-9\_]+)\]\ \[[A-z0-9]+\].*?,$/", $v, $matches)){
				array_push($cols, $matches[1]);
			}
		}
		
$out = "IF NOT EXISTS(SELECT * 
                 FROM INFORMATION_SCHEMA.TABLES 
                 WHERE  TABLE_NAME = '$tableName')
BEGIN
		$query;
END";
		return $out;
	}

	if(preg_match("/DROP TABLE \[dbo\]\.\[([A-z0-9\_]+)\]$/", $lines[0], $matches)){

		$tableName = $matches[1];
		$out = "IF EXISTS(SELECT * 
                 FROM INFORMATION_SCHEMA.TABLES 
                 WHERE  TABLE_NAME = '$tableName')
BEGIN
		$query;
END";
		return $out;
	}

	if(preg_match("/^ALTER TABLE \[dbo\].\[([A-z0-9\_]+)\] ALTER COLUMN \[([A-z0-9\_]+)\] \[varchar\] \(([0-9]+)\) (COLLATE [A-z0-9\_]+)* (NULL)*/", $lines[0], $matches)){

			$table = $matches[1];
			$col = $matches[2];
			$varcharLen = $matches[3];
			$collate = $matches[4];
			$nullable = $matches[5];
			
			if($nullable == ""){
				$isNullable = "NO";
			}else{
				$isNullable = "YES";
			}

			$out = "IF NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$table' AND COLUMN_NAME='$col' AND DATA_TYPE='varchar' AND CHARACTER_MAXIMUM_LENGTH='$varcharLen' AND IS_NULLABLE = '$isNullable')
			BEGIN 
				$query;
			END";

			return $out;
	}

	return $query;
}

$buff = array();
$segments = array();

do{
	$line = trim(fgets(STDIN));

	if($line == "GO" && count($buff) > 0){
		array_push($segments, processLineSection($buff));
		$buff = array();
	}
	
	if($line != "" && $line != "GO") // skip blank lines
		array_push($buff, $line);

} while($line != ".");

$fname = tempnam("/tmp", "sql-") . ".sql";
$h = fopen($fname, "w");

fwrite($h, "\n");
fwrite($h, "--- GENERATED SQL\n\n");
fwrite($h, implode("\nGO\n\n", $segments));
fwrite($h, "\n\n--- END\n\n");

fclose($h);

exec("open $fname");

