<?php

namespace Harryes\CrudPackage\Helpers;

class ColumnValidator
{
    /**
     * Validate the column format and return detailed errors.
     *
     * @param string $columns
     * @return array
     */
    public static function validateColumnsFormat(string $columns): array
    {
        // Define the pattern to match Laravel-supported data types with optional ? and * with default values
        $columnPattern = '/^(\w+:(bigIncrements|bigInteger|binary|boolean|char|date|dateTime|decimal|double|enum|float|geometry|geometryCollection|increments|integer|ipAddress|json|jsonb|lineString|longText|macAddress|mediumIncrements|mediumInteger|mediumText|morphs|multiLineString|multiPoint|multiPolygon|nullableMorphs|nullableTimestamps|point|polygon|rememberToken|set|smallIncrements|smallInteger|softDeletes|softDeletesTz|string|text|time|timeTz|timestamp|timestampTz|tinyIncrements|tinyInteger|tinyText|unsignedBigInteger|unsignedDecimal|unsignedInteger|unsignedMediumInteger|unsignedSmallInteger|unsignedTinyInteger|uuid|year)(\?|(\*[^,]*))?(,)?)+$/';

        $errors = [];
        $columnArray = explode(',', $columns);

        foreach ($columnArray as $index => $column) {
            $column = trim($column);
            if (!preg_match($columnPattern, $column)) {
                $errors[] = "Column at index $index ('$column') has an invalid format.";
            }
        }

        return $errors;
    }
}
