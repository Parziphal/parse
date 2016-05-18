<?php

namespace Parziphal\Parse;

use Parse\ParseObject;
use Parziphal\Parse\Contracts\ObjectModel as ObjectModelContract;

class ObjectModel implements ObjectModelContract
{
    use ObjectModelMethods;
    
    protected static $parseClassName;
}
