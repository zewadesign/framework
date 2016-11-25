<?php
declare(strict_types=1);
namespace Zewa\HTTP;

use Zewa\Container;
use Zewa\Security;

final class File extends SuperGlobal
{
    public function __construct(Container $container, Security $security)
    {
        parent::__construct($container, $security);

        $files = $_FILES ?? [];
        $this->registerGlobal($files);
    }

}
