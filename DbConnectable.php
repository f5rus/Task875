<?php
interface iDbConnectable {
/**
* @param Array $attributes
* @return iDbConnectable
*/
public function __construct($attributes = array());
/**
*
* @param integer $pk
* @return iDbConnectable
*/
public function findByPk($pk);
/**
* make 'insert' if there no id and 'update where id = ?' if there is
* returns itself
* @return iDbConnectable
*/
public function save();
/**
* use new $this->_construct; for each element
*
* @param string $attribute
* @param string $value
* @return iDbConnectable[] $objects - ���������� ������ �������� �������� ������
*/
public function where($attribute, $value);

}

class DbConnectable implements iDbConnectable
{
    public $mysqliCon;   
    public $table_name;    
    public $attributes;
    
    public function __construct($attributes = array()) {
          
        try {
            $this->mysqliCon = new PDO('mysql:dbname=Articles;host=localhost', 'root', '123456');       
        } catch (Exception $ex) {
            echo "Ошибка: ".  $ex->getMessage()."<br/>";
            die();
        }
        
        foreach($attributes as $key=>$value)
        {    
            if(!isset($this->attributes[$key])) {
                echo 'Не соответствие входящих параметров!';
                exit;
            };
        };
                    
        $this->attributes = $attributes;
        
    }  

    public function findByPk($pk)
    {

        $stmt =$this->mysqliCon->prepare("SELECT * FROM $this->table_name WHERE id=?");
        $stmt->bindparam(1, $pk);
        $stmt->execute();
                        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            foreach($row as $key=>$value)
            {                  
                if(!isset($this->attributes[$key])) {
                    echo 'Не соответствие параметров запроса!';
                    return;
                };
            };

            $this->attributes = $row;       
        }
        else {
            echo 'Не найдено!';
            return;
        }
    }

    public function prepare_attributes()
    {       
        if(count($this->attributes)<0)
        {
            return;
        }
        $strattr="";
        $separ="";
        
        $keys = array_keys($this->attributes);
        foreach ($keys as $key)
        {           
            $strattr.=$separ.$key."=:".$key; 
            $separ=",";
        };
        
        return $strattr;
    }
    
    public function existindb()
    {    
        $query = "SELECT * FROM $this->table_name WHERE id=?";
        $stmt= $this->mysqliCon->prepare($query);
        $stmt->bindparam(1,$this->attributes['id']);
        $stmt->execute();
        
        if($stmt->fetch())
        {
            return true;
        }
    }


    public function save()
    {        
               
        $strattr = $this->prepare_attributes($this->attributes);
                
        if($this->existindb())
        {           
            $query = "UPDATE $this->table_name SET $strattr WHERE id=:id";                       
        }   
        else 
        {       
            $query ="INSERT INTO $this->table_name SET $strattr";           
        }        
       
             
        $stmt= $this->mysqliCon->prepare($query);             
 
      
        foreach($this->attributes as $key=>$value)
        {  
                       
            $stmt->bindvalue(":$key", $value);  
        }        
           
        $stmt->execute();   
        
    }
    
    public function get_table_relation($result,$with)
    {
        $rel_array = array();   
        
        $key_rel = $this->relations[$with][1];
        $key_rel_this = $this->relations[$with][0];
        $class_name = $this->table_name;
        
        $stmt =$this->mysqliCon->prepare("SELECT * FROM $with WHERE $key_rel in (SELECT $key_rel_this FROM $class_name)");
        
        $stmt->execute();
            
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $new_obj= new $with($row);            
            $rel_array[$row[$key_rel]] =$new_obj ; 
        };              
               
        return $rel_array;
    }

    
   
    public function where($attribute, $value, $with=false)
    {   
        $result_array = array();              
        $class_name = $this->table_name; //Articles
              
        $stmt =$this->mysqliCon->prepare("SELECT * FROM $class_name WHERE $attribute=?");
        $stmt->bindValue(1,$value);
        $stmt->execute();           
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);        
        
        //выборка данных из таблицы по условию
        /*    $result =array(2) { 
                   [0]=> array(4) { ["id"]=> string(1) "1" ["head"]=> string(4) "Dddd" ["text"]=> string(4) "4444" ["user_id"]=> string(1) "1" } 
                   [1]=> array(4) { ["id"]=> string(1) "3" ["head"]=> string(3) "333" ["text"]=> string(4) "4444" ["user_id"]=> string(1) "2" } 
          } 
        */        
               
        $array_relation = $this->get_table_relation($result,$with);                                 
                   
        $key_rel_this = $this->relations[$with][0];
        
        while($row = each($result)){
            
           //создание экземпляра класса по строке из таблицы бд
                      
            $new_obj = new $class_name($row['value']);
            
            $new_obj->$key_rel_this = $array_relation[$new_obj->$key_rel_this];
            
            array_push($result_array, $new_obj);
            
            
        }
        
       // var_dump($result_array);
        
        return $result_array;
    }
    
    public function __get($name)
    {        
        if (isset($this->attributes[$name]))
        {
            return $this->attributes[$name];   
        }    
    }
    
     public function __set($name,$value)
    {        
        if (isset($this->attributes[$name]))
        {
            $this->attributes[$name] = $value;   
        }    
    }
    

}
