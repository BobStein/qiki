<?php

require_once('Noun.php');

class UnusedNoun implements NounLean   // a stub noun, a template for a new lean-noun class 
{
    public function __construct() 
    {
    }
    public function info() 
    {
        return NULL;
    }
    public function id() 
    {
        return '0';
    }
    public function assertValid($classname = NULL) 
    {
        NounLean_assertValid($this, $classname);
    }
    static public function selectId($id) 
    {
        return new static;
    }
    static public function selectInfo($info) 
    {
        return new static;
    }
    public function stow()
    {
    }
    // public function is()
    // {
        // return TRUE;
    // }
    static public function classname()
    {
        return NounClass::UnusedNoun;
    }
}
