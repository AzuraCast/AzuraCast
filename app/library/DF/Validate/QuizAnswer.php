<?php
/**
 * DF Form Validate - Quiz Answer
 */

namespace DF\Validate;
class QuizAnswer extends \Zend_Validate_Abstract
{
    const QUIZ_ANSWER = 'quiz_answer';

    protected $_messageTemplates = array(
        self::QUIZ_ANSWER => "'%value%' is not the correct answer."
    );
    
    /**
     * Constructor of this validator
     *
     * The argument to this constructor is the third argument to the elements' addValidator
     * method.
     *
     * @param array|string $fieldsToMatch
     */
    
    protected $_correct_answer;
    
    public function __construct($correct_answer = NULL) {
        $this->_correct_answer = $correct_answer;
    }
    
    public function isValid($value)
    {
        $this->_setValue(trim(strtoupper($value)));
        
        if (strcasecmp($value, $this->_correct_answer) !== 0)
        {
            $this->_error(self::QUIZ_ANSWER);
            return false;
        }

        return true;
    }
}