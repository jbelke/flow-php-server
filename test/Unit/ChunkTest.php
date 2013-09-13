<?php
namespace Unit;

use \org\bovigo\vfs\vfsStreamWrapper;
use \org\bovigo\vfs\vfsStreamDirectory;
use \org\bovigo\vfs\vfsStream;
use Resumable\Exception;
use Resumable\Chunk;

class ChunkTest extends \PHPUnit_Framework_TestCase
{
    public $request;
    /**
     * @var vfsStreamDirectory
     */
    public $root;

    protected function setUp()
    {
        $this->request = [
            'resumableChunkNumber' => 1,
            'resumableChunkSize' => 1048576,
            'resumableCurrentChunkSize' => 13632,
            'resumableTotalSize' => 13632,
            'resumableIdentifier' => '13632-prettifyjs',
            'resumableFilename' => 'prettify.js',
            'resumableRelativePath' => 'home/prettify.js',
            'resumableTotalChunks' => 3
        ];

        vfsStreamWrapper::register();
        $this->root = new vfsStreamDirectory('chunks');
        vfsStreamWrapper::setRoot($this->root);
    }

    public function testRequest()
    {
        $chunk = new Chunk($this->request);
        $this->assertEquals($chunk->index, 1);
        $this->assertEquals($chunk->size, 13632);
    }

    public function testExists()
    {
        $chunk = new Chunk($this->request);
        $this->assertFalse($chunk->exists(vfsStream::url('chunks')));
        $file = vfsStream::newFile('1');
        $this->root->addChild($file);
        $this->assertTrue($chunk->exists(vfsStream::url('chunks')));
    }

    public function testSave()
    {
        $chunk = new Chunk($this->request);
        $this->assertFalse($chunk->exists(vfsStream::url('chunks')));
        $file = vfsStream::newFile('1');
        $this->root->addChild($file);
        $chunk->save([
            'tmp_name' => vfsStream::url('chunks/1')
        ], $this->root);
    }

    public function testValidate()
    {
        $this->request['resumableCurrentChunkSize'] = 10;
        $chunk = new Chunk($this->request);
        $this->assertTrue($chunk->validate([
            'size' => 10,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => ''
        ]));
        $this->assertFalse($chunk->validate([
            'size' => 9,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => ''
        ]));
        $this->assertFalse($chunk->validate([
            'size' => 10,
            'error' => UPLOAD_ERR_EXTENSION,
            'tmp_name' => ''
        ]));
        try {
            $this->assertFalse($chunk->validate([]));
            $this->fail();
        } catch (Exception $e) {

        }
    }

    public function testDelete()
    {
        $chunk = new Chunk($this->request);
        $file = vfsStream::newFile('1');
        $this->root->addChild($file);
        $this->assertTrue($chunk->exists($this->root->url()));
        $this->assertTrue($chunk->delete($this->root->url()));
        $this->assertFalse($chunk->exists($this->root->url()));
    }

    public function testPrefixSave()
    {
        $chunk = new Chunk($this->request, 'pre_');
        $this->assertFalse($chunk->exists($this->root->url()));
        $file = vfsStream::newFile('pre_1');
        $this->root->addChild($file);
        $this->assertTrue($chunk->exists($this->root->url()));
    }
}