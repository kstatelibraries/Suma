<?php
/**
 * Class to handle an arbitraty number of variables to be inserted into a 
 * PDO-style mysql query. Allows output of the PDO-style query string with
 * :placeholders, and correspondingly handles bindParam requests for those
 * :placeholders. 
 *
 * @author Ken Irwin <kirwin@wittenberg.edu>
 */
class AndWherePDO {
    
    /**
     * Array of condition arrays
     * @var array
     * @access private
     */
    private $conditions; //array of condition-sets 

    /**
     * constructor creates an empty array
     * @access public
     */
    public function __construct () {
        $this->conditions = array();
    }
    /**
     * adds a new array describing one MySQL "AND" condition
     * @param string $field Name of MySQL field
     * @param string $value The search value to be matched in $field
     * @param string $operator MySQL operator such as 'LIKE', '=', '!=' 
       describing the relationship between field and value
     * @param string $type Type of value: int, str, bool, null
     * @access public
     */

    public function AddCondition ($field,$value,$operator='=',$type='str') {
        $placeholder = ':' . $field;
        
        switch ($type) {
        case 'str': 
            $param_type = PDO::PARAM_STR;
            break;
        case 'int': 
            $param_type = PDO::PARAM_INT;
            break;
        case 'bool':
            $param_type = PDO::PARAM_BOOL;
            break;
        case 'null':
            $param_type = PDO::PARAM_NULL;
            break;
        }
        
        $condition = array ('field' => $field,
                            'value' => $value,
                            'operator' => $operator,
                            'type'  => $type,
                            'param_type' => $param_type,
                            'placeholder' => $placeholder,
                             );
        array_push($this->conditions, $condition); 

    } //end AddCondition

    /** 
     * Takes $conditions array and generates an SQL query string to be 
     * appended to a WHERE query. Each condition begins with 'AND'
     * e.g. 'AND date = :date AND deleted = :deleted
     * @access public
     */
    public function AndWhereString () {
        $return_string = "";
        foreach ($this->conditions as $a) {
            $return_string .= ' AND '.$a['field'].' '.$a['operator'].' '.$a['placeholder'];
        }
        return $return_string;
    }

    public function Bind ($stmt) {
        foreach ($this->conditions as $a) {
            $stmt->bindParam($a['placeholder'], $a['value'], $a['param_type']);
        }
        return $stmt;
    }

} //end class


?>