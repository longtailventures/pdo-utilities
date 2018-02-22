<?php

namespace LongTailVentures;

class PdoDebug
{
    public static function getPreparedQuery($query, array $queryParams, $returnFormat = 'text')
    {
        $preparedQuery = $query;

        krsort($queryParams);

        foreach ($queryParams as $column => $value)
            $preparedQuery = str_replace($column, "'$value'", $preparedQuery);

        if ($returnFormat === 'html')
        {
            $preparedQuery = str_replace([PHP_EOL, "\t"], ['<br />', '&nbsp;&nbsp&nbsp;&nbsp'], $preparedQuery);
            $preparedQuery = "<pre>$preparedQuery</pre>";
        }

        return $preparedQuery;
    }
}
