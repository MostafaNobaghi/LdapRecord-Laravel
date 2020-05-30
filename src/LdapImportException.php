<?php

namespace LdapRecord\Laravel;

use LdapRecord\Models\Model;
use LdapRecord\LdapRecordException;

class LdapImportException extends LdapRecordException
{
    /**
     * Generate a new exception for a model that is missing a GUID.
     *
     * @param Model $model
     *
     * @return static
     */
    public static function missingGuid(Model $model)
    {
        return new static(
            sprintf(
                'Attribute [%s] does not exist on LDAP model [%s] object [%s]',
                $model->getGuidKey(),
                get_class($model),
                $model->getDn()
            )
        );
    }
}
