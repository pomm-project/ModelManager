<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model;

use PommProject\ModelManager\Exception\ModelException;
use PommProject\Foundation\Client\ClientPoolerInterface;
use PommProject\Foundation\Client\ClientPooler;
use PommProject\Foundation\Session;

/**
 * ModelPooler
 *
 * Client pooler for model package.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see ClientPooler
 */
class ModelPooler extends ClientPooler
{
    /**
     * @see ClientPoolerInterface
     */
    public function getPoolerType()
    {
        return 'model';
    }

    /**
     * The ModelPooler checks if the Session's ClientHolder has got
     * the wanted instance. If so, it is returned. Otherwise, it checks if the
     * wanted model class exists and try to instance it. It is then
     * registered in the ClientHolder and sent back.
     *
     * @see ClientPoolerInterface
     */
    public function getClient($class)
    {
        $class   = trim($class, '\\');
        $model = $this->session->getClient('model', $class);

        if ($model === null) {
            try {
                $class_name = sprintf("%sModel", $class);
                $reflection = new \ReflectionClass($class_name);
                $model    = new $class_name();
                $this->session->registerClient($model);
            } catch (\ReflectionException $e) {
                throw new ModelException(sprintf(
                    "Could not instanciate Model class '%s'. (Reason: '%s').",
                    $class_name,
                    $e->getMessage()
                ));
            }
        }

        return $model;
    }
}
