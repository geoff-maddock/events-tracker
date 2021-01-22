<?php

namespace App\Http\ResultBuilder;

use App\Http\Response\ResultSet\ListResultSet;

interface ListResultBuilderInterface
{
    public function listResultSetFactory(): ListResultSet;
}
