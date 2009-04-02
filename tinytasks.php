<?php
define('PHPSELF', 'tinytasks.php');
define('DELIMITER', '|');
define('END_LINE', "\n");
define('FILENAME', dirname(__FILE__).'/data/data.txt');

/* model */
class Database {
    private $data;
    private $isChanged;

    public function __construct($filename, $load_data = true)
    {
        $this->data = array();
        $this->filename = $filename;
        if(file_exists($this->filename) && $load_data)
        {
            $this->load();
        }
    }

    public function __destruct()
    {
        if($this->isChanged)
            $this->save();
    }

    private function nextId($offset = 0)
    {
        $pos = count($this->data);
        return count($this->data)+1;
        if($pos == 0)
            return 0;
        $nextId = $this->data[$pos][0] + 1 + $offset;
        if($this->findId($nextId) == false)
            return $this->nextId($offset + 1);
        else
            return $nextId;

    }
    private function findId($id)
    {
        $keys = array_keys($this->data);
        if(in_array($id, $keys))
            return true;
        else
            return false;
    }

    private function load()
    {
        $array = file($this->filename);
        if(isset($array) && is_array($array))
            foreach($array as $row)
            {
                if(strlen(trim($row)) > 0)
                {
                    $values = explode(DELIMITER, $row);
                    if(count($values) > 0)
                    {
                        $id = $values[0];
                        unset($values[0]);
                        // shift array
                        $temp_array = array();
                        foreach($values as $temp)
                        {
                            $temp_array[] = $temp;
                        }
                        $this->set($id, $temp_array);
                    }
                }

            }
        $this->isChanged = false;
    }

    private function save()
    {
        $fp = fopen($this->filename, "w");
        if($fp != NULL)
        {
            foreach($this->data as $key => $row)
            {
                if(count($row) > 0)
                {
                    $array = array($key);
                    $array = array_merge($array, $row);
                    fputs($fp, implode(DELIMITER, $array).END_LINE);
                }
            }
            fclose($fp);
        }
        $this->isChanged = false;
    }

    public function get($id)
    {
        return $this->data[$id];
    }

    public function set($id, $values)
    {   
        foreach($values as $index => $value)
        {
            $this->data[$id][$index] = $value;
        }
        $this->isChanged = true;
    }

    public function add($values)
    {
        $id = $this->nextId();
        $this->set($id, $values);
        return $id;
    }

    public function getAll()
    {
        return $this->data;
    }
};

/* controller */

if(isset($_GET) && count($_GET) > 0)
{
    if(isset($_GET['create']))
    {
        $desc = $_GET['create'];

        $db = new Database(FILENAME);
        $db->add( array('0', $desc) );
        unset($db);

        header("Content-type: text/xml");
        echo('<response>');
        echo('<id>0</id>');
        echo('<completed>0</completed>');
        echo('<desc>'.$desc.'</desc>');
        echo('</response>');
        exit;
    }
    else
    if(isset($_GET['read']))
    {
        $db = new Database(FILENAME);
        $array = $db->getAll();
        unset($db);
        header("Content-type: text/xml");
        echo('<response>');
        if(count($array) > 0)
            foreach($array as $key => $value)
            {
                echo('<item id="'.$key.'">');
                if($value[0] == TRUE)
                    echo('<completed>1</completed>');
                else
                    echo('<completed>0</completed>');
                echo('<desc>'.$value[1].'</desc>');
                echo('</item>');
            }
        echo('</response>');
        exit();
    }
    else
    if(isset($_GET['update']))
    {
        $id = $_GET['id'];
        $completed = 100;
        $desc = NULL;
        if(isset($_GET['completed']))
        {
            $completed = $_GET['completed'];
            if($completed == 'true')
                $completed = 1;
            else
                $completed = 0;
        }
        if(isset($_GET['desc']))
            $desc = $_GET['desc'];
        $values = array();
        if(isset($completed))
            $values['0'] = $completed;
        if($desc)
            $values['1'] = $desc;
        $db = new Database(FILENAME);
        $db->set($id, $values);

        header("Content-type: text/xml");
        echo('<response>');
        echo('<id>'.$id.'</id>');
        if(isset($completed))
            echo('<completed>'.$completed.'</completed>');
        if(isset($desc))
            echo('<desc>'.$desc.'</desc>');
        echo('</response>');
        exit;
    }
    else
    if(isset($_GET['delete']))
    {
        $id = $_GET['delete'];

        header("Content-type: text/xml");
        echo('<response>');
        echo('<id>'.$id.'</id>');
        echo('</response>');
        exit;
    }
}
/* view */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title></title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="jee.css" />
    <!--<meta http-equiv="refresh" content="1">-->
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript">

    function trim(str) {
        return str.replace(/^\s+|\s+$/g,"");
    }

    function ui_update_item(id, desc, stat)
    {
        var id = '#item'+id
        var node = $(id)
        if(stat == '1')
        {
            $('#todo ul.completed-tasks').prepend(node)
            node = $(id + ' label input')
            node.attr('checked','checked')
            node.unbind('click')
            node.click( markTaskIncomplete )
        } else
        {
            $('#todo ul.incompleted-tasks').prepend(node)
            node = $(id + ' label input')
            node.attr('checked','')
            node.unbind('click')
            node.click( markTaskComplete )

        }

    }

    /* create a list item and insert into correct list */
    function ui_create_item(id, desc, isCompleted, animate)
    {
        var id = 'item'+id
        var item = document.createElement('li')
        item.setAttribute('id', id)
        var label = document.createElement('label')
        var checkbox = document.createElement('input')
        checkbox.type = "checkbox"
        if(isCompleted == 1)
        {
            checkbox.setAttribute('checked', true)
            //checkbox.removeAttribute('checked')
        }
        var text = document.createTextNode(desc)
        label.appendChild(checkbox)
        label.appendChild(text)
        item.appendChild(label)
        if(isCompleted == 0)
        {
            // set task incompleted
            $('#todo ul.incompleted-tasks').prepend(item)
            $('#todo ul.incompleted-tasks li:first label input').click( markTaskComplete )
            if(animate)
                $('#todo ul.incompleted-tasks li:first').animate( { fontSize: '100%'}, 200)
        }
        else
        {
            // set task completed
            if(animate)
                item.style.fontSize = '150%'
            $('#todo ul.completed-tasks').prepend(item)
            $('#todo ul.completed-tasks li:first label input').click( markTaskIncomplete )
        }
    }
    /* build a dom structure */
    function ui_build(root)
    {
        var finished = document.createElement('ul')
        finished.className = 'completed-tasks';
        var unfinished = document.createElement('ul')
        unfinished.className = 'incompleted-tasks'
        root.append(unfinished)
        root.append(finished)
    }

    /* read all items from server */
    function read()
    {
        $.ajax({
            type: 'GET',
            url: '<?php echo(PHPSELF);?>?read',
            dataType: 'xml',
            success: function(xml) {
                $(xml).find('item').each( function() {
                    var completed = $(this).find('completed').text()
                    if(completed == '0')
                        completed = 0;
                    else if(completed == '1') 
                        completed = 1;
                    var desc = trim($(this).find('desc').text())
                    var id = $(this).attr('id')
                    if(id != undefined && desc != undefined & completed != undefined)
                        ui_create_item(id, desc, completed); 
                });
            }
        });
    }

    /* create one item */
    function create(value)
    {
        $.ajax({
            type: 'GET',
            url: '<?php echo(PHPSELF)?>?create='+value,
            dataType: "xml",
            success: function(xml) {
                $(xml).find("response").each(function() {
                    var id = $(this).find('id').text()
                    var desc = $(this).find('desc').text()
                    if(id != undefined && desc != undefined)
                        ui_create_item(id, desc, false, true); 
                });
            }
        });
    }
    /* update one item */
    function update(id, values)
    {
        val = ""
        for( var i in values)
        {
            val = val + i + "=" + values[i] + "&"
        }
        $.ajax({
            type: 'GET',
            url: '<?php echo(PHPSELF)?>?update&id='+id+'&'+val,
            dataType: "xml",
            success: function(xml) {
                $(xml).find("response").each( function() {
                    var id = $(this).find('id').text()
                    var desc = null
                    var status = null
                    if($(this).find('desc'))
                    {
                        desc = $(this).find('desc').text()
                    }
                    if($(this).find('completed'))
                    {
                        completed = $(this).find('completed').text()
                        if(completed == '0')
                            completed = 0
                        else
                            completed = 1
                    }
                    ui_update_item(id, desc, completed)
                });
            }
        });
    }

    /* mark item 'finished' */
    function markTaskComplete(node)
    {
        id = $(this).parent().parent().attr('id').substring(4)
        update(id, {completed: true})
    }

    /* mark item 'unfinished' */
    function markTaskIncomplete()
    {
        id = $(this).parent().parent().attr('id').substring(4)
        update(id, {completed: false})
    }

    /* document init method */
    $(document).ready( function() {
        $('#input_field').hide()
        $('#todo ul.incompleted-tasks li input').click( markTaskComplete );
        $('#todo ul.completed-tasks li input').click( markTaskIncomplete );
        $('#add_link').click( function() {
            $('#input_field').show()
            $(this).hide()
            $('#desc').val('')
            return false;
        });
        $('#add form').submit( function() {
            var value = $('#desc').val()
            if(value && value.length > 0)
            {
                create(value)
                $('#desc').val('')
            }
            $('#input_field').hide()
            $('#add_link').show()
            return false;
        });
        $('#cancel').click( function()
        {
            $('#input_field').hide()
            $('#add_link').show()
            $('#desc').val('')
            return false
        });
        // first, build empty lists
        ui_build($('#todo'))
        // populate lists
        read()
    });
    </script>
    <style>
        #container {
            border: 1px solid #ddd;
            width: 80%;
            margin: 2em auto;
            padding: 2em;
            font-family: "Trebuchet MS", Arial, sans-serif;
        }
        #desc {
            width: 40em;
            margin: 0.3em;
            padding: 0.2em;
        }
        #input_field input {
            font-size: 110%;
        }
        ul.incompleted-tasks{
            font-size: 150%;
        }
        ul.incompleted-tasks, ul.completed-tasks {
            list-style: none;
        }
        ul.completed-tasks label {
            text-decoration: line-through;
        }
        button {
            background-color:#baf4ba;
            border:2px solid #9ccc9c;
            color:#333333;
            padding: 0.2em 0.6em;
            margin: 0.1em;
        }
    </style>
</head>
<body>
<div id="container">
    <h1>Tinytasks</h1>
    <div id="add">
        <button id="add_link">Add new task</button>
        <div id="input_field">
        <form action="#" method="get">
        <input type="text" id="desc" /><input type="submit" value="Add" />
        </form>
        <a id="cancel" href="#">Cancel</a>
        </div>
    </div>
    <div id="todo">
    </div>
</div>
</body>
</html>

