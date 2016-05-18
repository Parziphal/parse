<?php

namespace Parziphal\Parse\Contracts;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

interface ObjectModel extends Arrayable, Jsonable
{
}
