<?php

namespace Stripe;

class StripeObjectTest extends TestCase
{
    public function testArrayAccessorsSemantics()
    {
        $s = new StripeObject();
        $s['foo'] = 'a';
        $this->assertSame($s['foo'], 'a');
        $this->assertTrue(isset($s['foo']));
        unset($s['foo']);
        $this->assertFalse(isset($s['foo']));
    }

    public function testNormalAccessorsSemantics()
    {
        $s = new StripeObject();
        $s->foo = 'a';
        $this->assertSame($s->foo, 'a');
        $this->assertTrue(isset($s->foo));
        unset($s->foo);
        $this->assertFalse(isset($s->foo));
    }

    public function testArrayAccessorsMatchNormalAccessors()
    {
        $s = new StripeObject();
        $s->foo = 'a';
        $this->assertSame($s['foo'], 'a');

        $s['bar'] = 'b';
        $this->assertSame($s->bar, 'b');
    }

    public function testCount()
    {
        $s = new StripeObject();
        $this->assertSame(0, count($s));

        $s['key1'] = 'value1';
        $this->assertSame(1, count($s));

        $s['key2'] = 'value2';
        $this->assertSame(2, count($s));
    }

    public function testKeys()
    {
        $s = new StripeObject();
        $s->foo = 'bar';
        $this->assertSame($s->keys(), array('foo'));
    }

    public function testValues()
    {
        $s = new StripeObject();
        $s->foo = 'bar';
        $this->assertSame($s->values(), array('bar'));
    }

    public function testToArray()
    {
        $s = new StripeObject();
        $s->foo = 'a';

        $converted = $s->__toArray();

        $this->assertInternalType('array', $converted);
        $this->assertArrayHasKey('foo', $converted);
        $this->assertEquals('a', $converted['foo']);
    }

    public function testRecursiveToArray()
    {
        $s = new StripeObject();
        $z = new StripeObject();

        $s->child = $z;
        $z->foo = 'a';

        $converted = $s->__toArray(true);

        $this->assertInternalType('array', $converted);
        $this->assertArrayHasKey('child', $converted);
        $this->assertInternalType('array', $converted['child']);
        $this->assertArrayHasKey('foo', $converted['child']);
        $this->assertEquals('a', $converted['child']['foo']);
    }

    public function testNonexistentProperty()
    {
        $s = new StripeObject();
        $this->assertNull($s->nonexistent);
    }

    public function testPropertyDoesNotExists()
    {
        $s = new StripeObject();
        $this->assertNull($s['nonexistent']);
    }

    public function testJsonEncode()
    {
        // We can only JSON encode our objects in PHP 5.4+. 5.3 must use ->__toJSON()
        if (version_compare(phpversion(), '5.4.0', '<')) {
            return;
        }

        $s = new StripeObject();
        $s->foo = 'a';

        $this->assertEquals('{"foo":"a"}', json_encode($s->__toArray()));
    }

    public function testReplaceNewNestedUpdatable()
    {
        $s = new StripeObject();

        $s->metadata = array('bar');
        $this->assertSame($s->metadata, array('bar'));
        $s->metadata = array('baz', 'qux');
        $this->assertSame($s->metadata, array('baz', 'qux'));
    }

    public function testSerializeParametersEmptyObject()
    {
        $obj = new StripeObject();
        $this->assertSame(array(), $obj->serializeParameters());
    }

    public function testSerializeParametersOnNewObjectWithSubObject()
    {
        $obj = new StripeObject();
        $obj->metadata = array('foo' => 'bar');
        $this->assertSame(array('metadata' => array('foo' => 'bar')), $obj->serializeParameters());
    }

    public function testSerializeParametersOnMoreComplexObject()
    {
        $obj = StripeObject::constructFrom(array(
            'metadata' => StripeObject::constructFrom(array(
                'bar' => null,
                'baz' => null,
            )),
        ));
        $obj->metadata->bar = 'newbar';
        $this->assertSame(array('metadata' => array('bar' => 'newbar')), $obj->serializeParameters());
    }

    public function testSerializeParametersOnArray()
    {
        $obj = StripeObject::constructFrom(array(
            'foo' => null,
        ));
        $obj->foo = array('new-value');
        $this->assertSame(array('foo' => array('new-value')), $obj->serializeParameters());
    }

    public function testSerializeParametersOnArrayThatShortens()
    {
        $obj = StripeObject::constructFrom(array(
            'foo' => array('0-index', '1-index', '2-index'),
        ));
        $obj->foo = array('new-value');
        $this->assertSame(array('foo' => array('new-value')), $obj->serializeParameters());
    }

    public function testSerializeParametersOnArrayThatLengthens()
    {
        $obj = StripeObject::constructFrom(array(
            'foo' => array('0-index', '1-index', '2-index'),
        ));
        $obj->foo = array_fill(0, 4, 'new-value');
        $this->assertSame(array('foo' => array_fill(0, 4, 'new-value')), $obj->serializeParameters());
    }

    public function testSerializeParametersOnArrayOfHashes()
    {
        $obj = StripeObject::constructFrom(array(
            'additional_owners' => array(
                StripeObject::constructFrom(array('bar' => null))
            ),
        ));
        $obj->additional_owners[0]->bar = 'baz';
        $this->assertSame(array('additional_owners' => array(array('bar' => 'baz'))), $obj->serializeParameters());
    }

    public function testSerializeParametersDoesNotIncludeUnchangedValues()
    {
        $obj = StripeObject::constructFrom(array(
            'foo' => null,
        ));
        $this->assertSame(array(), $obj->serializeParameters());
    }

    public function testSerializeParametersOnReplacedAttachedObject()
    {
        $obj = StripeObject::constructFrom(array(
            'metadata' => AttachedObject::constructFrom(array(
                'bar' => 'foo',
            )),
        ));
        $obj->metadata = array('baz' => 'foo');
        $this->assertSame(array('metadata' => array('bar' => '', 'baz' => 'foo')), $obj->serializeParameters());
    }

    public function testDirty()
    {
        $obj = StripeObject::constructFrom(array(
            'id' => 'id',
            'metadata' => AttachedObject::constructFrom(array(
                'bar' => 'foo',
            )),
        ));
        $obj->dirty();
        $this->assertSame(array('id' => 'id', 'metadata' => array('bar' => 'foo')), $obj->serializeParameters());
    }

    public function testDeepCopy()
    {
        // This is used to invoke the `deepCopy` protected function
        $deepCopyReflector = new \ReflectionMethod('Stripe\\StripeObject', 'deepCopy');
        $deepCopyReflector->setAccessible(true);

        // This is used to access the `_opts` protected variable
        $optsReflector = new \ReflectionProperty('Stripe\\StripeObject', '_opts');
        $optsReflector->setAccessible(true);

        $opts = array(
            "api_base" => Stripe::$apiBase,
            "api_key" => "apikey",
        );
        $values = array(
            "id" => 1,
            "name" => "Stripe",
            "arr" => array(
                StripeObject::constructFrom(array("id" => "index0"), $opts),
                "index1",
                2,
            ),
            "map" => array(
                "0" => StripeObject::constructFrom(array("id" => "index0"), $opts),
                "1" => "index1",
                "2" => 2
            ),
        );
        $copyValues = $deepCopyReflector->invoke(null, $values);
        // we can't compare the hashes directly because they have embedded
        // objects which are different from each other
        $this->assertEquals($values["id"], $copyValues["id"]);
        $this->assertEquals($values["name"], $copyValues["name"]);
        $this->assertEquals(count($values["arr"]), count($copyValues["arr"]));
        // internal values of the copied StripeObject should be the same,
        // but the object itself should be new (hence the assertNotSame)
        $this->assertEquals($values["arr"][0]["id"], $copyValues["arr"][0]["id"]);
        $this->assertNotSame($values["arr"][0], $copyValues["arr"][0]);
        // likewise, the Util\RequestOptions instance in _opts should have
        // copied values but be a new instance
        $this->assertEquals(
            $optsReflector->getValue($values["arr"][0]),
            $optsReflector->getValue($copyValues["arr"][0])
        );
        $this->assertNotSame(
            $optsReflector->getValue($values["arr"][0]),
            $optsReflector->getValue($copyValues["arr"][0])
        );
        // scalars however, can be compared
        $this->assertEquals($values["arr"][1], $copyValues["arr"][1]);
        $this->assertEquals($values["arr"][2], $copyValues["arr"][2]);
        // and a similar story with the hash
        $this->assertEquals($values["map"]["0"]["id"], $copyValues["map"]["0"]["id"]);
        $this->assertNotSame($values["map"]["0"], $copyValues["map"]["0"]);
        $this->assertNotSame(
            $optsReflector->getValue($values["arr"][0]),
            $optsReflector->getValue($copyValues["arr"][0])
        );
        $this->assertEquals(
            $optsReflector->getValue($values["map"]["0"]),
            $optsReflector->getValue($copyValues["map"]["0"])
        );
        $this->assertNotSame(
            $optsReflector->getValue($values["map"]["0"]),
            $optsReflector->getValue($copyValues["map"]["0"])
        );
        $this->assertEquals($values["map"]["1"], $copyValues["map"]["1"]);
        $this->assertEquals($values["map"]["2"], $copyValues["map"]["2"]);
    }
    public function testDeepCopyMaintainClass()
    {
        // This is used to invoke the `deepCopy` protected function
        $deepCopyReflector = new \ReflectionMethod('Stripe\\StripeObject', 'deepCopy');
        $deepCopyReflector->setAccessible(true);

        $charge = Charge::constructFrom(array("id" => 1), null);
        $copyCharge = $deepCopyReflector->invoke(null, $charge);
        $this->assertEquals(get_class($charge), get_class($copyCharge));
    }
}
