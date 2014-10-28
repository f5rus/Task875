<?php
Class Articles extends DbConnectable
{
    public $table_name = "Articles";        
    public $attributes = array('id'=>0, 'head'=>'', 'text'=>'', 'autor'=>'');
    public $relations = array('Users'=> array('autor','id'));
}