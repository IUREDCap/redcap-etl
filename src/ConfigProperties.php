<?php

namespace IU\REDCapETL;

/**
 * Configurations properties class.
 */
class ConfigProperties
{
    #----------------------------------------------------------------
    # Configuration properties
    #----------------------------------------------------------------
    const ALLOWED_SERVERS_PROPERTY        = 'allowed_servers';
    const BATCH_SIZE_PROPERTY             = 'batch_size';
    const CA_CERT_FILE_PROPERTY           = 'ca_cert_file';
    const CONFIG_API_TOKEN_PROPERTY       = 'config_api_token';
    const DATA_SOURCE_API_TOKEN_PROPERTY  = 'data_source_api_token';
    const DB_CONNECTION_PROPERTY          = 'db_connection';

    const EMAIL_FROM_ADDRESS_PROPERTY     = 'email_from_address';
    const EMAIL_SUBJECT_PROPERTY          = 'email_subject';
    const EMAIL_TO_LIST_PROPERTY          = 'email_to_list';
    
    const LABEL_VIEW_SUFFIX_PROPERTY      = 'label_view_suffix';
    const LOG_FILE_PROPERTY               = 'log_file';
    const LOG_PROJECT_API_TOKEN_PROPERTY  = 'log_project_api_token';
    const REDCAP_API_URL_PROPERTY         = 'redcap_api_url';
    const SSL_VERIFY_PROPERTY             = 'ssl_verify';
    const TABLE_PREFIX_PROPERTY           = 'table_prefix';
    const TIME_LIMIT_PROPERTY             = 'time_limit';
    const TIMEZONE_PROPERTY               = 'timezone';

    const TRANSFORM_RULES_CHECK_PROPERTY  = 'transform_rules_check';
    const TRANSFORM_RULES_FILE_PROPERTY   = 'transform_rules_file';
    const TRANSFORM_RULES_SOURCE_PROPERTY = 'transform_rules_source';
    const TRANSFORM_RULES_TEXT_PROPERTY   = 'transform_rules_text';
    
    const TRIGGER_ETL_PROPERTY            = 'trigger_etl';
    const WEB_SCRIPT_PROPERTY             = 'web_script';
    const WEB_SCRIPT_LOG_FILE_PROPERTY    = 'web_script_log_file';
    const WEB_SCRIPT_URL_PROPERTY         = 'web_script_url';
 
    /**
     * Indicates if the specified property is a valid configuration
     * property.
     *
     * @param string $property the property to check for validity.
     *
     * @return boolean true if the specified property is valid, and
     *     false otherwise.
     */
    public static function isValid($property)
    {
        $isValid = false;

        if ($property != null) {
            $property = trim($property);
            $reflection = new \ReflectionClass(self::class);

            foreach ($reflection->getConstants() as $name => $value) {
                if ($property === $value) {
                    $isValid = true;
                    break;
                }
            }
        }
        return $isValid;
    }
}
