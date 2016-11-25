<?php
declare(strict_types=1);
namespace Zewa\HTTP;

use Zewa\Container;
use Zewa\Security;

final class Post extends SuperGlobal
{
    public function __construct(Container $container, Security $security)
    {
        parent::__construct($container, $security);

        $post = $_POST ?? [];
        $this->registerGlobal($post);
    }

}
