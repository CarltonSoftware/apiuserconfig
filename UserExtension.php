<?php

class UserExtention extends \Twig_Extension
{
    public function getName()
    {
        return 'user_extenstion';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('attrVal', array($this, 'attrVal'))
        );
    }
    
    public function attrVal($attr)
    {
        if (is_bool($attr)) {
            $attr = ($attr === true) ? 'true' : 'false';
        }
        
        return (string) $attr;
    }
}
