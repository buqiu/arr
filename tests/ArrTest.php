<?php
/**
 * Name: 数组测试类.
 * User: 董坤鸿
 * Date: 2019-06-14
 * Time: 13:51
 */

namespace tests;

use buqiu\Arr\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    protected $data;

    /**
     * Change the autogenerated stub
     */
    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->data = [
            'a' => 1,
            'b' => 2,
            'web' => ['id' => 1, 'url', 'lnmp.org.cn'],
        ];
    }

    /**
     * @测试
     */
    public function get()
    {
        $this->assertEquals(Arr::get($this->data, 'a'), 1);
        $this->assertEquals(Arr::get($this->data, 'web.id'), 1);
    }

    /**
     * @测试
     */
    public function getExtName()
    {
        $data = Arr::getExtName($this->data, ['web', 'a']);
        $this->assertTrue(!isset($data['a']));
        $this->assertTrue(!isset($data['web']));
    }

    /**
     * @测试
     */
    public function set()
    {
        $data = Arr::set($this->data, 'f.b.c', 99);
        $this->assertEquals(Arr::get($data, 'f.b.c'), 99);
    }

    /**
     * @测试
     */
    public function keyCase()
    {
        //转大写
        $data = Arr::keyCase($this->data, 1);
        $this->assertTrue(isset($data['A']));
        //转小写
        $data = Arr::keyCase($this->data, 0);
        $this->assertTrue(isset($data['a']));
    }

    /**
     * @测试
     */
    public function intToString()
    {
        $data = ['status' => 0];
        $d = Arr::intToString(
            $data,
            ['status' => [0 => '关闭', 1 => '开启']]
        );
        $this->assertEquals($d['_status'], '关闭');
    }

    /**
     * @测试
     */
    public function stringToInt()
    {
        $data = ['status' => '1', 'click' => '200'];
        $d = Arr::stringToInt($data);
        $this->assertInternalType('int', $d['status']);
    }

    /**
     * @测试
     */
    public function filterKeys()
    {
        $d = ['id' => 1, 'url' => 'houdunwang.com', 'title' => '后盾网'];
        $data = Arr::filterKeys($d, ['id', 'url']);
        $this->assertCount(1, $data);
        $data = Arr::filterKeys($d, ['id'], 0);
        $this->assertCount(1, $data);
    }

    /**
     * @测试
     */
    public function tree()
    {
        $data = [
            ['cid' => 1, 'pid' => 0, 'title' => '新闻'],
            ['cid' => 2, 'pid' => 1, 'title' => '国内新闻'],
        ];
        $d = Arr::tree($data, 'title', 'cid', 'pid');
        $this->assertEquals($d[1]['_level'], 2);
    }

    /**
     * @测试
     */
    public function channelList()
    {
        $data = [
            ['cid' => 1, 'pid' => 0, 'title' => '新闻'],
            ['cid' => 2, 'pid' => 1, 'title' => '国内新闻'],
        ];
        $d = Arr::channelList(
            $data,
            0,
            "&nbsp;",
            'cid',
            'pid'
        );
        $this->assertEquals($d[2]['_level'], 2);
    }

    /**
     * @测试
     */
    public function channelLevel()
    {
        $data = [
            ['cid' => 1, 'pid' => 0, 'title' => '新闻'],
            ['cid' => 2, 'pid' => 1, 'title' => '国内新闻'],
        ];
        $d = Arr::channelLevel(
            $data,
            0,
            "&nbsp;",
            'cid',
            'pid'
        );
        $this->assertCount(1, $d);
    }

    /**
     * @测试
     */
    public function parentChannel()
    {
        $data = [
            ['cid' => 1, 'pid' => 0, 'title' => '新闻'],
            ['cid' => 2, 'pid' => 1, 'title' => '国内新闻'],
            ['cid' => 3, 'pid' => 1, 'title' => '汽车新闻'],
        ];
        $d = Arr::parentChannel($data, 2, 'cid', 'pid');
        $this->assertEquals(1, $d[1]['cid']);
    }

    /**
     * @测试
     */
    public function isChild()
    {
        $data = [
            ['cid' => 1, 'pid' => 0, 'title' => '新闻'],
            ['cid' => 2, 'pid' => 1, 'title' => '国内新闻'],
            ['cid' => 3, 'pid' => 1, 'title' => '汽车新闻'],
        ];
        $state = Arr::isChild($data, 2, 1, 'cid', 'pid');
        $this->assertTrue($state);
    }

    /**
     * @测试
     */
    public function hasChild()
    {
        $data = [
            ['cid' => 1, 'pid' => 0, 'title' => '新闻'],
            ['cid' => 2, 'pid' => 1, 'title' => '国内新闻'],
        ];
        $this->assertTrue(Arr::hasChild($data, 1, 'pid'));
    }
}