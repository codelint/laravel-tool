<?php namespace Codelint\Laravel\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

/**
 * DBOperatorTest:
 * @date 15/10/28
 * @time 23:33
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class DBOperatorTest extends \TestCase {

    /**
     * @var DBOperator
     */
    protected $operator;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = DBOperator::apply('users');
        //create table users for test
        Schema::create('users', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function tearDown():void
    {
        $this->operator->reset()->delete();
        if (Schema::hasTable('users'))
        {
            Schema::drop('users');
        }
        parent::tearDown();
    }


    public function testCURD()
    {
        $this->operator->reset();
        $mock = [
            'name' => 'hello',
            'email' => 'gzhang@codelint.com',
            'password' => md5('123456'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $user = $this->operator->insert($mock);

        $this->assertArrayHasKey('id', $user);

        $obj = $this->operator->reset()->findById($user['id']);

        $this->assertNotEmpty($obj);
        $this->assertEquals($user['name'], $obj['name']);

        $this->operator->reset()->upsertById([
            'id' => $user['id'],
            'name' => 'world',
            'email' => 'world@codelint.com'
        ]);
        $obj = $this->operator->reset()->findByOne(['name' => 'hello']);
        $this->assertEmpty($obj);

        $obj = $this->operator->reset()->findBy(['name' => 'world']);
        $this->assertNotEmpty($obj);
        $this->assertEquals('world', $obj[0]['name']);

        $this->operator->reset()->insert($mock);
        $set = $this->operator->reset()->findBy([]);
        $this->assertEquals(2, count($set));

        $mock['email'] = 'add@codelint.com';
        $this->operator->reset()->add($mock, ['name']);
        $set = $this->operator->reset()->findBy([]);
        $this->assertEquals(2, count($set));

        $this->operator->reset()->add($mock, ['email']);
        $set = $this->operator->reset()->findBy([]);
        $this->assertEquals(3, count($set));

        $this->operator->reset()->delete();
    }

    public function testMetaOperate()
    {
        Schema::dropIfExists('users_meta');
        $mock = [
            'name' => 'hello',
            'email' => 'gzhang@codelint.com',
            'password' => md5('123456'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $user = $this->operator->reset()->insert($mock);

        // test auto build meta table
        $this->assertFalse(Schema::hasTable('users_meta'), 'expect users_meta not exist, but not');
        $this->operator->meta($user['id'], 'meta.key', $mock);
        $this->assertTrue(Schema::hasTable('users_meta'), 'expect users_meta exist, but not');

        // test find meta
        $value = $this->operator->meta($user['id'], 'meta.key');
        $this->assertEquals($mock, $value);

        // test set multi meta value
        $this->operator->metadata($user['id'], [
            'age' => 16,
            'nick' => 'codelint',
            'ext' => [
                'something...'
            ]
        ]);
        $age = $this->operator->meta($user['id'], 'age');
        $this->assertEquals(16, $age);

        $nick = $this->operator->meta($user['id'], 'nick');
        $this->assertEquals('codelint', $nick);

        $metadata = $this->operator->metadata($user['id']);
        $this->assertEquals([
            'meta.key' => $mock,
            'age' => 16,
            'nick' => 'codelint',
            'ext' => [
                'something...'
            ]
        ], $metadata);

        $this->operator->metadata($user['id'], ['age' => 17, 'nick' => 'ray', 'sex' => '***']);
        $metadata = $this->operator->metadata($user['id']);
        $this->assertEquals([
            'meta.key' => $mock,
            'age' => 17,
            'nick' => 'ray',
            'sex' => '***',
            'ext' => [
                'something...'
            ]
        ], $metadata);


    }
} 