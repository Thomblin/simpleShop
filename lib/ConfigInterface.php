<?php
/**
 * Interface for accessing application configuration including database credentials and shop settings.
 */

interface ConfigInterface
{
    /**
     * Get MySQL host
     *
     * @return string
     */
    public function getMysqlHost(): string;

    /**
     * Get MySQL user
     *
     * @return string
     */
    public function getMysqlUser(): string;

    /**
     * Get MySQL password
     *
     * @return string
     */
    public function getMysqlPassword(): string;

    /**
     * Get MySQL database name
     *
     * @return string
     */
    public function getMysqlDatabase(): string;

    /**
     * Get language code
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Check if inventory should be shown
     *
     * @return bool
     */
    public function getShowInventory(): bool;

    /**
     * Get allowed text fields
     *
     * @return array
     */
    public function getAllowedTextfields(): array;

    /**
     * Get currency symbol
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Get mail address
     *
     * @return string
     */
    public function getMailAddress(): string;

    /**
     * Get mail user name
     *
     * @return string
     */
    public function getMailUser(): string;
}
