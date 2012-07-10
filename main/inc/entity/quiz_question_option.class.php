<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class QuizQuestionOption extends \CourseEntity
{
    /**
     * @return \Entity\Repository\QuizQuestionOptionRepository
     */
     public static function repository(){
        return \Entity\Repository\QuizQuestionOptionRepository::instance();
    }

    /**
     * @return \Entity\QuizQuestionOption
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $question_id
     */
    protected $question_id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var integer $position
     */
    protected $position;


    /**
     * Set c_id
     *
     * @param integer $value
     * @return QuizQuestionOption
     */
    public function set_c_id($value)
    {
        $this->c_id = $value;
        return $this;
    }

    /**
     * Get c_id
     *
     * @return integer 
     */
    public function get_c_id()
    {
        return $this->c_id;
    }

    /**
     * Set id
     *
     * @param integer $value
     * @return QuizQuestionOption
     */
    public function set_id($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set question_id
     *
     * @param integer $value
     * @return QuizQuestionOption
     */
    public function set_question_id($value)
    {
        $this->question_id = $value;
        return $this;
    }

    /**
     * Get question_id
     *
     * @return integer 
     */
    public function get_question_id()
    {
        return $this->question_id;
    }

    /**
     * Set name
     *
     * @param string $value
     * @return QuizQuestionOption
     */
    public function set_name($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Set position
     *
     * @param integer $value
     * @return QuizQuestionOption
     */
    public function set_position($value)
    {
        $this->position = $value;
        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function get_position()
    {
        return $this->position;
    }
}