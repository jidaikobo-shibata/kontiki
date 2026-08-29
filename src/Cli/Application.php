<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Cli;

use Composer\InstalledVersions;
use Jidaikobo\Kontiki\Cli\Command\InstallCommand;
use Jidaikobo\Kontiki\Cli\Command\MigrateCommand;
use Jidaikobo\Kontiki\Cli\Command\StatusCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        $version = InstalledVersions::getPrettyVersion('jidaikobo/kontiki') ?? '1.0-dev';
        parent::__construct('Kontiki', $version);

        $this->add(new InstallCommand());
        $this->add(new MigrateCommand());
        $this->add(new StatusCommand());
        $this->setDefaultCommand('list');
    }
}
