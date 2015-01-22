<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit\Model\FlexibleEntity;

use Atoum;

class FlexibleContainer extends Atoum
{
    public function testHydrate()
    {
        $container = $this->newTestedInstance();
        $this
            ->object($container->hydrate(["a" => "one"]))
            ->isInstanceOf('\PommProject\ModelManager\Model\FlexibleEntity\FlexibleContainer')
            ->array($container->fields())
            ->isIdenticalTo(["a" => "one"])
            ->object($container->hydrate(["b" => "two"]))
            ->array($container->fields())
            ->isIdenticalTo(["a" => "one", "b" => "two"])
            ->object($container->hydrate(["a" => "three", "c" => "four"]))
            ->array($container->fields())
            ->isIdenticalTo(["a" => "three", "b" => "two", "c" => "four"])
            ;
    }

    public function testFields()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => null])
            ;
        $this
            ->array($container->fields())
            ->isIdenticalTo(["a" => "one", "b" => "two", "c" => null])
            ->array($container->fields(["a"]))
            ->isIdenticalTo(["a" => "one"])
            ->array($container->fields(["a", "c"]))
            ->isIdenticalTo(["a" => "one", "c" => null])
            ->array($container->fields([]))
            ->isIdenticalTo([])
            ->exception(function() use ($container) { return $container->fields(["d"]); })
            ->isInstanceOf("\InvalidArgumentException")
            ->message->contains("{a, b, c}")
            ;
    }

    public function testExtract()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => "three"])
            ;
        $this
            ->array($container->fields())
            ->isIdenticalTo(["a" => "one", "b" => "two", "c" => "three"])
            ;
    }

    public function testGetIterator()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => "three"])
            ;
        $this
            ->object($container->getIterator())
            ->isInstanceOf('\ArrayIterator')
            ->array($container->getIterator()->getArrayCopy())
            ->isIdenticalTo(["a" => "one", "b" => "two", "c" => "three"])
            ;
    }

    public function testGenericGet()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => "three"])
            ;
        $this
            ->string($container->getA())
            ->isEqualTo("one")
            ->string($container->getC())
            ->isEqualTo("three")
            ->exception(function() use ($container) { $container->getPika(); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("{a, b, c")
            ;
    }

    public function testGenericSet()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => "three"])
            ;
        $this
            ->object($container->setPika('chu'))
            ->isInstanceOf('\PommProject\ModelManager\Model\FlexibleEntity\FlexibleContainer')
            ->array($container->fields())
            ->isIdenticalTo(["a" => "one", "b" => "two", "c" => "three", "pika" => "chu"])
            ->object($container->setA("four"))
            ->array($container->fields())
            ->isIdenticalTo(["a" => "four", "b" => "two", "c" => "three", "pika" => "chu"])
            ->object($container->setA(null))
            ->array($container->fields())
            ->isIdenticalTo(["a" => null, "b" => "two", "c" => "three", "pika" => "chu"])
            ;
    }

    public function testGenericHas()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => null])
            ;
        $this
            ->boolean($container->hasA())
            ->isTrue()
            ->boolean($container->hasPika())
            ->isFalse()
            ->boolean($container->hasC())
            ->isTrue()
            ;
    }

    public function testGenericClear()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => "three"])
            ;
        $this
            ->object($container->clearA())
            ->isInstanceOf('\PommProject\ModelManager\Model\FlexibleEntity\FlexibleContainer')
            ->array($container->fields())
            ->isIdenticalTo(["b" => "two", "c" => "three"])
            ->exception(function() use ($container) { $container->clearA(); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("{b, c")
            ;
    }

    public function testCall()
    {
        $container = $this->newTestedInstance()
            ->hydrate(["a" => "one", "b" => "two", "c" => "three"])
            ;
        $this
            ->exception(function() use ($container) { $container->pika(); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("No such argument")
            ->exception(function() use ($container) { $container->cliPika(); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("No such method")
            ;
    }
}
