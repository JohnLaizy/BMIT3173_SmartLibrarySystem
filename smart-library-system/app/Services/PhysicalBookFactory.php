<?php

namespace App\Services;

class PhysicalBookFactory extends BookFactory
{
    public function createBookProduct(): BookProductInterface
    {
        return new PhysicalBookProduct();
    }
}