<?php
require_once 'DbConnectable.php';
require_once 'Articles.php';
require_once 'Users.php';

$attributes = array('id' => 2, 'head' => 'blabla', 'text' => '444','autor'=>2);

$article = new Articles($attributes);


$article->findByPk(1);

$article->head = "blabla";
$article->text = "texttext";

$article->save();

$res= $article->where('head','blabla','Users');
 
echo $res[0]->autor->name;

