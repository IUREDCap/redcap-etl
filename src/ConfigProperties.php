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
    const ALLOWED_SERVERS        = 'allowed_servers';
    const BATCH_SIZE             = 'batch_size';
    const CA_CERT_FILE           = 'ca_cert_file';
    const CONFIG_API_TOKEN       = 'config_api_token';
    const DATA_SOURCE_API_TOKEN  = 'data_source_api_token';
    const DB_CONNECTION          = 'db_connection';

    const EMAIL_FROM_ADDRESS     = 'email_from_address';
    const EMAIL_SUBJECT          = 'email_subject';
    const EMAIL_TO_LIST          = 'email_to_list';
    
    const LABEL_VIEW_SUFFIX      = 'label_view_suffix';
    const LOG_FILE               = 'log_file';
    const LOG_PROJECT_API_TOKEN  = 'log_project_api_token';
    const REDCAP_API_URL         = 'redcap_api_url';
    const SSL_VERIFY             = 'ssl_verify';
    const TABLE_PREFIX           = 'table_prefix';
    const TIME_LIMIT             = 'time_limit';
    const TIMEZONE               = 'timezone';

    const TRANSFORM_RULES_CHECK  = 'transform_rules_check';
    const TRANSFORM_RULES_FILE   = 'transform_rules_file';
    const TRANSFORM_RULES_SOURCE = 'transform_rules_source';
    const TRANSFORM_RULES_TEXT   = 'transform_rules_text';
    
    const TRIGGER_ETL            = 'trigger_etl';
    const WEB_SCRIPT             = 'web_script';
    const WEB_SCRIPT_LOG_FILE    = 'web_script_log_file';
    const WEB_SCRIPT_URL         = 'web_script_url';
 
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
            
            $properties = self::getProperties();

            foreach ($properties as $name => $value) {
                if ($property === $value) {
                    $isValid = true;
                    break;
                }
            }
        }
        return $isValid;
    }
    
    /**
     * Gets the property names and values.
     *
     * @return array a map from property name to property value for
     *     all the configuration properties.
     */
    public static function getProperties()
    {
        $reflection = new \ReflectionClass(self::class);
        $properties = $reflection->getConstants();
        return $properties;
    }
}
