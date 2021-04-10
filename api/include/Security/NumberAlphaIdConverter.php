<?php

namespace Flytrap\Security;

class NumberAlphaIdConverter
{
    protected $idLength;
    protected $index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected $base;

    function __construct($idLength = 1)
    {
        $this->idLength = $idLength;
        $this->base = strlen($this->index);
    }

    /**
     * Generates a unique alphanumeric id by selecting random position in $index, 
     * $idLength times
     * @link https://stackoverflow.com/a/3203857/9860982
    */
    public function generateId() : string
    {
        $randomId = '';

        for ($i = 0; $i < $this->idLength; $i++) { 
            $randomId .= $this->index[rand(0, $this->base - 1)]; 
        }

        return $randomId;
    }
}
