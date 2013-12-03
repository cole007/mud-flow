<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>ExpressionEngine SQL dumper</title>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css">
</head>
<body>
     <div class="container">
        <div class="row">
            <h1>ExpressionEngine SQL dumper</h1>
<?php
    if (count($_POST) > 0) {
        $system_path = '';
        if ($system_path == '')
    	{
    		$system_path = pathinfo(__FILE__, PATHINFO_DIRNAME);
    	}
    	
    	
    	if (realpath($system_path) !== FALSE)
    	{
    		$system_path = realpath($system_path).'/';
        }
        
        $system_path = rtrim($system_path, '/');
        
        // assumes current file path is /system/.db/index.php
        $system_path = substr($system_path, 0, strrpos($system_path,'/'));
        // assumes we want to save SQL dumps to /.db/
        $dir = '../../.db';
        // ensure there's a trailing slash
    	$system_path .= '/';
    	
    	define('BASEPATH', str_replace("\\", "/", $system_path.'codeigniter/system/'));
        
        
        include_once($system_path.'expressionengine/config/environment.php');
        include_once($system_path.'expressionengine/config/database.php');
        
        $mysqli = new mysqli($db['expressionengine']['hostname'], $db['expressionengine']['username'], $db['expressionengine']['password'], $db['expressionengine']['database']);
        
        /* check connection */
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }
        echo '<table class="table table-striped">';
        /* return name of current default database */
        if ($result = $mysqli->query("SHOW TABLE STATUS FROM `".$db['expressionengine']['database']."`")) {
            while($row = $result->fetch_row()) {
                
                $output = "DROP TABLE IF EXISTS `".$row[0]."`;\n";
                $output .= "CREATE TABLE `".$row[0]."` (";
                $file = $dir.'/'.$row[0].'.sql';
                $handle = fopen($file, "w+");
                $cols = $mysqli->query("SHOW COLUMNS FROM ".$row[0]);
                while($rowCols = $cols->fetch_row()) {    
                    $fields[] =  '`'.$rowCols[0].'`';
                    $output .= "`".$rowCols[0]."` ".$rowCols[1];
                    if ($rowCols[2] == 'NO') $output .= " NOT NULL";
                    if ($rowCols[3] == 'PRI') $primary[] = "`".$rowCols[0]."`";
                    if ($rowCols[3] == 'MUL') $keys[] = "KEY `".$rowCols[0]."` (`".$rowCols[0]."`)";
                    if ($rowCols[4] != '') $output .= " DEFAULT '".$rowCols[4]."'";
                    if ($rowCols[5] != '') $output .= strtoupper($rowCols[4]);
                    $output .= ",";
                }
                
                if (isset($primary)) $output .= " PRIMARY KEY (".implode($primary,',')."),";
                if (isset($keys)) $output .= " ".implode($keys,', ');
                $output = substr($output, 0, -1);
                $output .= ')';
                $output .= ' ENGINE='.$row[1] . " DEFAULT CHARSET=utf8;\n";
                
                echo '<tr><td>';
                if (fwrite($handle, $output)) echo $row[0];
                echo '</td><td>';
                $data = $mysqli->query("SELECT * FROM ".$row[0]);
                while($dataCols = $data->fetch_row()) {    
                    foreach ($dataCols as  $value) {
                        // is a string (including decimal integers)
                        if (strpos($value,'.') === false && is_numeric($value)) $dump[] = $value;
                        else $dump[] = "'".$value."'";
                    } 
                    $dataVals[] = implode($dump,',');
                    unset($dump);
                }
                
                if (isset($dataVals)) {
                    $insert = "INSERT INTO `exp_accessories` (".implode($fields,',').") VALUES \n\t(";
                    $insert .= implode($dataVals,",\n\t");
                    $insert .= ");\n";
                    // echo $insert;
                    if (fwrite($handle, $insert)) echo count($dataVals)." rows added";
                }
                echo '</td></tr>';
                unset($primary);
                unset($keys);
                unset($fields);
                unset($dataVals);
                
                fclose($handle);    
            }
            $result->close();
        }  
        echo '</table>';
    
    } else {
        ?>
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" role="form">
            <p>Click 'Go' to generate the SQL files</p>
            <input type="hidden" name="act" value="sql" />
            <button type="submit" class="btn btn-default">Go</button>
        </form>
        <?
    }
    
?>      
        </div>
     </div>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.2/js/bootstrap.min.js"></script>
</body>
</html>