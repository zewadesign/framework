<?php
declare(strict_types=1);
namespace Zewa\HTTP;

use Zewa\Container;
use Zewa\Security;

final class Delete extends SuperGlobal
{
    public function __construct(Container $container, Security $security)
    {
        parent::__construct($container, $security);

        if ($_SERVER['REQUEST_METHOD'] === "DELETE") {
            parse_str(file_get_contents('php://input'), $delete);
            $_POST = [];
        }

        $delete = $delete ?? [];
        $this->registerGlobal($delete);
    }
}
