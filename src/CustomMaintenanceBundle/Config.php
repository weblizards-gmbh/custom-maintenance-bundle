<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle;

use Pimcore\File;

/**
 * Class CustomMaintenanceBundle\Config.
 *
 * Reads and creates the configfile
 */
class Config
{
    public const TOKEN_PIMCORE = 'pimcore';

    private array $config = [];

    /**
     * Config constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $filename = $this->getFilename();
        if (file_exists($filename)) {
            $config = include $filename;
            $this->config = $config;
        }
    }

    public function save(): void
    {
        $configFile = $this->getFilename();
        File::putPhpFile($configFile, to_php_data_file_format($this->config));
    }

    public function getAllTokens()
    {
        $tokens = self::getTokens();
        array_unshift($tokens, self::TOKEN_PIMCORE);

        return $tokens;
    }

    public function getTokens()
    {
        $config = $this->config;

        return array_keys($config['custom']);
    }

    public function getData(): array
    {
        return $this->config;
    }

    public function setData(array $data): void
    {
        $this->config = $data;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php';
    }
}
