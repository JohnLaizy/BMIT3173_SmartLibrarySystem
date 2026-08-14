<?php

namespace App\Services;

class DigitalBookFactory extends BookFactory
{
    public function createBookProduct(): BookProductInterface
    {
        return new DigitalBookProduct();
    }
}