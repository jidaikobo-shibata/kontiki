<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Cli;

use Jidaikobo\Kontiki\Cli\Command\InstallCommand;
use Jidaikobo\Kontiki\Cli\Command\MigrateCommand;
use Jidaikobo\Kontiki\Cli\Command\StatusCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Kontiki', '1.0-dev');

        $this->add(new InstallCommand());
        $this->add(new MigrateCommand());
        $this->add(new StatusCommand());
        $this->setDefaultCommand('list');
    }
}
