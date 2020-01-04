<?php 
    define( 'DB_HOST', '127.0.0.1' );
    define( 'DB_USER', 'root' );
    define( 'DB_PASS', '' );
    define( 'DB_DB'  , 'mysql_dust' );
    
    define( 'WEB_URL', 'http://localhost:8080/cs/ChatManager' );
    $mysqli = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_DB
    );
    $table_name = "db_patterns";
    $show_pattern = -1;
    $patterns = array( 0=>"kick", 1=>"ban", 2=>"hide", 3=>"whitelist" );
    //$pattern_values = array_keys( $patterns );
    if( !isset( $_GET['show_pattern'] ) || $_GET['show_pattern'] == 'ban' || ( $_GET['show_pattern'] != 'whitelist' && $_GET['show_pattern'] != 'hide' && $_GET['show_pattern'] != 'kick') )
    {
        //$index_fields = array( "pattern"=>"Pattern", "reason"=>"Reason", "time"=>"Ban Length" );
        $edit_fields = array( "pattern"=>"Pattern", "reason"=>"Reason", "time"=>"Ban Length" );
        $show_pattern = 1;
    }
    else if( $_GET['show_pattern'] == 'whitelist' )
    {
        //$index_fields = array( "pattern"=>"Pattern" );
        $edit_fields = array( "pattern"=>"Pattern");
        $show_pattern = 3;
    }
    else if( $_GET['show_pattern'] == 'hide' )
    {
        //$index_fields = array( "pattern"=>"Pattern" ); 
        $edit_fields = array( "pattern"=>"Pattern" );
        $show_pattern = 2;
    }
    else if( $_GET['show_pattern'] == 'kick' )
    {
        $edit_fields = array( "pattern"=>"Pattern", "reason"=>"Reason" );
        $show_pattern = 0;
    }
    $index_fields = array( "pattern"=>"Pattern" );
    $search_fields = array( "pattern"=>"Pattern", "reason"=>"Reason", "time"=>"Ban Length" );

    $index = isset( $_GET['id'] )? $_GET['id']:0;
    

    function CheckValid()
    {
        return true;
    }
    function log_error( $text )
    {
        ?>
            <br>
            <div class="ui-widget" style='font-size: 1.0em;'>
                <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
                    <p>
                        <span class="ui-icon ui-icon-alert"></span>
                        <?php 
                        echo "<strong>Error:</strong> ".$text;
                        ?>
                    </p>
                </div>
            </div>
            <br>
        <?php
    }

    if( isset($_GET['del']) && $index )
    {
        $query = "DELETE FROM ".$table_name." WHERE `id`=".$index;
    }
    else if( (isset( $_GET['edit'] ) && $index) || isset( $_GET['add'] ) )
    {
        if( CheckValid() )
        {
            $query = isset( $_GET['add'] )? "INSERT INTO ":"UPDATE ";
            $query .= $table_name." SET ";
            foreach( $edit_fields as $field=>$field_value )
            {
                if( isset( $_GET[ $field ] ) && ( strlen($_GET[$field]) > 0 ) )
                {
                    $query .= $field."='".$mysqli->real_escape_string($_GET[$field])."',";
                }
            }
            $query .= "block_type=".$show_pattern." ";
            //$query = substr($query, 0, -1);
            if( !isset( $_GET['add'] ) )
                $query .= "WHERE `id`=".$index;
        }
        else if( $index )
            $index_show = $index;
        else
            $index_show = -2;
    }
    else if( isset( $_GET['search'] ) )
    {
        $query_edit = "SELECT * FROM ".$table_name." WHERE ";
        foreach( $search_fields as $search=>$search_value )
        {
            if( isset( $_GET[$search] ) && !empty( $_GET[$search] ))
            {
                $query_edit .= $search." LIKE '%".$_GET[$search]."%' AND ";
            }
        }
        $query_edit .= "block_type = ".$show_pattern;
        //$query_edit = substr( $query_edit, 0, -5 );
    }
    if( isset($query) )
    {
        $mysqli->query($query);
        if( $mysqli->error )
        {
            if(empty($query)) {
                echo "Connect Error (".$mysqli->connect_errno.") ".$mysqli->connect_error;
            } else {
                echo "<br/>".$query."<br/>Error:".$mysqli->error."<br/>";
            }
            return 0;
        }
    }
    
?>
<head>
    <title>Chat Manager RegEx</title>
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    
    <style>
        .lefty{
            float: left;
        }
        .moveLeft
        {
            width: 100px;
            left: 4.5%;
        }
        .moveRight
        {
            width: 100px; 
            right: 4.5%; 
            float:right;
            padding: 0;
        }
    </style>
    
    <script>
        $(document).ready( function () {
            $('#table_display')
            .dataTable( {
                //responsive: true,
                columnDefs: [
                    { targets: [-1], className: 'dt-body-center' }
                ],
                columnDefs: [
                    { targets: [ -1 ], orderable: false }
                ],
                "order": [0, 'desc'],
                ordering: true,
                bFilter: false,
                lengthChange: false
            } );
        } );
    
        //<div style='width: 4.5%; display: inline-table;'></div>
    </script>
</head>
<body>
    <br>
    
    <a href=<?php echo WEB_URL;?>><button id='HomeButton' class=moveLeft>HOME</button></a> 
    <button id='searchBtn' class=moveLeft>Search</button>
    <a href="index.php?show_pattern=ban"><button class=moveRight>Ban</button></a>
    <a href="index.php?show_pattern=hide"><button class=moveRight>Hide</button></a>
    <a href="index.php?show_pattern=kick"><button class=moveRight>Kick</button></a>
    <a href="index.php?show_pattern=whitelist"><button class=moveRight>Whitelist</button></a>
    <script>
        $( '#searchBtn' ).click(function() { 
            $( '#searchDiv' ).dialog( 'open' );
        });
    </script>
    <br>
    <div id='searchDiv' title='Search'>
        <form method=GET>
        <?php
            foreach( $search_fields as $field=>$field_value )
            {
                echo "<p>";
                echo $field_value.": <input type='text' name='".$field."' style='float:right; width: 700px;'>";
                echo "</p>";
            }
        ?>  
        <input type=hidden value=1 name=search>
        </form>
    </div>
    <script>
        
        $( '#searchDiv' ).dialog({
            autoOpen: false,
            width: 900,
            modal: true, 
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#searchDiv').dialog('close');
                })
            },
            draggable: false,
            buttons: [{ 
                text: 'Search', 
                click: function() { 
                    $(this).find('form').submit(); 
                    $(this).dialog('close'); 
                }
            }]
        });
    </script>
    <div id='addDiv' title='Add Ban'>
        <form method=GET>
        <?php
            echo "<input type=hidden value=".$patterns[$show_pattern]." name=show_pattern>";
            foreach( $edit_fields as $field=>$field_value )
            {
                echo "<p>";
                echo $field_value.": <input type='text'  name='".$field."' style='float:right; width: 700px;' value='".((isset($index_show) && $index_show == -2 && isset($_GET[$field]))? $_GET[$field]:'')."'>";
                echo "</p>";
            }
        ?>
        <input type=hidden value=1 name=add>
        
        </form>
    </div>
    <script>
        $( '#addDiv' ).dialog({
            <?php
                if( isset($index_show) && $index_show == -2 )
                    echo "autoOpen: true,";
                else
                    echo "autoOpen: false,";
            ?>
            width: 900,
            modal: true, 
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#addDiv').dialog('close');
                })
            },
            draggable: false,
            buttons: [{ 
                text: 'Add', 
                click: function() { 
                    $(this).find('form').submit(); 
                    $(this).dialog('close'); 
                }
            }]
        });
        
    </script>
    <table id="table_display" class="display" style='width:90%;'>
        <thead>
            <tr>
                <?php
                    foreach( $index_fields as $field => $field_display )
                        echo "<th width=90%>".$field_display."</th>";
                ?>
                <th><button id='addButton'>Add</button></th>
                <script>
                    $( '#addButton' ).click(function() { 
                        $( '#addDiv' ).dialog( 'open' );
                    });
                    //<script>
                    $( "button" ).button();
                </script>
            </tr>
        </thead>
        <tbody>
            <?php
                if( isset( $query_edit ) )
                {
                    $query = $query_edit;
                }
                else
                {
                    $query = "SELECT * FROM ".$table_name." WHERE block_type=".$show_pattern;
                }
                $result = $mysqli->query($query);
                if( $mysqli->error )
                {
                    if(empty($query)) {
                        echo "Connect Error (".$mysqli->connect_errno.") ".$mysqli->connect_error;
                    } else {
                        echo "<br/>".$query."<br/>Error:".$mysqli->error."<br/>";
                    }
                    return 0;
                }
                while( $r = $result->fetch_assoc())
                {
                    //$exp = ($r['expired']>=1)? "dff0d8":"";
                    echo "<tr id='".$r['id']."'>";
                    foreach( $index_fields as $field=>$field_ass )
                    {
                            echo "<td>";
                            if( $field == 'date_added' )
                                echo gmdate("Y-m-d H:i:s", intval($r[$field]));
                            else
                                echo $r[$field];
                            echo "</td>";
                    }
                    echo "<td><button id='edit_".$r['id']."' class=lefty>Edit</button><form method=GET><input type=hidden name=id value='".$r['id']."'><input type=submit name=del value=Delete></form></td>";
                    echo "</tr>";
                    echo "<div id='ban_".$r['id']."' title='Details #".$r['id']."'>";
                    echo "<form action='' method='GET'>";
                    //echo "<script>alert(".$pattern_values[$show_pattern].");</script>";
                    echo "<input type=hidden value=".$patterns[$show_pattern]." name=show_pattern>";
                    foreach( $edit_fields as $field=>$field_value )
                    {
                        echo "<p>";
                        echo $field_value.": <input type='text' name='".$field."' value='".((isset($index_show) && $index_show == $r['id'] && isset($_GET[$field]))? $_GET[$field]:$r[$field] )."' style='float:right; width:700px;'>";
                        echo "</p>";
                    }
                    echo "<input type=hidden name=id value='".$r['id']."'>";
                    echo "<input type=hidden name=edit value=1>";
                    echo "</form></div>";
                    echo "<script>";
                    echo "$( '#ban_".$r['id']."' ).dialog({";
                    if( isset( $index_show ) && $index_show == $r['id'] )
                        echo "autoOpen: true, width: 900,";
                    else
                        echo "autoOpen: false, width: 900,";
                    echo "modal: true, draggable: false,";
                    echo "open: function(){";
                    echo "jQuery('.ui-widget-overlay').bind('click',function(){";
                    echo "jQuery('#ban_".$r['id']."').dialog('close');})},";
                    echo "buttons: [{ text: 'Ok', click: function() { $(this).find('form').submit(); $(this).dialog('close'); }}]";
                    echo "});";
                    echo "$( '#edit_".$r['id']."' ).click(function() { $( '#ban_".$r['id']."' ).dialog( 'open' );});";
                    echo "</script>";
                }
            ?>
        </tbody>

    </table>

</body>
