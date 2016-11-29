<?php
declare(strict_types=1);
namespace Zewa\HTTP;

use Zewa\Container;
use Zewa\Security;

final class Put extends SuperGlobal
{
    public function __construct(Container $container, Security $security)
    {
        parent::__construct($container, $security);

        if ($_SERVER['REQUEST_METHOD'] === "PUT") {
            parse_str(file_get_contents('php://input', "r"), $put);
            $_POST = [];
        }

        $put = $put ?? [];
        $this->registerGlobal($put);
    }
}
