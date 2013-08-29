<?php

require_once('Noun.php');



// TODO: IPv6

class IPAddress implements NounLean   // IPv4 address, and a NounLean oddity: the id is the info  (TODO: IPv6 via a database?  a clause noun??)
{
    protected $ipv4;
    
    public function __construct($info)   // e.g. "123.45.67.89"
    {
        $this->ipv4 = $info;
    }
    static public function classname()
    {
        return NounClass::IPAddress;
    }
    public function info() 
    {
        return $this->ipv4;
    }
    static public function selectId($id)
    {
        return new static(long2ip(intval($id)));
    }
    static public function selectInfo($ipv4)
    {
        return new static($ipv4);
    }
                    // public function is() 
                    // {
                        // return FALSE !== ip2long($this->ipv4);
                    // }
    public function stow()
    {
    }
    public function id() 
    {
        // if ($this->is()) {
            return sprintf("%u", ip2long($this->ipv4));
        // } else {
            // return FALSE;
        // }
    }
    public function assertValid($classname = NULL) 
    {
        NounLean_assertValid($this, $classname);
    }
}
