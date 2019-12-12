<?php

namespace LongTailVentures;

class PdoDebug
{
    /**
     * simple function to retrieved a prepared query with its parameters
     *
     * @param string $query
     * @param array $queryParams
     *
     * @param string $returnFormat
     * Should be one of: html, text
     * Determines whether or not on line break, character is:
     * text => PHP_EOL
     * html => <br />
     * Optional, default is 'text'
     *
     * @return string $preparedQuery
     */
    public static function getPreparedQuery($query, array $queryParams, $returnFormat = 'text') : string
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
