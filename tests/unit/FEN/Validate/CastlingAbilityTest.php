<?php

namespace Chess\Tests\Unit\FEN\Validate;

use Chess\Exception\UnknownNotationException;
use Chess\FEN\Validate;
use Chess\Tests\AbstractUnitTestCase;

class CastlingAbilityTest extends AbstractUnitTestCase
{
    /**
     * @test
     */
    public function foobar()
    {
        $this->expectException(UnknownNotationException::class);

        Validate::castling('foobar');
    }

    /**
     * @test
     */
    public function start_b_kqKQ()
    {
        $this->expectException(UnknownNotationException::class);

        Validate::castling('kqKQ');
    }

    /**
     * @test
     */
    public function start_rearrange_KkQq()
    {
        $this->expectException(UnknownNotationException::class);

        Validate::castling('KkQq');
    }

    /**
     * @test
     */
    public function double_hyphen()
    {
        $this->expectException(UnknownNotationException::class);

        Validate::castling('--');
    }

    /**
     * @test
     */
    public function b_k_hyphen()
    {
        $this->expectException(UnknownNotationException::class);

        Validate::castling('k-');
    }

    /**
     * @test
     */
    public function empty_string()
    {
        $this->expectException(UnknownNotationException::class);

        Validate::castling('');
    }

    /**
     * @test
     */
    public function start_w_KQkq()
    {
        $this->assertSame('KQkq', Validate::castling('KQkq'));
    }

    /**
     * @test
     */
    public function w_k()
    {
        $this->assertSame('K', Validate::castling('K'));
    }

    /**
     * @test
     */
    public function w_q()
    {
        $this->assertSame('Q', Validate::castling('Q'));
    }

    /**
     * @test
     */
    public function b_k()
    {
        $this->assertSame('k', Validate::castling('k'));
    }

    /**
     * @test
     */
    public function b_q()
    {
        $this->assertSame('q', Validate::castling('q'));
    }

    /**
     * @test
     */
    public function w_kq()
    {
        $this->assertSame('KQ', Validate::castling('KQ'));
    }

    /**
     * @test
     */
    public function b_kq()
    {
        $this->assertSame('kq', Validate::castling('kq'));
    }

    /**
     * @test
     */
    public function hyphen()
    {
        $this->assertSame('-', Validate::castling('-'));
    }
}
