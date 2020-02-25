<?php

namespace Ezpz\Common\Repository;

use WC\Models\ListModel;

interface DbConfigInterface
{
    function loadSettings(ListModel $settings, ListModel $configParams);

    function getAsArray(): array;
}