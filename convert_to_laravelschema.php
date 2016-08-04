<?php

$db_host = "localhost";
$db_port = "3306";
$db_database = "";
$db_username = "root";
$db_password = "";
$output_dir = "C:\wamp\www\migrations\\";


if(!file_exists($output_dir)){
    exit($output_dir.' Directory Not Found!');
}

$mysqli = new mysqli($db_host, $db_username , $db_password, $db_database);

if(!$mysqli){
    exit('Database Can Not Connected!');
}


function laravel_table_schema($structure){
    $method = "";
    $para = strpos($structure->COLUMN_TYPE, '(');
    $type = $para > -1 ? substr($structure->COLUMN_TYPE, 0, $para) : $structure->COLUMN_TYPE;
    $numbers = "";
    $nullable = $structure->IS_NULLABLE == "NO" ? "" : "->nullable()";
    $default = empty($structure->COLUMN_DEFAULT) ? "" : "->default(\"{$structure->COLUMN_DEFAULT}\")";
    $unsigned = strpos($structure->COLUMN_TYPE, "unsigned") === false ? '' : '->unsigned()';
    $unique = $structure->COLUMN_KEY == 'UNI' ? "->unique()" : "";
    $choices = '';
    switch ($type) {
        case 'enum':
            $method = 'enum';
            $choices = preg_replace('/enum/', 'array', $structure->COLUMN_TYPE);
            $choices = ", $choices";
            break;
        case 'int' :
            $method = 'unsignedInteger';
            break;
        case 'bigint' :
            $method = 'bigInteger';
            break;
        case 'samllint' :
            $method = 'smallInteger';
            break;
        case 'char' :
        case 'varchar' :
            $para = strpos($structure->COLUMN_TYPE, '(');
            $numbers = ", " . substr($structure->COLUMN_TYPE, $para + 1, -1);
            $method = 'string';
            break;
        case 'float' :
            $method = 'float';
            break;
        case 'double':
        case 'decimal' :
            $para = strpos($structure->COLUMN_TYPE, '(');
            $numbers = ", " . substr($structure->COLUMN_TYPE, $para + 1, -1);
            $method = 'decimal';
            break;
        case 'tinyint' :
            if ($structure->COLUMN_TYPE == 'tinyint(1)') {
                $method = 'boolean';
            } else {
                $method = 'tinyInteger';
            }
            break;
        case 'date':
            $method = 'date';
            break;
        case 'timestamp' :
            $method = 'timestamp';
            break;
        case 'datetime' :
            $method = 'dateTime';
            break;
        case 'mediumtext' :
            $method = 'mediumtext';
            break;
        case 'longtext':
        case 'text' :
            $method = 'text';
        case 'longblob' :
        case 'blob' :
            $method = 'binary';
            break;

    }
    if ($structure->COLUMN_KEY == 'PRI') {
        $method = 'increments';
    }
    if($method == ''){
        echo 'Error!! Column '.$structure->COLUMN_NAME.'('.$type.') not found.<br>'.PHP_EOL;
    }
    return "            $" . "table->{$method}('{$structure->COLUMN_NAME}'{$choices}{$numbers}){$nullable}{$default}{$unsigned}{$unique};".PHP_EOL;
}

$sql = "SHOW TABLES";
$result = $mysqli->query($sql);
while($tables = $result->fetch_row()){

    $filename = date('Y_m_d_His') . "_create_" . $tables[0] . "_table.php";
    $fp = fopen($output_dir.$filename, 'w');

    $content = "<?php".PHP_EOL.PHP_EOL.
    "use Illuminate\Database\Schema\Blueprint;".PHP_EOL.
    "use Illuminate\Database\Migrations\Migration;".PHP_EOL.PHP_EOL.
    "class CreateUsersTable extends Migration".PHP_EOL.
    "{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ";

    fwrite($fp, $content);
    $content = 'Schema::create(\''.$tables[0].'\', function (Blueprint $table) {'.PHP_EOL;

    $sql = 'SELECT * FROM information_schema.columns WHERE table_schema="'.$db_database.'" AND table_name="'.$tables[0].'"';
    $table_structure_query = $mysqli->query($sql);
    while($table_structure = $table_structure_query->fetch_object()){
       $content.= laravel_table_schema($table_structure);
    }
    $content.= '        }'.PHP_EOL;

    $content.= "    }

    public function down(){
        Schema::drop('".$tables[0]."');
    }".PHP_EOL.
    "}";
    fwrite($fp, $content);
    fclose($fp);
    echo $output_dir.$filename.' created successfully<br>'.PHP_EOL;
}
