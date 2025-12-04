<?php

interface ConfigInterface
{
    /**
     * Get MySQL host
     *
     * @return string
     */
    public function getMysqlHost();

    /**
     * Get MySQL user
     *
     * @return string
     */
    public function getMysqlUser();

    /**
     * Get MySQL password
     *
     * @return string
     */
    public function getMysqlPassword();

    /**
     * Get MySQL database name
     *
     * @return string
     */
    public function getMysqlDatabase();

    /**
     * Get language code
     *
     * @return string
     */
    public function getLanguage();

    /**
     * Check if inventory should be shown
     *
     * @return bool
     */
    public function getShowInventory();

    /**
     * Get allowed text fields
     *
     * @return array
     */
    public function getAllowedTextfields();

    /**
     * Get currency symbol
     *
     * @return string
     */
    public function getCurrency();

    /**
     * Get mail address
     *
     * @return string
     */
    public function getMailAddress();

    /**
     * Get mail user name
     *
     * @return string
     */
    public function getMailUser();
}
